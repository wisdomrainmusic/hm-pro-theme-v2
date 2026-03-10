(function () {
  function qs(el, sel) {
    return el ? el.querySelector(sel) : null;
  }

  function qsa(el, sel) {
    return el ? Array.from(el.querySelectorAll(sel)) : [];
  }

  function clamp(num, min, max) {
    return Math.min(Math.max(num, min), max);
  }

  async function fetchPage(root, pane, page) {
    const ajaxUrl = root.getAttribute("data-ajax-url") || "";
    const nonce = root.getAttribute("data-nonce") || "";
    if (!ajaxUrl || !nonce) return null;

    const taxonomy = pane.getAttribute("data-taxonomy") || "product_cat";
    const termId = pane.getAttribute("data-term-id") || "0";
    const perPage = pane.getAttribute("data-per-page") || "8";

    const body = new URLSearchParams();
    body.set("action", "hmpro_pft_fetch_products");
    body.set("nonce", nonce);
    body.set("taxonomy", taxonomy);
    body.set("termId", termId);
    body.set("perPage", perPage);
    body.set("page", String(page));

    const res = await fetch(ajaxUrl, {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
      body: body.toString(),
      credentials: "same-origin",
    });

    if (!res.ok) return null;
    const json = await res.json();
    if (!json || !json.success || !json.data || typeof json.data.html !== "string") return null;
    return json.data.html;
  }

  function updatePager(pane) {
    const totalPages = parseInt(pane.getAttribute("data-total-pages") || "1", 10) || 1;
    const currentPage = parseInt(pane.getAttribute("data-current-page") || "1", 10) || 1;

    const pager = qs(pane, ".hmpro-pft__pager");
    if (!pager) return;

    const prev = qs(pager, ".hmpro-pft__pager-prev");
    const next = qs(pager, ".hmpro-pft__pager-next");
    const status = qs(pager, ".hmpro-pft__pager-status");

    if (status) status.textContent = `${currentPage} / ${totalPages}`;
    if (prev) prev.disabled = currentPage <= 1;
    if (next) next.disabled = currentPage >= totalPages;
  }

  function scrollToGridTop(root) {
    // Scroll to the block wrapper so the grid stays in view.
    try {
      root.scrollIntoView({ behavior: "smooth", block: "start" });
    } catch (e) {
      // no-op
    }
  }

  function bindPaging(root, pane) {
    const pager = qs(pane, ".hmpro-pft__pager");
    if (!pager) return;

    const prev = qs(pager, ".hmpro-pft__pager-prev");
    const next = qs(pager, ".hmpro-pft__pager-next");

    const onClick = async (dir) => {
      const totalPages = parseInt(pane.getAttribute("data-total-pages") || "1", 10) || 1;
      const currentPage = parseInt(pane.getAttribute("data-current-page") || "1", 10) || 1;
      const targetPage = clamp(currentPage + dir, 1, totalPages);
      if (targetPage === currentPage) return;

      pane.classList.add("is-loading");
      if (prev) prev.disabled = true;
      if (next) next.disabled = true;

      const html = await fetchPage(root, pane, targetPage);
      pane.classList.remove("is-loading");

      if (!html) {
        // Restore button state.
        pane.setAttribute("data-current-page", String(currentPage));
        updatePager(pane);
        return;
      }

      const oldGrid = qs(pane, ".hmpro-pft__grid");
      if (oldGrid) {
        // Replace only the grid markup to avoid losing the pager.
        const tmp = document.createElement("div");
        tmp.innerHTML = html;
        const newGrid = qs(tmp, ".hmpro-pft__grid");
        if (newGrid) oldGrid.replaceWith(newGrid);
      }

      pane.setAttribute("data-current-page", String(targetPage));
      updatePager(pane);
      scrollToGridTop(root);
    };

    if (prev) prev.addEventListener("click", () => onClick(-1));
    if (next) next.addEventListener("click", () => onClick(1));

    updatePager(pane);
  }

  function init(root) {
    const tabs = qsa(root, ".hmpro-pft__tab");
    const panes = qsa(root, ".hmpro-pft__pane");
    if (!tabs.length || !panes.length) return;

    function activate(index) {
      tabs.forEach((t) => {
        const i = parseInt(t.getAttribute("data-tab-index") || "0", 10);
        const active = i === index;
        t.classList.toggle("is-active", active);
        t.setAttribute("aria-selected", active ? "true" : "false");
      });
      panes.forEach((p) => {
        const i = parseInt(p.getAttribute("data-tab-index") || "0", 10);
        p.classList.toggle("is-active", i === index);
      });
    }

    tabs.forEach((btn) => {
      btn.addEventListener("click", () => {
        const index = parseInt(btn.getAttribute("data-tab-index") || "0", 10);
        activate(index);
      });
    });

    panes.forEach((pane) => bindPaging(root, pane));
  }

  function boot() {
    document.querySelectorAll(".hmpro-pft").forEach(init);
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", boot);
  } else {
    boot();
  }
})();
