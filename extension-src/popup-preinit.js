/* Synchronous pre-paint session & slots bootstrap so the popup opens instantaneously without flashing black or jumping height */
(function () {
  "use strict";

  var SNAPSHOT_KEY = "pf_popup_snapshot_v1";

  function parseSlotNumber(val) {
    var n = Number(String(val || "").replace(/\D+/g, ""));
    return n > 0 ? n : 0;
  }

  function buildSnapshotFromStorage(st) {
    if (!st || typeof st !== "object") return null;
    var rawToken =
      st.userToken ||
      st.pakflow_token ||
      st.flow_token ||
      st.token ||
      st.authToken ||
      "";
    var hasJwt = typeof rawToken === "string" && rawToken.length > 10 && rawToken.indexOf("usertoken_local_") !== 0;
    var ok = hasJwt && st.isLoggedIn !== false && st.isLoggedIn !== "false";
    if (!ok) {
      return { loggedIn: false, ts: Date.now() };
    }
    var planRaw = String(st.userPlan || (st.user && st.user.plan) || "plus").trim();
    var pLow = planRaw.toLowerCase();
    var isBasic = /^(basic|free|trial|vibe|starter)$/.test(pLow);
    var expMs = st.expiresAt ? Date.parse(String(st.expiresAt)) : NaN;
    var remSec = isFinite(expMs) ? Math.max(0, Math.floor((expMs - Date.now()) / 1000)) : null;
    var daysDisplay = null;
    if (pLow === "demo" && remSec !== null) {
      if (remSec <= 0) daysDisplay = "0m";
      else if (remSec < 3600) daysDisplay = Math.max(1, Math.ceil(remSec / 60)) + "m";
      else daysDisplay = Math.ceil(remSec / 3600) + "h";
    } else {
      var dLeft =
        st.daysLeft != null
          ? Number(st.daysLeft)
          : st.pakflow_days_cache != null
            ? Number(st.pakflow_days_cache)
            : isFinite(expMs)
              ? Math.max(0, Math.ceil((expMs - Date.now()) / 86400000))
              : null;
      daysDisplay = dLeft != null && !isNaN(dLeft) ? String(Math.max(0, Math.round(dLeft))) : "30";
    }
    var slotNum =
      parseSlotNumber(st.activeSlot || st.selectedSlot || st.currentSlot || st.currentServerNumber) || 1;
    var slots = Array.isArray(st.pakflow_slots_cache)
      ? st.pakflow_slots_cache
          .map(function (s) { return Number(s && (s.slotNumber || parseSlotNumber(s.id))); })
          .filter(function (n) { return n >= 1 && n <= 10; })
      : [];
    return {
      loggedIn: true,
      email: st.userEmail || (st.user && st.user.email) || "",
      plan: planRaw,
      credits: isBasic ? "0" : st.credits != null ? String(st.credits) : (pLow === "demo" ? "2000" : (pLow === "pro" || pLow === "plus" ? "25000" : "45000")),
      daysLeft: daysDisplay,
      activeSlot: slotNum,
      slotNumbers: slots,
      apiBase: st.apiBase || st.connectedFromSite || st.pakflowWorkingOrigin || "",
      ts: Date.now(),
    };
  }

  function saveSnapshot(snap) {
    try {
      if (!snap) return;
      localStorage.setItem(SNAPSHOT_KEY, JSON.stringify(snap));
    } catch (_e) {}
  }

  function applyPrePaintSnapshot(snap) {
    if (!snap || typeof snap !== "object") return false;
    var loginView = document.getElementById("loginView");
    var mainView = document.getElementById("mainView");
    var maintView = document.getElementById("maintenanceView");

    if (!snap.loggedIn) {
      if (mainView) mainView.classList.add("hidden");
      if (maintView) maintView.classList.add("hidden");
      if (loginView) loginView.classList.remove("hidden");
      document.documentElement.classList.remove("is-authenticated");
      return false;
    }

    if (loginView) loginView.classList.add("hidden");
    if (maintView) maintView.classList.add("hidden");
    if (mainView) mainView.classList.remove("hidden");
    document.documentElement.classList.add("is-authenticated");

    var email = snap.email || "";
    var emailEl = document.getElementById("userEmailBadge");
    if (emailEl && email) emailEl.textContent = email;

    var avatarEl = document.getElementById("customAvatar");
    if (avatarEl && email) {
      var parts = String(email).trim().split(/\s+/).filter(Boolean);
      avatarEl.textContent =
        parts.length >= 2
          ? (parts[0][0] + parts[1][0]).toUpperCase()
          : String(email).trim().slice(0, 2).toUpperCase() || "FL";
    }

    var planRaw = String(snap.plan || "plus").trim();
    var planEl = document.getElementById("userPlanBadge");
    if (planEl && planRaw) {
      planEl.textContent = planRaw.toUpperCase() + " Plan";
    }

    var creditsEl = document.getElementById("customCreditsVal");
    if (creditsEl && snap.credits != null) {
      var snapCredNum = Number(snap.credits);
      creditsEl.textContent = isFinite(snapCredNum) && snapCredNum >= 0
        ? Math.round(snapCredNum).toLocaleString()
        : String(snap.credits);
    }

    var daysEl = document.getElementById("customDaysLeftVal");
    if (daysEl && snap.daysLeft != null) {
      daysEl.textContent = String(snap.daysLeft);
    }

    var slotNum = Number(snap.activeSlot) || 1;
    var cycleTitle = document.getElementById("injectCycleTitle");
    if (cycleTitle) cycleTitle.textContent = "Session " + slotNum + " Selected";
    var cleanStatus = document.getElementById("pfCleanSessionStatus");
    if (cleanStatus) {
      cleanStatus.textContent = "Session " + slotNum + " Active — Flow Ready";
    }

    // Pre-populate session slot buttons synchronously before first paint to stop window height jumping
    var grid = document.getElementById("sessionButtonsGrid");
    if (grid && Array.isArray(snap.slotNumbers) && snap.slotNumbers.length > 0 && grid.children.length === 0) {
      var frag = document.createDocumentFragment();
      snap.slotNumbers.forEach(function (num) {
        var btn = document.createElement("button");
        btn.type = "button";
        btn.className = "session-slot-btn" + (num === slotNum ? " is-active" : "");
        btn.textContent = "Session " + num;
        btn.dataset.slotNumber = String(num);
        frag.appendChild(btn);
      });
      grid.appendChild(frag);
    }

    return true;
  }

  function updatePortalLinks(base) {
    if (!base || !/^https?:\/\//i.test(base)) return;
    base = base.replace(/\/+$/, "");
    var portalLink = document.querySelector('#loginView a.btn-dark[href*="flowbypak"]');
    if (portalLink) portalLink.href = base + "/";
    var regLink = document.querySelector('#loginView .footer-note a[href*="register"]');
    if (regLink) regLink.href = base + "/register";
  }

  function syncManifestVersionBadges() {
    try {
      var m = chrome && chrome.runtime && typeof chrome.runtime.getManifest === "function" ? chrome.runtime.getManifest() : null;
      var ver = m && (m.version || m.version_name) ? String(m.version || m.version_name).replace(/^v/i, "") : "";
      if (!ver) return;
      var els = document.querySelectorAll(".top-version");
      for (var i = 0; i < els.length; i++) {
        els[i].textContent = "v" + ver;
      }
    } catch (_e) {}
  }

  syncManifestVersionBadges();
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", syncManifestVersionBadges);
  }

  // 1. Synchronous pre-paint restore (0ms) so there is no black flash or height jump
  try {
    var rawSnap = localStorage.getItem(SNAPSHOT_KEY);
    if (rawSnap) {
      var parsedSnap = JSON.parse(rawSnap);
      if (parsedSnap) {
        updatePortalLinks(parsedSnap.apiBase);
        applyPrePaintSnapshot(parsedSnap);
      }
    }
  } catch (_e) {}

  // 2. Async reconcile against chrome.storage.local (and recover token if synced by website bridge)
  if (typeof chrome !== "undefined" && chrome.storage && chrome.storage.local) {
    var keys = [
      "isLoggedIn",
      "userToken",
      "pakflow_token",
      "flow_token",
      "token",
      "authToken",
      "userEmail",
      "userPlan",
      "daysLeft",
      "expiresAt",
      "credits",
      "pakflow_days_cache",
      "user",
      "activeSlot",
      "selectedSlot",
      "currentSlot",
      "currentServerNumber",
      "pakflow_slots_cache",
      "apiBase",
      "connectedFromSite",
      "pakflowWorkingOrigin"
    ];
    try {
      chrome.storage.local.get(keys, function (st) {
        void chrome.runtime?.lastError;
        if (!st) return;
        var snap = buildSnapshotFromStorage(st);
        saveSnapshot(snap);
        if (snap && snap.apiBase) updatePortalLinks(snap.apiBase);
        applyPrePaintSnapshot(snap);
      });
      if (chrome.storage.onChanged) {
        chrome.storage.onChanged.addListener(function (_changes, area) {
          if (area !== "local") return;
          chrome.storage.local.get(keys, function (st) {
            void chrome.runtime?.lastError;
            if (st) saveSnapshot(buildSnapshotFromStorage(st));
          });
        });
      }
    } catch (_e) {}
  }
})();
