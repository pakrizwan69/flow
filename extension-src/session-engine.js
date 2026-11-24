/* PakFlow Session Engine — Mobile & Desktop Compatible
 * Injects real browser cookies across active cookie stores (supporting Android
 * Kiwi, Mises, Lemur, Edge Mobile, and Desktop Chromium), persists session state
 * across MV3 service-worker restarts, and enables Desktop headers on Mobile so
 * Google Flow opens the studio directly instead of "/about" ("Create with Flow"). */
(() => {
  'use strict';

  const STORAGE_KEYS = {
    active: 'pakflowSessionActive',
    cookieKeys: 'pakflowSessionCookieKeys',
    updatedAt: 'pakflowSessionUpdatedAt'
  };

  const RULE_IDS = [78101, 78102, 78103, 78104, 78105, 78106, 78107];
  const DESKTOP_UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36';
  const ALL_RESOURCE_TYPES = [
    'main_frame', 'sub_frame', 'stylesheet', 'script', 'image', 'font',
    'object', 'xmlhttprequest', 'ping', 'csp_report', 'media', 'websocket', 'other'
  ];
  const AUTH_COOKIE_NAMES = new Set([
    'SID', 'HSID', 'SSID', 'APISID', 'SAPISID',
    '__Secure-1PSID', '__Secure-3PSID',
    '__Secure-1PAPISID', '__Secure-3PAPISID',
    '__Secure-1PSIDTS', '__Secure-3PSIDTS',
    '__Secure-1PSIDCC', '__Secure-3PSIDCC', 'SIDCC',
    '__Secure-next-auth.session-token', 'next-auth.session-token'
  ]);
  // Strip Google credential-rotation cookies so shared users' browsers cannot rotate/invalidate the master Google session
  const BLOCKED_ROTATION_COOKIE_NAMES = new Set([
    '__Secure-1PSIDRTS', '__Secure-3PSIDRTS', 'SIDRTS', '__Secure-STRP',
    'LSID', '__Host-1PLSID', '__Host-3PLSID', '__Host-GAPS',
    'ACCOUNT_CHOOSER', 'SMSV', 'OTZ'
  ]);

  function computeAccountHash(cookies) {
    if (!Array.isArray(cookies) || !cookies.length) return '';
    let seed = '';
    const priority = ['SAPISID', '__Secure-1PAPISID', '__Secure-3PAPISID', 'SID', '__Secure-1PSID', '__Secure-3PSID'];
    for (const key of priority) {
      const found = cookies.find(c => c && String(c.name || '').trim() === key && c.value);
      if (found) { seed = key + ':' + String(found.value).trim(); break; }
    }
    if (!seed) return '';
    let h = 0x811c9dc5;
    for (let i = 0; i < seed.length; i++) {
      h ^= seed.charCodeAt(i);
      h = Math.imul(h, 0x01000193) >>> 0;
    }
    return 'acc_' + h.toString(16).padStart(8, '0');
  }

  function storageGet(keys) {
    return new Promise(resolve => {
      try {
        chrome.storage.local.get(keys, res => resolve(res || {}));
      } catch {
        resolve({});
      }
    });
  }

  function storageSet(obj) {
    return new Promise(resolve => {
      try {
        chrome.storage.local.set(obj, () => resolve());
      } catch {
        resolve();
      }
    });
  }

  async function detectMobilePlatform() {
    try {
      if (typeof navigator !== 'undefined') {
        if (/Android|Mobile|iPhone|iPad|iPod|CriOS/i.test(navigator.userAgent || '')) return true;
        if (navigator.userAgentData && navigator.userAgentData.mobile === true) return true;
      }
      if (chrome.runtime && typeof chrome.runtime.getPlatformInfo === 'function') {
        const info = await new Promise(resolve => {
          try {
            chrome.runtime.getPlatformInfo(res => resolve(res || null));
          } catch {
            resolve(null);
          }
        });
        if (info && (info.os === 'android' || info.os === 'ios')) return true;
      }
    } catch {}
    return false;
  }

  async function getCookieStoreIds() {
    const stores = [undefined];
    if (!chrome.cookies || typeof chrome.cookies.getAllCookieStores !== 'function') return stores;
    try {
      const list = await new Promise(resolve => {
        try {
          chrome.cookies.getAllCookieStores(res => resolve(Array.isArray(res) ? res : []));
        } catch {
          resolve([]);
        }
      });
      for (const s of list) {
        if (s && s.id !== undefined && s.id !== null && !stores.includes(String(s.id))) {
          // Skip incognito store '1' on desktop when store '0' exists, unless '1' has active tabs
          if (String(s.id) === '1' && list.some(x => String(x.id) === '0') && (!Array.isArray(s.tabIds) || s.tabIds.length === 0)) {
            continue;
          }
          stores.push(String(s.id));
        }
      }
    } catch {}
    return stores;
  }

  function cookiesGetAll(query) {
    return new Promise(resolve => {
      if (!chrome.cookies || typeof chrome.cookies.getAll !== 'function') return resolve([]);
      try {
        chrome.cookies.getAll(query, list => {
          void chrome.runtime?.lastError;
          resolve(Array.isArray(list) ? list : []);
        });
      } catch {
        resolve([]);
      }
    });
  }

  function cookiesRemove(details) {
    return new Promise(resolve => {
      if (!chrome.cookies || typeof chrome.cookies.remove !== 'function') return resolve(null);
      try {
        chrome.cookies.remove(details, res => {
          void chrome.runtime?.lastError;
          resolve(res || null);
        });
      } catch {
        resolve(null);
      }
    });
  }

  function cookiesSet(details) {
    return new Promise(resolve => {
      if (!chrome.cookies || typeof chrome.cookies.set !== 'function') return resolve(null);
      try {
        chrome.cookies.set(details, res => {
          const err = chrome.runtime?.lastError;
          resolve(err ? null : (res || null));
        });
      } catch {
        resolve(null);
      }
    });
  }

  function buildCookieUrl(domain, path, secure) {
    const cleanHost = String(domain || 'google.com').trim().replace(/^\.+/, '') || 'google.com';
    const hostForUrl = cleanHost === 'google.com' ? 'www.google.com' : cleanHost;
    const cleanPath = String(path || '/').startsWith('/') ? String(path || '/') : '/' + String(path || '');
    const proto = secure === false ? 'http://' : 'https://';
    return proto + hostForUrl + cleanPath;
  }

  function normalizeSameSite(raw) {
    const s = String(raw || '').trim().toLowerCase();
    if (s === 'no_restriction' || s === 'none') return 'no_restriction';
    if (s === 'lax') return 'lax';
    if (s === 'strict') return 'strict';
    return undefined;
  }

  async function clearConflictingGoogleCookies(storeIds, previousKeys) {
    const domainsToClean = ['google.com', 'labs.google', 'flow.google.com', 'accounts.google.com', 'aisandbox-pa.googleapis.com', 'ogs.google.com'];
    // Fetch existing cookies across all stores and domains in parallel
    const queries = [];
    for (const storeId of storeIds) {
      for (const domain of domainsToClean) {
        queries.push({ storeId, domain, query: storeId ? { domain, storeId } : { domain } });
      }
    }
    const existingLists = await Promise.all(queries.map(q => cookiesGetAll(q.query)));
    const removalMap = new Map();
    queries.forEach((q, idx) => {
      const list = existingLists[idx] || [];
      for (const ck of list) {
        if (!ck || !ck.name) continue;
        const url = buildCookieUrl(ck.domain || q.domain, ck.path || '/', ck.secure !== false);
        const key = `${q.storeId || ''}|${url}|${ck.name}`;
        if (!removalMap.has(key)) {
          const rm = { url, name: ck.name };
          if (q.storeId) rm.storeId = q.storeId;
          removalMap.set(key, rm);
        }
      }
    });
    if (Array.isArray(previousKeys)) {
      for (const storeId of storeIds) {
        for (const k of previousKeys) {
          if (!k || !k.name) continue;
          const url = buildCookieUrl(k.domain || 'google.com', k.path || '/', true);
          const key = `${storeId || ''}|${url}|${k.name}`;
          if (!removalMap.has(key)) {
            const rm = { url, name: k.name };
            if (storeId) rm.storeId = storeId;
            removalMap.set(key, rm);
          }
        }
      }
    }
    if (removalMap.size > 0) {
      await Promise.all(Array.from(removalMap.values(), rm => cookiesRemove(rm)));
    }
  }

  // Deduplicate uploaded cookies so Google-wide auth cookies (SIDCC, __Secure-1PSIDCC, __Secure-3PSIDCC, SID, etc.)
  // are NEVER injected both as host-only (flow.google.com) and domain (.google.com) — duplicate SIDCC headers cause 401/stalled renders.
  function deduplicateCookies(rawList) {
    const byKey = new Map();
    const googleWideAuth = new Set();
    for (const ck of rawList) {
      if (!ck || !ck.name) continue;
      const name = String(ck.name).trim();
      if (BLOCKED_ROTATION_COOKIE_NAMES.has(name)) continue;
      const rawDomain = String(ck.domain || '.google.com').trim().toLowerCase();
      const cleanHost = rawDomain.replace(/^\.+/, '') || 'google.com';
      if (AUTH_COOKIE_NAMES.has(name) && cleanHost === 'google.com') {
        googleWideAuth.add(name);
      }
    }
    for (const ck of rawList) {
      if (!ck || !ck.name) continue;
      const name = String(ck.name).trim();
      if (BLOCKED_ROTATION_COOKIE_NAMES.has(name)) continue;
      const rawDomain = String(ck.domain || '.google.com').trim().toLowerCase();
      const cleanHost = rawDomain.replace(/^\.+/, '') || 'google.com';
      if (cleanHost === 'accounts.google.com' || cleanHost.endsWith('.accounts.google.com')) continue;
      const path = String(ck.path || '/');
      // Drop redundant subdomain/host-only copy of a .google.com auth cookie when a .google.com entry exists
      if (AUTH_COOKIE_NAMES.has(name) && googleWideAuth.has(name) && cleanHost !== 'google.com' && cleanHost.endsWith('.google.com')) {
        continue;
      }
      const key = `${cleanHost}|${path}|${name}`;
      const prev = byKey.get(key);
      // Prefer domain-scoped (.google.com) over host-only when duplicate key exists
      if (!prev || (prev.hostOnly && !ck.hostOnly)) {
        byKey.set(key, ck);
      }
    }
    return Array.from(byKey.values());
  }

  async function setSingleCookieSmart(rawCookie, storeId) {
    if (!rawCookie || !rawCookie.name) return false;
    const name = String(rawCookie.name).trim();
    const value = String(rawCookie.value ?? '');
    if (!name || /[\r\n\0]/.test(name) || /[\r\n\0]/.test(value)) return false;

    const isHostPrefix = name.startsWith('__Host-');
    const isSecurePrefix = isHostPrefix || name.startsWith('__Secure-');
    const rawDomain = String(rawCookie.domain || '.google.com').trim();
    const cleanHost = rawDomain.replace(/^\.+/, '') || 'google.com';
    const isHostOnly = Boolean(isHostPrefix || rawCookie.hostOnly === true || (!rawDomain.startsWith('.') && (cleanHost === 'labs.google' || cleanHost === 'flow.google.com' || cleanHost === 'accounts.google.com')));
    const path = isHostPrefix ? '/' : (String(rawCookie.path || '/').startsWith('/') ? String(rawCookie.path || '/') : '/');
    const sameSite = normalizeSameSite(rawCookie.sameSite);
    const secure = Boolean(isSecurePrefix || sameSite === 'no_restriction' || rawCookie.secure !== false);
    const httpOnly = Boolean(rawCookie.httpOnly);

    // Rolling 30-minute (1800s) TTL: kept alive locally every ~1 min by alarm while extension is active & connected.
    // Long enough that MV3 service-worker sleep or long video queues NEVER expire cookies at 100%,
    // yet Chrome still auto-expires cookies if the extension is disabled or uninstalled.
    const nowSec = Math.floor(Date.now() / 1000);
    const expirationDate = nowSec + 1800;

    const baseUrl = buildCookieUrl(cleanHost, path, secure);
    const baseDetails = {
      url: baseUrl,
      name,
      value,
      path,
      secure,
      httpOnly,
      expirationDate
    };
    if (!isHostOnly) {
      baseDetails.domain = '.' + cleanHost;
    }
    if (storeId) {
      baseDetails.storeId = storeId;
    }

    // Attempt 1: with normalized sameSite (if explicitly lax/strict/no_restriction)
    const attempt1 = sameSite ? { ...baseDetails, sameSite } : baseDetails;
    let saved = await cookiesSet(attempt1);
    if (saved) return true;

    // Attempt 2: without sameSite (crucial for Kiwi/older Android Chromium)
    if (sameSite) {
      saved = await cookiesSet(baseDetails);
      if (saved) return true;
    }

    // Attempt 3: direct host URL without leading-dot domain
    const fallbackHostDetails = {
      url: 'https://' + cleanHost + path,
      name,
      value,
      path,
      secure: true,
      httpOnly,
      expirationDate
    };
    if (storeId) fallbackHostDetails.storeId = storeId;
    saved = await cookiesSet(fallbackHostDetails);
    return Boolean(saved);
  }

  async function safeClearRules() {
    if (!chrome.declarativeNetRequest) return;
    if (typeof chrome.declarativeNetRequest.updateSessionRules === 'function') {
      try {
        await chrome.declarativeNetRequest.updateSessionRules({ removeRuleIds: RULE_IDS });
      } catch {}
    }
    if (typeof chrome.declarativeNetRequest.updateDynamicRules === 'function') {
      try {
        await chrome.declarativeNetRequest.updateDynamicRules({ removeRuleIds: RULE_IDS });
      } catch {}
    }
  }

  async function installDnrRules(cookies, needCookieHeaderFallback) {
    if (!chrome.declarativeNetRequest) return;
    await safeClearRules();

    const isMobile = await detectMobilePlatform();
    const addRules = [];

    if (isMobile) {
      addRules.push({
        id: 78105,
        priority: 2,
        action: {
          type: 'modifyHeaders',
          requestHeaders: [
            { header: 'User-Agent', operation: 'set', value: DESKTOP_UA },
            { header: 'Sec-CH-UA-Mobile', operation: 'set', value: '?0' },
            { header: 'Sec-CH-UA-Platform', operation: 'set', value: '"Windows"' },
            { header: 'Sec-CH-UA', operation: 'set', value: '"Google Chrome";v="131", "Chromium";v="131", "Not_A Brand";v="24"' }
          ]
        },
        condition: {
          requestDomains: ['flow.google.com', 'labs.google', 'aisandbox-pa.googleapis.com'],
          resourceTypes: ALL_RESOURCE_TYPES
        }
      });
    }

    if (needCookieHeaderFallback && Array.isArray(cookies) && cookies.length > 0) {
      const googlePairs = [];
      const labsPairs = [];
      for (const c of cookies) {
        if (!c || !c.name) continue;
        const pair = `${c.name}=${c.value ?? ''}`;
        const dom = String(c.domain || '').toLowerCase();
        if (dom.includes('labs.google')) labsPairs.push(pair);
        else googlePairs.push(pair);
      }
      if (googlePairs.length > 0) {
        addRules.push({
          id: 78103,
          priority: 1,
          action: {
            type: 'modifyHeaders',
            requestHeaders: [{ header: 'cookie', operation: 'set', value: googlePairs.join('; ') }]
          },
          condition: {
            requestDomains: ['flow.google.com', 'aisandbox-pa.googleapis.com', 'accounts.google.com', 'ogs.google.com'],
            resourceTypes: ALL_RESOURCE_TYPES
          }
        });
      }
      if (labsPairs.length > 0) {
        addRules.push({
          id: 78104,
          priority: 1,
          action: {
            type: 'modifyHeaders',
            requestHeaders: [{ header: 'cookie', operation: 'set', value: labsPairs.join('; ') }]
          },
          condition: {
            requestDomains: ['labs.google'],
            resourceTypes: ALL_RESOURCE_TYPES
          }
        });
      }
    }

    // Protect shared Master Google Account sessions from background RotateCookies / accidental Logout calls
    addRules.push(
      {
        id: 78106,
        priority: 3,
        action: { type: 'block' },
        condition: {
          urlFilter: '||accounts.google.com/*RotateCookies*',
          resourceTypes: ['xmlhttprequest', 'ping', 'sub_frame', 'script', 'image', 'other']
        }
      },
      {
        id: 78107,
        priority: 3,
        action: { type: 'block' },
        condition: {
          urlFilter: '||accounts.google.com/*Logout*',
          initiatorDomains: ['flow.google.com', 'labs.google', 'ogs.google.com'],
          resourceTypes: ['main_frame', 'sub_frame', 'xmlhttprequest', 'ping', 'other']
        }
      }
    );

    if (!addRules.length) return;

    let installed = false;
    if (typeof chrome.declarativeNetRequest.updateSessionRules === 'function') {
      try {
        await chrome.declarativeNetRequest.updateSessionRules({ removeRuleIds: RULE_IDS, addRules });
        installed = true;
      } catch {}
    }
    if (!installed && typeof chrome.declarativeNetRequest.updateDynamicRules === 'function') {
      try {
        await chrome.declarativeNetRequest.updateDynamicRules({ removeRuleIds: RULE_IDS, addRules });
      } catch {}
    }
  }

  async function apply(inputCookies, isAllowedCheck) {
    const rawList = Array.isArray(inputCookies) ? inputCookies : Array.from(inputCookies || []);
    const list = deduplicateCookies(rawList);
    if (!list.length) {
      throw new Error('No Google Flow session cookies available.');
    }
    if (typeof isAllowedCheck === 'function' && !(await isAllowedCheck())) {
      throw new Error('Session disconnected.');
    }

    const accountHash = computeAccountHash(list);
    const prev = await storageGet([STORAGE_KEYS.cookieKeys]);
    const storeIds = await getCookieStoreIds();

    await clearConflictingGoogleCookies(storeIds, prev[STORAGE_KEYS.cookieKeys]);

    let verified = 0;
    let failed = 0;
    const savedKeys = [];

    const results = await Promise.all(list.map(async (ck) => {
      const storeResults = await Promise.all(storeIds.map(storeId => setSingleCookieSmart(ck, storeId)));
      return { ck, okAnyStore: storeResults.some(Boolean) };
    }));
    for (const res of results) {
      if (res.okAnyStore) {
        verified++;
        savedKeys.push({
          domain: String(res.ck.domain || '.google.com'),
          name: String(res.ck.name || ''),
          path: String(res.ck.path || '/')
        });
      } else {
        failed++;
      }
    }

    await installDnrRules(list, verified === 0);

    await storageSet({
      [STORAGE_KEYS.active]: true,
      [STORAGE_KEYS.cookieKeys]: savedKeys,
      [STORAGE_KEYS.updatedAt]: Date.now(),
      pf_active_account_hash: accountHash
    });

    if (typeof isAllowedCheck === 'function' && !(await isAllowedCheck())) {
      await clear();
      throw new Error('Session disconnected.');
    }

    return {
      verified: verified > 0 ? verified : list.length,
      failed: verified > 0 ? failed : 0,
      total: list.length
    };
  }

  async function active() {
    const state = await storageGet([
      STORAGE_KEYS.active,
      'flowSessionDetached',
      'isLoggedIn',
      'lastInjectedSlot'
    ]);
    if (state.flowSessionDetached === true || state.flowSessionDetached === 'true') {
      return false;
    }
    const loggedIn = state.isLoggedIn === true || state.isLoggedIn === 'true';
    if (!loggedIn && !state[STORAGE_KEYS.active]) {
      return false;
    }

    // Check real browser cookie jar first (survives Android service worker restarts!)
    const googleCookies = await cookiesGetAll({ domain: 'google.com' });
    if (googleCookies.some(c => c && AUTH_COOKIE_NAMES.has(c.name) && c.value)) {
      return true;
    }
    const labsCookies = await cookiesGetAll({ domain: 'labs.google' });
    if (labsCookies.some(c => c && c.value && (AUTH_COOKIE_NAMES.has(c.name) || /session|auth/i.test(c.name)))) {
      return true;
    }

    if (state[STORAGE_KEYS.active] === true) {
      return true;
    }

    // Fallback check for DNR rules
    if (chrome.declarativeNetRequest) {
      try {
        if (typeof chrome.declarativeNetRequest.getDynamicRules === 'function') {
          const dyn = await chrome.declarativeNetRequest.getDynamicRules();
          if (Array.isArray(dyn) && dyn.some(r => r && (r.id === 78103 || r.id === 78104))) return true;
        }
        if (typeof chrome.declarativeNetRequest.getSessionRules === 'function') {
          const ses = await chrome.declarativeNetRequest.getSessionRules();
          if (Array.isArray(ses) && ses.some(r => r && (r.id === 78101 || r.id === 78102 || r.id === 78103 || r.id === 78104))) return true;
        }
      } catch {}
    }
    return false;
  }

  async function clear() {
    const state = await storageGet([STORAGE_KEYS.cookieKeys]);
    const storeIds = await getCookieStoreIds();
    await clearConflictingGoogleCookies(storeIds, state[STORAGE_KEYS.cookieKeys]);
    await safeClearRules();
    await storageSet({
      [STORAGE_KEYS.active]: false,
      [STORAGE_KEYS.cookieKeys]: [],
      [STORAGE_KEYS.updatedAt]: 0
    });
    return true;
  }

  globalThis.PakFlowSession = Object.freeze({ apply, active, clear });
})();
