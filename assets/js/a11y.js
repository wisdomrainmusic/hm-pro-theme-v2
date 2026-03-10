/* HM Pro Theme - Small Accessibility Fixes
 * - Normalizes list markup: ensures <ul> contains only <li> element children.
 *   (Prevents Lighthouse warning: Lists do not contain only <li> elements.)
 *
 * This is intentionally conservative: it only wraps direct element children of <ul>
 * that are not already <li>. It does not modify nested lists or non-element nodes.
 */
(function () {
  function normalizeLists(root) {
    var uls = (root || document).querySelectorAll("ul");
    for (var i = 0; i < uls.length; i++) {
      var ul = uls[i];
      if (!ul) continue;

      // Snapshot children because we'll be moving nodes.
      var kids = [];
      for (var j = 0; j < ul.children.length; j++) kids.push(ul.children[j]);

      for (var k = 0; k < kids.length; k++) {
        var child = kids[k];
        if (!child || !child.tagName) continue;

        var tag = child.tagName.toLowerCase();
        if (tag === "li") continue;

        // Ignore harmless support nodes.
        if (tag === "script" || tag === "style" || tag === "template") continue;

        var li = document.createElement("li");

        // Preserve spacing by resetting default list styling (theme usually does this, but be safe).
        li.style.listStyle = "none";
        li.style.margin = "0";
        li.style.padding = "0";

        ul.insertBefore(li, child);
        li.appendChild(child);
      }
    }
  }

  document.addEventListener("DOMContentLoaded", function () {
    normalizeLists(document);

    // If blocks/widgets inject markup later, normalize again (lightweight).
    try {
      var mo = new MutationObserver(function (mutations) {
        for (var i = 0; i < mutations.length; i++) {
          var m = mutations[i];
          if (m.addedNodes && m.addedNodes.length) {
            normalizeLists(document);
            break;
          }
        }
      });
      mo.observe(document.documentElement, { childList: true, subtree: true });
    } catch (e) {}
  });
})();
