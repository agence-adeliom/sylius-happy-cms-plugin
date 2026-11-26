/**
 * Preview agent: injected by the server into a signed page builder preview (PreviewResponseListener).
 * It runs inside the iframe, possibly on another origin than the admin, and talks to the page builder through
 * postMessage only: the admin never reads or writes this document.
 */
import { MessageType, isAcceptedMessage, postToWindow } from './preview-protocol.js';

// Read synchronously: document.currentScript is null once the script has run.
const adminOrigin = document.currentScript?.dataset.adminOrigin || '';

const BLOCK_SELECTOR = '.content-block-wrapper[data-block-id]';
const EDIT_BUTTON_SELECTOR = '.content-block-edit-button';
const THEME_VARIABLE = /^--hcms-[a-z0-9-]+$/;

const STYLES = `
  /* Minimal height for blocks (some are blank) */
  .content-block-wrapper {
    min-height: 150px;
  }

  /* Show outline and shadow on hover */
  .content-block-wrapper.is-hovered {
    outline: 3px solid var(--hcms-accent, #22b99a) !important;
    outline-offset: -3px !important;
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--hcms-accent, #22b99a) 20%, transparent) !important;
    z-index: 100 !important;
  }

  /* Show hover overlay when hovering */
  .content-block-wrapper.is-hovered .content-block-hover-overlay {
    display: flex !important;
  }

  /* Hide hover overlay for unpublished blocks (they have their own overlay) */
  .content-block-wrapper[data-published="false"] .content-block-hover-overlay {
    display: none !important;
  }

  /* Hide hover overlay for deleted blocks (they have their own overlay) */
  .content-block-wrapper[data-deleted="true"] .content-block-hover-overlay {
    display: none !important;
  }

  /* Button hover effect */
  .content-block-edit-button:hover {
    background: var(--hcms-accent-hover, #1d9d83) !important;
    transform: translateY(-2px) !important;
    box-shadow: 0 6px 16px color-mix(in srgb, var(--hcms-accent, #22b99a) 50%, transparent) !important;
  }

  /* The Symfony toolbar has no use inside the page builder */
  .sf-toolbar, .sf-minitoolbar {
    display: none !important;
  }
`;

function send(type, payload = {}) {
  postToWindow(window.parent, adminOrigin, type, payload);
}

function findBlock(blockId) {
  return document.querySelector(`.content-block-wrapper[data-block-id="${CSS.escape(String(blockId))}"]`);
}

function geometry() {
  return {
    path: window.location.pathname,
    blocks: Array.from(document.querySelectorAll(BLOCK_SELECTOR), block => {
      const rect = block.getBoundingClientRect();

      return { id: block.dataset.blockId, top: rect.top, height: rect.height };
    }),
    viewportHeight: window.innerHeight,
    scrollY: window.scrollY,
  };
}

// Sends the block positions at most once per frame
let geometryFrame = null;
function scheduleGeometry() {
  if (geometryFrame !== null) return;

  geometryFrame = window.requestAnimationFrame(() => {
    geometryFrame = null;
    send(MessageType.GEOMETRY, geometry());
  });
}

function injectStyles() {
  if (document.getElementById('page-builder-styles')) return;

  const style = document.createElement('style');
  style.id = 'page-builder-styles';
  style.textContent = STYLES;
  document.head.appendChild(style);
}

function bindDocument() {
  // Clicks: an edit button opens the block form, any other link stays in place
  document.addEventListener('click', event => {
    const target = event.target instanceof Element ? event.target : null;
    const editButton = target?.closest(EDIT_BUTTON_SELECTOR);

    if (editButton) {
      event.preventDefault();
      event.stopPropagation();
      send(MessageType.EDIT, { blockId: editButton.dataset.blockId });

      return;
    }

    if (target?.closest('a[href]')) {
      event.preventDefault();
    }
  }, true);

  document.addEventListener('submit', event => event.preventDefault(), true);

  document.querySelectorAll(BLOCK_SELECTOR).forEach(block => {
    block.addEventListener('mouseenter', () => {
      block.classList.add('is-hovered');
      send(MessageType.HOVER, { blockId: block.dataset.blockId });
    });
    block.addEventListener('mouseleave', () => {
      block.classList.remove('is-hovered');
      send(MessageType.HOVER, { blockId: null });
    });
  });

  window.addEventListener('scroll', scheduleGeometry, { passive: true });
  window.addEventListener('resize', scheduleGeometry);

  // Late images and fonts move the blocks without any scroll or resize
  if ('ResizeObserver' in window) {
    new ResizeObserver(scheduleGeometry).observe(document.body);
  }
}

function applyTheme(variables) {
  if (!variables || typeof variables !== 'object') return;

  Object.entries(variables).forEach(([name, value]) => {
    if (THEME_VARIABLE.test(name) && typeof value === 'string') {
      document.documentElement.style.setProperty(name, value);
    }
  });
}

function onMessage(event) {
  if (!isAcceptedMessage(event, { origin: adminOrigin, source: window.parent })) return;

  const { type, payload = {} } = event.data;

  switch (type) {
    case MessageType.THEME:
      applyTheme(payload.variables);
      break;
    case MessageType.HIGHLIGHT:
      findBlock(payload.blockId)?.classList.toggle('is-hovered', Boolean(payload.on));
      break;
    case MessageType.SCROLL_TO_BLOCK:
      findBlock(payload.blockId)?.scrollIntoView({ block: 'start' });
      break;
    case MessageType.RESTORE_SCROLL:
      if (Number.isFinite(payload.y)) {
        window.scrollTo(0, payload.y);
      }
      break;
    default:
      break;
  }
}

function start() {
  injectStyles();
  bindDocument();
  window.addEventListener('message', onMessage);

  const sendReady = () => send(MessageType.READY, geometry());
  if (document.readyState === 'complete') {
    sendReady();
  } else {
    window.addEventListener('load', sendReady, { once: true });
  }
}

// Outside of a frame, or without an admin origin to talk to, the page stays a plain page
if (window.parent !== window && adminOrigin) {
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start, { once: true });
  } else {
    start();
  }
}
