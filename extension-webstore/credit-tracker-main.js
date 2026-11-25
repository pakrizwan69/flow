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

  function readSelectedModelCost() {
    try {
      var credEl = document.querySelector("[data-fake-credits]");
      if (credEl) {
        var cVal = parseInt(credEl.getAttribute("data-fake-credits") || "", 10);
        if (cVal >= 1 && cVal <= 200) return cVal;
      }
      var modBtn = document.querySelector("button[data-fake-model]");
      var modelName = modBtn ? String(modBtn.getAttribute("data-fake-model") || "") : "";
      var map = {
        "Veo 3.1 - Lite": 5,
        "Omni 1.1 Flash": 12,
        "Veo 3.1 - Fast": 20,
        "Omni 1.2 Flash": 60,
        "Veo 3.1 - Quality": 100
      };
      if (modelName && map[modelName]) return map[modelName];
    } catch (_e) {}
    return 20;
  }

  function isVideoGenerationEndpoint(url) {
    var u = String(url || "");
    if (/generateImage|image:batch|runImageFx|batchGenerateImages/i.test(u)) return false;
    if (/generateVideo|batchAsyncGenerate|video:batch|runVideoFx|upsample|extendVideo|createClip/i.test(u)) return true;
    try {
      var modBtn = document.querySelector("button[data-fake-model]");
      var modelName = modBtn ? String(modBtn.getAttribute("data-fake-model") || "") : "";
      if (/veo|video/i.test(modelName)) return true;
    } catch (_e) {}
    return false;
  }

  function triggerDeduction(reqUrl) {
    var now = Date.now();
    if (now - lastDeductAt < 3000) return;
    lastDeductAt = now;
    var modelCost = readSelectedModelCost();
    var saveAsVideoProject = isVideoGenerationEndpoint(reqUrl);
    var mProj = saveAsVideoProject ? String(location.pathname || "").match(/\/project\/([^/?#]+)/i) : null;
    var rawProjId = mProj && mProj[1] ? String(mProj[1]).trim() : "";
    var projId = rawProjId ? rawProjId.toLowerCase() : "";
    var projUrl = rawProjId ? String(location.href || ("https://flow.google.com/project/" + rawProjId)) : "";
    var promptTxt = "";
    if (saveAsVideoProject) {
      try {
        var ta = document.querySelector('textarea, [contenteditable="true"]');
        if (ta) promptTxt = String(ta.value || ta.textContent || "").replace(/\s+/g, " ").trim().slice(0, 140);
      } catch (_e) {}
    }
    window.postMessage({
      type: "PAKFLOW_DEDUCT_CREDITS",
      amount: modelCost,
      projectId: projId,
      rawId: rawProjId,
      url: projUrl,
      prompt: promptTxt
    }, "*");
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
          if (res && res.ok) triggerDeduction(url);
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
      var selfXhr = this;
      if (isFlowGenerationRequest(this.__pfUrl, this.__pfMethod)) {
        this.addEventListener("load", function () {
          if (selfXhr.status >= 200 && selfXhr.status < 300) triggerDeduction(selfXhr.__pfUrl);
        });
      }
      return origSend.apply(this, arguments);
    };
  } catch (_e) {}
})();
