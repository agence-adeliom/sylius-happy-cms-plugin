// Resizable editor panel: the width is driven by a CSS variable, clamped and remembered in localStorage

export const DEFAULT_WIDTH = 820;
export const MIN_WIDTH = 360;
export const VIEWPORT_GUTTER = 120;
export const KEYBOARD_STEP = 20;
export const STORAGE_KEY = 'happy-cms.page-builder.editor-width';

const WIDTH_VARIABLE = '--hcms-editor-width';

function maxWidth(viewportWidth) {
  return Math.max(MIN_WIDTH, viewportWidth - VIEWPORT_GUTTER);
}

export function clampWidth(width, viewportWidth) {
  return Math.min(Math.max(width, MIN_WIDTH), maxWidth(viewportWidth));
}

export function readStoredWidth(storage = globalThis.localStorage) {
  try {
    const raw = storage.getItem(STORAGE_KEY);
    if (raw === null || !/^\d+$/.test(raw)) return null;

    const width = parseInt(raw, 10);

    return width > 0 ? width : null;
  } catch (e) {
    return null;
  }
}

export function storeWidth(width, storage = globalThis.localStorage) {
  try {
    storage.setItem(STORAGE_KEY, String(Math.round(width)));
  } catch (e) {
    // Storage unavailable (private mode, blocked): the width simply is not remembered
  }
}

export function initEditorResize(root, panel, handle) {
  if (!root || !panel || !handle) return;

  let currentWidth = DEFAULT_WIDTH;

  function apply(width) {
    currentWidth = clampWidth(width, window.innerWidth);
    panel.style.setProperty(WIDTH_VARIABLE, `${currentWidth}px`);
    handle.setAttribute('aria-valuenow', String(currentWidth));
    handle.setAttribute('aria-valuemin', String(MIN_WIDTH));
    handle.setAttribute('aria-valuemax', String(maxWidth(window.innerWidth)));
  }

  function applyAndStore(width) {
    apply(width);
    storeWidth(currentWidth);
  }

  apply(readStoredWidth() ?? DEFAULT_WIDTH);

  handle.addEventListener('pointerdown', function(e) {
    if (e.button !== 0) return;

    e.preventDefault();
    handle.setPointerCapture(e.pointerId);
    root.classList.add('is-resizing');
  });

  handle.addEventListener('pointermove', function(e) {
    if (!root.classList.contains('is-resizing')) return;

    apply(window.innerWidth - e.clientX);
  });

  function endDrag() {
    if (!root.classList.contains('is-resizing')) return;

    root.classList.remove('is-resizing');
    storeWidth(currentWidth);
  }

  handle.addEventListener('pointerup', endDrag);
  handle.addEventListener('pointercancel', endDrag);

  handle.addEventListener('dblclick', function() {
    applyAndStore(DEFAULT_WIDTH);
  });

  handle.addEventListener('keydown', function(e) {
    let width;

    switch (e.key) {
      case 'ArrowLeft': width = currentWidth + KEYBOARD_STEP; break;
      case 'ArrowRight': width = currentWidth - KEYBOARD_STEP; break;
      case 'Home': width = MIN_WIDTH; break;
      case 'End': width = maxWidth(window.innerWidth); break;
      case 'Enter': width = DEFAULT_WIDTH; break;
      default: return;
    }

    e.preventDefault();
    applyAndStore(width);
  });

  // Re-clamp the displayed width on window resize, without overwriting the stored value
  window.addEventListener('resize', function() {
    apply(readStoredWidth() ?? DEFAULT_WIDTH);
  });
}
