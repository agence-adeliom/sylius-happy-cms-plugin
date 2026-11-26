// Resizable editor panel: the width is driven by a CSS variable, clamped and remembered in localStorage

export const DEFAULT_WIDTH = 820;
export const MIN_WIDTH = 360;
export const PREVIEW_VISIBLE = 60;
export const KEYBOARD_STEP = 20;
export const STORAGE_KEY = 'happy-cms.page-builder.editor-width';

const WIDTH_VARIABLE = '--hcms-editor-width';

// The panel stops PREVIEW_VISIBLE px to the right of the left edge of the preview content
export function maxWidth(viewportWidth, previewLeft) {
  return Math.max(MIN_WIDTH, Math.floor(viewportWidth - previewLeft - PREVIEW_VISIBLE));
}

export function clampWidth(width, max) {
  return Math.min(Math.max(width, MIN_WIDTH), max);
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

// Left edge of the preview content (inside the padding of the preview area), in viewport pixels
function contentLeft(preview) {
  if (!preview) return 0;

  return preview.getBoundingClientRect().left + (parseFloat(getComputedStyle(preview).paddingLeft) || 0);
}

export function initEditorResize(root, panel, handle, preview) {
  if (!root || !panel || !handle) return;

  const currentMax = () => maxWidth(window.innerWidth, contentLeft(preview));

  let currentWidth = DEFAULT_WIDTH;

  function apply(width) {
    const max = currentMax();

    currentWidth = clampWidth(width, max);
    panel.style.setProperty(WIDTH_VARIABLE, `${currentWidth}px`);
    handle.setAttribute('aria-valuenow', String(currentWidth));
    handle.setAttribute('aria-valuemin', String(MIN_WIDTH));
    handle.setAttribute('aria-valuemax', String(max));
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
      case 'End': width = currentMax(); break;
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
