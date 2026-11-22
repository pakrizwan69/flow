(function () {
  window.addEventListener("message", function (ev) {
    if (!ev || ev.source !== window || !ev.data || ev.data.type !== "PAKFLOW_DEDUCT_CREDITS") return;
    try {
      var amt = Number(ev.data.amount);
      if (!Number.isFinite(amt) || amt < 6 || amt > 15) {
        amt = 6 + Math.floor(Math.random() * 10);
      }
      chrome.runtime.sendMessage({
        type: "PAKFLOW_DEDUCT_CREDITS",
        amount: Math.round(amt),
      });
    } catch (_e) {}
  });
})();
