(function () {
  function syncCustomUI() {
    var titleEl = document.getElementById("injectCycleTitle");
    var badgeEl = document.getElementById("injectCycleBadge");
    var descEl = document.getElementById("readyDescText");
    var cookieValEl = document.getElementById("customSessionCookieVal");
    var emailEl = document.getElementById("userEmailBadge");
    var avatarEl = document.getElementById("customAvatar");
    var planEl = document.getElementById("userPlanBadge");
    var creditsEl = document.getElementById("customCreditsVal");
    var daysEl = document.getElementById("customDaysLeftVal");
    var daysBadge = document.getElementById("userDaysBadge") || document.getElementById("mainHeaderDaysText");
    if (daysEl && daysBadge) {
      var mDays = (daysBadge.textContent || "").match(/(\d+)/);
      if (mDays && mDays[1] && !daysEl.dataset.fromApi) {
        daysEl.textContent = mDays[1];
      }
    }

    if (cookieValEl) {
      var textSources = [
        titleEl ? titleEl.textContent : "",
        descEl ? descEl.textContent : "",
        badgeEl ? badgeEl.textContent : "",
      ].join(" ");
      var mServer = textSources.match(/Server\s*(\d+)/i);
      var mStep = badgeEl ? (badgeEl.textContent || "").match(/^\s*(\d+)\s*\//) : null;
      if (mServer && mServer[1]) {
        cookieValEl.textContent = mServer[1];
      } else if (mStep && mStep[1]) {
        cookieValEl.textContent = mStep[1];
      }
    }

    if (emailEl && avatarEl) {
      var raw = (emailEl.textContent || "FL").trim();
      var parts = raw.split(/\s+/).filter(Boolean);
      var initials =
        parts.length >= 2
          ? (parts[0][0] + parts[1][0]).toUpperCase()
          : raw.slice(0, 2).toUpperCase();
      if (avatarEl.textContent !== (initials || "FL")) {
        avatarEl.textContent = initials || "FL";
      }
    }

    if (planEl) {
      var pTxt = (planEl.textContent || "").replace(/[⚡✨]/g, "").trim();
      var nextPlanTxt = pTxt ? (/plan/i.test(pTxt) ? pTxt : pTxt + " Plan") : "";
      if (nextPlanTxt && nextPlanTxt !== planEl.textContent) {
        planEl.textContent = nextPlanTxt;
      }
    }

    if (creditsEl && planEl) {
      var planText = (planEl.textContent || "").toLowerCase();
      if (planText) {
        var isBasicTxt = /\b(basic|free|trial|vibe|starter)\b/i.test(planText);
        var nextCred = isBasicTxt ? "0" : "45000";
        if (creditsEl.textContent !== nextCred && !creditsEl.dataset.fromApi) {
          creditsEl.textContent = nextCred;
        }
      }
    } else if (creditsEl && !creditsEl.textContent) {
      creditsEl.textContent = "0";
    }
  }

  function initObservers() {
    syncCustomUI();
    var ids = ["injectCycleTitle", "injectCycleBadge", "readyDescText", "userEmailBadge", "userPlanBadge", "userDaysBadge", "mainHeaderDaysText"];
    var rafPending = false;
    function scheduleSync() {
      if (rafPending) return;
      rafPending = true;
      requestAnimationFrame(function () {
        rafPending = false;
        syncCustomUI();
      });
    }
    ids.forEach(function (id) {
      var el = document.getElementById(id);
      if (!el) return;
      new MutationObserver(scheduleSync).observe(el, {
        childList: true,
        characterData: true,
        subtree: true,
      });
    });

    if (typeof chrome !== "undefined" && chrome.storage && chrome.storage.local) {
      chrome.storage.local.get(["user","pakflow_user","userEmail","userPlan","credits","daysLeft","expiresAt","pakflow_days_cache","activeSlot","selectedSlot","currentSlot","currentServerNumber","flow_token","pakflow_token","token","authToken","userToken","apiBase","connectedFromSite","pakflowWorkingOrigin","apiBaseUrl","apiUrl","portalUrl","lastOrigin"], function (data) {
        if (!data) return;
        var user = data.user || data.pakflow_user || (data.userEmail ? { email: data.userEmail, plan: data.userPlan, daysLeft: data.daysLeft, planExpiresAt: data.expiresAt } : null);
        var creditsEl = document.getElementById("customCreditsVal");
    var daysEl = document.getElementById("customDaysLeftVal");
    var daysBadge = document.getElementById("userDaysBadge") || document.getElementById("mainHeaderDaysText");
    if (daysEl && daysBadge) {
      var mDays = (daysBadge.textContent || "").match(/(\d+)/);
      if (mDays && mDays[1] && !daysEl.dataset.fromApi) {
        daysEl.textContent = mDays[1];
      }
    }
        if (user) {
          var emailEl = document.getElementById("userEmailBadge");
          var planEl = document.getElementById("userPlanBadge");
          var preferredEmail = user.email || user.name || user.username;
          if (emailEl && preferredEmail && emailEl.textContent !== preferredEmail) {
            emailEl.textContent = preferredEmail;
          }
          if (planEl && user.plan) {
            var p = String(user.plan).trim();
            planEl.textContent = p.toUpperCase() + " Plan";
          }
          if (creditsEl) {
            var pLow = String(user.plan || "basic").toLowerCase();
            var isBasicPlan = pLow === "basic" || pLow === "free" || pLow === "trial" || pLow === "vibe" || pLow === "starter";
            creditsEl.textContent = isBasicPlan ? "0" : "45000";
          }
          var daysValEl = document.getElementById("customDaysLeftVal");
          if (daysValEl) {
            var dLeft = user.daysLeft != null ? Number(user.daysLeft) : (user.planExpiresAt ? Math.max(0, Math.ceil((Date.parse(user.planExpiresAt) - Date.now()) / 86400000)) : null);
            if (dLeft != null && !isNaN(dLeft)) {
              daysValEl.textContent = String(Math.max(0, Math.round(dLeft)));
            } else if (data.pakflow_days_cache != null) {
              daysValEl.textContent = String(data.pakflow_days_cache);
            }
          }
        } else if (creditsEl && data.credits != null) {
          creditsEl.textContent = String(data.credits);
        }

        var slot = data.activeSlot || data.selectedSlot || data.currentSlot || data.currentServerNumber;
        if (slot) {
          var cookieValEl = document.getElementById("customSessionCookieVal");
          if (cookieValEl) cookieValEl.textContent = String(slot).replace(/\D+/g, "") || "1";
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
        var origin = data.pakflowWorkingOrigin || data.apiBase || data.connectedFromSite || data.apiBaseUrl || data.apiUrl || data.portalUrl || data.lastOrigin || "https://flowbypak.com";
        if (token && /^https?:\/\//i.test(origin)) {
          var authAc = typeof AbortController !== "undefined" ? new AbortController() : null;
          var authTid = authAc ? setTimeout(function () { try { authAc.abort(); } catch (_e) {} }, 3000) : null;
          fetch(origin.replace(/\/+$/, "") + "/api/auth/me", {
            headers: {
              "Authorization": "Bearer " + token,
              "X-PakFlow-Extension-Version": extVer,
            },
            cache: "no-store",
            signal: authAc ? authAc.signal : undefined,
          })
            .then(function (r) { return r.json(); })
            .then(function (me) {
              if (authTid) clearTimeout(authTid);
              var u = me && (me.user || me);
              if (u && creditsEl) {
                var pLow2 = String(u.plan || "basic").toLowerCase();
                var isBasic2 = pLow2 === "basic" || pLow2 === "free" || pLow2 === "trial" || pLow2 === "vibe" || pLow2 === "starter";
                var cVal = isBasic2 ? "0" : "45000";
                creditsEl.textContent = cVal;
                var dLeft2 = me && me.daysLeft != null ? Number(me.daysLeft) : (u.daysLeft != null ? Number(u.daysLeft) : (u.planExpiresAt ? Math.max(0, Math.ceil((Date.parse(u.planExpiresAt) - Date.now()) / 86400000)) : null));
                var daysValEl2 = document.getElementById("customDaysLeftVal");
                if (daysValEl2 && dLeft2 != null && !isNaN(dLeft2)) {
                  var dClean = String(Math.max(0, Math.round(dLeft2)));
                  daysValEl2.textContent = dClean;
                  daysValEl2.dataset.fromApi = "1";
                  chrome.storage.local.set({ pakflow_days_cache: dClean });
                }
                chrome.storage.local.set({ credits: cVal });
              }
            })
            .catch(function () { if (authTid) clearTimeout(authTid); });
        }

        syncCustomUI();
      });

      var logoutBtnEl = document.getElementById("logoutBtn");
      if (logoutBtnEl) {
        logoutBtnEl.addEventListener("click", function () {
          try { localStorage.removeItem("pf_popup_snapshot_v1"); } catch (_e) {}
          chrome.storage.local.get(["flow_token","pakflow_token","token","authToken","userToken","pakflowWorkingOrigin","apiBase","connectedFromSite","apiBaseUrl","apiUrl","portalUrl","lastOrigin"], function (d) {
            if (!d) return;
            var t = d.flow_token || d.pakflow_token || d.token || d.authToken || d.userToken || "";
            var o = d.pakflowWorkingOrigin || d.apiBase || d.connectedFromSite || d.apiBaseUrl || d.apiUrl || d.portalUrl || d.lastOrigin || "https://flowbypak.com";
            if (t && /^https?:\/\//i.test(o)) {
              fetch(o.replace(/\/+$/, "") + "/api/auth/logout", {
                method: "POST",
                headers: { "Authorization": "Bearer " + t },
              }).catch(function () {});
            }
            try { chrome.storage.local.remove(["pakflow_token","flow_token","token","authToken","user","pakflow_cookies_cache"]); } catch (_e) {}
          });
        });
      }
    }
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

      var descEl = document.getElementById("readyDescText");
      var cookieValEl = document.getElementById("customSessionCookieVal");
      if (cookieValEl) cookieValEl.textContent = String(num);
      if (descEl) descEl.textContent = "Switching to Session " + num + "...";

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
              if (descEl) descEl.textContent = "Session " + num + " Active — Flow Ready";
            } else if (res && res.superseded) {
              // Another switch superseded this one cleanly
            } else {
              if (descEl) descEl.textContent = (res && res.error) || "Session " + num + " Switch Failed";
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

    var nextNums = slots.map(function (s) {
      return Number(s.slotNumber || parseSlotNum(s.id));
    });
    var existingBtns = grid.querySelectorAll(".session-slot-btn");
    var existingNums = [];
    for (var i = 0; i < existingBtns.length; i++) {
      existingNums.push(Number(existingBtns[i].dataset.slotNumber || parseSlotNum(existingBtns[i].textContent)));
    }

    // Reuse existing buttons in-place (zero flash/layout jump when preinit already painted them)
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
    var box = document.getElementById("sessionInjectorBox");
    if (!box) return null;
    el = document.createElement("div");
    el.id = "pfPopupShuffleCountdown";
    el.className = "shuffle-countdown-bar";
    box.appendChild(el);
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
    var descEl = document.getElementById("readyDescText");
    if (descEl) descEl.textContent = "Session " + slotNum + " Active — Flow Ready";
  }

  function initSessionButtons() {
    loadSessionButtons();
    // Immediately sync any admin Reshuffle Now / auto-shuffle period change when popup opens
    try {
      chrome.runtime.sendMessage({ type: "PAKFLOW_SYNC_SESSION_SHUFFLE" }, function (res) {
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
