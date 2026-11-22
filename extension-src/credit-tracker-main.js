(function () {
  if (window.__pakflowCreditHooked) return;
  window.__pakflowCreditHooked = true;
  var origFetch = window.fetch;
  if (typeof origFetch !== "function") return;

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
          window.postMessage({ type: "PAKFLOW_DEDUCT_CREDITS", amount: 60 }, "*");
        }
      }).catch(function () {});
    }
    return p;
  };
})();
