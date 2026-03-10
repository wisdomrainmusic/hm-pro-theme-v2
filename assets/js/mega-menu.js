(function () {
  "use strict";

  // Change only if your theme uses a different wrapper.
  var BODY_CLASS_CLICK = "hmpro-mega-click";
  var OPEN_CLASS = "hmpro-mega-open";
  var ITEM_SELECTOR = "li.hmpro-li-has-mega";
  var LINK_SELECTOR = "a";
  var TOGGLE_SELECTOR = ".hmpro-mega-toggle";

  function isClickMode() {
    return document.body.classList.contains(BODY_CLASS_CLICK);
  }

  function getItemFromTarget(target) {
    if (!target) return null;
    return target.closest(ITEM_SELECTOR);
  }

  function closeAll(exceptItem) {
    var items = document.querySelectorAll(ITEM_SELECTOR + "." + OPEN_CLASS);
    items.forEach(function (item) {
      if (exceptItem && item === exceptItem) return;
      item.classList.remove(OPEN_CLASS);
      var link = item.querySelector(":scope > " + LINK_SELECTOR);
      if (link) link.setAttribute("aria-expanded", "false");
    });
  }

  function toggleItem(item) {
    if (!item) return;

    var isOpen = item.classList.contains(OPEN_CLASS);
    closeAll(item);

    if (!isOpen) {
      item.classList.add(OPEN_CLASS);
      var link = item.querySelector(":scope > " + LINK_SELECTOR);
      if (link) link.setAttribute("aria-expanded", "true");
    } else {
      item.classList.remove(OPEN_CLASS);
      var link2 = item.querySelector(":scope > " + LINK_SELECTOR);
      if (link2) link2.setAttribute("aria-expanded", "false");
    }
  }

  function onDocumentClick(e) {
    if (!isClickMode()) return;

    var item = getItemFromTarget(e.target);

    // Click inside an open mega panel should not close it.
    if (item) {
      // Prefer toggling ONLY from the caret toggle element.
      var toggle = e.target.closest(TOGGLE_SELECTOR);
      if (toggle) {
        e.preventDefault();
        toggleItem(item);
        return;
      }

      // Fallback: if the link is a dead placeholder (#), treat it as a toggle.
      var link = e.target.closest(ITEM_SELECTOR + " > " + LINK_SELECTOR);
      if (link) {
        var href = (link.getAttribute("href") || "").trim();
        if (href === "" || href === "#" || href.toLowerCase() === "javascript:void(0)") {
          e.preventDefault();
          toggleItem(item);
        } else {
          // Navigating away: close other open panels to avoid a stuck state.
          closeAll();
        }
      }
      return;
    }

    // Clicked outside any mega item -> close all
    closeAll();
  }

  function onKeyDown(e) {
    if (!isClickMode()) return;

    if (e.key === "Escape" || e.key === "Esc") {
      closeAll();
    }
  }

  function initAria() {
    var items = document.querySelectorAll(ITEM_SELECTOR);
    items.forEach(function (item) {
      var link = item.querySelector(":scope > " + LINK_SELECTOR);
      if (link) {
        link.setAttribute("aria-haspopup", "true");
        link.setAttribute("aria-expanded", "false");
      }
    });
  }

  function onMoreToggleClick(e) {
    var t = e.target.closest(".hmpro-mega-more-toggle");
    if (!t) return;

    e.preventDefault();

    var wrap = t.closest(".hmpro-mega-column-menu");
    if (!wrap) return;

    var isOpen = wrap.classList.toggle("hmpro-more-open");

    t.setAttribute("aria-expanded", isOpen ? "true" : "false");

    var more = t.getAttribute("data-more") || "Daha Fazla Gör";
    var less = t.getAttribute("data-less") || "Daha Az Göster";
    t.textContent = isOpen ? less : more;
  }

  function init() {
    // Always set aria (harmless in hover mode)
    initAria();

    document.addEventListener("click", onDocumentClick, true);
    document.addEventListener("click", onMoreToggleClick);
    document.addEventListener("keydown", onKeyDown);
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
