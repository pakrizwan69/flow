/* PakFlow Runtime Error & Warning Shield (Service Worker, Popup & Isolated World) */
(() => {
  'use strict';
  if (globalThis.__pfErrorShieldInstalled) return;
  globalThis.__pfErrorShieldInstalled = true;
  try {
    const noop = () => {};
    const dbg = typeof console !== 'undefined' && typeof console.debug === 'function'
      ? console.debug.bind(console)
      : noop;
    if (typeof console !== 'undefined') {
      console.error = (...args) => { try { dbg(...args); } catch {} };
      console.warn = (...args) => { try { dbg(...args); } catch {} };
    }
  } catch {}
  try {
    if (typeof globalThis.addEventListener === 'function') {
      globalThis.addEventListener('error', (e) => {
        try {
          const isExtPage = typeof location === 'object' && location && location.protocol === 'chrome-extension:';
          const src = e && typeof e.filename === 'string' ? e.filename : '';
          if (isExtPage || !src || src.startsWith('chrome-extension://')) {
            e.preventDefault();
          }
        } catch {}
      }, true);
      globalThis.addEventListener('unhandledrejection', (e) => {
        try { e.preventDefault(); } catch {}
      }, true);
    }
  } catch {}
})();
