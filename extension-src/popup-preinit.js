/* Synchronous pre-paint session & slots bootstrap so the popup opens instantaneously without flashing black or jumping height */
(function () {
  "use strict";

  var SNAPSHOT_KEY = "pf_popup_snapshot_v2";

  function parseSlotNumber(val) {
    var n = Number(String(val || "").replace(/\D+/g, ""));
    return n > 0 ? n : 0;
  }

  function defaultCreditsTotalForPlan(pLow) {
    if (pLow === "demo") return 2000;
    if (pLow === "pro" || pLow === "plus") return 25000;
    if (/^(basic|free|trial|vibe|starter)$/.test(pLow)) return 0;
    return 45000;
  }

  function formatPlanDevBadge(planRaw) {
    var pLow = String(planRaw || "max").trim().toLowerCase();
    return pLow === "demo" ? "DEMO PLAN" : (pLow === "pro" || pLow === "plus") ? "PRO PLAN" : (pLow === "ultra" || pLow === "max") ? "MAX PLAN" : (pLow.toUpperCase() + " PLAN");
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
    var baseOrigin = st.pakflowWorkingOrigin || st.apiBase || st.connectedFromSite || "https://flowbypak.com";
    if (!ok) {
      return { loggedIn: false, apiBase: baseOrigin, ts: Date.now() };
    }
    var planRaw = String(st.userPlan || (st.user && st.user.plan) || "max").trim();
    var pLow = planRaw.toLowerCase();
    var isBasic = /^(basic|free|trial|vibe|starter)$/.test(pLow);
    var expMs = st.expiresAt ? Date.parse(String(st.expiresAt)) : (st.user && st.user.planExpiresAt ? Date.parse(String(st.user.planExpiresAt)) : NaN);
    var remSec = isFinite(expMs) ? Math.max(0, Math.floor((expMs - Date.now()) / 1000)) : null;
    var daysDisplay = "30 Days";
    var isDemo = pLow === "demo";
    if (isDemo && remSec !== null) {
      if (remSec <= 0) daysDisplay = "0m";
      else if (remSec < 3600) daysDisplay = Math.max(1, Math.ceil(remSec / 60)) + "m";
      else {
        var hrs = Math.floor(remSec / 3600);
        var mins = Math.floor((remSec % 3600) / 60);
        daysDisplay = mins > 0 ? (hrs + "h " + mins + "m") : (hrs + "h");
      }
    } else {
      var dLeft =
        st.daysLeft != null
          ? Number(st.daysLeft)
          : st.pakflow_days_cache != null && !isNaN(Number(st.pakflow_days_cache))
            ? Number(st.pakflow_days_cache)
            : isFinite(expMs)
              ? Math.max(0, Math.ceil((expMs - Date.now()) / 86400000))
              : 30;
      var roundedDays = Math.max(0, Math.round(Number(dLeft) || 0));
      daysDisplay = roundedDays + (roundedDays === 1 ? " Day" : " Days");
    }

    var defTot = defaultCreditsTotalForPlan(pLow);
    var credNum = isBasic
      ? 0
      : st.credits != null && isFinite(Number(st.credits))
        ? Math.max(0, Math.round(Number(st.credits)))
        : (st.user && st.user.credits != null && isFinite(Number(st.user.credits)) ? Math.max(0, Math.round(Number(st.user.credits))) : defTot);
    var credTot = isBasic
      ? 0
      : st.creditsTotal != null && Number(st.creditsTotal) > 0
        ? Math.round(Number(st.creditsTotal))
        : (st.user && Number(st.user.creditsTotal) > 0 ? Math.round(Number(st.user.creditsTotal)) : Math.max(credNum, defTot));

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
      maxDevices: Math.max(1, Number((st.user && st.user.maxDevices) || st.maxDevices || 1)),
      credits: credNum,
      creditsTotal: credTot,
      daysLeft: daysDisplay,
      isDemo: isDemo,
      activeSlot: slotNum,
      slotNumbers: slots,
      apiBase: baseOrigin,
      ts: Date.now(),
    };
  }

  function saveSnapshot(snap) {
    try {
      if (!snap) return;
      localStorage.setItem(SNAPSHOT_KEY, JSON.stringify(snap));
    } catch (_e) {}
  }

  function updatePortalLinks(base) {
    if (!base || !/^https?:\/\//i.test(base)) return;
    base = base.replace(/\/+$/, "");
    var loginBtn = document.getElementById("portalConnectBtn");
    if (loginBtn) loginBtn.href = base + "/login";
    var regBtn = document.getElementById("portalRegisterBtn");
    if (regBtn) regBtn.href = base + "/signup";
    var panelBtn = document.getElementById("openUserPanelBtn");
    if (panelBtn) panelBtn.href = base + "/dashboard";
  }

  function applyPrePaintSnapshot(snap) {
    if (!snap || typeof snap !== "object") return false;
    if (snap.apiBase) updatePortalLinks(snap.apiBase);

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
      var cleanLocal = String(email).split("@")[0].replace(/[^a-zA-Z0-9]/g, "");
      avatarEl.textContent = (cleanLocal.slice(0, 2) || "PF").toUpperCase();
    }

    var planEl = document.getElementById("userPlanBadge");
    if (planEl) {
      var pLowSnap = String(snap.plan || "max").trim().toLowerCase();
      planEl.textContent = formatPlanDevBadge(snap.plan);
      planEl.setAttribute("data-plan", pLowSnap === "demo" ? "demo" : (pLowSnap === "pro" || pLowSnap === "plus") ? "pro" : "max");
    }

    var credNum = Math.max(0, Number(snap.credits) || 0);
    var credTot = Math.max(credNum, Number(snap.creditsTotal) || 0);
    var pct = credTot > 0 ? Math.max(0, Math.min(100, Math.round((credNum / credTot) * 100))) : 0;

    var creditsEl = document.getElementById("customCreditsVal");
    if (creditsEl) creditsEl.textContent = credNum.toLocaleString();
    var totEl = document.getElementById("customCreditsTotalVal");
    if (totEl) totEl.textContent = credTot.toLocaleString();
    var barEl = document.getElementById("customCreditsBarFill");
    if (barEl) barEl.style.width = pct + "%";
    var pctEl = document.getElementById("customCreditsPct");
    if (pctEl) pctEl.textContent = pct + "%";

    var daysEl = document.getElementById("customDaysLeftVal");
    if (daysEl && snap.daysLeft != null) {
      daysEl.textContent = String(snap.daysLeft);
    }
    var daysLbl = document.getElementById("customDaysLabel");
    if (daysLbl) {
      daysLbl.textContent = snap.isDemo ? "Demo Left" : "Validity";
    }

    var slotNum = Number(snap.activeSlot) || 1;
    var cycleTitle = document.getElementById("injectCycleTitle");
    if (cycleTitle) cycleTitle.textContent = "Session " + slotNum + " Selected";
    var cleanStatus = document.getElementById("pfCleanSessionStatus");
    if (cleanStatus) {
      cleanStatus.textContent = "Session " + slotNum + " Ready";
    }

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
      var countBadge = document.getElementById("activeSessionsCount");
      if (countBadge) countBadge.textContent = snap.slotNumbers.length + " Active";
    }

    return true;
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

  try {
    var rawSnap = localStorage.getItem(SNAPSHOT_KEY);
    var painted = false;
    if (rawSnap) {
      var parsedSnap = JSON.parse(rawSnap);
      if (parsedSnap) {
        applyPrePaintSnapshot(parsedSnap);
        painted = true;
      }
    }
    if (!painted) {
      var lv0 = document.getElementById("loginView");
      if (lv0) lv0.classList.remove("hidden");
    }
  } catch (_e) {}

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
      "maxDevices",
      "daysLeft",
      "expiresAt",
      "credits",
      "creditsTotal",
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
        applyPrePaintSnapshot(snap);
      });
      if (chrome.storage.onChanged) {
        chrome.storage.onChanged.addListener(function (_changes, area) {
          if (area !== "local") return;
          chrome.storage.local.get(keys, function (st) {
            void chrome.runtime?.lastError;
            if (!st) return;
            var snap = buildSnapshotFromStorage(st);
            saveSnapshot(snap);
            applyPrePaintSnapshot(snap);
          });
        });
      }
    } catch (_e) {}
  }
})();
