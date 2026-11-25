(function () {
  function defaultTotalForPlan(pLow) {
    if (pLow === "demo") return 2000;
    if (pLow === "pro" || pLow === "plus") return 25000;
    if (/^(basic|free|trial|vibe|starter)$/.test(pLow)) return 0;
    return 45000;
  }

  function formatPlanBadge(planRaw) {
    var pLow = String(planRaw || "max").trim().toLowerCase();
    return pLow === "demo" ? "DEMO PLAN" : (pLow === "pro" || pLow === "plus") ? "PRO PLAN" : (pLow === "ultra" || pLow === "max") ? "MAX PLAN" : (pLow.toUpperCase() + " PLAN");
  }

  function applyPlanChipTone(planEl, planRaw) {
    if (!planEl) return;
    var pLow = String(planRaw || "max").trim().toLowerCase();
    planEl.textContent = formatPlanBadge(planRaw);
    planEl.setAttribute("data-plan", pLow === "demo" ? "demo" : (pLow === "pro" || pLow === "plus") ? "pro" : "max");
  }

  function renderCreditsMeter(credVal, credTotalVal) {
    var cNum = Math.max(0, Math.round(Number(credVal) || 0));
    var tNum = Math.max(cNum, Math.round(Number(credTotalVal) || 0));
    var pct = tNum > 0 ? Math.max(0, Math.min(100, Math.round((cNum / tNum) * 100))) : 0;

    var creditsEl = document.getElementById("customCreditsVal");
    var totEl = document.getElementById("customCreditsTotalVal");
    var barEl = document.getElementById("customCreditsBarFill");
    var pctEl = document.getElementById("customCreditsPct");

    if (creditsEl) {
      creditsEl.textContent = cNum.toLocaleString();
      creditsEl.dataset.fromApi = "1";
    }
    if (totEl) totEl.textContent = tNum.toLocaleString();
    if (barEl) barEl.style.width = pct + "%";
    if (pctEl) pctEl.textContent = pct + "%";
  }

  function updatePortalLinks(origin) {
    if (!origin || !/^https?:\/\//i.test(origin)) return;
    var base = String(origin).replace(/\/+$/, "");
    var loginBtn = document.getElementById("portalConnectBtn");
    if (loginBtn) loginBtn.href = base + "/login";
    var regBtn = document.getElementById("portalRegisterBtn");
    if (regBtn) regBtn.href = base + "/signup";
    var panelBtn = document.getElementById("openUserPanelBtn");
    if (panelBtn) panelBtn.href = base + "/dashboard";
  }

  function syncCustomUI() {
    var emailEl = document.getElementById("userEmailBadge");
    var avatarEl = document.getElementById("customAvatar");
    if (emailEl && avatarEl) {
      var raw = String(emailEl.textContent || "PF").split("@")[0].replace(/[^a-zA-Z0-9]/g, "");
      var initials = (raw.slice(0, 2) || "PF").toUpperCase();
      if (avatarEl.textContent !== initials) {
        avatarEl.textContent = initials;
      }
    }
  }

  function initObservers() {
    syncCustomUI();

    if (typeof chrome === "undefined" || !chrome.storage || !chrome.storage.local) return;

    chrome.storage.local.get(
      [
        "user",
        "pakflow_user",
        "userEmail",
        "userPlan",
        "maxDevices",
        "credits",
        "creditsTotal",
        "daysLeft",
        "expiresAt",
        "pakflow_days_cache",
        "activeSlot",
        "selectedSlot",
        "currentSlot",
        "currentServerNumber",
        "flow_token",
        "pakflow_token",
        "token",
        "authToken",
        "userToken",
        "apiBase",
        "connectedFromSite",
        "pakflowWorkingOrigin",
        "apiBaseUrl",
        "apiUrl",
        "portalUrl",
        "lastOrigin"
      ],
      function (data) {
        if (!data) return;
        var origin =
          data.pakflowWorkingOrigin ||
          data.apiBase ||
          data.connectedFromSite ||
          data.apiBaseUrl ||
          data.apiUrl ||
          data.portalUrl ||
          data.lastOrigin ||
          "https://flowbypak.com";
        updatePortalLinks(origin);

        var user =
          data.user ||
          data.pakflow_user ||
          (data.userEmail
            ? { email: data.userEmail, plan: data.userPlan, daysLeft: data.daysLeft, planExpiresAt: data.expiresAt, maxDevices: data.maxDevices }
            : null);

        if (user) {
          var emailEl = document.getElementById("userEmailBadge");
          var planEl = document.getElementById("userPlanBadge");
          var preferredEmail = user.email || user.name || user.username;
          if (emailEl && preferredEmail) emailEl.textContent = preferredEmail;
          var pRaw = String(user.plan || data.userPlan || "max").trim();
          var pLow = pRaw.toLowerCase();
          var maxDevs = Math.max(1, Number(user.maxDevices || data.maxDevices || 1));
          applyPlanChipTone(planEl, pRaw);

          var isBasicPlan = /^(basic|free|trial|vibe|starter)$/.test(pLow);
          var defTot = defaultTotalForPlan(pLow);
          var storedCred = data.credits != null ? Number(data.credits) : (user.credits != null ? Number(user.credits) : defTot);
          if (!isFinite(storedCred) || storedCred < 0) storedCred = defTot;
          var storedTot = data.creditsTotal != null && Number(data.creditsTotal) > 0
            ? Number(data.creditsTotal)
            : (user.creditsTotal != null && Number(user.creditsTotal) > 0 ? Number(user.creditsTotal) : Math.max(storedCred, defTot));
          renderCreditsMeter(isBasicPlan ? 0 : storedCred, isBasicPlan ? 0 : storedTot);

          var daysValEl = document.getElementById("customDaysLeftVal");
          var daysLblEl = document.getElementById("customDaysLabel");
          if (daysValEl) {
            var expMsInit = user.planExpiresAt
              ? Date.parse(String(user.planExpiresAt))
              : data.expiresAt
                ? Date.parse(String(data.expiresAt))
                : NaN;
            if (pLow === "demo" && isFinite(expMsInit)) {
              var remSecInit = Math.max(0, Math.floor((expMsInit - Date.now()) / 1000));
              if (remSecInit <= 0) daysValEl.textContent = "0m";
              else if (remSecInit < 3600) daysValEl.textContent = Math.max(1, Math.ceil(remSecInit / 60)) + "m";
              else {
                var hrs0 = Math.floor(remSecInit / 3600);
                var mins0 = Math.floor((remSecInit % 3600) / 60);
                daysValEl.textContent = mins0 > 0 ? (hrs0 + "h " + mins0 + "m") : (hrs0 + "h");
              }
              if (daysLblEl) daysLblEl.textContent = "Demo Left";
            } else {
              var dLeft = user.daysLeft != null
                ? Number(user.daysLeft)
                : isFinite(expMsInit)
                  ? Math.max(0, Math.ceil((expMsInit - Date.now()) / 86400000))
                  : (data.pakflow_days_cache != null && !isNaN(Number(data.pakflow_days_cache)) ? Number(data.pakflow_days_cache) : 30);
              var dClean0 = Math.max(0, Math.round(Number(dLeft) || 0));
              daysValEl.textContent = dClean0 + (dClean0 === 1 ? " Day" : " Days");
              if (daysLblEl) daysLblEl.textContent = "Validity";
            }
          }
        }

        var slot = data.activeSlot || data.selectedSlot || data.currentSlot || data.currentServerNumber;
        if (slot) {
          var sNum = String(slot).replace(/\D+/g, "") || "1";
          var cookieValEl = document.getElementById("customSessionCookieVal");
          if (cookieValEl) cookieValEl.textContent = sNum;
          var cleanStatusInit = document.getElementById("pfCleanSessionStatus");
          if (cleanStatusInit) {
            cleanStatusInit.textContent = "Session " + sNum + " Ready";
          }
        }

        var extVer = "1.7";
        try {
          var mf = chrome && chrome.runtime && typeof chrome.runtime.getManifest === "function" ? chrome.runtime.getManifest() : null;
          if (mf && mf.version) extVer = String(mf.version).replace(/^v/i, "");
        } catch (_e) {}
        var verEls = document.querySelectorAll(".top-version");
        for (var vi = 0; vi < verEls.length; vi++) {
          verEls[vi].textContent = "v" + extVer;
        }

        var token = data.flow_token || data.pakflow_token || data.token || data.authToken || data.userToken || "";
        if (token && /^https?:\/\//i.test(origin)) {
          var authAc = typeof AbortController !== "undefined" ? new AbortController() : null;
          var authTid = authAc ? setTimeout(function () { try { authAc.abort(); } catch (_e) {} }, 3000) : null;
          fetch(origin.replace(/\/+$/, "") + "/api/auth/me", {
            headers: {
              Authorization: "Bearer " + token,
              "X-PakFlow-Extension-Version": extVer,
            },
            cache: "no-store",
            signal: authAc ? authAc.signal : undefined,
          })
            .then(function (r) { return r.json().then(function (body) { return { status: r.status, body: body }; }); })
            .then(function (resObj) {
              if (authTid) clearTimeout(authTid);
              var me = resObj && resObj.body;
              if (me && me.outdatedExtension) {
                showOutdatedExtensionBanner(
                  me.message || ("Download latest PakFlow Extension" + (me.latestVersion ? (" (v" + me.latestVersion + ")") : "") + " from your dashboard."),
                  origin.replace(/\/+$/, "")
                );
                return;
              }
              var u = me && (me.user || me);
              if (u && (u.email || u.id)) {
                var pLow2 = String(u.plan || "basic").toLowerCase();
                var isBasic2 = /^(basic|free|trial|vibe|starter)$/.test(pLow2);
                var defTot2 = defaultTotalForPlan(pLow2);
                var liveCredNum = u.credits != null ? Number(u.credits) : defTot2;
                if (!isFinite(liveCredNum) || liveCredNum < 0) liveCredNum = 0;
                var liveTotNum = u.creditsTotal != null && Number(u.creditsTotal) > 0 ? Number(u.creditsTotal) : Math.max(liveCredNum, defTot2);
                renderCreditsMeter(isBasic2 ? 0 : liveCredNum, isBasic2 ? 0 : liveTotNum);

                var planEl2 = document.getElementById("userPlanBadge");
                var maxDevs2 = Math.max(1, Number(u.maxDevices || 1));
                applyPlanChipTone(planEl2, u.plan || pLow2);

                var emailEl2 = document.getElementById("userEmailBadge");
                if (emailEl2 && u.email) emailEl2.textContent = u.email;

                var dLeft2 = me && me.daysLeft != null
                  ? Number(me.daysLeft)
                  : u.daysLeft != null
                    ? Number(u.daysLeft)
                    : u.planExpiresAt
                      ? Math.max(0, Math.ceil((Date.parse(u.planExpiresAt) - Date.now()) / 86400000))
                      : null;
                var daysValEl2 = document.getElementById("customDaysLeftVal");
                var daysLblEl2 = document.getElementById("customDaysLabel");
                if (daysValEl2) {
                  if (pLow2 === "demo" && u.planExpiresAt) {
                    var remSec2 = Math.max(0, Math.floor((Date.parse(String(u.planExpiresAt)) - Date.now()) / 1000));
                    if (remSec2 <= 0) daysValEl2.textContent = "0m";
                    else if (remSec2 < 3600) daysValEl2.textContent = Math.max(1, Math.ceil(remSec2 / 60)) + "m";
                    else {
                      var hrs2 = Math.floor(remSec2 / 3600);
                      var mins2 = Math.floor((remSec2 % 3600) / 60);
                      daysValEl2.textContent = mins2 > 0 ? (hrs2 + "h " + mins2 + "m") : (hrs2 + "h");
                    }
                    if (daysLblEl2) daysLblEl2.textContent = "Demo Left";
                  } else if (dLeft2 != null && !isNaN(dLeft2)) {
                    var dRound2 = Math.max(0, Math.round(dLeft2));
                    daysValEl2.textContent = dRound2 + (dRound2 === 1 ? " Day" : " Days");
                    if (daysLblEl2) daysLblEl2.textContent = "Validity";
                    chrome.storage.local.set({ pakflow_days_cache: String(dRound2) });
                  }
                }
                chrome.storage.local.set({
                  credits: isBasic2 ? "0" : String(Math.round(liveCredNum)),
                  creditsTotal: isBasic2 ? "0" : String(Math.round(liveTotNum)),
                  maxDevices: maxDevs2,
                  fakeBalance: isBasic2 ? 0 : Math.round(liveCredNum),
                  pf_last_synced_fb: isBasic2 ? 0 : Math.round(liveCredNum),
                  pf_outdated_ext_notice: null
                });
                syncCustomUI();
              }
            })
            .catch(function () { if (authTid) clearTimeout(authTid); });
        }

        function showOutdatedExtensionBanner(messageText, portalOrigin) {
          var existing = document.getElementById("pfOutdatedBanner");
          if (existing) existing.remove();
          var base = String(portalOrigin || "https://flowbypak.com").replace(/\/+$/, "");
          var box = document.createElement("div");
          box.id = "pfOutdatedBanner";
          box.style.cssText = "margin:0 0 10px;padding:13px;border-radius:14px;background:rgba(245,158,11,0.12);border:1px solid rgba(245,158,11,0.42);color:#fef3c7;text-align:center;";
          box.innerHTML =
            '<div style="font-size:12.5px;font-weight:800;color:#fcd34d;margin-bottom:4px;">Extension Update Required</div>' +
            '<div style="font-size:11.5px;line-height:1.45;color:#fde68a;margin-bottom:10px;">' + String(messageText || "Please download the latest extension from your dashboard.").replace(/</g, "&lt;") + '</div>' +
            '<a href="' + base + '/downloads/latest" target="_blank" rel="noopener noreferrer" style="display:block;width:100%;padding:9px 12px;border-radius:10px;background:linear-gradient(135deg,#8b5cf6,#6366f1);color:#fff;font-size:12px;font-weight:700;text-decoration:none;margin-bottom:6px;">Download Latest Extension &#8599;</a>' +
            '<a href="' + base + '/dashboard" target="_blank" rel="noopener noreferrer" style="display:block;width:100%;padding:7px 12px;border-radius:10px;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.14);color:#e2e8f0;font-size:11.5px;font-weight:600;text-decoration:none;">Open Dashboard &#8599;</a>';
          var host = document.getElementById("mainView") || document.querySelector("#loginView .connect-card") || document.querySelector(".app-container");
          if (host) host.prepend(box);
        }

        try {
          chrome.storage.local.get(["pf_outdated_ext_notice"], function (od) {
            if (od && od.pf_outdated_ext_notice && od.pf_outdated_ext_notice.message) {
              showOutdatedExtensionBanner(od.pf_outdated_ext_notice.message, origin);
            }
          });
        } catch (_e) {}

        if (chrome.storage && chrome.storage.onChanged) {
          chrome.storage.onChanged.addListener(function (changes, area) {
            if (area !== "local") return;
            if (changes.pf_outdated_ext_notice) {
              var odVal = changes.pf_outdated_ext_notice.newValue;
              if (odVal && odVal.message) {
                showOutdatedExtensionBanner(odVal.message, origin);
              } else {
                var oldBanner = document.getElementById("pfOutdatedBanner");
                if (oldBanner) oldBanner.remove();
              }
            }
            if (changes.credits || changes.creditsTotal) {
              chrome.storage.local.get(["credits", "creditsTotal", "userPlan"], function (st) {
                void chrome.runtime?.lastError;
                if (!st) return;
                var pL = String(st.userPlan || "max").toLowerCase();
                var dT = defaultTotalForPlan(pL);
                var cN = st.credits != null ? Number(st.credits) : dT;
                var tN = st.creditsTotal != null && Number(st.creditsTotal) > 0 ? Number(st.creditsTotal) : Math.max(cN, dT);
                if (isFinite(cN) && cN >= 0) renderCreditsMeter(cN, tN);
              });
            }
          });
        }

        syncCustomUI();
      }
    );
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initObservers);
  } else {
    initObservers();
  }
})();

