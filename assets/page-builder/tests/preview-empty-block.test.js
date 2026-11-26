import { test } from 'node:test';
import assert from 'node:assert/strict';

import {
  EMPTY_HEIGHT_THRESHOLD,
  RESERVED_CLASSES,
  contentNodesOf,
  isEmptyHeight,
} from '../preview-empty-block.js';

function element(...classes) {
  return { nodeType: 1, textContent: '', classList: { contains: name => classes.includes(name) } };
}

function text(content) {
  return { nodeType: 3, textContent: content };
}

function comment(content = 'x') {
  return { nodeType: 8, textContent: content };
}

function wrapper(...childNodes) {
  return { childNodes };
}

test('the reserved classes are the placeholder and both overlays', () => {
  assert.deepEqual(RESERVED_CLASSES, [
    'content-block-empty-placeholder',
    'content-block-state-overlay',
    'content-block-hover-overlay',
  ]);
});

test('contentNodesOf leaves out the placeholder, the state overlay and the hover overlay', () => {
  const content = element('hero');
  const nodes = contentNodesOf(wrapper(
    content,
    element('content-block-empty-placeholder'),
    element('content-block-state-overlay', 'content-block-state-overlay--deleted'),
    element('content-block-hover-overlay'),
  ));

  assert.deepEqual(nodes, [content]);
});

test('contentNodesOf keeps a non empty text node and drops whitespace and comments', () => {
  const hello = text('Bonjour');
  const nodes = contentNodesOf(wrapper(text('\n  '), comment(), hello, comment('<!-- a -->')));

  assert.deepEqual(nodes, [hello]);
});

test('contentNodesOf returns nothing for a wrapper without content', () => {
  assert.deepEqual(contentNodesOf(wrapper(text('  '), element('content-block-hover-overlay'))), []);
});

test('isEmptyHeight is true under the threshold and for NaN', () => {
  assert.equal(EMPTY_HEIGHT_THRESHOLD, 4);
  assert.equal(isEmptyHeight(0), true);
  assert.equal(isEmptyHeight(3.9), true);
  assert.equal(isEmptyHeight(NaN), true);
  assert.equal(isEmptyHeight(undefined), true);
});

test('isEmptyHeight is false from the threshold', () => {
  assert.equal(isEmptyHeight(4), false);
  assert.equal(isEmptyHeight(20), false);
});
