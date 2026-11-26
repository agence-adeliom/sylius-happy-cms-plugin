/**
 * Messages exchanged between the page builder (admin) and the preview agent (front), through postMessage.
 * Shared by both sides so they filter and send messages the same way. Pure functions, tested with `node --test`.
 */

export const SOURCE = 'happy-cms';

export const MessageType = Object.freeze({
  // Preview -> admin
  READY: 'ready',
  GEOMETRY: 'geometry',
  HOVER: 'hover',
  EDIT: 'edit',
  // Admin -> preview
  THEME: 'theme',
  HIGHLIGHT: 'highlight',
  SCROLL_TO_BLOCK: 'scroll-to-block',
  RESTORE_SCROLL: 'restore-scroll',
});

const KNOWN_TYPES = new Set(Object.values(MessageType));

/**
 * True only for a happy-cms message of a known type, sent by the expected window from the expected origin.
 */
export function isAcceptedMessage(event, { origin, source }) {
  if (!origin || event.origin !== origin || event.source !== source) {
    return false;
  }

  const data = event.data;

  return data !== null
    && typeof data === 'object'
    && data.source === SOURCE
    && KNOWN_TYPES.has(data.type);
}

/**
 * Posts a happy-cms message to one exact origin. A wildcard or empty origin is a programming error.
 */
export function postToWindow(targetWindow, targetOrigin, type, payload = {}) {
  if (!targetOrigin || targetOrigin === '*') {
    throw new Error('A preview message needs an exact target origin.');
  }

  targetWindow.postMessage({ source: SOURCE, type, payload }, targetOrigin);
}
