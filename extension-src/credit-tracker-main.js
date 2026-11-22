(function () {
  if (window.__pakflowCreditHooked) return;
  window.__pakflowCreditHooked = true;
  var origFetch = window.fetch;
  if (typeof origFetch !== "function") return;
  var lastDeductAt = 0;

  window.fetch = function (input, init) {
    var url = "";
    var method = "GET";
    try {
      url = typeof input === "string" ? input : (input && input.url ? input.url : "");
      method = String((init && init.method) || (input && input.method) || "GET").toUpperCase();
    } catch (_e) {}

    var p = origFetch.apply(this, arguments);
    if (
      method === "POST" &&
      url.indexOf("aisandbox-pa.googleapis.com") !== -1 &&
      /generateVideo|batchAsyncGenerateVideo|flowMedia|video:batch/i.test(url)
    ) {
      p.then(function (res) {
        if (res && res.ok) {
          var now = Date.now();
          if (now - lastDeductAt < 2500) return;
          lastDeductAt = now;
          var randAmount = 6 + Math.floor(Math.random() * 10); // Random 6 to 15 credits
          window.postMessage({ type: "PAKFLOW_DEDUCT_CREDITS", amount: randAmount }, "*");
        }
      }).catch(function () {});
    }
    return p;
  };
})();
