import { test } from 'node:test';
import assert from 'node:assert/strict';

import { STORAGE_KEY, clampWidth, readStoredWidth, storeWidth } from '../editor-resize.js';

function storageWith(value) {
  return { getItem: (key) => (key === STORAGE_KEY ? value : null) };
}

const throwingStorage = {
  getItem() {
    throw new Error('blocked');
  },
  setItem() {
    throw new Error('blocked');
  },
};

test('clampWidth keeps a width inside the range', () => {
  assert.equal(clampWidth(640, 1400), 640);
});

test('clampWidth raises a width below the minimum', () => {
  assert.equal(clampWidth(100, 1400), 360);
});

test('clampWidth lowers a width above the viewport minus the gutter', () => {
  assert.equal(clampWidth(5000, 1400), 1280);
});

test('clampWidth returns the minimum when the viewport is too narrow', () => {
  assert.equal(clampWidth(700, 400), 360);
});

test('readStoredWidth returns null when nothing is stored', () => {
  assert.equal(readStoredWidth(storageWith(null)), null);
});

test('readStoredWidth returns null for a non numeric value', () => {
  assert.equal(readStoredWidth(storageWith('abc')), null);
});

test('readStoredWidth returns null for a negative value', () => {
  assert.equal(readStoredWidth(storageWith('-5')), null);
});

test('readStoredWidth does not clamp', () => {
  assert.equal(readStoredWidth(storageWith('99999')), 99999);
});

test('readStoredWidth returns a valid width', () => {
  assert.equal(readStoredWidth(storageWith('640')), 640);
});

test('readStoredWidth returns null when the storage throws', () => {
  assert.equal(readStoredWidth(throwingStorage), null);
});

test('readStoredWidth returns null without a global localStorage', () => {
  assert.equal(readStoredWidth(), null);
});

test('storeWidth writes the rounded width', () => {
  const written = [];
  storeWidth(640.6, { setItem: (key, value) => written.push([key, value]) });
  assert.deepEqual(written, [[STORAGE_KEY, '641']]);
});

test('storeWidth swallows a throwing storage', () => {
  assert.doesNotThrow(() => storeWidth(640, throwingStorage));
});

test('storeWidth does not throw without a global localStorage', () => {
  assert.doesNotThrow(() => storeWidth(640));
});
