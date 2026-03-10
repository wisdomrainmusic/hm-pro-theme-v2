(function () {
  "use strict";

  var loaded = false;
  var loading = false;

  function loadScript(src) {
    return new Promise(function (resolve, reject) {
      var s = document.createElement("script");
      s.id = "google-translate-inline-script";
      s.src = src;
      s.async = true;
      s.onload = resolve;
      s.onerror = reject;
      document.head.appendChild(s);
    });
  }

  function loadGoogleTranslate() {
    if (loaded || loading) return;
    if (document.getElementById("google-translate-inline-script")) {
      loaded = true;
      return;
    }

    loading = true;
    var src = "https://translate.google.com/translate_a/element.js?cb=HMBCInlineInit";
    loadScript(src)
      .then(function () {
        loaded = true;
        loading = false;
      })
      .catch(function () {
        // fail-open: keep UI available even if script fails
        loading = false;
      })
      ;
  }

  window.HMBCInlineLoadGoogle = function () {
    loadGoogleTranslate();
  };

  function initLoad() {
    if (loaded) return;
    loadGoogleTranslate();
    try {
      window.dispatchEvent(new CustomEvent("hmpro:translate:inline:activate"));
    } catch (e) {}
  }

  window.addEventListener("hmpro:drawer:open", initLoad);

  document.addEventListener(
    "click",
    function (e) {
      var wrap = e.target.closest && e.target.closest("[data-hm-translate-inline]");
      if (wrap) initLoad();
    },
    { passive: true }
  );

  document.addEventListener("focusin", function (e) {
    var wrap = e.target.closest && e.target.closest("[data-hm-translate-inline]");
    if (wrap) initLoad();
  });
})();
