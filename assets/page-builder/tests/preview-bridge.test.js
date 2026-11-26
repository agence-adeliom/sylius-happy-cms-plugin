import { test, beforeEach } from 'node:test';
import assert from 'node:assert/strict';

import { MessageType, SOURCE } from '../preview-protocol.js';

// Minimal browser globals used by the bridge
const listeners = [];
const timers = [];
globalThis.window = {
  location: { href: 'https://admin.test/admin/happy-cms-builder/page/1' },
  addEventListener: (type, listener) => { if (type === 'message') listeners.push(listener); },
  setTimeout: (callback) => { timers.push(callback); return timers.length; },
  clearTimeout: (id) => { if (id) timers[id - 1] = null; },
};

const { PreviewBridge } = await import('../preview-bridge.js');

const FRONT = 'https://front.test';
const SIGNED = `${FRONT}/fr_FR/ma-page?happy_cms_preview=1&_hash=x`;

let iframe;
let bridge;
let calls;

function deliver(type, payload = {}, overrides = {}) {
  const event = { origin: FRONT, source: iframe.contentWindow, data: { source: SOURCE, type, payload }, ...overrides };
  listeners.forEach(listener => listener(event));
}

beforeEach(() => {
  listeners.length = 0;
  timers.length = 0;
  calls = { messages: [], timeout: 0, expired: 0, unexpected: 0, posted: [] };
  iframe = { src: '', contentWindow: { postMessage: (...args) => calls.posted.push(args) } };
  bridge = new PreviewBridge(iframe, {
    refreshEndpoint: '/admin/preview-url',
    onMessage: (type, payload) => calls.messages.push([type, payload]),
    onTimeout: () => calls.timeout++,
    onSessionExpired: () => calls.expired++,
    onUnexpectedPage: () => calls.unexpected++,
  });
});

test('loads the signed URL and expects its origin', () => {
  bridge.load(SIGNED);

  assert.equal(iframe.src, SIGNED);
  assert.equal(bridge.origin, FRONT);
});

test('reports an unreachable preview when the agent never announces itself', () => {
  bridge.load(SIGNED);
  timers.filter(Boolean).forEach(callback => callback());

  assert.equal(calls.timeout, 1);
});

test('reports an unreachable preview at once without URL', () => {
  bridge.load('');

  assert.equal(calls.timeout, 1);
  assert.equal(iframe.src, '');
});

test('ignores messages before ready', () => {
  bridge.load(SIGNED);
  deliver(MessageType.HOVER, { blockId: '3' });

  assert.deepEqual(calls.messages, []);
});

test('stops trusting the preview when ready comes from another page', () => {
  bridge.load(SIGNED);
  deliver(MessageType.READY, { path: '/autre-page', blocks: [] });
  deliver(MessageType.HOVER, { blockId: '3' });

  assert.equal(bridge.isOnExpectedPage, false);
  assert.equal(calls.unexpected, 1);
  assert.deepEqual(calls.messages, []);
});

test('forwards messages of the signed page once ready', () => {
  bridge.load(SIGNED);
  deliver(MessageType.READY, { path: '/fr_FR/ma-page', blocks: [], scrollY: 0 });
  deliver(MessageType.HOVER, { blockId: '3' });

  assert.equal(bridge.isOnExpectedPage, true);
  assert.deepEqual(calls.messages.map(([type]) => type), [MessageType.READY, MessageType.HOVER]);
  assert.equal(calls.timeout, 0);
});

test('ignores a message from another origin', () => {
  bridge.load(SIGNED);
  deliver(MessageType.READY, { path: '/fr_FR/ma-page', blocks: [] }, { origin: 'https://evil.test' });

  assert.deepEqual(calls.messages, []);
});

test('reloads with a fresh URL and restores the scroll position', async () => {
  bridge.load(SIGNED);
  deliver(MessageType.READY, { path: '/fr_FR/ma-page', blocks: [], scrollY: 0 });
  deliver(MessageType.GEOMETRY, { path: '/fr_FR/ma-page', blocks: [], scrollY: 420 });

  const fresh = `${FRONT}/fr_FR/ma-page?happy_cms_preview=1&_hash=y`;
  globalThis.fetch = async () => ({
    redirected: false,
    status: 200,
    headers: { get: () => 'application/json' },
    json: async () => ({ url: fresh }),
  });

  await bridge.reload();
  assert.equal(iframe.src, fresh);

  calls.posted.length = 0;
  deliver(MessageType.READY, { path: '/fr_FR/ma-page', blocks: [], scrollY: 0 });

  assert.deepEqual(calls.posted, [[{ source: SOURCE, type: MessageType.RESTORE_SCROLL, payload: { y: 420 } }, FRONT]]);
});

test('reports an expired session when the renewal is redirected to the login', async () => {
  bridge.load(SIGNED);
  globalThis.fetch = async () => ({ redirected: true, status: 200, headers: { get: () => 'text/html' }, json: async () => ({}) });

  await bridge.reload();

  assert.equal(calls.expired, 1);
  assert.equal(iframe.src, SIGNED);
});

test('reports an expired session when the renewal is refused', async () => {
  bridge.load(SIGNED);
  globalThis.fetch = async () => ({ redirected: false, status: 403, headers: { get: () => 'application/json' }, json: async () => ({ error: 'x' }) });

  await bridge.reload();

  assert.equal(calls.expired, 1);
});

test('sends nothing to the preview before ready', () => {
  bridge.load(SIGNED);
  bridge.send(MessageType.THEME, { variables: {} });

  assert.deepEqual(calls.posted, []);
});