;(function () {
  var activeSwitchBusy = false;

  function parseSlotNum(val) {
    var n = Number(String(val || "").replace(/\D+/g, ""));
    return n > 0 ? n : 0;
  }

  function extractActiveSlots(res) {
    var rawList = (res && Array.isArray(res.files) && res.files) || (res && Array.isArray(res.slots) && res.slots) || [];
    return rawList
      .filter(function (s) {
        if (!s) return false;
        var num = Number(s.slotNumber || parseSlotNum(s.id));
        if (!num || num < 1 || num > 10) return false;
        var isActive = s.isActive === true || String(s.status || "").toUpperCase() === "ACTIVE";
        var hasCookies = s.hasCookies === true || Number(s.cookieCount || 0) > 0;
        return isActive && hasCookies;
      })
      .sort(function (a, b) {
        return Number(a.slotNumber || 0) - Number(b.slotNumber || 0);
      })
      .slice(0, 10);
  }

  function attachSlotButtonClick(btn, num, grid) {
    if (!btn || btn.dataset.pfBound === "1") return;
    btn.dataset.pfBound = "1";
    btn.addEventListener("click", function () {
      if (activeSwitchBusy) return;
      activeSwitchBusy = true;

      var allBtns = grid.querySelectorAll(".session-slot-btn");
      for (var i = 0; i < allBtns.length; i++) {
        allBtns[i].classList.remove("is-active");
        allBtns[i].disabled = true;
      }
      btn.classList.add("is-busy");
      btn.textContent = "Switching...";

      var statusEl = document.getElementById("pfCleanSessionStatus");
      var cookieValEl = document.getElementById("customSessionCookieVal");
      if (cookieValEl) cookieValEl.textContent = String(num);
      if (statusEl) statusEl.textContent = "Session " + num + "...";

      var settled = false;
      var unlockFailsafe = setTimeout(function () {
        if (settled) return;
        settled = true;
        activeSwitchBusy = false;
        for (var j = 0; j < allBtns.length; j++) {
          allBtns[j].disabled = false;
        }
        btn.classList.remove("is-busy");
        btn.textContent = "Session " + num;
      }, 7000);

      try {
        chrome.storage.local.set({ activeSlot: "slot_" + num, selectedSlot: "slot_" + num, currentSlot: "slot_" + num });
      } catch (_e) {}

      try {
        chrome.runtime.sendMessage(
          { type: "PAKFLOW_REINJECT_CURRENT", slotNumber: num, openFlow: true },
          function (res) {
            void chrome.runtime?.lastError;
            clearTimeout(unlockFailsafe);
            settled = true;
            activeSwitchBusy = false;
            for (var j = 0; j < allBtns.length; j++) {
              allBtns[j].disabled = false;
            }
            btn.classList.remove("is-busy");
            btn.textContent = "Session " + num;
            if (res && res.ok) {
              btn.classList.add("is-active");
              if (statusEl) statusEl.textContent = "Session " + num + " Ready";
            } else if (!res || !res.superseded) {
              if (statusEl) statusEl.textContent = "Session " + num + " Retry";
            }
          }
        );
      } catch (_e) {
        clearTimeout(unlockFailsafe);
        settled = true;
        activeSwitchBusy = false;
        btn.classList.remove("is-busy");
        btn.textContent = "Session " + num;
        for (var k = 0; k < allBtns.length; k++) {
          allBtns[k].disabled = false;
        }
      }
    });
  }

  function renderSessionButtons(slots, currentSlotNum) {
    var grid = document.getElementById("sessionButtonsGrid");
    if (!grid) return;

    var countBadge = document.getElementById("activeSessionsCount");
    if (countBadge) {
      countBadge.textContent = slots.length + " Active";
    }

    var nextNums = slots.map(function (s) {
      return Number(s.slotNumber || parseSlotNum(s.id));
    });
    var existingBtns = grid.querySelectorAll(".session-slot-btn");
    var existingNums = [];
    for (var i = 0; i < existingBtns.length; i++) {
      existingNums.push(Number(existingBtns[i].dataset.slotNumber || parseSlotNum(existingBtns[i].textContent)));
    }

    if (existingBtns.length > 0 && existingNums.join(",") === nextNums.join(",")) {
      for (var j = 0; j < existingBtns.length; j++) {
        var b = existingBtns[j];
        var n = nextNums[j];
        attachSlotButtonClick(b, n, grid);
        if (!activeSwitchBusy) {
          b.classList.toggle("is-active", n === currentSlotNum);
        }
      }
      return;
    }

    grid.innerHTML = "";
    slots.forEach(function (s) {
      var num = Number(s.slotNumber || parseSlotNum(s.id));
      var btn = document.createElement("button");
      btn.type = "button";
      btn.className = "session-slot-btn" + (num === currentSlotNum ? " is-active" : "");
      btn.textContent = "Session " + num;
      btn.dataset.slotNumber = String(num);
      attachSlotButtonClick(btn, num, grid);
      grid.appendChild(btn);
    });
  }

  function slotsFromSelectEl() {
    var sel = document.getElementById("serverFileSelect");
    if (!sel || !sel.options) return [];
    var out = [];
    for (var i = 0; i < sel.options.length; i++) {
      var opt = sel.options[i];
      var n = parseSlotNum(opt.value || opt.textContent);
      if (n >= 1 && n <= 10) {
        out.push({ slotNumber: n, isActive: true, hasCookies: true, cookieCount: 1 });
      }
    }
    return out;
  }

  function loadSessionButtons() {
    if (typeof chrome === "undefined" || !chrome.storage || !chrome.storage.local) return;
    chrome.storage.local.get(
      ["activeSlot", "selectedSlot", "currentSlot", "currentServerNumber", "pakflow_slots_cache"],
      function (d) {
        d = d || {};
        var curNum = parseSlotNum(d.activeSlot || d.selectedSlot || d.currentSlot || d.currentServerNumber) || 1;
        var rendered = false;

        if (Array.isArray(d.pakflow_slots_cache) && d.pakflow_slots_cache.length > 0) {
          renderSessionButtons(d.pakflow_slots_cache, curNum);
          rendered = true;
        } else {
          var fromSel = slotsFromSelectEl();
          if (fromSel.length > 0) {
            renderSessionButtons(fromSel, curNum);
            rendered = true;
          }
        }

        try {
          chrome.runtime.sendMessage({ type: "PAKFLOW_GET_ACTIVE_SLOTS" }, function (res) {
            void chrome.runtime?.lastError;
            if (res && res.ok && Array.isArray(res.files)) {
              var activeSlots = extractActiveSlots(res);
              try { chrome.storage.local.set({ pakflow_slots_cache: activeSlots }); } catch (_e) {}
              chrome.storage.local.get(["activeSlot", "selectedSlot", "currentSlot", "currentServerNumber"], function (latest) {
                var latestNum = parseSlotNum(latest && (latest.activeSlot || latest.selectedSlot || latest.currentSlot || latest.currentServerNumber)) || curNum;
                renderSessionButtons(activeSlots, latestNum);
              });
              rendered = true;
            } else if (!rendered) {
              var fallbackSlots = slotsFromSelectEl();
              if (fallbackSlots.length > 0) {
                renderSessionButtons(fallbackSlots, curNum);
              }
            }
          });
        } catch (_e) {}
      }
    );
  }

  var shuffleCountdownTimer = null;
  var shuffleTargetMs = 0;

  function formatCountdown(sec) {
    sec = Math.max(0, Math.floor(sec));
    var h = Math.floor(sec / 3600);
    var m = Math.floor((sec % 3600) / 60);
    var s = sec % 60;
    var pad = function (n) { return n < 10 ? "0" + n : String(n); };
    return h > 0 ? (pad(h) + ":" + pad(m) + ":" + pad(s)) : (pad(m) + ":" + pad(s));
  }

  function ensureShuffleTimerEl() {
    var el = document.getElementById("pfPopupShuffleCountdown");
    if (el) return el;
    var card = document.querySelector("#sessionInjectorBox .sessions-card") || document.getElementById("sessionInjectorBox");
    if (!card) return null;
    el = document.createElement("div");
    el.id = "pfPopupShuffleCountdown";
    el.className = "shuffle-countdown-bar";
    card.appendChild(el);
    return el;
  }

  function updateShuffleCountdownUI(assigned) {
    var el = ensureShuffleTimerEl();
    if (!el) return;
    if (shuffleCountdownTimer) {
      clearInterval(shuffleCountdownTimer);
      shuffleCountdownTimer = null;
    }
    if (!assigned || !assigned.shuffleEnabled) {
      el.style.display = "none";
      return;
    }
    var rem = typeof assigned.remainingSeconds === "number" ? assigned.remainingSeconds : null;
    if (rem != null) {
      shuffleTargetMs = Date.now() + Math.max(0, rem) * 1000;
    } else if (assigned.nextShuffleTs) {
      shuffleTargetMs = Number(assigned.nextShuffleTs) * 1000;
    } else if (assigned.nextShuffleAt) {
      shuffleTargetMs = Date.parse(assigned.nextShuffleAt) || 0;
    }
    if (!shuffleTargetMs) {
      el.style.display = "none";
      return;
    }
    var tick = function () {
      var leftSec = Math.max(0, Math.ceil((shuffleTargetMs - Date.now()) / 1000));
      el.style.display = "flex";
      el.textContent = "Next Session Shuffle in " + formatCountdown(leftSec);
      if (leftSec <= 0) {
        clearInterval(shuffleCountdownTimer);
        shuffleCountdownTimer = null;
        try {
          chrome.runtime.sendMessage({ type: "PAKFLOW_SYNC_SESSION_SHUFFLE" }, function (syncRes) {
            void chrome.runtime?.lastError;
            if (syncRes && syncRes.assignedServer) updateShuffleCountdownUI(syncRes.assignedServer);
          });
        } catch (_e) {}
      }
    };
    tick();
    shuffleCountdownTimer = setInterval(tick, 1000);
  }

  function highlightActiveSessionInPopup(slotNum) {
    if (!slotNum || activeSwitchBusy) return;
    var grid = document.getElementById("sessionButtonsGrid");
    if (grid) {
      var btns = grid.querySelectorAll(".session-slot-btn");
      for (var i = 0; i < btns.length; i++) {
        var n = Number(btns[i].dataset.slotNumber || parseSlotNum(btns[i].textContent));
        btns[i].classList.toggle("is-active", n === slotNum);
      }
    }
    var cookieValEl = document.getElementById("customSessionCookieVal");
    if (cookieValEl) cookieValEl.textContent = String(slotNum);
    var cleanStatus = document.getElementById("pfCleanSessionStatus");
    if (cleanStatus) cleanStatus.textContent = "Session " + slotNum + " Ready";
  }

  function initSessionButtons() {
    loadSessionButtons();
    try {
      chrome.runtime.sendMessage({ type: "PAKFLOW_SYNC_SESSION_SHUFFLE", reloadFlowTabs: false }, function (res) {
        void chrome.runtime?.lastError;
        if (res && res.activeSlot) {
          var nextNum = parseSlotNum(res.activeSlot);
          if (nextNum) highlightActiveSessionInPopup(nextNum);
        }
        if (res && res.assignedServer) {
          updateShuffleCountdownUI(res.assignedServer);
        }
      });
    } catch (_e) {}

    if (typeof chrome !== "undefined" && chrome.storage && chrome.storage.local) {
      chrome.storage.local.get(["pf_assigned_server"], function (st) {
        void chrome.runtime?.lastError;
        if (st && st.pf_assigned_server) updateShuffleCountdownUI(st.pf_assigned_server);
      });
      if (chrome.storage.onChanged) {
        chrome.storage.onChanged.addListener(function (changes, area) {
          if (area !== "local") return;
          if (changes.activeSlot && changes.activeSlot.newValue) {
            var nextNum = parseSlotNum(changes.activeSlot.newValue);
            if (nextNum) highlightActiveSessionInPopup(nextNum);
          }
          if (changes.pf_assigned_server && changes.pf_assigned_server.newValue) {
            updateShuffleCountdownUI(changes.pf_assigned_server.newValue);
          }
        });
      }
    }

    var sel = document.getElementById("serverFileSelect");
    if (sel && typeof MutationObserver !== "undefined") {
      new MutationObserver(function () {
        var grid = document.getElementById("sessionButtonsGrid");
        if (grid && grid.children.length === 0) {
          var fromSel = slotsFromSelectEl();
          if (fromSel.length > 0) {
            chrome.storage.local.get(["activeSlot", "selectedSlot", "currentSlot", "currentServerNumber"], function (d) {
              var curNum = parseSlotNum(d && (d.activeSlot || d.selectedSlot || d.currentSlot || d.currentServerNumber)) || 1;
              renderSessionButtons(fromSel, curNum);
            });
          }
        }
      }).observe(sel, { childList: true, subtree: true });
    }
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initSessionButtons);
  } else {
    initSessionButtons();
  }
})();
