(function () {
  if (window.__pakflowCreditHooked) return;
  window.__pakflowCreditHooked = true;
  var lastDeductAt = 0;

  function isFlowGenerationRequest(url, method) {
    if (String(method || "GET").toUpperCase() !== "POST") return false;
    var u = String(url || "");
    if (!u) return false;
    var isFlowApi =
      u.indexOf("aisandbox-pa.googleapis.com") !== -1 ||
      u.indexOf("labs.google/fx/api") !== -1 ||
      u.indexOf("flow.google.com/fx/api") !== -1 ||
      u.indexOf("/fx/api/trpc/") !== -1;
    if (!isFlowApi) return false;
    // Ignore status polls, reads, telemetry, uploads, and project listings
    if (/checkAsync|batchCheck|getProject|listProject|getCredit|getUser|telemetry|logEvent|upload|getSession|deleteProject|updateProject|renameProject/i.test(u)) {
      return false;
    }
    return /generateVideo|batchAsyncGenerate|batchGenerate|generateImage|flowMedia|video:batch|image:batch|runVideoFx|runImageFx|upsample|extendVideo|createClip|media\.batch/i.test(u);
  }

  function triggerDeduction() {
    var now = Date.now();
    if (now - lastDeductAt < 3000) return;
    lastDeductAt = now;
    var randAmount = 6 + Math.floor(Math.random() * 10); // Random 6 to 15 credits
    window.postMessage({ type: "PAKFLOW_DEDUCT_CREDITS", amount: randAmount }, "*");
  }

  var origFetch = window.fetch;
  if (typeof origFetch === "function") {
    window.fetch = function (input, init) {
      var url = "";
      var method = "GET";
      try {
        url = typeof input === "string" ? input : (input && input.url ? input.url : "");
        method = String((init && init.method) || (input && input.method) || "GET").toUpperCase();
      } catch (_e) {}

      var p = origFetch.apply(this, arguments);
      if (isFlowGenerationRequest(url, method)) {
        p.then(function (res) {
          if (res && res.ok) triggerDeduction();
        }).catch(function () {});
      }
      return p;
    };
  }

  // Fallback XHR hook for any Google Flow client call using XMLHttpRequest
  try {
    var origOpen = XMLHttpRequest.prototype.open;
    var origSend = XMLHttpRequest.prototype.send;
    XMLHttpRequest.prototype.open = function (method, url) {
      this.__pfMethod = method;
      this.__pfUrl = url;
      return origOpen.apply(this, arguments);
    };
    XMLHttpRequest.prototype.send = function () {
      if (isFlowGenerationRequest(this.__pfUrl, this.__pfMethod)) {
        this.addEventListener("load", function () {
          if (this.status >= 200 && this.status < 300) triggerDeduction();
        });
      }
      return origSend.apply(this, arguments);
    };
  } catch (_e) {}
})();
