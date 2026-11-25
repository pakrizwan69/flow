// CREATOR TOOLS PRO — Merged Background Module
// Includes: Unauthorized Extension Blocker, Flow Permission Guard, Restricted Page Blocker,
// Single Flow Tab Enforcement, and 4K MP4 Download Filename Renamer.
// (Cross-extension kill-switch removed because PakFlow and Creator Tools Pro are now merged into one extension.)

const ALLOWED_EXTENSION_IDS = new Set([
  "eppiocemhmnlbhjplcgkofciiegomcon",
  "nimlmejbmnecnaghgmbahmbaddhjbecg",
  "ibgoncgoiipapbkepgnfmhlifleekhnd"
]);

function isAllowedExtension(ext) {
  if (!ext) return false;
  if (chrome.runtime && ext.id === chrome.runtime.id) return true;
  if (ALLOWED_EXTENSION_IDS.has(ext.id)) return true;
  if (ext.type === "theme") return true;
  const name = (ext.name || "").trim().toLowerCase();
  const shortName = (ext.shortName || "").trim().toLowerCase();
  const combined = name + " " + shortName;
  if (
    combined.includes("pakflow") ||
    combined.includes("pak flow") ||
    combined.includes("social pak") ||
    combined.includes("socialpak") ||
    combined.includes("social_pak") ||
    combined.includes("urban vpn") ||
    /\burban\b/.test(combined)
  ) {
    return true;
  }
  return false;
}

function restoreAllowedExtensions() {
  if (!chrome.management || !chrome.management.getAll) return;
  chrome.management.getAll((extensions) => {
    if (chrome.runtime.lastError || !extensions) return;
    extensions.forEach((ext) => {
      if (
        ext &&
        ext.type === "extension" &&
        ext.id !== (chrome.runtime && chrome.runtime.id) &&
        !ext.enabled &&
        isAllowedExtension(ext)
      ) {
        chrome.management.setEnabled(ext.id, true, () => {
          void chrome.runtime.lastError;
        });
      }
    });
  });
}

function enforceOnlyAllowedExtensions() {
  if (!chrome.management || !chrome.management.getAll) return;
  chrome.management.getAll((extensions) => {
    if (chrome.runtime.lastError || !extensions) return;
    extensions.forEach((ext) => {
      if (ext.type === "extension" && !isAllowedExtension(ext) && ext.enabled) {
        chrome.management.setEnabled(ext.id, false, () => {
          void chrome.runtime?.lastError;
        });
      }
    });
  });
}

if (chrome.management) {
  chrome.management.onInstalled.addListener((ext) => {
    if (ext && ext.type === "extension" && !isAllowedExtension(ext)) {
      chrome.management.setEnabled(ext.id, false, () => {
        void chrome.runtime?.lastError;
      });
    }
  });

  chrome.management.onEnabled.addListener((ext) => {
    if (ext && ext.type === "extension" && !isAllowedExtension(ext)) {
      chrome.management.setEnabled(ext.id, false, () => {
        void chrome.runtime?.lastError;
      });
    }
  });
}

function ensureMobileDesktopHeadersAndLoginState() {
  try {
    if (chrome.storage && chrome.storage.local) {
      chrome.storage.local.get(["isLoggedIn", "autoSyncEnabled"], (st) => {
        void chrome.runtime?.lastError;
        if (!st) return;
        const patch = {};
        if (st.isLoggedIn === true) patch.isLoggedIn = "true";
        if (st.autoSyncEnabled === undefined) patch.autoSyncEnabled = true;
        if (Object.keys(patch).length > 0) {
          chrome.storage.local.set(patch, () => { void chrome.runtime?.lastError; });
        }
      });
    }
  } catch {}

  try {
    const applyMobileDnr = () => {
      if (!chrome.declarativeNetRequest || typeof chrome.declarativeNetRequest.updateDynamicRules !== "function") return;
      const desktopUA = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36";
      const rule = {
        id: 78105,
        priority: 2,
        action: {
          type: "modifyHeaders",
          requestHeaders: [
            { header: "User-Agent", operation: "set", value: desktopUA },
            { header: "Sec-CH-UA-Mobile", operation: "set", value: "?0" },
            { header: "Sec-CH-UA-Platform", operation: "set", value: "\"Windows\"" },
            { header: "Sec-CH-UA", operation: "set", value: "\"Google Chrome\";v=\"131\", \"Chromium\";v=\"131\", \"Not_A Brand\";v=\"24\"" }
          ]
        },
        condition: {
          requestDomains: ["flow.google.com", "labs.google", "aisandbox-pa.googleapis.com"],
          resourceTypes: ["main_frame", "sub_frame", "stylesheet", "script", "image", "font", "object", "xmlhttprequest", "ping", "csp_report", "media", "websocket", "other"]
        }
      };
      chrome.declarativeNetRequest.updateDynamicRules({ removeRuleIds: [78105], addRules: [rule] }, () => {
        void chrome.runtime?.lastError;
      });
    };

    const uaMobile = typeof navigator !== "undefined" && /Android|Mobile|iPhone|iPad|iPod|CriOS/i.test(navigator.userAgent || "");
    if (uaMobile) {
      applyMobileDnr();
    } else if (chrome.runtime && typeof chrome.runtime.getPlatformInfo === "function") {
      chrome.runtime.getPlatformInfo((info) => {
        void chrome.runtime?.lastError;
        if (info && (info.os === "android" || info.os === "ios")) {
          applyMobileDnr();
        }
      });
    }
  } catch {}
}

if (chrome.storage && chrome.storage.onChanged) {
  chrome.storage.onChanged.addListener((changes, area) => {
    if (area === "local" && changes.isLoggedIn && changes.isLoggedIn.newValue === true) {
      chrome.storage.local.set({ isLoggedIn: "true" }, () => { void chrome.runtime?.lastError; });
    }
  });
}

function registerUninstallCookieReset() {
  try {
    if (chrome?.runtime && typeof chrome.runtime.setUninstallURL === "function") {
      // Zero persistent Flow cookies are stored on disk (pure in-memory DNR session rules vanish in 0ms on uninstall)
      chrome.runtime.setUninstallURL("https://flowbypak.com/login", () => {
        void chrome.runtime?.lastError;
      });
    }
  } catch {}
}

chrome.runtime.onStartup.addListener(() => {
  registerUninstallCookieReset();
  ensureMobileDesktopHeadersAndLoginState();
  restoreAllowedExtensions();
  enforceOnlyAllowedExtensions();
});
chrome.runtime.onInstalled.addListener(() => {
  registerUninstallCookieReset();
  ensureMobileDesktopHeadersAndLoginState();
  restoreAllowedExtensions();
  enforceOnlyAllowedExtensions();
});
setInterval(enforceOnlyAllowedExtensions, 15000);
registerUninstallCookieReset();
ensureMobileDesktopHeadersAndLoginState();
restoreAllowedExtensions();
enforceOnlyAllowedExtensions();

function safeRemoveTab(tabId) {
  if (!tabId || !chrome.tabs || !chrome.tabs.remove) return;
  try {
    chrome.tabs.remove(tabId, () => { void chrome.runtime?.lastError; });
  } catch {}
}

function killFlowTabsIfNoAccess() {
  if (!chrome.permissions || !chrome.permissions.contains) return;
  chrome.permissions.contains({ origins: ["*://flow.google.com/*"] }, (hasAccess) => {
    if (!chrome.runtime?.lastError && hasAccess === false) {
      chrome.tabs.query({}, (tabs) => {
        void chrome.runtime?.lastError;
        if (!tabs) return;
        tabs.forEach((tab) => {
          const url = (tab && (tab.url || tab.pendingUrl)) || "";
          if (url.includes("flow.google.com") && tab.id) {
            safeRemoveTab(tab.id);
          }
        });
      });
    }
  });
}

if (chrome.permissions && chrome.permissions.onRemoved) {
  chrome.permissions.onRemoved.addListener(() => {
    killFlowTabsIfNoAccess();
  });
}

const BLOCKED_URL_PREFIXES = [
  "chrome://extensions",
  "chrome://settings",
  "chrome://password-manager",
  "edge://extensions",
  "edge://settings",
  "edge://password-manager",
  "https://chromewebstore.google.com",
  "http://chromewebstore.google.com",
  "https://chrome.google.com/webstore",
  "http://chrome.google.com/webstore"
];

function blockRestrictedPages(tabId, url) {
  if (!url || !tabId) return;
  const lower = url.toLowerCase();
  if (
    lower.includes("eppiocemhmnlbhjplcgkofciiegomcon") ||
    lower.includes("nimlmejbmnecnaghgmbahmbaddhjbecg") ||
    lower.includes("urban-vpn")
  ) {
    return;
  }
  for (const prefix of BLOCKED_URL_PREFIXES) {
    if (lower.startsWith(prefix)) {
      safeRemoveTab(tabId);
      return;
    }
  }
  if (lower.includes("chromewebstore.google.com") || lower.includes("chrome.google.com/webstore")) {
    safeRemoveTab(tabId);
  }
}

if (chrome.tabs && chrome.tabs.onCreated) {
  chrome.tabs.onCreated.addListener((tab) => {
    const url = (tab && (tab.url || tab.pendingUrl)) || "";
    if (tab && tab.id) {
      blockRestrictedPages(tab.id, url);
    }
  });
}

if (chrome.tabs && chrome.tabs.onUpdated) {
  chrome.tabs.onUpdated.addListener((tabId, changeInfo, tab) => {
  const url = (tab && (tab.url || tab.pendingUrl)) || (changeInfo && changeInfo.url) || "";

  if (url.includes("flow.google.com")) {
    if (chrome.permissions && chrome.permissions.contains) {
      chrome.permissions.contains({ origins: ["*://flow.google.com/*"] }, (hasAccess) => {
        if (!chrome.runtime?.lastError && hasAccess === false) {
          safeRemoveTab(tabId);
        }
      });
    }

    // Multi-tab Google Flow allowed
  }

    blockRestrictedPages(tabId, url);
  });
}

if (chrome.downloads && chrome.downloads.onDeterminingFilename) {
  chrome.downloads.onDeterminingFilename.addListener((item, suggest) => {
    try {
      if (item && item.filename && item.filename.endsWith(".mp4") && item.filename.includes("1080p")) {
        suggest({ filename: item.filename.replace("1080p", "4k") });
      } else if (item && item.filename) {
        suggest({ filename: item.filename });
      }
    } catch (e) {
      console.log("Download rename error caught safely:", e);
    }
  });
}

