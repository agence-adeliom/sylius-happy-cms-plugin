/**
 * Decides whether a block of the preview renders nothing visible. Pure functions, no access to the global DOM:
 * the preview agent measures the block, this module tells what to measure and how to read the result.
 */

/** Under this height (in px) the content of a block counts as empty. */
export const EMPTY_HEIGHT_THRESHOLD = 4;

/** Elements added by the server around the content of a block: never part of what is measured. */
export const RESERVED_CLASSES = [
  'content-block-empty-placeholder',
  'content-block-state-overlay',
  'content-block-hover-overlay',
];

const ELEMENT_NODE = 1;
const TEXT_NODE = 3;

function isReserved(node) {
  return node.nodeType === ELEMENT_NODE
    && RESERVED_CLASSES.some(name => node.classList.contains(name));
}

/**
 * The child nodes of a block wrapper that make up its content: elements, and text that is not only whitespace.
 * Comments, whitespace and the server overlays are left out.
 */
export function contentNodesOf(wrapper) {
  return Array.from(wrapper.childNodes).filter(node => {
    if (node.nodeType === ELEMENT_NODE) return !isReserved(node);
    if (node.nodeType === TEXT_NODE) return String(node.textContent).trim() !== '';

    return false;
  });
}

export function isEmptyHeight(height) {
  return !(height >= EMPTY_HEIGHT_THRESHOLD);
}
