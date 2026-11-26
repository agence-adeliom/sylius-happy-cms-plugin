/**
 * Admin side of the preview dialog. The preview iframe may live on another origin than the admin: the page builder
 * never reads its document, it only exchanges messages with the preview agent (preview-agent.js).
 */
import { MessageType, isAcceptedMessage, postToWindow } from './preview-protocol.js';

// Delay for the preview agent to announce itself before the page builder reports an unreachable preview
export const READY_TIMEOUT_MS = 10000;

export class PreviewBridge {
  /**
   * @param {HTMLIFrameElement} iframe
   * @param {{
   *   refreshEndpoint: string,
   *   onMessage?: (type: string, payload: object) => void,
   *   onTimeout?: () => void,
   *   onSessionExpired?: () => void,
   *   onUnexpectedPage?: () => void,
   * }} options
   */
  constructor(iframe, { refreshEndpoint, onMessage, onTimeout, onSessionExpired, onUnexpectedPage }) {
    this.iframe = iframe;
    this.refreshEndpoint = refreshEndpoint;
    this.onMessage = onMessage || (() => {});
    this.onTimeout = onTimeout || (() => {});
    this.onSessionExpired = onSessionExpired || (() => {});
    this.onUnexpectedPage = onUnexpectedPage || (() => {});

    this.expectedOrigin = '';
    this.expectedPath = '';
    this.ready = false;
    this.trusted = true;
    this.readyTimer = null;
    this.lastScrollY = 0;
    this.pendingScrollY = null;

    window.addEventListener('message', event => this.handleMessage(event));
  }

  /** Origin of the signed preview URL currently loaded. */
  get origin() {
    return this.expectedOrigin;
  }

  /** False once the preview showed another page than the signed one, until the next load. */
  get isOnExpectedPage() {
    return this.trusted;
  }

  /**
   * Loads a signed preview URL in the iframe and waits for the agent to announce itself.
   */
  load(url) {
    window.clearTimeout(this.readyTimer);
    this.ready = false;
    this.trusted = true;

    if (!url) {
      this.expectedOrigin = '';
      this.onTimeout();

      return;
    }

    const target = new URL(url, window.location.href);
    this.expectedOrigin = target.origin;
    this.expectedPath = target.pathname;

    this.readyTimer = window.setTimeout(() => {
      if (!this.ready) {
        this.onTimeout();
      }
    }, READY_TIMEOUT_MS);

    this.iframe.src = target.toString();
  }

  /**
   * Reloads the preview with a freshly signed URL, then restores the scroll position.
   * A signature expires: reloading the iframe as is would end on a refusal page without the agent.
   */
  async reload() {
    this.pendingScrollY = this.lastScrollY;

    let response;
    try {
      response = await fetch(this.refreshEndpoint, {
        headers: { Accept: 'application/json' },
        credentials: 'same-origin',
      });
    } catch (error) {
      console.error('Cannot renew the preview URL:', error);
      this.onSessionExpired();

      return;
    }

    const contentType = response.headers.get('Content-Type') || '';
    if (response.redirected || response.status !== 200 || !contentType.includes('application/json')) {
      // A redirection to the login form, or a refusal: the admin session can no longer sign URLs
      this.onSessionExpired();

      return;
    }

    const data = await response.json();
    if (!data || typeof data.url !== 'string' || data.url === '') {
      this.onSessionExpired();

      return;
    }

    this.load(data.url);
  }

  /**
   * Sends a message to the preview agent, once it is ready and on the signed page.
   */
  send(type, payload = {}) {
    if (!this.ready || !this.trusted || !this.iframe.contentWindow) return;

    postToWindow(this.iframe.contentWindow, this.expectedOrigin, type, payload);
  }

  handleMessage(event) {
    if (!this.trusted) return;
    if (!isAcceptedMessage(event, { origin: this.expectedOrigin, source: this.iframe.contentWindow })) return;

    const { type } = event.data;
    const payload = event.data.payload && typeof event.data.payload === 'object' ? event.data.payload : {};

    if (type === MessageType.READY) {
      window.clearTimeout(this.readyTimer);

      if (payload.path !== this.expectedPath) {
        this.trusted = false;
        this.ready = false;
        this.onUnexpectedPage();

        return;
      }

      this.ready = true;

      if (this.pendingScrollY !== null) {
        this.send(MessageType.RESTORE_SCROLL, { y: this.pendingScrollY });
        this.pendingScrollY = null;
      }
    }

    if (!this.ready) return;

    if (Number.isFinite(payload.scrollY)) {
      this.lastScrollY = payload.scrollY;
    }

    this.onMessage(type, payload);
  }
}
