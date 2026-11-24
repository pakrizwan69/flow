/* 1) MAIN-world SAPISID bridge for Google Flow SAPISIDHASH XHR signing (zero persistent browser cookies).
 * 2) Spoof desktop navigator hints on mobile browsers so Google Flow opens the studio workspace directly. */
(() => {
  'use strict';

  try {
    const cookieDesc =
      Object.getOwnPropertyDescriptor(Document.prototype, 'cookie') ||
      Object.getOwnPropertyDescriptor(HTMLDocument.prototype, 'cookie');
    if (cookieDesc && typeof cookieDesc.get === 'function' && !cookieDesc.get.__pfSapisidWrapped) {
      const nativeGet = cookieDesc.get;
      const nativeSet = cookieDesc.set;
      const wrappedGet = function () {
        let base = '';
        try {
          base = String(nativeGet.call(this) || '');
        } catch {
          base = '';
        }
        try {
          let extra = '';
          if (document.documentElement) {
            extra = document.documentElement.getAttribute('data-pf-sapisid') || '';
          }
          if (!extra) {
            try { extra = sessionStorage.getItem('__pf_sapisid_cache') || ''; } catch {}
          }
          if (!extra) return base;
          if (!base) return extra;
          const map = new Map();
          for (const part of base.split(';')) {
            const p = part.trim();
            if (!p) continue;
            const eq = p.indexOf('=');
            const k = eq === -1 ? p : p.slice(0, eq).trim();
            if (k) map.set(k, p);
          }
          for (const part of extra.split(';')) {
            const p = part.trim();
            if (!p) continue;
            const eq = p.indexOf('=');
            const k = eq === -1 ? p : p.slice(0, eq).trim();
            if (k) map.set(k, p);
          }
          return Array.from(map.values()).join('; ');
        } catch {
          return base;
        }
      };
      wrappedGet.__pfSapisidWrapped = true;
      Object.defineProperty(Document.prototype, 'cookie', {
        configurable: true,
        enumerable: cookieDesc.enumerable !== false,
        get: wrappedGet,
        set: typeof nativeSet === 'function'
          ? function (val) {
              try { return nativeSet.call(this, val); } catch {}
            }
          : undefined
      });
    }
  } catch {}

  try {
    const ua = navigator.userAgent || '';
    const isMobileUA = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini|Mobile|CriOS/i.test(ua);
    const isMobileHint = Boolean(navigator.userAgentData && navigator.userAgentData.mobile === true);
    const isSmallTouch = Number(navigator.maxTouchPoints || 0) > 1 && Math.min(window.screen?.width || 9999, window.screen?.height || 9999) < 900;

    if (!isMobileUA && !isMobileHint && !isSmallTouch) return;

    const desktopUA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36';
    const desktopAppVersion = '5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36';
    const brands = [
      { brand: 'Google Chrome', version: '131' },
      { brand: 'Chromium', version: '131' },
      { brand: 'Not_A Brand', version: '24' }
    ];
    const fakeUAData = {
      brands,
      mobile: false,
      platform: 'Windows',
      getHighEntropyValues: async () => ({
        architecture: 'x86',
        bitness: '64',
        brands,
        fullVersionList: [
          { brand: 'Google Chrome', version: '131.0.6778.86' },
          { brand: 'Chromium', version: '131.0.6778.86' },
          { brand: 'Not_A Brand', version: '24.0.0.0' }
        ],
        mobile: false,
        model: '',
        platform: 'Windows',
        platformVersion: '15.0.0',
        uaFullVersion: '131.0.6778.86',
        wow64: false
      }),
      toJSON: () => ({ brands, mobile: false, platform: 'Windows' })
    };

    const defineNavProp = (prop, getter) => {
      try {
        Object.defineProperty(Navigator.prototype, prop, { get: getter, configurable: true });
      } catch {
        try {
          Object.defineProperty(navigator, prop, { get: getter, configurable: true });
        } catch {}
      }
    };

    defineNavProp('userAgent', () => desktopUA);
    defineNavProp('appVersion', () => desktopAppVersion);
    defineNavProp('platform', () => 'Win32');
    defineNavProp('userAgentData', () => fakeUAData);
  } catch {}
})();
