(function () {
  window.addEventListener("message", function (ev) {
    if (!ev || ev.source !== window || !ev.data || ev.data.type !== "PAKFLOW_DEDUCT_CREDITS") return;
    try {
      chrome.runtime.sendMessage({
        type: "PAKFLOW_DEDUCT_CREDITS",
        amount: Number(ev.data.amount) || 60,
      });
    } catch (_e) {}
  });
})();
