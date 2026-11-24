(function () {
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
