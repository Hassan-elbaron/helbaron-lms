import "@testing-library/jest-dom/vitest";
import { cleanup } from "@testing-library/react";
import { afterEach, vi } from "vitest";

/**
 * jsdom implements neither the Pointer Capture API nor `scrollIntoView`, both of which Radix
 * primitives (Select, Dropdown, Popover) call unconditionally on open. Without these shims the
 * component throws mid-interaction and the failure surfaces as an unrelated unhandled error, so
 * the gap is filled once here rather than worked around in individual tests.
 *
 * These are no-ops on purpose: nothing under test asserts on capture or scroll behaviour, and a
 * fake implementation that pretended to track capture state would be a lie the tests could
 * accidentally rely on.
 */
if (!Element.prototype.hasPointerCapture) {
  Element.prototype.hasPointerCapture = () => false;
  Element.prototype.setPointerCapture = () => {};
  Element.prototype.releasePointerCapture = () => {};
}

if (!Element.prototype.scrollIntoView) {
  Element.prototype.scrollIntoView = () => {};
}

// jsdom has no layout engine, so Radix's collision detection reads zeroes unless this exists.
if (!globalThis.ResizeObserver) {
  globalThis.ResizeObserver = class {
    observe() {}
    unobserve() {}
    disconnect() {}
  } as unknown as typeof ResizeObserver;
}

afterEach(() => {
  cleanup();
  // Any test that opted into fake timers gets real ones back, so a leaked fake clock cannot make a
  // later test in the same file hang on a promise that never settles.
  vi.useRealTimers();
});