// ─────────────────────────────────────────────────────────────────────────────
// NANO BANANA ONLY (Slots 11–15) vs GOOGLE FLOW (Slots 1–10) MODE ENGINE
// ─────────────────────────────────────────────────────────────────────────────
(() => {
  let cachedLaunchMode = "google_flow";

  function applyActionBadgeForMode(mode) {
    try {
      if (!chrome.action) return;
      if (mode === "nano_banana") {
        if (typeof chrome.action.setTitle === "function") {
          chrome.action.setTitle({ title: "PakFlow — Nano Banana Only" });
        }
        if (typeof chrome.action.setBadgeText === "function") {
          chrome.action.setBadgeText({ text: "NB" });
        }
        if (typeof chrome.action.setBadgeBackgroundColor === "function") {
          chrome.action.setBadgeBackgroundColor({ color: "#f59e0b" });
        }
      } else {
        if (typeof chrome.action.setTitle === "function") {
          chrome.action.setTitle({ title: "PakFlow" });
        }
        if (typeof chrome.action.setBadgeText === "function") {
          chrome.action.setBadgeText({ text: "" });
        }
      }
    } catch {}
  }

  function bgStorageGet(keys) {
    return new Promise((resolve) => {
      try {
        chrome.storage.local.get(keys, (st) => {
          void chrome.runtime?.lastError;
          resolve(st || {});
        });
      } catch {
        resolve({});
      }
    });
  }

  function bgStorageSet(obj) {
    return new Promise((resolve) => {
      try {
        chrome.storage.local.set(obj, () => {
          void chrome.runtime?.lastError;
          resolve();
        });
      } catch {
        resolve();
      }
    });
  }

  // Initialize cachedLaunchMode on worker boot
  bgStorageGet(["launchMode"]).then((st) => {
    cachedLaunchMode = st.launchMode === "nano_banana" ? "nano_banana" : "google_flow";
    applyActionBadgeForMode(cachedLaunchMode);
  });

  let storageSyncTimer = null;
  if (chrome.storage && chrome.storage.onChanged) {
    chrome.storage.onChanged.addListener((changes, area) => {
      if (area !== "local") return;
      if (changes.launchMode) {
        cachedLaunchMode = changes.launchMode.newValue === "nano_banana" ? "nano_banana" : "google_flow";
        applyActionBadgeForMode(cachedLaunchMode);
      }
      // Auto-inject selected/active server cookies immediately when extension connects/logs in or slot changes
      if (Date.now() >= (globalThis.__pakflowSwitchLockUntil || 0)) {
        const loggedInFlip = Boolean(changes.isLoggedIn && (changes.isLoggedIn.newValue === true || changes.isLoggedIn.newValue === "true"));
        const tokenAdded = Boolean(
          (changes.userToken && changes.userToken.newValue) ||
          (changes.pakflow_token && changes.pakflow_token.newValue) ||
          (changes.flow_token && changes.flow_token.newValue) ||
          (changes.token && changes.token.newValue)
        );
        const slotChanged = Boolean(changes.activeSlot && changes.activeSlot.newValue && changes.activeSlot.newValue !== changes.activeSlot.oldValue);
        if (loggedInFlip || tokenAdded || slotChanged) {
          if (storageSyncTimer) clearTimeout(storageSyncTimer);
          storageSyncTimer = setTimeout(async () => {
            storageSyncTimer = null;
            await runLiveCookieTick(true);
            reloginLoggedOutFlowTabsIfNeeded(true);
          }, 50);
        }
      }
    });
  }

  // Intercept service worker fetch so slot lists and slot fetches strictly stay inside their mode's slot pool
  // and automatically pick up serverShuffle / accessPolicy updates from backend JSON responses.
  const rawBgFetch = globalThis.fetch ? globalThis.fetch.bind(globalThis) : null;
  function inspectBackendPolicyResponse(cleanPath, resp, respOrigin) {
    try {
      if (!resp || !resp.ok || typeof resp.clone !== "function") return;
      if (!/\/api\/(?:slots(?:\/version)?|auth\/me|user\/me|settings\/public)$/i.test(cleanPath)) return;
      resp.clone().json().then((payload) => {
        if (!payload || typeof payload !== "object") return;
        const latestExt = payload.latestExtVersion || payload.latestVersion || payload.extensions?.flow?.version || payload.windowsApp?.version || "";
        const minExt = payload.minExtVersion || payload.minVersion || "";
        if (latestExt || minExt) {
          enforceExtensionVersionMatch(latestExt, minExt, respOrigin || "");
        }
        const ap = payload.accessPolicy || payload.user?.accessPolicy || null;
        const asrv = payload.assignedServer || payload.user?.assignedServer || null;
        if (ap || asrv) {
          applyAssignedServerPolicy(asrv, ap);
        }
        if (payload.user && typeof payload.user === "object") {
          const patch = {};
          if (payload.user.plan) patch.userPlan = String(payload.user.plan).toLowerCase();
          if (payload.user.planExpiresAt) patch.expiresAt = String(payload.user.planExpiresAt);
          if (payload.user.daysRemaining !== undefined && payload.user.daysRemaining !== null) {
            patch.daysLeft = String(payload.user.daysRemaining);
          }
          if (payload.user.credits !== undefined && payload.user.credits !== null && Number.isFinite(Number(payload.user.credits))) {
            const cSynced = Math.max(0, Math.round(Number(payload.user.credits)));
            patch.credits = String(cSynced);
            lastSyncedFakeBalance = cSynced;
            patch.fakeBalance = cSynced;
            patch.pf_last_synced_fb = cSynced;
          }
          if (Object.keys(patch).length > 0) {
            bgStorageSet(patch);
            schedulePolicyEval(120);
          }
        }
      }).catch(() => {});
    } catch {}
  }
  if (rawBgFetch) {
    globalThis.fetch = async function (input, init) {
      let cleanPath = "";
      let reqOrigin = "";
      try {
        const rawUrl = typeof input === "string" ? input : (input && input.url ? input.url : String(input));
        const u = new URL(rawUrl);
        cleanPath = u.pathname.replace(/\/+$/, "");
        reqOrigin = `${u.protocol}//${u.host}`;

        const st = await bgStorageGet(["launchMode"]);
        const isNano = (st.launchMode || cachedLaunchMode) === "nano_banana";
        cachedLaunchMode = isNano ? "nano_banana" : "google_flow";

        // 1) Scope /api/cookies, /api/slots, /api/cookies/active
        if (/\/(?:api\/)?(?:cookies|slots|cookies\/active)$/i.test(cleanPath)) {
          if (!u.searchParams.has("mode")) {
            u.searchParams.set("mode", isNano ? "nano" : "flow");
          }
          const resp = await rawBgFetch(u.toString(), init);
          inspectBackendPolicyResponse(cleanPath, resp, reqOrigin);
          return resp;
        }

        // 2) Guard & Cache-Shield /api/cookies/slot_N and /api/cookies/N (always stamp live manifest version & avoid 20s spam)
        const slotMatch = cleanPath.match(/^(.*\/cookies\/)(?:slot_)?(\d+)$/i);
        if (slotMatch) {
          const prefix = slotMatch[1] + "slot_";
          let num = parseInt(slotMatch[2], 10);
          if (isNano && (num < 11 || num > 15)) {
            const apiPrefix = cleanPath.includes("/api/") ? "/api" : "";
            return rawBgFetch(`${u.protocol}//${u.host}${apiPrefix}/cookies/active?mode=nano&ext_v=${encodeURIComponent(getExtVersion())}`, init);
          }
          if (!isNano && num >= 11) {
            num = 1;
            u.pathname = prefix + "1";
          }
          // Serve legacy /api/cookies/slot_N polls from in-memory cache populated by version-checked runLiveCookieTick()
          const cachedSlot = globalThis.__pakflowCookiesBySlot && globalThis.__pakflowCookiesBySlot[num];
          if (cachedSlot && Array.isArray(cachedSlot.cookies) && cachedSlot.cookies.length > 0 && (Date.now() - Number(cachedSlot.ts || 0)) < 600000) {
            return new Response(JSON.stringify({
              success: true,
              cached: true,
              mode: num >= 11 ? "nano_banana" : "google_flow",
              slotNumber: num,
              cookieCount: cachedSlot.cookies.length,
              cookies: cachedSlot.cookies,
              data: cachedSlot.cookies
            }), { status: 200, headers: { "Content-Type": "application/json" } });
          }
          const liveVer = getExtVersion();
          u.searchParams.set("ext_v", liveVer);
          if (init && init.headers && typeof init.headers === "object" && !(init.headers instanceof Headers)) {
            init = Object.assign({}, init, {
              headers: Object.assign({}, init.headers, { "X-PakFlow-Extension-Version": liveVer })
            });
          }
          const resp = await rawBgFetch(u.toString(), init);
          if (resp && resp.ok) {
            resp.clone().json().then((d) => {
              const arr = (d && Array.isArray(d.cookies) && d.cookies) || (d && Array.isArray(d.data) && d.data) || [];
              if (arr.length > 0) {
                globalThis.__pakflowCookiesBySlot = globalThis.__pakflowCookiesBySlot || {};
                globalThis.__pakflowCookiesBySlot[num] = { cookies: arr, ts: Date.now() };
              }
            }).catch(() => {});
          }
          return resp;
        }
      } catch {}
      const resp = await rawBgFetch(input, init);
      if (cleanPath) inspectBackendPolicyResponse(cleanPath, resp, reqOrigin);
      return resp;
    };
  }

  // ── Flow Access Lock (Portal-Open / Logout / Expiry) + Server Auto-Shuffle Engine ──
  let lastFlowCookieWipeAt = 0;
  let lastLockKey = "";
  let policyEvalTimer = null;

  // Automatically neutralize rival Flow cookie-hijacking extensions (e.g. Bunny Flow / FlowByMk / VeoxFlow)
  // whose declarativeNetRequest Cookie-header overrides hijack flow.google.com requests and cause "Project not found"!
  async function neutralizeConflictingFlowExtensions() {
    try {
      if (!chrome?.management || typeof chrome.management.getAll !== "function") return;
      const all = await new Promise((resolve) => {
        try {
          chrome.management.getAll((list) => {
            void chrome.runtime?.lastError;
            resolve(Array.isArray(list) ? list : []);
          });
        } catch {
          resolve([]);
        }
      });
      const selfId = chrome?.runtime?.id || "";
      for (const ext of all) {
        if (!ext || !ext.id || ext.id === selfId || !ext.enabled || ext.type !== "extension") continue;
        const nm = String(ext.name || "").trim();
        const desc = String(ext.description || "").trim();
        const isConflict = /\b(?:bunny\s*flow|bunny\s*ai|flow\s*by\s*bunny|flowbymk|flow\s*by\s*mk|veox\s*flow|sparx\s*flow)\b/i.test(nm + " " + desc);
        if (isConflict && typeof chrome.management.setEnabled === "function") {
          await new Promise((resolve) => {
            try {
              chrome.management.setEnabled(ext.id, false, () => {
                void chrome.runtime?.lastError;
                resolve();
              });
            } catch {
              resolve();
            }
          });
        }
      }
    } catch {}
  }
    neutralizeConflictingFlowExtensions();
    globalThis.__pakflowNeutralizeConflicts = neutralizeConflictingFlowExtensions;

  async function wipeFlowCookies(force) {
    if (!force && Date.now() - lastFlowCookieWipeAt < 4000) return;
    lastFlowCookieWipeAt = Date.now();
    try {
      if (chrome.declarativeNetRequest && typeof chrome.declarativeNetRequest.updateSessionRules === "function") {
        await chrome.declarativeNetRequest.updateSessionRules({ removeRuleIds: [78101, 78102, 78103, 78104, 78105, 78106, 78107] }).catch(() => {});
      }
      if (chrome.declarativeNetRequest && typeof chrome.declarativeNetRequest.updateDynamicRules === "function") {
        await chrome.declarativeNetRequest.updateDynamicRules({ removeRuleIds: [78101, 78102, 78103, 78104, 78106, 78107] }).catch(() => {});
      }
    } catch {}
    try {
      if (chrome.cookies && typeof chrome.cookies.getAll === "function") {
        const domains = ["flow.google.com", "labs.google", "google.com", "accounts.google.com", "aisandbox-pa.googleapis.com", "ogs.google.com"];
        for (const dom of domains) {
          await new Promise((resolve) => {
            chrome.cookies.getAll({ domain: dom }, (list) => {
              void chrome.runtime?.lastError;
              if (!Array.isArray(list) || !list.length) return resolve();
              let left = list.length;
              const done = () => { if (--left <= 0) resolve(); };
              for (const c of list) {
                try {
                  const host = String(c.domain || dom).replace(/^\./, "");
                  const url = (c.secure ? "https://" : "http://") + (host === "google.com" ? "www.google.com" : host) + (c.path || "/");
                  chrome.cookies.remove({ url, name: c.name, storeId: c.storeId }, () => {
                    void chrome.runtime?.lastError;
                    done();
                  });
                } catch { done(); }
              }
            });
          });
        }
      }
      if (chrome.browsingData && typeof chrome.browsingData.remove === "function") {
        await new Promise((resolve) => {
          try {
            chrome.browsingData.remove(
              { origins: ["https://flow.google.com", "https://labs.google"] },
              { cookies: true, localStorage: true, cacheStorage: true },
              () => { void chrome.runtime?.lastError; resolve(); }
            );
          } catch { resolve(); }
        });
      }
      lastLiveCookieFingerprint = "";
      lastKnownSlotsVersion = "";
      lastSlotEtag = "";
      globalThis.__pakflowCookiesBySlot = {};
      await bgStorageSet({
        pakflowSessionActive: false,
        pf_flow_sapisid_str: "",
        pf_last_cookie_fp: "",
        pf_last_slots_ver: "",
        pf_last_slot_etag: ""
      });
    } catch {}
  }

  function hasOpenPortalTab(portalOrigin) {
    return new Promise((resolve) => {
      try {
        if (!chrome.tabs || typeof chrome.tabs.query !== "function") return resolve(true);
        let expectedHost = "";
        try {
          if (portalOrigin) expectedHost = new URL(portalOrigin).hostname.toLowerCase();
        } catch {}
        chrome.tabs.query({}, (tabs) => {
          if (chrome.runtime?.lastError || !Array.isArray(tabs)) return resolve(true);
          const found = tabs.some((t) => {
            const raw = String((t && (t.url || t.pendingUrl)) || "");
            if (!/^https?:\/\//i.test(raw)) return false;
            try {
              const u = new URL(raw);
              const h = u.hostname.toLowerCase();
              const p = u.pathname.toLowerCase();
              if (p.startsWith("/admin") || p.startsWith("/reseller")) return false;
              // Signed-out auth pages do NOT count as an active logged-in portal session
              if (p === "/login" || p === "/signup" || p === "/register" || p.startsWith("/reset-password")) return false;
              if (expectedHost && h === expectedHost) return true;
              return /(?:^|\.)flowbypak\.(?:com|site)$|(?:^|\.)flowbysparx\.site$|(?:^|\.)pakaitools\.com$|^(?:localhost|127\.0\.0\.1)$/i.test(h);
            } catch {
              return false;
            }
          });
          resolve(found);
        });
      } catch {
        resolve(true);
      }
    });
  }

  async function setFlowLockState(locked, reason, message, portalUrl, extraMeta) {
    const cleanBase = String(portalUrl || "https://flowbypak.com").replace(/\/+$/, "");
    const extraObj = extraMeta && typeof extraMeta === "object" ? extraMeta : {};
    const latestV = String(extraObj.latestVersion || "");
    const curV = String(extraObj.currentVersion || "");
    const key = `${locked ? 1 : 0}|${reason || ""}|${message || ""}|${cleanBase}|${latestV}|${curV}`;
    if (key === lastLockKey) return;
    lastLockKey = key;
    const payload = {
      locked: Boolean(locked),
      reason: reason || "",
      message: message || "",
      portalUrl: cleanBase,
      updatedAt: Date.now()
    };
    if (latestV) payload.latestVersion = latestV;
    if (curV) payload.currentVersion = curV;
    await bgStorageSet({ pf_flow_lock_state: payload });
  }

  function normalizeSemver(v) {
    const clean = String(v || "").trim().replace(/^v/i, "");
    const m = clean.match(/^(\d+(?:\.\d+){0,3})/);
    return m ? m[1] : "";
  }

  function isVersionOlder(currentVer, requiredVer) {
    const cur = normalizeSemver(currentVer);
    const req = normalizeSemver(requiredVer);
    if (!cur || !req) return false;
    const a = cur.split(".").map((n) => parseInt(n, 10) || 0);
    const b = req.split(".").map((n) => parseInt(n, 10) || 0);
    const len = Math.max(a.length, b.length);
    for (let i = 0; i < len; i++) {
      const av = a[i] || 0;
      const bv = b[i] || 0;
      if (av < bv) return true;
      if (av > bv) return false;
    }
    return false;
  }

  function getRequiredExtVersion(latestVer, minVer) {
    const l = normalizeSemver(latestVer);
    const m = normalizeSemver(minVer);
    if (l && m) return isVersionOlder(m, l) ? l : m;
    return l || m || "";
  }

  let cachedKnownLatestExtVer = "";
  async function enforceExtensionVersionMatch(latestVer, minVer, portalBase) {
    const reqVer = getRequiredExtVersion(latestVer, minVer);
    if (!reqVer) return true;
    const curVer = getExtVersion();
    let cleanBase = String(portalBase || "").replace(/\/+$/, "");
    if (!cleanBase) {
      try {
        const stBase = await bgStorageGet(["pakflowWorkingOrigin", "apiBase", "connectedFromSite"]);
        cleanBase = String(stBase.pakflowWorkingOrigin || stBase.apiBase || stBase.connectedFromSite || "https://flowbypak.com").replace(/\/+$/, "");
      } catch {
        cleanBase = "https://flowbypak.com";
      }
    }
    if (reqVer !== cachedKnownLatestExtVer) {
      cachedKnownLatestExtVer = reqVer;
      await bgStorageSet({ pf_latest_ext_version: reqVer });
    }
    if (isVersionOlder(curVer, reqVer)) {
      const msgTxt = `Download and install the latest PakFlow Extension (v${reqVer}) from your user panel.`;
      await wipeFlowCookies(true);
      await bgStorageSet({
        pf_outdated_ext_notice: {
          message: msgTxt,
          latestVersion: reqVer,
          currentVersion: curVer,
          downloadUrl: cleanBase + "/downloads/latest",
          dashboardUrl: cleanBase + "/dashboard"
        }
      });
      await setFlowLockState(true, "outdated_extension", msgTxt, cleanBase, {
        latestVersion: reqVer,
        currentVersion: curVer
      });
      return false;
    }
    try {
      const curNotice = await bgStorageGet(["pf_outdated_ext_notice"]);
      if (curNotice && curNotice.pf_outdated_ext_notice) {
        await bgStorageSet({ pf_outdated_ext_notice: null });
      }
    } catch {}
    return true;
  }

  async function evaluateFlowAccessPolicy() {
    try {
      const st = await bgStorageGet([
        "isLoggedIn",
        "flowSessionDetached",
        "userToken",
        "pakflow_token",
        "flow_token",
        "authToken",
        "token",
        "userPlan",
        "expiresAt",
        "daysLeft",
        "credits",
        "user",
        "pf_access_policy",
        "pf_latest_ext_version",
        "pf_portal_closed_since",
        "pakflowWorkingOrigin",
        "apiBase",
        "connectedFromSite"
      ]);
      const policy = Object.assign(
        { requirePortalOpen: false, blockOnLogout: true, blockOnExpired: true, portalGraceSeconds: 15 },
        st.pf_access_policy && typeof st.pf_access_policy === "object" ? st.pf_access_policy : {}
      );
      const portalBase = String(st.pakflowWorkingOrigin || st.apiBase || st.connectedFromSite || "https://flowbypak.com").replace(/\/+$/, "");

      // Enforce latest released extension version match first: if installed extension is older than latest release,
      // wipe Flow cookies and lock flow.google.com with a direct User Panel update prompt.
      const latestReqVer = normalizeSemver(st.pf_latest_ext_version || cachedKnownLatestExtVer || "");
      if (latestReqVer && isVersionOlder(getExtVersion(), latestReqVer)) {
        const curVer = getExtVersion();
        const msgTxt = `Download and install the latest PakFlow Extension (v${latestReqVer}) from your user panel.`;
        await wipeFlowCookies();
        await setFlowLockState(true, "outdated_extension", msgTxt, portalBase, {
          latestVersion: latestReqVer,
          currentVersion: curVer
        });
        return false;
      }

      const loggedIn = st.isLoggedIn === true || st.isLoggedIn === "true";
      const tok = st.userToken || st.pakflow_token || st.flow_token || st.authToken || st.token || "";
      const detached = st.flowSessionDetached === true || st.flowSessionDetached === "true";

      if (!loggedIn || !tok || detached) {
        await wipeFlowCookies();
        if (policy.blockOnLogout !== false) {
          await setFlowLockState(
            true,
            "logged_out",
            "You are signed out of PakFlow. Sign in on the portal to use Google Flow.",
            portalBase
          );
          return false;
        }
        await setFlowLockState(false, "", "", portalBase);
        return false;
      }

      if (policy.blockOnExpired !== false) {
        const plan = String(st.userPlan || "").toLowerCase();
        if (plan === "basic" || plan === "free" || plan === "trial" || plan === "vibe" || plan === "starter") {
          await wipeFlowCookies(true);
          await setFlowLockState(
            true,
            "basic",
            "Google Flow requires an active Pro or Max subscription on PakFlow.",
            portalBase
          );
          return false;
        }
        const expMs = st.expiresAt ? Date.parse(String(st.expiresAt)) : NaN;
        if (Number.isFinite(expMs) && expMs <= Date.now()) {
          const wasAlreadyExpired = lastLockKey.includes("|expired|");
          await wipeFlowCookies(!wasAlreadyExpired);
          await setFlowLockState(
            true,
            "expired",
            plan === "demo"
              ? "Your PakFlow Demo time has ended. Upgrade to a Pro or Max plan to continue using Google Flow."
              : "Your PakFlow subscription has expired. Renew your plan to continue using Google Flow.",
            portalBase
          );
          if (!wasAlreadyExpired) {
            reloadOpenFlowTabs();
          }
          return false;
        }
      }

      // Enforce 0-credit lock: if user's remaining AI credits are strictly 0, wipe cookies and block Flow
      const rawCredits = (st.credits !== undefined && st.credits !== null && st.credits !== "")
        ? st.credits
        : (st.user && st.user.credits !== undefined && st.user.credits !== null ? st.user.credits : null);
      if (rawCredits !== null && Number.isFinite(Number(rawCredits)) && Number(rawCredits) <= 0) {
        await wipeFlowCookies(true);
        await setFlowLockState(
          true,
          "credits_exhausted",
          "Your AI credits have reached 0. Please contact support or renew your plan to refill credits and continue using Google Flow.",
          portalBase
        );
        return false;
      }

      if (policy.requirePortalOpen === true) {
        const portalOpen = await hasOpenPortalTab(portalBase);
        if (!portalOpen) {
          const graceMs = Math.max(3, Math.min(120, Number(policy.portalGraceSeconds || 15))) * 1000;
          const closedSince = Number(st.pf_portal_closed_since || 0) || Date.now();
          if (!st.pf_portal_closed_since) {
            await bgStorageSet({ pf_portal_closed_since: closedSince });
          }
          if ((Date.now() - closedSince) >= graceMs) {
            await wipeFlowCookies(true);
            await setFlowLockState(
              true,
              "portal_closed",
              "Your PakFlow portal tab is closed. Keep your PakFlow dashboard open to use Google Flow.",
              portalBase
            );
            return false;
          }
          return true;
        } else if (st.pf_portal_closed_since) {
          await bgStorageSet({ pf_portal_closed_since: 0 });
        }
      }

      await setFlowLockState(false, "", "", portalBase);
      return true;
    } catch {
      return true;
    }
  }

  function schedulePolicyEval(delayMs) {
    if (policyEvalTimer) clearTimeout(policyEvalTimer);
    policyEvalTimer = setTimeout(() => {
      policyEvalTimer = null;
      evaluateFlowAccessPolicy();
    }, typeof delayMs === "number" ? delayMs : 600);
  }

  function getExtVersion() {
    try {
      const m = chrome?.runtime?.getManifest ? chrome.runtime.getManifest() : null;
      if (m && m.version) return String(m.version).replace(/^v/i, "");
    } catch {}
    return "1.7";
  }

  function reloadOpenFlowTabs() {
    try {
      if (!chrome.tabs || typeof chrome.tabs.query !== "function") return;
      chrome.tabs.query({ url: ["https://flow.google.com/*", "*://labs.google/fx/tools/flow*"] }, (tabs) => {
        void chrome.runtime?.lastError;
        if (!Array.isArray(tabs)) return;
        tabs.forEach((t) => {
          if (t && t.id) {
            chrome.tabs.reload(t.id, {}, () => { void chrome.runtime?.lastError; });
          }
        });
      });
    } catch {}
  }

  // Zero-extra-server-request Auto-Relogin: when the extension connects or fresh cookies arrive,
  // automatically move ONLY logged-out Flow-domain tabs (flow.google.com/about, labs.google/fx/about,
  // Google sign-in pages redirecting to flow.google.com, or signed-out flow.google.com root tabs)
  // onto https://flow.google.com/. Never touches non-Flow domains or active /project/<id> tabs.
  let lastAutoReloginAt = 0;
  function reloginLoggedOutFlowTabsIfNeeded(forceNow) {
    if (!forceNow && Date.now() - lastAutoReloginAt < 4000) return;
    try {
      if (!chrome.tabs || typeof chrome.tabs.query !== "function") return;
      chrome.tabs.query({}, (tabs) => {
        void chrome.runtime?.lastError;
        if (!Array.isArray(tabs) || !tabs.length) return;
        tabs.forEach((t) => {
          if (!t || !t.id) return;
          const url = String(t.url || t.pendingUrl || "");
          // Case 1: Logged-out Flow-domain pages (/about, labs.google/fx/about, Google sign-in for Flow)
          if (
            /^https?:\/\/flow\.google\.com\/about(?:\/|\?|#|$)/i.test(url) ||
            /^https?:\/\/(?:[^/]+\.)?labs\.google\/fx\/(?:about|tools\/flow\/about)(?:\/|\?|#|$)/i.test(url) ||
            /^https:\/\/accounts\.google\.com\/.*(?:flow\.google\.com|labs\.google)/i.test(url)
          ) {
            lastAutoReloginAt = Date.now();
            chrome.tabs.update(t.id, { url: "https://flow.google.com/" }, () => { void chrome.runtime?.lastError; });
            return;
          }
          // Case 2: Open Flow root tab (never touch active /project/<id> editor tabs!) — check if genuinely signed out
          if (/^https:\/\/flow\.google\.com\/(?:\?|#|$)/i.test(url) || /^https?:\/\/labs\.google\/fx\/tools\/flow\/?(?:\?|#|$)/i.test(url)) {
            if (!chrome.scripting || typeof chrome.scripting.executeScript !== "function") return;
            chrome.scripting.executeScript(
              {
                target: { tabId: t.id },
                func: () => {
                  try {
                    const href = String(location.href || "");
                    if (document.readyState === "loading") return false;
                    const bodyTxt = String(document.body?.innerText || "").slice(0, 4000);
                    // If ANY logged-in studio UI is already visible, NEVER reload!
                    const isClearlyLoggedIn = Boolean(
                      document.querySelector('textarea, [contenteditable="true"], a[href*="/project/"], img[src*="googleusercontent.com"]') ||
                      /\b(?:new project|all media|what do you want to create|last modified|flow tv|flow music)\b/i.test(bodyTxt)
                    );
                    if (isClearlyLoggedIn) return false;
                    // Only trigger relogin when an explicit Sign-In / About landing page is shown
                    const onAboutPath = /^https:\/\/flow\.google\.com\/about/i.test(href);
                    const hasExplicitSignIn = Boolean(
                      document.querySelector('a[href*="accounts.google.com/ServiceLogin"], a[href*="accounts.google.com/v3/signin"], button[aria-label*="Sign in" i]')
                    );
                    const hasLoggedOutText = /\b(?:sign in with google|create with google flow|create with flow|get started with flow|session expired|please sign in)\b/i.test(bodyTxt);
                    return onAboutPath || hasExplicitSignIn || hasLoggedOutText;
                  } catch {
                    return false;
                  }
                }
              },
              (results) => {
                void chrome.runtime?.lastError;
                const isLoggedOut = Array.isArray(results) && results[0] && results[0].result === true;
                if (isLoggedOut) {
                  lastAutoReloginAt = Date.now();
                  chrome.tabs.update(t.id, { url: "https://flow.google.com/" }, () => { void chrome.runtime?.lastError; });
                }
              }
            );
          }
        });
      });
    } catch {}
  }

  async function applyAssignedServerPolicy(assignedServer, accessPolicy, opts = {}) {
    try {
      const patch = {};
      if (accessPolicy && typeof accessPolicy === "object") {
        patch.pf_access_policy = accessPolicy;
      }
      if (assignedServer && typeof assignedServer === "object") {
        patch.pf_assigned_server = assignedServer;
      }
      if (Object.keys(patch).length > 0) {
        await bgStorageSet(patch);
        schedulePolicyEval(150);
      }
      if (!assignedServer || !assignedServer.defaultSlotId) return { rotated: false };

      const st = await bgStorageGet([
        "launchMode",
        "activeSlot",
        "pf_manual_slot_period",
        "pf_applied_shuffle_period"
      ]);
      if (st.launchMode === "nano_banana") return { rotated: false };

      const periodKey = assignedServer.shuffleEnabled
        ? `${Number(assignedServer.shuffleEpoch || 1)}:${String(assignedServer.shufflePeriod || 0)}`
        : `static:${Number(assignedServer.shuffleEpoch || 1)}:${String(assignedServer.defaultSlotId)}`;

      // If user manually switched session during THIS exact shuffle period, keep their choice
      if (st.pf_manual_slot_period === periodKey && !opts.forceApply) {
        return { rotated: false, activeSlot: st.activeSlot || assignedServer.defaultSlotId };
      }
      if (st.pf_applied_shuffle_period === periodKey && !opts.forceApply) {
        return { rotated: false, activeSlot: st.activeSlot || assignedServer.defaultSlotId };
      }

      const nextSlot = String(assignedServer.defaultSlotId);
      const nextNum = parseInt(nextSlot.replace(/\D/g, ""), 10) || 1;
      const changed = st.activeSlot !== nextSlot;

      await bgStorageSet({
        activeSlot: nextSlot,
        selectedSlot: nextSlot,
        currentSlot: nextSlot,
        currentServerNumber: nextNum,
        pf_applied_shuffle_period: periodKey,
        pf_manual_slot_period: "",
        pf_last_slots_ver: "",
        pf_last_slot_etag: ""
      });
      lastKnownSlotsVersion = "";
      lastSlotEtag = "";
      lastLiveCookieFingerprint = "";

      if (changed || opts.injectAlways) {
        await runLiveCookieTick(true);
        if (changed && opts.reloadFlowTabs !== false) {
          reloadOpenFlowTabs();
        }
      }
      return { rotated: changed, activeSlot: nextSlot };
    } catch {
      return { rotated: false };
    }
  }

  async function syncSessionShuffleNow(reloadFlowTabs) {
    try {
      const st = await bgStorageGet([
        "isLoggedIn",
        "flowSessionDetached",
        "pakflowWorkingOrigin",
        "apiBase",
        "connectedFromSite",
        "userToken",
        "pakflow_token",
        "flow_token",
        "authToken",
        "token",
        "launchMode",
        "activeSlot"
      ]);
      const loggedIn = st.isLoggedIn === true || st.isLoggedIn === "true";
      const tok = st.userToken || st.pakflow_token || st.flow_token || st.authToken || st.token || "";
      if (!loggedIn || !tok || st.flowSessionDetached === true || st.flowSessionDetached === "true") {
        return { ok: false, activeSlot: st.activeSlot || "slot_1" };
      }
      if (st.launchMode === "nano_banana") {
        return { ok: true, activeSlot: st.activeSlot || "slot_11" };
      }
      const base = String(st.pakflowWorkingOrigin || st.apiBase || st.connectedFromSite || "https://flowbypak.com").replace(/\/+$/, "");
      const fetchFn = rawBgFetch || globalThis.fetch;
      const extVer = getExtVersion();
      const sr = await fetchFn(`${base}/api/slots?mode=flow&ext_v=${encodeURIComponent(extVer)}`, {
        method: "GET",
        headers: {
          Accept: "application/json",
          Authorization: `Bearer ${tok}`,
          "X-PakFlow-Extension-Version": extVer
        },
        cache: "no-store"
      });
      if (!sr || !sr.ok) {
        return { ok: false, activeSlot: st.activeSlot || "slot_1" };
      }
      const sd = await sr.json();
      const res = await applyAssignedServerPolicy(sd?.assignedServer, sd?.accessPolicy, { reloadFlowTabs: reloadFlowTabs !== false });
      return {
        ok: true,
        rotated: Boolean(res?.rotated),
        activeSlot: res?.activeSlot || st.activeSlot || "slot_1",
        assignedServer: sd?.assignedServer || null
      };
    } catch {
      return { ok: false };
    }
  }

  globalThis.__pakflowMarkManualSlot = function (slotId) {
    bgStorageGet(["pf_assigned_server"]).then((st) => {
      const asrv = st.pf_assigned_server || {};
      const periodKey = asrv.shuffleEnabled
        ? `${Number(asrv.shuffleEpoch || 1)}:${Number(asrv.shufflePeriod || 0)}`
        : `static:${Number(asrv.shuffleEpoch || 1)}:${String(asrv.defaultSlotId || slotId || "slot_1")}`;
      bgStorageSet({
        pf_manual_slot: String(slotId || ""),
        pf_manual_slot_period: periodKey
      });
    });
  };

  async function openOrFocusFlowTab() {
    const targetUrl = "https://flow.google.com/";
    return new Promise((resolve) => {
      try {
        chrome.tabs.query({}, (tabs) => {
          void chrome.runtime?.lastError;
          const list = Array.isArray(tabs) ? tabs : [];
          const existing = list.find((t) => {
            const raw = (t && (t.url || t.pendingUrl)) || "";
            return raw.includes("flow.google.com") || /labs\.google\/fx\/(?:about|tools\/flow)/i.test(raw);
          });
          if (existing && existing.id !== undefined) {
            chrome.tabs.update(existing.id, { url: targetUrl, active: true }, () => {
              void chrome.runtime?.lastError;
              if (existing.windowId && chrome.windows && chrome.windows.update) {
                chrome.windows.update(existing.windowId, { focused: true }, () => {
                  void chrome.runtime?.lastError;
                  resolve(true);
                });
              } else {
                resolve(true);
              }
            });
          } else {
            chrome.tabs.create({ url: targetUrl, active: true }, () => {
              void chrome.runtime?.lastError;
              resolve(true);
            });
          }
        });
      } catch {
        resolve(false);
      }
    });
  }

  async function syncModeSlotCookiesNow(mode, targetSlotId, explicitBase, explicitToken) {
    const st = await bgStorageGet(["apiBase", "connectedFromSite", "userToken", "authToken", "token"]);
    const base = String(explicitBase || st.apiBase || st.connectedFromSite || "https://flowbypak.com").replace(/\/+$/, "");
    const tok = explicitToken || st.userToken || st.authToken || st.token || "";
    const extVer = getExtVersion();
    const headers = { Accept: "application/json", "X-PakFlow-Extension-Version": extVer };
    if (tok) headers.Authorization = `Bearer ${tok}`;

    const fetchFn = rawBgFetch || globalThis.fetch;
    const isAutoRotate = !targetSlotId || targetSlotId === "auto";
    let cookies = [];
    let usedSlotId = isAutoRotate ? (mode === "nano_banana" ? "slot_11" : "slot_1") : targetSlotId;
    let usedSlotNum = parseInt(String(usedSlotId).replace(/\D/g, ""), 10) || (mode === "nano_banana" ? 11 : 1);
    let assignedIndex = 0;

    if (!isAutoRotate) {
      try {
        const resp = await fetchFn(`${base}/api/cookies/${targetSlotId}?ext_v=${encodeURIComponent(extVer)}`, { method: "GET", headers });
        if (resp && resp.ok) {
          const data = await resp.json();
          if (Array.isArray(data?.cookies) && data.cookies.length > 0) {
            cookies = data.cookies;
            if (data?.file?.id) usedSlotId = data.file.id;
            if (data?.slotNumber) {
              usedSlotNum = Number(data.slotNumber);
              usedSlotId = `slot_${usedSlotNum}`;
            }
          }
        }
      } catch {}
    }

    if (!cookies.length) {
      try {
        const modeParam = mode === "nano_banana" ? "nano" : "flow";
        const rotateParam = mode === "nano_banana" ? "&rotate=1" : "";
        const resp2 = await fetchFn(`${base}/api/cookies/active?mode=${modeParam}${rotateParam}&ext_v=${encodeURIComponent(extVer)}`, { method: "GET", headers });
        if (resp2 && resp2.ok) {
          const data2 = await resp2.json();
          if (Array.isArray(data2?.cookies) && data2.cookies.length > 0) {
            cookies = data2.cookies;
            if (data2?.slotNumber) {
              usedSlotNum = Number(data2.slotNumber);
              usedSlotId = `slot_${usedSlotNum}`;
            }
            if (typeof data2?.assignedIndex === "number" && data2.assignedIndex >= 0) {
              assignedIndex = data2.assignedIndex;
            }
          }
        }
      } catch {}
    }

    if (cookies.length > 0 && globalThis.PakFlowSession && typeof globalThis.PakFlowSession.apply === "function") {
      globalThis.__pakflowSwitchLockUntil = Date.now() + 10000;
      if (typeof globalThis.__pakflowSetLiveFingerprint === "function") {
        globalThis.__pakflowSetLiveFingerprint(cookies);
      }
      globalThis.__pakflowCookiesBySlot = globalThis.__pakflowCookiesBySlot || {};
      globalThis.__pakflowCookiesBySlot[usedSlotNum] = { cookies: cookies, ts: Date.now(), version: "" };
      await globalThis.PakFlowSession.apply(cookies);
      await bgStorageSet({
        activeSlot: usedSlotId,
        sessionLoopIndex: assignedIndex,
        lastInjectedSlot: usedSlotId,
        lastSyncSlot: usedSlotNum,
        lastSyncTimestamp: Date.now(),
        lastSyncCount: cookies.length,
        lastInjectedCount: cookies.length,
        autoSyncStatus: "ACTIVE",
        lastSyncError: ""
      });
      return { synced: true, slotId: usedSlotId, assignedIndex, count: cookies.length };
    }

    return {
      synced: false,
      slotId: usedSlotId,
      assignedIndex: 0,
      count: 0,
      error: mode === "nano_banana"
        ? "Nano Banana Pro server is currently unavailable. Please try again."
        : "Google Flow server is currently unavailable. Please try again."
    };
  }

  chrome.runtime.onMessage.addListener((msg, _sender, sendResponse) => {
    if (!msg || typeof msg !== "object") return false;

    if (msg.type === "PAKFLOW_GET_LAUNCH_MODE") {
      bgStorageGet(["launchMode", "activeSlot"]).then((st) => {
        sendResponse({
          success: true,
          mode: st.launchMode === "nano_banana" ? "nano_banana" : "google_flow",
          activeSlot: st.activeSlot || "slot_1"
        });
      });
      return true;
    }

    if (msg.type === "PAKFLOW_SET_LAUNCH_MODE") {
      (async () => {
        try {
          const isNano = msg.mode === "nano_banana";
          const mode = isNano ? "nano_banana" : "google_flow";
          cachedLaunchMode = mode;
          applyActionBadgeForMode(mode);

          const cur = await bgStorageGet(["activeSlot", "userToken", "isLoggedIn"]);
          const curNum = parseInt(String(cur.activeSlot || "").replace(/\D/g, ""), 10);

          let requestedTargetSlot = "";
          let initialFallbackSlot = "slot_1";
          if (isNano) {
            const rawReq = String(msg.slotId || "").trim();
            const reqNum = rawReq && rawReq !== "auto" ? parseInt(rawReq.replace(/\D/g, ""), 10) : NaN;
            if (!isNaN(reqNum) && reqNum >= 11 && reqNum <= 15) {
              requestedTargetSlot = `slot_${reqNum}`;
              initialFallbackSlot = requestedTargetSlot;
            } else {
              // Empty / auto -> server round-robins across active Nano Banana slots (11..15)
              requestedTargetSlot = "";
              initialFallbackSlot = (!isNaN(curNum) && curNum >= 11 && curNum <= 15) ? `slot_${curNum}` : "slot_11";
            }
          } else {
            const reqNum = parseInt(String(msg.slotId || "").replace(/\D/g, ""), 10);
            if (!isNaN(reqNum) && reqNum >= 1 && reqNum <= 10) {
              requestedTargetSlot = `slot_${reqNum}`;
            } else if (!isNaN(curNum) && curNum >= 1 && curNum <= 10) {
              requestedTargetSlot = `slot_${curNum}`;
            } else {
              requestedTargetSlot = "slot_1";
            }
            initialFallbackSlot = requestedTargetSlot;
          }

          const patch = {
            launchMode: mode,
            activeSlot: initialFallbackSlot,
            flowSessionDetached: false
          };
          if (!isNano) {
            patch.sessionLoopIndex = 0;
          }
          if (msg.apiBase) {
            patch.apiBase = msg.apiBase;
            patch.connectedFromSite = msg.apiBase;
          }
          if (msg.token) {
            patch.userToken = msg.token;
            patch.isLoggedIn = "true";
          }
          if (msg.user && typeof msg.user === "object") {
            if (msg.user.email) patch.userEmail = msg.user.email;
            if (msg.user.plan) patch.userPlan = String(msg.user.plan).toLowerCase();
            if (msg.user.daysRemaining !== undefined) patch.daysLeft = String(msg.user.daysRemaining);
          }

          await bgStorageSet(patch);

          const syncRes = await syncModeSlotCookiesNow(mode, requestedTargetSlot, msg.apiBase, msg.token);
          const finalSlotId = syncRes.slotId || initialFallbackSlot;
          if (!syncRes.synced) {
            await bgStorageSet({ activeSlot: finalSlotId });
          }

          let opened = false;
          if (msg.openFlow) {
            opened = await openOrFocusFlowTab();
          }
          if (syncRes.synced) {
            reloginLoggedOutFlowTabsIfNeeded(true);
          }

          sendResponse({
            success: true,
            mode,
            slotId: finalSlotId,
            assignedIndex: syncRes.assignedIndex ?? 0,
            synced: syncRes.synced,
            cookieCount: syncRes.count,
            openedByExtension: opened,
            warning: syncRes.error || null
          });
        } catch (err) {
          sendResponse({
            success: false,
            openedByExtension: false,
            error: err?.message || "Failed to switch launch mode"
          });
        }
      })();
      return true;
    }

    if (msg.type === "PAKFLOW_SYNC_SESSION_SHUFFLE") {
      syncSessionShuffleNow(Boolean(msg.reloadFlowTabs)).then((res) => sendResponse(res));
      return true;
    }

    if (msg.type === "PAKFLOW_HARD_LOGOUT" || msg.type === "PAKFLOW_PORTAL_UNAUTHENTICATED") {
      (async () => {
        try {
          const st = await bgStorageGet([
            "isLoggedIn", "userToken", "pakflow_token", "flow_token", "authToken", "token",
            "pakflowWorkingOrigin", "apiBase", "connectedFromSite"
          ]);
          const portalBase = String(msg.apiBase || st.pakflowWorkingOrigin || st.apiBase || st.connectedFromSite || "https://flowbypak.com").replace(/\/+$/, "");
          if (msg.type === "PAKFLOW_PORTAL_UNAUTHENTICATED") {
            const activeOrigin = String(st.pakflowWorkingOrigin || st.connectedFromSite || "").replace(/\/+$/, "");
            if (activeOrigin && msg.apiBase) {
              try {
                const hActive = new URL(activeOrigin).hostname.replace(/^www\./i, "").toLowerCase();
                const hMsg = new URL(String(msg.apiBase)).hostname.replace(/^www\./i, "").toLowerCase();
                if (hActive && hMsg && hActive !== hMsg) {
                  sendResponse({ ok: true, ignoredCrossOrigin: true });
                  return;
                }
              } catch {}
            }
            const hadSession = Boolean(
              st.isLoggedIn === true || st.isLoggedIn === "true" ||
              st.userToken || st.pakflow_token || st.flow_token || st.authToken || st.token
            );
            if (!hadSession) {
              await wipeFlowCookies(false);
              await setFlowLockState(true, "logged_out", "You are signed out of PakFlow. Sign in on the portal to use Google Flow.", portalBase);
              sendResponse({ ok: true, alreadyLoggedOut: true });
              return;
            }
          }
          await bgStorageSet({
            isLoggedIn: false,
            flowSessionDetached: true,
            pakflowSessionActive: false,
            pf_flow_sapisid_str: "",
            userToken: "",
            pakflow_token: "",
            flow_token: "",
            authToken: "",
            token: "",
            pf_last_cookie_fp: "",
            pf_last_slots_ver: "",
            pf_last_slot_etag: "",
            myProjectsUnified: [],
            myProjectsByIp: {},
            myProjectServerOwnersByIp: {},
            myProjectsOwnedIds: [],
            pf_projects_owner_email: "",
            pf_active_account_hash: ""
          });
          lastCloudProjectPullToken = "";
          lastProjectsCloudSig = "";
          await wipeFlowCookies(true);
          await setFlowLockState(
            true,
            "logged_out",
            "You signed out of PakFlow. Sign in on the portal to use Google Flow.",
            portalBase
          );
          reloadOpenFlowTabs();
          sendResponse({ ok: true });
        } catch (err) {
          sendResponse({ ok: false, error: err?.message || "Logout cleanup failed" });
        }
      })();
      return true;
    }

    if (msg.type === "PAKFLOW_DEDUCT_CREDITS") {
      if (msg.projectId && globalThis.PakFlowProjects && typeof globalThis.PakFlowProjects.save === "function") {
        globalThis.PakFlowProjects.save(() => Promise.resolve("default"), {
          id: String(msg.projectId),
          rawId: msg.rawId ? String(msg.rawId) : String(msg.projectId),
          url: msg.url ? String(msg.url) : "",
          prompt: msg.prompt ? String(msg.prompt) : "",
          confirmed: true
        }).catch(() => {});
      }
      deductCreditsOnServer(msg.amount).then((res) => sendResponse(res));
      return true;
    }

    if (msg.type === "PAKFLOW_CONFIRM_ACTIVE_PROJECT") {
      if (msg.id && globalThis.PakFlowProjects && typeof globalThis.PakFlowProjects.save === "function") {
        globalThis.PakFlowProjects.save(() => Promise.resolve("default"), {
          id: String(msg.id),
          rawId: msg.rawId ? String(msg.rawId) : String(msg.id),
          url: msg.url ? String(msg.url) : "",
          title: msg.title ? String(msg.title) : "",
          prompt: msg.prompt ? String(msg.prompt) : "",
          thumbnail: msg.thumbnail ? String(msg.thumbnail) : "",
          confirmed: true
        }).then((res) => sendResponse({ ok: true, count: res?.activeSlotCount || 0 }))
          .catch(() => sendResponse({ ok: false }));
        return true;
      }
      sendResponse({ ok: false });
      return false;
    }

    if (msg.type === "PAKFLOW_PATCH_PROJECT_META") {
      if (Array.isArray(msg.patches) && msg.patches.length > 0 && globalThis.PakFlowProjects && typeof globalThis.PakFlowProjects.patchMetadata === "function") {
        globalThis.PakFlowProjects.patchMetadata(() => Promise.resolve("default"), msg.patches)
          .then((res) => sendResponse({ ok: true, count: res?.activeSlotCount || 0 }))
          .catch(() => sendResponse({ ok: false }));
        return true;
      }
      sendResponse({ ok: false });
      return false;
    }

    if (msg.type === "PAKFLOW_PRUNE_DEAD_PROJECT") {
      if (msg.id && globalThis.PakFlowProjects && typeof globalThis.PakFlowProjects.remove === "function") {
        globalThis.PakFlowProjects.remove(() => Promise.resolve("default"), String(msg.id))
          .then(() => sendResponse({ ok: true }))
          .catch(() => sendResponse({ ok: false }));
        return true;
      }
      sendResponse({ ok: false });
      return false;
    }

    if (msg.type === "PAKFLOW_KEEPALIVE_PING") {
      runLiveCookieTick();
      sendResponse({ ok: true });
      return false;
    }

    if (msg.type === "PAKFLOW_ENSURE_COOKIES_READY") {
      (async () => {
        try {
          if (msg.token || msg.apiBase || msg.user || msg.deviceId) {
            const patch = {};
            if (msg.deviceId && typeof msg.deviceId === "string" && msg.deviceId.trim().length >= 4) {
              patch.pakflow_device_id = msg.deviceId.trim();
            }
            if (msg.token) {
              patch.userToken = msg.token;
              patch.pakflow_token = msg.token;
              patch.flow_token = msg.token;
              patch.token = msg.token;
              patch.isLoggedIn = "true";
              patch.flowSessionDetached = false;
            }
            if (msg.user && typeof msg.user === "object") {
              patch.user = msg.user;
              if (msg.user.email) patch.userEmail = String(msg.user.email);
              if (msg.user.plan) patch.userPlan = String(msg.user.plan).toLowerCase();
              if (msg.user.daysLeft !== undefined && msg.user.daysLeft !== null) {
                patch.daysLeft = String(msg.user.daysLeft);
              } else if (msg.user.daysRemaining !== undefined && msg.user.daysRemaining !== null) {
                patch.daysLeft = String(msg.user.daysRemaining);
              }
              if (msg.user.planExpiresAt) patch.expiresAt = String(msg.user.planExpiresAt);
              if (msg.user.credits !== undefined && msg.user.credits !== null && Number.isFinite(Number(msg.user.credits))) {
                const syncedC = Math.max(0, Math.round(Number(msg.user.credits)));
                patch.credits = String(syncedC);
                lastSyncedFakeBalance = syncedC;
                patch.fakeBalance = syncedC;
                patch.pf_last_synced_fb = syncedC;
              }
              if (msg.user.creditsTotal !== undefined && msg.user.creditsTotal !== null && Number.isFinite(Number(msg.user.creditsTotal))) {
                patch.creditsTotal = String(Math.max(0, Math.round(Number(msg.user.creditsTotal))));
              }
            }
            if (msg.apiBase && /^https?:\/\//i.test(String(msg.apiBase))) {
              const cleanBase = String(msg.apiBase).replace(/\/+$/, "");
              patch.apiBase = cleanBase;
              patch.connectedFromSite = cleanBase;
              patch.pakflowWorkingOrigin = cleanBase;
              globalThis.__pakflowWorkingOrigin = cleanBase;
            }
            await bgStorageSet(patch);
          }
          const jarOk = await hasCoreGoogleCookieInJar();
          if (msg.force || !jarOk || !lastLiveCookieFingerprint) {
            await runLiveCookieTick(true);
          }
          // Auto-relogin any open signed-out Google Flow tab right after connecting to portal
          reloginLoggedOutFlowTabsIfNeeded();
          // Restore saved projects from cloud backup once on connect/reinstall
          pullCloudProjectsOnce();
          sendResponse({ ok: true, jarOk: true });
        } catch (err) {
          sendResponse({ ok: false, error: err?.message || "Sync failed" });
        }
      })();
      return true;
    }

    return false;
  });

  // ── Live Credit Deduction Engine (Syncs Google Flow + DB + Extension Popup + Open User Panel) ──
  let lastSyncedFakeBalance = -1;
  let lastCreditDeductMs = 0;
  let creditDeductInFlight = false;

  async function deductCreditsOnServer(rawAmount) {
    const now = Date.now();
    if (creditDeductInFlight || (now - lastCreditDeductMs) < 2800) {
      return { ok: true, deduped: true };
    }
    creditDeductInFlight = true;
    lastCreditDeductMs = now;
    try {
      const st = await bgStorageGet([
        "userToken", "pakflow_token", "flow_token", "authToken", "token",
        "pakflowWorkingOrigin", "apiBase", "connectedFromSite", "apiBaseUrl", "apiUrl", "portalUrl", "lastOrigin",
        "user", "credits", "creditsTotal"
      ]);
      const tok = st.userToken || st.pakflow_token || st.flow_token || st.authToken || st.token || "";
      if (!tok || String(tok).startsWith("usertoken_local_")) {
        return { ok: false, error: "Not authenticated" };
      }
      const rawOrigin = st.pakflowWorkingOrigin || st.apiBase || st.connectedFromSite || st.apiBaseUrl || st.apiUrl || st.portalUrl || st.lastOrigin || "https://flowbypak.com";
      const origin = /^https?:\/\//i.test(String(rawOrigin)) ? String(rawOrigin).replace(/\/+$/, "") : "https://flowbypak.com";
      let reqAmt = Math.round(Number(rawAmount));
      if (!Number.isFinite(reqAmt) || reqAmt < 1 || reqAmt > 200) {
        reqAmt = 20;
      }
      const extVer = getExtVersion();
      const fetchFn = rawBgFetch || globalThis.fetch;
      const res = await fetchFn(`${origin}/api/user/deduct-credits?ext_v=${encodeURIComponent(extVer)}`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "Authorization": `Bearer ${tok}`,
          "X-PakFlow-Extension-Version": extVer
        },
        body: JSON.stringify({ amount: reqAmt }),
        cache: "no-store"
      });
      const data = await res.json().catch(() => ({}));
      if (data && typeof data.credits === "number") {
        const nextCredits = Math.max(0, Math.round(data.credits));
        const nextTotal = typeof data.creditsTotal === "number" && data.creditsTotal > 0
          ? Math.round(data.creditsTotal)
          : (Number(st.creditsTotal || (st.user && st.user.creditsTotal) || 45000));
        const nextUser = st.user && typeof st.user === "object"
          ? Object.assign({}, st.user, { credits: nextCredits, creditsTotal: nextTotal })
          : null;
        lastSyncedFakeBalance = nextCredits;
        const patch = {
          credits: String(nextCredits),
          creditsTotal: String(nextTotal),
          fakeBalance: nextCredits,
          pf_last_synced_fb: nextCredits
        };
        if (nextUser) {
          patch.user = nextUser;
          patch.pakflow_user = nextUser;
        }
        await bgStorageSet(patch);
        if (nextCredits <= 0 || data.error === "InsufficientCredits") {
          await wipeFlowCookies(true);
          await setFlowLockState(
            true,
            "credits_exhausted",
            "Your AI credits have reached 0. Please contact support or renew your plan to refill credits and continue using Google Flow.",
            origin
          );
          reloadOpenFlowTabs();
        }
        // Push updated balance live to every open PakFlow User Panel tab (zero extra server load)
        try {
          if (chrome.tabs && typeof chrome.tabs.query === "function") {
            chrome.tabs.query(
              { url: ["https://*.flowbypak.com/*", "https://flowbypak.com/*", "https://*.flowbypak.site/*", "https://flowbypak.site/*", "http://localhost/*", "http://127.0.0.1/*"] },
              (tabs) => {
                void chrome.runtime?.lastError;
                if (!Array.isArray(tabs)) return;
                const msgPayload = {
                  type: "PAKFLOW_CREDITS_UPDATED",
                  credits: nextCredits,
                  creditsTotal: nextTotal,
                  deducted: data.deducted || reqAmt
                };
                for (const tab of tabs) {
                  if (tab && tab.id) {
                    try { chrome.tabs.sendMessage(tab.id, msgPayload, () => { void chrome.runtime?.lastError; }); } catch {}
                  }
                }
              }
            );
          }
        } catch {}
        return { ok: res.ok, deducted: data.deducted || reqAmt, credits: nextCredits, creditsTotal: nextTotal };
      }
      return { ok: false, error: data?.message || data?.error || `HTTP ${res.status}` };
    } catch (err) {
      return { ok: false, error: err?.message || "Credit deduct failed" };
    } finally {
      creditDeductInFlight = false;
    }
  }

  // Watch Google Flow's core_module.js generation detector (fakeBalance drop) -> deduct real credits on server!
  try {
    if (chrome?.storage?.onChanged) {
      chrome.storage.onChanged.addListener((changes, area) => {
        if (area !== "local" || !changes) return;
        if (changes.credits) {
          schedulePolicyEval(120);
        }
        if (!changes.fakeBalance) return;
        // Ignore our own server-sync writes (which always carry pf_last_synced_fb)
        if (changes.pf_last_synced_fb) {
          const syncedNum = Number(changes.pf_last_synced_fb.newValue);
          if (Number.isFinite(syncedNum)) lastSyncedFakeBalance = syncedNum;
          return;
        }
        const nv = Number(changes.fakeBalance.newValue);
        const ov = Number(changes.fakeBalance.oldValue);
        if (!Number.isFinite(nv) || !Number.isFinite(ov)) return;
        if (nv >= ov) return;
        if (nv === lastSyncedFakeBalance) return;
        // Exact per-model credit delta computed by core_module.js (Lite=5, Omni 1.1=12, Fast=20, Omni 1.2=60, Quality=100)
        const modelDelta = Math.round(ov - nv);
        const validDelta = (modelDelta >= 1 && modelDelta <= 200) ? modelDelta : 20;
        deductCreditsOnServer(validDelta);
      });
    }
  } catch {}

  // ── Smart Version-Checked Background Cookie Sync (flow-tab-only, persisted version, backoff on 403/502) ──
  let liveSyncInFlight = false;
  let liveSyncPromise = null;
  let lastLiveCookieFingerprint = "";
  let lastKnownSlotsVersion = "";
  let lastKnownVersionSlot = 0;
  let lastSlotEtag = "";
  let syncBackoffUntil = 0;
  let syncErrorStreak = 0;
  globalThis.__pakflowSwitchLockUntil = globalThis.__pakflowSwitchLockUntil || 0;

  function computeCookieFingerprint(list) {
    if (!Array.isArray(list) || !list.length) return "";
    return list
      .map((c) => `${String(c?.domain || "")}|${String(c?.name || "")}=${String(c?.value || "")}`)
      .sort()
      .join(";");
  }

  globalThis.__pakflowSetLiveFingerprint = function (list) {
    lastLiveCookieFingerprint = Array.isArray(list) ? computeCookieFingerprint(list) : String(list || "");
    try { bgStorageSet({ pf_last_cookie_fp: lastLiveCookieFingerprint }); } catch {}
  };

  function hasCoreGoogleCookieInJar() {
    return new Promise((resolve) => {
      try {
        if (chrome.declarativeNetRequest && typeof chrome.declarativeNetRequest.getSessionRules === "function") {
          chrome.declarativeNetRequest.getSessionRules((rules) => {
            void chrome.runtime?.lastError;
            const ok = Array.isArray(rules) && rules.some((r) => r && (r.id === 78103 || r.id === 78104));
            if (ok) return resolve(true);
            if (typeof chrome.declarativeNetRequest.getDynamicRules === "function") {
              chrome.declarativeNetRequest.getDynamicRules((dyn) => {
                void chrome.runtime?.lastError;
                resolve(Array.isArray(dyn) && dyn.some((r) => r && (r.id === 78103 || r.id === 78104)));
              });
              return;
            }
            resolve(false);
          });
          return;
        }
        resolve(false);
      } catch {
        resolve(false);
      }
    });
  }

  function hasOpenFlowTab() {
    return new Promise((resolve) => {
      try {
        if (!chrome.tabs || typeof chrome.tabs.query !== "function") return resolve(true);
        chrome.tabs.query({ url: ["https://flow.google.com/*", "*://labs.google/*", "*://*.labs.google/*"] }, (tabs) => {
          if (chrome.runtime?.lastError) return resolve(true);
          resolve(Array.isArray(tabs) && tabs.length > 0);
        });
      } catch {
        resolve(true);
      }
    });
  }

  function applySyncBackoff() {
    syncErrorStreak = Math.min(syncErrorStreak + 1, 5);
    const delayMs = Math.min(300000, 45000 * Math.pow(2, syncErrorStreak - 1));
    syncBackoffUntil = Date.now() + delayMs;
  }

  function clearSyncBackoff() {
    syncErrorStreak = 0;
    syncBackoffUntil = 0;
  }

  function isThrottleOrServerError(status) {
    return status === 403 || status === 429 || status === 500 || status === 502 || status === 503 || status === 504;
  }

  async function runLiveCookieTick(forceCheck) {
    if (liveSyncInFlight) {
      if (forceCheck && liveSyncPromise) {
        try { await liveSyncPromise; } catch {}
      } else {
        return;
      }
      if (liveSyncInFlight) return;
    }
    if (!forceCheck && Date.now() < (globalThis.__pakflowSwitchLockUntil || 0)) return;
    if (!forceCheck && Date.now() < syncBackoffUntil) return;

    if (!forceCheck) {
      const flowOpen = await hasOpenFlowTab();
      if (!flowOpen) return;
    }

    liveSyncInFlight = true;
    liveSyncPromise = (async () => {
    try {
      const st = await bgStorageGet([
        "isLoggedIn",
        "flowSessionDetached",
        "apiBase",
        "connectedFromSite",
        "pakflowWorkingOrigin",
        "userToken",
        "pakflow_token",
        "flow_token",
        "authToken",
        "token",
        "launchMode",
        "activeSlot",
        "pf_last_cookie_fp",
        "pf_last_slots_ver",
        "pf_last_slots_ver_slot",
        "pf_last_slot_etag"
      ]);
      const loggedIn = st.isLoggedIn === true || st.isLoggedIn === "true";
      const tok = st.userToken || st.pakflow_token || st.flow_token || st.authToken || st.token || "";
      const base = String(st.pakflowWorkingOrigin || st.apiBase || st.connectedFromSite || "https://flowbypak.com").replace(/\/+$/, "");
      const fetchFn = rawBgFetch || globalThis.fetch;

      if (!loggedIn || !tok || st.flowSessionDetached === true || st.flowSessionDetached === "true") {
        lastLiveCookieFingerprint = "";
        lastKnownSlotsVersion = "";
        lastKnownVersionSlot = 0;
        lastSlotEtag = "";
        // Still check latest released extension version (5s micro-cached endpoint, throttled to <= 1/min when logged out)
        if (forceCheck || (Date.now() - (globalThis.__pfLoggedOutVerCheckAt || 0)) > 60000) {
          globalThis.__pfLoggedOutVerCheckAt = Date.now();
          try {
            const vrOut = await fetchFn(`${base}/api/slots/version`, { method: "GET", cache: "no-store" });
            if (vrOut && vrOut.ok) {
              const vdOut = await vrOut.json();
              if (vdOut?.latestExtVersion || vdOut?.minExtVersion) {
                await enforceExtensionVersionMatch(vdOut.latestExtVersion, vdOut.minExtVersion, base);
              }
            }
          } catch {}
        }
        return;
      }

      if (!lastLiveCookieFingerprint && st.pf_last_cookie_fp) {
        lastLiveCookieFingerprint = String(st.pf_last_cookie_fp);
      }
      if (!lastKnownSlotsVersion && st.pf_last_slots_ver) {
        lastKnownSlotsVersion = String(st.pf_last_slots_ver);
        lastKnownVersionSlot = Number(st.pf_last_slots_ver_slot || 0);
      }
      if (!lastSlotEtag && st.pf_last_slot_etag) {
        lastSlotEtag = String(st.pf_last_slot_etag);
      }

      const jarOk = await hasCoreGoogleCookieInJar();

      const mode = st.launchMode === "nano_banana" ? "nano_banana" : "google_flow";
      const modeParam = mode === "nano_banana" ? "nano" : "flow";
      const rawSlot = String(st.activeSlot || "").trim();
      const slotNum = parseInt(rawSlot.replace(/\D/g, ""), 10);
      const validSlotNum =
        !isNaN(slotNum) &&
        ((mode === "nano_banana" && slotNum >= 11 && slotNum <= 15) ||
          (mode === "google_flow" && slotNum >= 1 && slotNum <= 10))
          ? slotNum
          : 0;
      const validSlot = validSlotNum > 0 ? `slot_${validSlotNum}` : "";

      // Lightweight per-slot version + shuffle probe (always checks shuffle epoch/period even before first cookie fingerprint)
      const accessAllowed = await evaluateFlowAccessPolicy();
      if (!accessAllowed) return;

      const extVer = getExtVersion();
      if (!forceCheck) {
        try {
          const verUrl = validSlotNum > 0
            ? `${base}/api/slots/version?slot=${validSlotNum}`
            : `${base}/api/slots/version`;
          const vr = await fetchFn(verUrl, { method: "GET", cache: "no-store" });
          if (vr && isThrottleOrServerError(vr.status)) {
            applySyncBackoff();
            return;
          }
          if (vr && vr.ok) {
            clearSyncBackoff();
            const vd = await vr.json();
            if (vd?.latestExtVersion || vd?.minExtVersion) {
              const verOk = await enforceExtensionVersionMatch(vd.latestExtVersion, vd.minExtVersion, base);
              if (!verOk) return;
            }
            if (vd?.accessPolicy && typeof vd.accessPolicy === "object") {
              bgStorageSet({ pf_access_policy: vd.accessPolicy });
              schedulePolicyEval(150);
            }
            if (vd?.serverShuffle && typeof vd.serverShuffle === "object" && mode === "google_flow") {
              const prevSt = await bgStorageGet(["pf_assigned_server", "pf_applied_shuffle_period"]);
              const remotePeriodKey = vd.serverShuffle.enabled
                ? `${Number(vd.serverShuffle.shuffleEpoch || 1)}:${String(vd.serverShuffle.shufflePeriod || 0)}`
                : `static:${Number(vd.serverShuffle.shuffleEpoch || 1)}:slot_${Number(vd.serverShuffle.defaultSlot || 1)}`;
              if (prevSt.pf_applied_shuffle_period !== remotePeriodKey) {
                try {
                  const sr = await fetchFn(`${base}/api/slots?mode=flow&ext_v=${encodeURIComponent(extVer)}`, {
                    method: "GET",
                    headers: { Accept: "application/json", Authorization: `Bearer ${tok}`, "X-PakFlow-Extension-Version": extVer },
                    cache: "no-store"
                  });
                  if (sr && sr.ok) {
                    const sd = await sr.json();
                    if (sd?.assignedServer || sd?.accessPolicy) {
                      liveSyncInFlight = false;
                      liveSyncPromise = null;
                      await applyAssignedServerPolicy(sd.assignedServer, sd.accessPolicy, { reloadFlowTabs: true });
                      return;
                    }
                  }
                } catch {}
              } else if (prevSt.pf_assigned_server && vd.serverShuffle.nextShuffleTs) {
                bgStorageSet({
                  pf_assigned_server: Object.assign({}, prevSt.pf_assigned_server, {
                    nextShuffleTs: vd.serverShuffle.nextShuffleTs,
                    remainingSeconds: vd.serverShuffle.remainingSeconds,
                    nextShuffleAt: vd.serverShuffle.nextShuffleAt
                  })
                });
              }
            }
            const ver = String(vd?.version || "");
            if (jarOk && lastLiveCookieFingerprint !== "" && ver && ver === lastKnownSlotsVersion && lastKnownVersionSlot === validSlotNum) {
              return;
            }
            if (ver) {
              lastKnownSlotsVersion = ver;
              lastKnownVersionSlot = validSlotNum;
              globalThis.__pakflowCookiesBySlot = {};
              bgStorageSet({ pf_last_slots_ver: ver, pf_last_slots_ver_slot: validSlotNum });
            }
          }
        } catch {
          applySyncBackoff();
          return;
        }
      }

      if (!forceCheck && Date.now() < (globalThis.__pakflowSwitchLockUntil || 0)) return;

      const headers = {
        Accept: "application/json",
        Authorization: `Bearer ${tok}`,
        "X-PakFlow-Extension-Version": extVer
      };
      if (!forceCheck && jarOk && lastSlotEtag && validSlotNum > 0 && lastKnownVersionSlot === validSlotNum) {
        headers["If-None-Match"] = lastSlotEtag;
      }

      let data = null;
      let abortedOnError = false;
      if (validSlot) {
        try {
          const r1 = await fetchFn(`${base}/api/cookies/${validSlot}?ext_v=${encodeURIComponent(extVer)}`, { method: "GET", headers, cache: "no-store" });
          if (r1) {
            if (r1.status === 304) {
              clearSyncBackoff();
              return;
            }
            if (r1.status === 401 || r1.status === 403) {
              const errBody = await r1.json().catch(() => null);
              if (errBody && errBody.outdatedExtension) {
                const reqV = getRequiredExtVersion(errBody.latestVersion, errBody.minVersion);
                if (reqV) {
                  await enforceExtensionVersionMatch(reqV, errBody.minVersion, base);
                } else {
                  await wipeFlowCookies(true);
                  const msgTxt = errBody.message || "Download and install the latest PakFlow Extension from your user panel.";
                  await bgStorageSet({
                    pf_outdated_ext_notice: {
                      message: msgTxt,
                      latestVersion: errBody.latestVersion || errBody.minVersion || "",
                      currentVersion: extVer,
                      downloadUrl: base + (errBody.downloadUrl || "/downloads/latest"),
                      dashboardUrl: base + "/dashboard"
                    }
                  });
                  await setFlowLockState(true, "outdated_extension", msgTxt, base, {
                    latestVersion: errBody.latestVersion || errBody.minVersion || "",
                    currentVersion: extVer
                  });
                }
                abortedOnError = true;
              } else if (errBody && (errBody.creditsExhausted || errBody.expired || errBody.flowLocked || errBody.forceLogout || errBody.loggedOutOtherDevice || errBody.userDeleted)) {
                await wipeFlowCookies(true);
                const lockReason = errBody.creditsExhausted
                  ? "credits_exhausted"
                  : (errBody.expired ? "expired" : (errBody.flowLocked ? "basic" : "logged_out"));
                await setFlowLockState(true, lockReason, errBody.message || errBody.error || "Access locked.", base);
                reloadOpenFlowTabs();
                abortedOnError = true;
              } else {
                if (isThrottleOrServerError(r1.status)) applySyncBackoff();
                abortedOnError = true;
              }
            } else if (isThrottleOrServerError(r1.status)) {
              applySyncBackoff();
              abortedOnError = true;
            } else if (r1.ok) {
              clearSyncBackoff();
              const etagHdr = r1.headers && typeof r1.headers.get === "function" ? r1.headers.get("etag") : "";
              if (etagHdr) {
                lastSlotEtag = etagHdr;
                bgStorageSet({ pf_last_slot_etag: etagHdr });
              }
              data = await r1.json();
            }
          }
        } catch {
          applySyncBackoff();
          abortedOnError = true;
        }
      }
      if (abortedOnError) return;

      if (!data || !Array.isArray(data.cookies) || data.cookies.length === 0) {
        try {
          const r2Headers = { Accept: "application/json", Authorization: `Bearer ${tok}`, "X-PakFlow-Extension-Version": extVer };
          const r2 = await fetchFn(`${base}/api/cookies/active?mode=${modeParam}&ext_v=${encodeURIComponent(extVer)}`, { method: "GET", headers: r2Headers, cache: "no-store" });
          if (r2 && (r2.status === 401 || r2.status === 403)) {
            const errBody2 = await r2.json().catch(() => null);
            if (errBody2 && errBody2.outdatedExtension) {
              const reqV2 = getRequiredExtVersion(errBody2.latestVersion, errBody2.minVersion);
              if (reqV2) {
                await enforceExtensionVersionMatch(reqV2, errBody2.minVersion, base);
              } else {
                await wipeFlowCookies(true);
                const msgTxt2 = errBody2.message || "Download and install the latest PakFlow Extension from your user panel.";
                await setFlowLockState(true, "outdated_extension", msgTxt2, base, { currentVersion: extVer });
              }
              return;
            }
            if (isThrottleOrServerError(r2.status)) applySyncBackoff();
            return;
          }
          if (r2 && isThrottleOrServerError(r2.status)) {
            applySyncBackoff();
            return;
          }
          if (r2 && r2.ok) {
            clearSyncBackoff();
            data = await r2.json();
          }
        } catch {
          applySyncBackoff();
          return;
        }
      }

      if (!data || !Array.isArray(data.cookies) || data.cookies.length === 0) return;
      if (!forceCheck && Date.now() < (globalThis.__pakflowSwitchLockUntil || 0)) return;

      const resolvedSlotNum = Number(data.slotNumber || validSlotNum || 1);
      globalThis.__pakflowCookiesBySlot = globalThis.__pakflowCookiesBySlot || {};
      globalThis.__pakflowCookiesBySlot[resolvedSlotNum] = { cookies: data.cookies, ts: Date.now() };
      if (data.versionTs) {
        lastKnownSlotsVersion = String(data.versionTs);
        lastKnownVersionSlot = resolvedSlotNum;
        bgStorageSet({ pf_last_slots_ver: lastKnownSlotsVersion, pf_last_slots_ver_slot: resolvedSlotNum });
      }

      const fp = computeCookieFingerprint(data.cookies);
      const prevFp = lastLiveCookieFingerprint;
      if (fp !== lastLiveCookieFingerprint || !jarOk) {
        await neutralizeConflictingFlowExtensions();
        if (globalThis.PakFlowSession && typeof globalThis.PakFlowSession.apply === "function") {
          await globalThis.PakFlowSession.apply(data.cookies);
          lastLiveCookieFingerprint = fp;
          bgStorageSet({ pf_last_cookie_fp: fp, pf_outdated_ext_notice: null });
          // Auto-relogin any open logged-out Flow-domain tab (/about, Google sign-in for Flow, etc.)
          if (fp !== prevFp || !jarOk || forceCheck) {
            reloginLoggedOutFlowTabsIfNeeded(true);
          }
          if (!lastKnownSlotsVersion) {
            try {
              const vr2 = await fetchFn(`${base}/api/slots/version?slot=${resolvedSlotNum}`, { method: "GET", cache: "no-store" });
              if (vr2 && vr2.ok) {
                const vd2 = await vr2.json();
                if (vd2?.version) {
                  lastKnownSlotsVersion = String(vd2.version);
                  lastKnownVersionSlot = resolvedSlotNum;
                  bgStorageSet({ pf_last_slots_ver: lastKnownSlotsVersion, pf_last_slots_ver_slot: resolvedSlotNum });
                }
              }
            } catch {}
          }
          const resolvedSlotId = `slot_${resolvedSlotNum}`;
          await bgStorageSet({
            activeSlot: resolvedSlotId,
            lastInjectedSlot: resolvedSlotId,
            lastSyncSlot: resolvedSlotNum,
            lastSyncTimestamp: Date.now(),
            lastSyncCount: data.cookies.length,
            lastInjectedCount: data.cookies.length,
            autoSyncStatus: "ACTIVE",
            lastSyncError: ""
          });
        }
      }
    } catch {
      // silent retry on next tick
    } finally {
      liveSyncInFlight = false;
      liveSyncPromise = null;
    }
    })();
    return liveSyncPromise;
  }

  // ── Zero-Disk-Cookie Hygiene Guard (0 server requests) ──
  // Ensures ZERO persistent cookies ever sit on flow.google.com / labs.google in Chrome's cookie jar,
  // and immediately strips DNR session rules whenever the extension is logged out, detached, or locked.
  let ttlKeepInFlight = false;
  async function refreshInjectedCookieTtl() {
    if (ttlKeepInFlight) return;
    ttlKeepInFlight = true;
    try {
      const st = await bgStorageGet([
        "isLoggedIn", "flowSessionDetached", "userToken", "pakflow_token", "flow_token", "authToken", "token",
        "pf_flow_lock_state"
      ]);
      const loggedIn = st.isLoggedIn === true || st.isLoggedIn === "true";
      const tok = st.userToken || st.pakflow_token || st.flow_token || st.authToken || st.token || "";
      const locked = Boolean(st.pf_flow_lock_state && st.pf_flow_lock_state.locked);
      if (!loggedIn || !tok || st.flowSessionDetached === true || st.flowSessionDetached === "true" || locked) {
        await wipeFlowCookies(false);
        return;
      }
      if (!chrome.cookies || typeof chrome.cookies.getAll !== "function") return;
      const domains = ["flow.google.com", "labs.google", "aisandbox-pa.googleapis.com", "ogs.google.com"];
      for (const dom of domains) {
        await new Promise((resolve) => {
          chrome.cookies.getAll({ domain: dom }, (list) => {
            void chrome.runtime?.lastError;
            if (!Array.isArray(list) || !list.length) return resolve();
            let left = list.length;
            const done = () => { if (--left <= 0) resolve(); };
            for (const c of list) {
              try {
                if (!c || !c.name) { done(); continue; }
                const host = String(c.domain || dom).replace(/^\.+/, "");
                const url = (c.secure ? "https://" : "http://") + host + (c.path || "/");
                chrome.cookies.remove({ url, name: c.name, storeId: c.storeId }, () => { void chrome.runtime?.lastError; done(); });
              } catch { done(); }
            }
          });
        });
      }
    } catch {} finally {
      ttlKeepInFlight = false;
    }
  }

  // ── Cloud Project Sync (keeps My Saved Projects across extension updates/reinstalls) ──
  let lastCloudProjectPullToken = "";
  async function pullCloudProjectsOnce() {
    try {
      const st = await bgStorageGet([
        "isLoggedIn", "userToken", "pakflow_token", "flow_token", "authToken", "token",
        "pakflowWorkingOrigin", "apiBase", "connectedFromSite",
        "userEmail", "user", "pakflow_user", "pf_projects_owner_email"
      ]);
      const tok = st.userToken || st.pakflow_token || st.flow_token || st.authToken || st.token || "";
      if (!tok || String(tok).startsWith("usertoken_local_") || tok === lastCloudProjectPullToken) return;
      lastCloudProjectPullToken = tok;

      // Strictly isolate local project cache per PakFlow user email so switching accounts or fresh login NEVER leaks another user's projects
      const curEmail = String(st.userEmail || st.user?.email || st.pakflow_user?.email || "").trim().toLowerCase();
      const prevOwner = String(st.pf_projects_owner_email || "").trim().toLowerCase();
      const switchedAccount = Boolean(curEmail && curEmail !== prevOwner);
      if (switchedAccount) {
        suppressingProjectCloudEcho = true;
        try {
          await bgStorageSet({
            myProjectsUnified: [],
            myProjectsByIp: {},
            myProjectServerOwnersByIp: {},
            myProjectsOwnedIds: [],
            pf_projects_owner_email: curEmail
          });
        } finally {
          setTimeout(() => { suppressingProjectCloudEcho = false; }, 1200);
        }
      }

      const base = String(st.pakflowWorkingOrigin || st.apiBase || st.connectedFromSite || "https://flowbypak.com").replace(/\/+$/, "");
      const extVer = getExtVersion();
      const fetchFn = rawBgFetch || globalThis.fetch;
      const res = await fetchFn(`${base}/api/user/projects?ext_v=${encodeURIComponent(extVer)}`, {
        method: "GET",
        headers: { Accept: "application/json", Authorization: `Bearer ${tok}`, "X-PakFlow-Extension-Version": extVer },
        cache: "no-store"
      });
      if (!res || !res.ok) return;
      const data = await res.json().catch(() => null);
      if (data && Array.isArray(data.projects) && globalThis.PakFlowProjects && typeof globalThis.PakFlowProjects.mergeCloud === "function") {
        suppressingProjectCloudEcho = true;
        try {
          if (data.projects.length > 0 || switchedAccount) {
            await globalThis.PakFlowProjects.mergeCloud(async () => "default", data.projects, curEmail);
          }
        } finally {
          setTimeout(() => { suppressingProjectCloudEcho = false; }, 1200);
        }
      }
    } catch {}
  }

  globalThis.__pakflowPushProjectsToCloud = async function (projects, replaceAll) {
    try {
      if (!Array.isArray(projects)) return;
      const st = await bgStorageGet([
        "userToken", "pakflow_token", "flow_token", "authToken", "token",
        "pakflowWorkingOrigin", "apiBase", "connectedFromSite"
      ]);
      const tok = st.userToken || st.pakflow_token || st.flow_token || st.authToken || st.token || "";
      if (!tok || String(tok).startsWith("usertoken_local_")) return;
      const base = String(st.pakflowWorkingOrigin || st.apiBase || st.connectedFromSite || "https://flowbypak.com").replace(/\/+$/, "");
      const extVer = getExtVersion();
      const fetchFn = rawBgFetch || globalThis.fetch;
      const slim = projects.slice(0, 100).map((p) => {
        const rawId = String(p.rawId || p.id || "").trim();
        const thumbStr = typeof p.thumbnail === "string" ? p.thumbnail.trim() : "";
        const validThumb = (
          /^https:\/\//i.test(thumbStr) ||
          (/^data:image\/(?:jpeg|jpg|webp|png);base64,/i.test(thumbStr) && thumbStr.length <= 65000)
        ) ? thumbStr : "";
        return {
          id: p.id,
          rawId: rawId || p.id,
          title: p.title || "Flow Project",
          customTitle: Boolean(p.customTitle),
          slot: p.slot || "slot_1",
          accountHash: p.accountHash ? String(p.accountHash) : "",
          url: p.url || ("https://flow.google.com/project/" + (rawId || p.id)),
          thumbnail: validThumb,
          prompt: p.prompt || "",
          createdAt: p.createdAt || Date.now(),
          updatedAt: p.updatedAt || Date.now()
        };
      });
      await fetchFn(`${base}/api/user/projects?ext_v=${encodeURIComponent(extVer)}`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Authorization: `Bearer ${tok}`,
          "X-PakFlow-Extension-Version": extVer
        },
        body: JSON.stringify({ projects: slim, replace: Boolean(replaceAll) }),
        cache: "no-store"
      });
    } catch {}
  };

  try {
    // 45s + random 0..15s jitter prevents thundering-herd spikes across 20k+ users
    const jitterMs = Math.floor(Math.random() * 15000);
    setInterval(() => { runLiveCookieTick(false); }, 45000 + jitterMs);
    setInterval(() => { evaluateFlowAccessPolicy(); }, 10000);
    setInterval(() => { refreshInjectedCookieTtl(); }, 30000);
    setTimeout(() => { evaluateFlowAccessPolicy(); syncSessionShuffleNow(false); runLiveCookieTick(false); pullCloudProjectsOnce(); }, 2000 + Math.floor(Math.random() * 1500));
  } catch {}

  let projectsCloudSyncTimer = null;
  let lastProjectsCloudSig = "";
  let suppressingProjectCloudEcho = false;

  if (chrome.storage && chrome.storage.onChanged) {
    chrome.storage.onChanged.addListener((changes, area) => {
      if (area !== "local") return;
      if (
        changes.isLoggedIn ||
        changes.flowSessionDetached ||
        changes.userToken ||
        changes.pakflow_token ||
        changes.userPlan ||
        changes.expiresAt ||
        changes.pf_access_policy ||
        changes.pf_latest_ext_version
      ) {
        schedulePolicyEval(200);
      }
      // Pull cloud-backed projects immediately whenever user logs into a fresh/updated extension
      if (
        (changes.isLoggedIn && (changes.isLoggedIn.newValue === true || changes.isLoggedIn.newValue === "true")) ||
        (changes.userToken && changes.userToken.newValue) ||
        (changes.pakflow_token && changes.pakflow_token.newValue) ||
        (changes.flow_token && changes.flow_token.newValue) ||
        (changes.token && changes.token.newValue)
      ) {
        pullCloudProjectsOnce();
      }
      // Backup local projects to user's cloud profile whenever ANY script updates myProjectsUnified or myProjectsByIp
      if (!suppressingProjectCloudEcho && (changes.myProjectsUnified || changes.myProjectsByIp)) {
        if (projectsCloudSyncTimer) clearTimeout(projectsCloudSyncTimer);
        projectsCloudSyncTimer = setTimeout(async () => {
          projectsCloudSyncTimer = null;
          try {
            const st = await bgStorageGet(["isLoggedIn", "flowSessionDetached", "userEmail", "user", "pakflow_user", "pf_projects_owner_email", "myProjectsUnified", "myProjectsByIp"]);
            if (st.isLoggedIn === false || st.isLoggedIn === "false" || st.flowSessionDetached === true) return;
            const curEmail = String(st.userEmail || st.user?.email || st.pakflow_user?.email || "").trim().toLowerCase();
            const ownerEmail = String(st.pf_projects_owner_email || "").trim().toLowerCase();
            if (!curEmail || (ownerEmail && ownerEmail !== curEmail)) return;
            const map = new Map();
            const ingest = (arr) => {
              if (!Array.isArray(arr)) return;
              for (const p of arr) {
                if (!p || !p.id) continue;
                const pOwner = String(p.ownerEmail || "").trim().toLowerCase();
                if (pOwner && pOwner !== curEmail) continue;
                map.set(String(p.id).toLowerCase(), p);
              }
            };
            if (st.myProjectsByIp && typeof st.myProjectsByIp === "object") {
              Object.values(st.myProjectsByIp).forEach(ingest);
            }
            ingest(st.myProjectsUnified);
            const merged = Array.from(map.values());
            if (!merged.length) return;
            const sig = merged.map((p) => `${p.id}|${p.title || ""}|${p.slot || ""}|${p.thumbnail || ""}`).sort().join(";");
            if (sig === lastProjectsCloudSig) return;
            lastProjectsCloudSig = sig;
            if (typeof globalThis.__pakflowPushProjectsToCloud === "function") {
              await globalThis.__pakflowPushProjectsToCloud(merged, false);
            }
          } catch {}
        }, 1800);
      }
    });
  }
  if (chrome.tabs && chrome.tabs.onRemoved) {
    chrome.tabs.onRemoved.addListener(() => { schedulePolicyEval(500); });
  }
  if (chrome.tabs && chrome.tabs.onUpdated) {
    chrome.tabs.onUpdated.addListener((_tabId, changeInfo) => {
      if (changeInfo.url || changeInfo.status === "complete") {
        schedulePolicyEval(500);
      }
    });
  }

  function injectBridgeIntoOpenPortalTabs() {
    try {
      if (!chrome.tabs || !chrome.scripting || typeof chrome.scripting.executeScript !== "function") return;
      chrome.tabs.query({}, (tabs) => {
        void chrome.runtime?.lastError;
        if (!Array.isArray(tabs)) return;
        tabs.forEach((t) => {
          const url = String(t && t.url || "");
          if (t && t.id && /^https?:\/\/(localhost|127\.0\.0\.1|([a-z0-9-]+\.)?(flowbypak|flowbysparx|pakaitools)\.(site|com))/i.test(url)) {
            chrome.scripting.executeScript({ target: { tabId: t.id }, files: ["flowbypak-bridge.js"] }, () => {
              void chrome.runtime?.lastError;
            });
          }
        });
      });
    } catch {}
  }

  try {
    if (chrome.alarms) {
      chrome.alarms.create("pakflow_live_cookie_sync", { periodInMinutes: 1 });
      chrome.alarms.onAlarm.addListener((alarm) => {
        if (alarm && alarm.name === "pakflow_live_cookie_sync") {
          refreshInjectedCookieTtl();
          runLiveCookieTick();
        }
      });
    }
    if (chrome.runtime && chrome.runtime.onStartup) {
      chrome.runtime.onStartup.addListener(() => { runLiveCookieTick(true); });
    }
    if (chrome.runtime && chrome.runtime.onInstalled) {
      chrome.runtime.onInstalled.addListener(() => {
        injectBridgeIntoOpenPortalTabs();
        runLiveCookieTick(true);
      });
    }
    if (chrome.tabs && chrome.tabs.onUpdated) {
      chrome.tabs.onUpdated.addListener((_tabId, changeInfo, tab) => {
        if (changeInfo.status === "loading" && /flow\.google\.com|labs\.google/i.test(String(tab?.url || ""))) {
          runLiveCookieTick(false);
        }
      });
    }
  } catch {}
})();

