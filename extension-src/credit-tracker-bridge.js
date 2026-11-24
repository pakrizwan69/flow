(function () {
  var SAPISID_ATTR = "data-pf-sapisid";
  var SAPISID_CACHE_KEY = "__pf_sapisid_cache";

  function applySapisidBridge(st) {
    try {
      var loggedIn = st && (st.isLoggedIn === true || st.isLoggedIn === "true");
      var detached = st && (st.flowSessionDetached === true || st.flowSessionDetached === "true");
      var locked = Boolean(st && st.pf_flow_lock_state && st.pf_flow_lock_state.locked);
      var raw = loggedIn && !detached && !locked && typeof st.pf_flow_sapisid_str === "string"
        ? st.pf_flow_sapisid_str.trim()
        : "";
      if (raw) {
        if (document.documentElement) {
          document.documentElement.setAttribute(SAPISID_ATTR, raw);
        }
        try { sessionStorage.setItem(SAPISID_CACHE_KEY, raw); } catch (_e) {}
      } else {
        if (document.documentElement) {
          document.documentElement.removeAttribute(SAPISID_ATTR);
        }
        try { sessionStorage.removeItem(SAPISID_CACHE_KEY); } catch (_e) {}
      }
    } catch (_e) {}
  }

  try {
    if (chrome && chrome.storage && chrome.storage.local) {
      var keys = ["pf_flow_sapisid_str", "isLoggedIn", "flowSessionDetached", "pf_flow_lock_state"];
      chrome.storage.local.get(keys, function (st) {
        void (chrome.runtime && chrome.runtime.lastError);
        applySapisidBridge(st || {});
      });
      if (chrome.storage.onChanged) {
        chrome.storage.onChanged.addListener(function (changes, area) {
          if (area !== "local" || !changes) return;
          if (
            changes.pf_flow_sapisid_str ||
            changes.isLoggedIn ||
            changes.flowSessionDetached ||
            changes.pf_flow_lock_state
          ) {
            chrome.storage.local.get(keys, function (st) {
              void (chrome.runtime && chrome.runtime.lastError);
              applySapisidBridge(st || {});
            });
          }
        });
      }
    }
  } catch (_e) {}

  window.addEventListener("message", function (ev) {
    if (!ev || ev.source !== window || !ev.data || ev.data.type !== "PAKFLOW_DEDUCT_CREDITS") return;
    try {
      var amt = Number(ev.data.amount);
      if (!Number.isFinite(amt) || amt < 1 || amt > 200) {
        amt = 20;
      }
      chrome.runtime.sendMessage({
        type: "PAKFLOW_DEDUCT_CREDITS",
        amount: Math.round(amt),
        projectId: ev.data.projectId ? String(ev.data.projectId) : "",
        prompt: ev.data.prompt ? String(ev.data.prompt) : ""
      });
    } catch (_e) {}
  });
})();
