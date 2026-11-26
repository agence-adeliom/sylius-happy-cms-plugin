import { test } from 'node:test';
import assert from 'node:assert/strict';

import { MessageType, SOURCE, isAcceptedMessage, postToWindow } from '../preview-protocol.js';

const ADMIN = 'https://admin.test';
const frame = { name: 'preview window' };

function event(overrides = {}) {
  return {
    origin: ADMIN,
    source: frame,
    data: { source: SOURCE, type: MessageType.READY, payload: {} },
    ...overrides,
  };
}

test('ignores a message from an unknown origin', () => {
  assert.equal(isAcceptedMessage(event({ origin: 'https://evil.test' }), { origin: ADMIN, source: frame }), false);
});

test('ignores a message from another window', () => {
  assert.equal(isAcceptedMessage(event({ source: { name: 'other' } }), { origin: ADMIN, source: frame }), false);
});

test('ignores a message without the happy-cms source', () => {
  const data = { source: 'other', type: MessageType.READY, payload: {} };
  assert.equal(isAcceptedMessage(event({ data }), { origin: ADMIN, source: frame }), false);
});

test('ignores a message without data', () => {
  assert.equal(isAcceptedMessage(event({ data: null }), { origin: ADMIN, source: frame }), false);
  assert.equal(isAcceptedMessage(event({ data: 'ready' }), { origin: ADMIN, source: frame }), false);
});

test('ignores an unknown type', () => {
  const data = { source: SOURCE, type: 'eval', payload: {} };
  assert.equal(isAcceptedMessage(event({ data }), { origin: ADMIN, source: frame }), false);
});

test('ignores everything while the expected origin is unknown', () => {
  assert.equal(isAcceptedMessage(event({ origin: '' }), { origin: '', source: frame }), false);
});

test('accepts a well-formed message', () => {
  assert.equal(isAcceptedMessage(event(), { origin: ADMIN, source: frame }), true);
});

test('posts to the exact target origin', () => {
  const calls = [];
  const target = { postMessage: (...args) => calls.push(args) };

  postToWindow(target, ADMIN, MessageType.HIGHLIGHT, { blockId: '3', on: true });

  assert.deepEqual(calls, [[{ source: SOURCE, type: 'highlight', payload: { blockId: '3', on: true } }, ADMIN]]);
});

test('refuses a wildcard or empty target origin', () => {
  const target = { postMessage: () => assert.fail('must not post') };

  assert.throws(() => postToWindow(target, '*', MessageType.READY));
  assert.throws(() => postToWindow(target, '', MessageType.READY));
});
