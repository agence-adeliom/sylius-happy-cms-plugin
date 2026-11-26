/**
 * Preview agent: injected by the server into a signed page builder preview (PreviewResponseListener).
 * It runs inside the iframe, possibly on another origin than the admin, and talks to the page builder through
 * postMessage only: the admin never reads or writes this document.
 */
import { MessageType, isAcceptedMessage, postToWindow } from './preview-protocol.js';
import { RESERVED_CLASSES, contentNodesOf, isEmptyHeight } from './preview-empty-block.js';

// Read synchronously: document.currentScript is null once the script has run.
const adminOrigin = document.currentScript?.dataset.adminOrigin || '';

const BLOCK_SELECTOR = '.content-block-wrapper[data-block-id]';
const EDIT_BUTTON_SELECTOR = '.content-block-edit-button';
const THEME_VARIABLE = /^--hcms-[a-z0-9-]+$/;

const STYLES = `
  /*
   * Minimal height of a block: the edit button (or the state badge and the button) plus a gap above and below,
   * so no button is ever cut. A project can override these variables on .content-block-wrapper.
   */
  .content-block-wrapper {
    --hcms-block-gap: 16px;
    --hcms-edit-button-height: 44px;
    --hcms-state-badge-height: 40px;
    --hcms-block-min-height: calc(var(--hcms-edit-button-height) + 2 * var(--hcms-block-gap));
    --hcms-block-state-min-height: calc(var(--hcms-state-badge-height) + 12px + var(--hcms-edit-button-height) + 2 * var(--hcms-block-gap));
    min-height: var(--hcms-block-min-height);
  }

  .content-block-wrapper[data-published="false"],
  .content-block-wrapper[data-deleted="true"] {
    min-height: var(--hcms-block-state-min-height);
  }

  /* A block that renders nothing visible shows its name, icon and a short notice */
  .content-block-wrapper.is-empty .content-block-empty-placeholder {
    display: flex !important;
  }

  .content-block-empty-placeholder-icon img,
  .content-block-empty-placeholder-icon svg {
    max-height: 40px;
    width: auto;
    object-fit: contain;
  }

  /* On an empty block the name stays readable on the left: the button moves to the right */
  .content-block-wrapper.is-empty .content-block-hover-overlay {
    justify-content: flex-end !important;
    padding: 0 var(--hcms-block-gap) !important;
  }

  .content-block-wrapper.is-empty .content-block-state-overlay {
    flex-direction: row !important;
    justify-content: flex-end !important;
    padding: 0 var(--hcms-block-gap) !important;
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

// Height of the content of a block (without the overlays and the placeholder), from its first to its last node
function contentHeight(wrapper) {
  const nodes = contentNodesOf(wrapper);
  if (nodes.length === 0) return 0;

  const range = document.createRange();
  range.setStartBefore(nodes[0]);
  range.setEndAfter(nodes[nodes.length - 1]);

  return range.getBoundingClientRect().height;
}

const resizeObserver = 'ResizeObserver' in window ? new ResizeObserver(scheduleMeasure) : null;
const observedContent = new WeakSet();

function observeContent(wrapper) {
  if (!resizeObserver) return;

  contentNodesOf(wrapper).forEach(node => {
    if (node.nodeType === Node.ELEMENT_NODE && !observedContent.has(node)) {
      observedContent.add(node);
      resizeObserver.observe(node);
    }
  });
}

// Flags the blocks that render nothing visible; the builder is told when a flag changes
function measureBlocks() {
  let changed = false;

  document.querySelectorAll(BLOCK_SELECTOR).forEach(wrapper => {
    const isEmpty = isEmptyHeight(contentHeight(wrapper));
    if (wrapper.classList.contains('is-empty') !== isEmpty) {
      wrapper.classList.toggle('is-empty', isEmpty);
      changed = true;
    }
  });

  if (changed) scheduleGeometry();
}

// Measures at most once per frame
let measureFrame = null;
function scheduleMeasure() {
  if (measureFrame !== null) return;

  measureFrame = window.requestAnimationFrame(() => {
    measureFrame = null;
    measureBlocks();
  });
}

function isInReservedElement(node, wrapper) {
  let element = node instanceof Element ? node : node.parentElement;

  while (element && element !== wrapper) {
    if (RESERVED_CLASSES.some(name => element.classList.contains(name))) return true;
    element = element.parentElement;
  }

  return false;
}

// A block filled later by a script (mount point), or emptied, changes the content without any resize
function watchContent(wrapper) {
  observeContent(wrapper);

  new MutationObserver(records => {
    if (records.every(record => isInReservedElement(record.target, wrapper))) return;

    observeContent(wrapper);
    scheduleMeasure();
  }).observe(wrapper, { childList: true, subtree: true, characterData: true });
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

  // A block hidden at some screen sizes becomes empty or not on resize
  window.addEventListener('resize', scheduleMeasure);
  document.querySelectorAll(BLOCK_SELECTOR).forEach(watchContent);

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
  measureBlocks();

  const sendReady = () => {
    // Images and fonts are loaded: measure again before the first positions are sent
    measureBlocks();
    send(MessageType.READY, geometry());
  };
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
