# HM Pro Theme Performance & Risk Audit (Mobile-First)

## Executive summary (non-technical)
- The theme is structurally solid and modular, but it ships several global styles/scripts that load everywhere even when not needed. This inflates mobile payloads and can slow down first render.
- The biggest performance risks come from hero/slider and promo blocks that use background images (no responsive `srcset`, no priority hints) and from full-width layouts that can trigger layout shifts on small screens.
- There are a few correctness risks (undefined variables in a block renderer) that can create PHP notices and break REST/JSON responses on strict setups.

---

## 1) Theme architecture overview

### Root structure
- `functions.php`: bootstrap, module wiring, admin/frontend enqueues, REST output guard, cache purge helpers.
- `assets/`: frontend CSS/JS + admin CSS/JS.
- `inc/`: main theme modules.
- `woocommerce/`: WooCommerce template overrides (not audited in detail here).

### Module map
- `inc/core/`: setup, enqueue, customizer, template tags.
- `inc/engine/`: presets, typography, CSS engine, builder integrations, mega menu data.
- `inc/hm-blocks/`: custom Gutenberg blocks (landing-page focus) with server-side render callbacks and block assets.
- `inc/woocommerce/` + `inc/woo/`: WooCommerce front-end tweaks and variation tooling.
- `inc/admin/`: admin pages, builder tools, AJAX handlers.
- `inc/tools/`: embedded utilities (category/product importers, menu controller, translate inline tool).

---

## 2) Risks & correctness

### High-priority correctness risks
1. **Undefined variables in Product Full Tabs render**
   - `$tab_bg_hover` / `$tab_c_hover` are referenced but never defined; `$link_open` / `$link_close` are also undefined.
   - This can trigger PHP notices, which can corrupt JSON responses and break the block editor or AJAX in strict environments.

### Medium-priority risks
2. **REST output buffering is global and unconditional for REST requests**
   - There is a guard that buffers output on REST requests. This helps Gutenberg stability but can hide noisy output from plugins in ways that are hard to debug.

3. **Hero slider uses CSS background images**
   - Images are set via CSS vars and not standard `<img>` tags. This prevents responsive image selection and `fetchpriority`/`loading` control for LCP-critical assets.

---

## 3) Performance audit (mobile-first)

### Enqueued assets (frontend)
- **Global theme CSS/JS**
  - `assets/css/base.css`
  - `assets/css/header.css`
  - `assets/css/footer.css`
  - `assets/css/mega-menu.css`
  - `assets/js/mega-menu.js`
  - `assets/js/mobile-header.js`
  - `style.css`
- **Conditional WooCommerce CSS/JS**
  - `assets/css/woocommerce.css` (only if WooCommerce active)
  - `assets/js/checkout-accordion.js` (checkout only)
  - `assets/js/variation-long-desc.js` (single variable products only)
  - `assets/js/woo-variation-gallery.js` + `assets/css/woo-variation-gallery.css` (single product only)
- **Customizer** (admin / preview)
  - `assets/js/customizer-preview.js`
  - `assets/js/customizer-reset.js` (AJAX reset)
- **Blocks**
  - `inc/hm-blocks/assets/css/blocks.css` (always in `enqueue_block_assets`)
  - Preloaded block styles for `features-row` and `feature-item`
  - `inc/hm-blocks/assets/css/editor.css` + `inc/hm-blocks/assets/js/editor.js` (editor only)
  - `inc/hm-blocks/blocks/instagram-story/view.js` (only when block renders)
- **Typography**
  - Google Fonts requested via `https://fonts.googleapis.com/css2?...`
- **Inline tools**
  - HM Basic Translate (inline) adds inline CSS + JS and a cookie-driven language switcher if enabled.

### Global assets that should be conditional
- `mega-menu.css` + `mega-menu.js` load on every page. If mega menu isn’t present, these should be conditional on menu presence.
- `mobile-header.js` loads on every page, even if the mobile header isn’t rendered (e.g., minimal landing pages).
- `hmpro-blocks` styles load on every page, even if the page does not include any HMPro blocks.

### Large bundles / duplicate libs / dependencies
- No obvious large bundles or duplicate libraries present; the asset set is composed of small, separate files.
- Assets are not minified, which is acceptable for small files but still adds overhead on mobile networks.

### Third‑party fonts and external assets
- Google Fonts are loaded dynamically based on preset selections (Inter, Poppins, Lato, Open Sans, Playfair Display, etc.).
- No external icon packs or slider libraries detected; slider logic is custom.

### Image usage patterns & LCP/CLS risk
- **Hero Slider**: images are applied as CSS backgrounds (no `srcset`, no `fetchpriority`, no `loading` hints). This can hurt LCP and adaptive sizing.
- **Promo Grid**: images are output as `<img>` tags but only use a single URL with no `srcset` or `sizes`. Also missing explicit width/height attributes (CLS risk).
- **Instagram Story**: thumbnails are plain `<img>` tags with no `srcset`/`sizes` or width/height attributes (CLS risk).
- **Product Tabs**: uses WooCommerce’s `get_image()` which provides responsive `srcset`/`sizes` and dimensions (good).

### CLS & layout shift risk (CSS)
- Multiple blocks use 100vw full-bleed layouts (`hero-slider`, `promo-grid`, `instagram-story`, `product-full-tabs`, `campaign-bar`) which can cause horizontal overflow/CLS if container padding differs from viewport.
- The theme attempts to mitigate overflow via `overflow-x: clip` on `html` and wrapper elements, but this can still introduce layout instability on older browsers.

### DOM bloat / layout complexity
- Hero slider and product tabs introduce layered wrappers and repeated elements per slide/tab. This is manageable but can add DOM weight on content-heavy pages.
- Promo grids and story modals can increase DOM depth quickly when many tiles/stories are configured.

---

## 4) Google PageSpeed Insights Action Plan (Top 15)

> Ordered by expected impact (LCP/INP/CLS/TTFB) and risk. Each item is scoped as a **low-risk additive change**.

1. **Preload LCP hero image when Hero Slider is used**
   - **Impact**: LCP (High)
   - **Risk**: Low
   - **Why**: CSS background images won’t get `fetchpriority`. Preloading gives the browser an early fetch hint.
   - **Change**: Add `<link rel="preload" as="image">` for the first slide image on pages with hero slider.
   - **Where**: `inc/hm-blocks/blocks/hero-slider/render.php`

2. **Switch hero background to `<img>` + `object-fit` on first slide**
   - **Impact**: LCP (High)
   - **Risk**: Medium
   - **Why**: Enables responsive `srcset` + `sizes` and `fetchpriority="high"`.
   - **Change**: Render the first slide image as `<img>` while keeping CSS background for others.
   - **Where**: `inc/hm-blocks/blocks/hero-slider/render.php`

3. **Add `srcset`/`sizes` to Promo Grid images**
   - **Impact**: LCP, TTFB (Med)
   - **Risk**: Low
   - **Why**: Adaptive image selection on mobile saves bytes.
   - **Change**: Replace `wp_get_attachment_image_url()` with `wp_get_attachment_image()` and pass sizes.
   - **Where**: `inc/hm-blocks/blocks/promo-grid/render.php`

4. **Add width/height (or aspect ratio) to promo and story images**
   - **Impact**: CLS (High)
   - **Risk**: Low
   - **Why**: Prevents layout shifts as images load.
   - **Change**: Output dimensions or `aspect-ratio` in CSS.
   - **Where**: `inc/hm-blocks/blocks/promo-grid/render.php`, `inc/hm-blocks/blocks/instagram-story/render.php`, `inc/hm-blocks/blocks/instagram-story/style.css`

5. **Conditionally enqueue mega-menu assets only when mega menu exists**
   - **Impact**: LCP/INP (Med)
   - **Risk**: Low
   - **Why**: Reduces CSS/JS on pages without mega menus.
   - **Change**: Add checks for menu location or class before enqueue.
   - **Where**: `functions.php`

6. **Conditionally enqueue mobile header JS only when mobile header is present**
   - **Impact**: INP (Med)
   - **Risk**: Low
   - **Why**: Avoids extra JS on landing pages.
   - **Where**: `functions.php`

7. **Load HM Pro block styles only when blocks are present**
   - **Impact**: LCP/CLS (Med)
   - **Risk**: Medium
   - **Why**: Global block CSS is currently loaded for every page.
   - **Change**: Use `has_block()` checks before enqueue.
   - **Where**: `inc/hm-blocks/hm-blocks.php`

8. **Fix undefined variables in Product Full Tabs block**
   - **Impact**: Correctness + REST stability (High)
   - **Risk**: Low
   - **Why**: Prevents PHP notices that break JSON responses in strict setups.
   - **Change**: Define `$tab_bg_hover`, `$tab_c_hover`, `$link_open`, `$link_close` or remove usage.
   - **Where**: `inc/hm-blocks/blocks/product-full-tabs/render.php`

9. **Add `fetchpriority="high"` to above-the-fold product images on mobile**
   - **Impact**: LCP (Med)
   - **Risk**: Low
   - **Why**: Helps the browser prioritize key images.
   - **Where**: `inc/hm-blocks/blocks/product-tabs/render.php` (first row only)

10. **Lazy load non-critical slider/stories assets**
   - **Impact**: LCP/INP (Med)
   - **Risk**: Low
   - **Why**: Defer JS for blocks below the fold using `IntersectionObserver`.
   - **Where**: `inc/hm-blocks/blocks/instagram-story/view.js`, `assets/js/hb-slider.js`

11. **Reduce animation work on low-power devices**
   - **Impact**: INP (Low)
   - **Risk**: Low
   - **Why**: Slider transitions and marquee can cost CPU on mobile.
   - **Change**: Respect `prefers-reduced-motion` more broadly.
   - **Where**: `inc/hm-blocks/blocks/hm-campaign-bar/style.css`, `inc/hm-blocks/blocks/hero-slider/style.css`

12. **Avoid 100vw overflow with safer full-bleed wrapper**
   - **Impact**: CLS (Med)
   - **Risk**: Low
   - **Why**: 100vw can cause overflow when scrollbars appear.
   - **Change**: Use `width: 100%` + `margin: 0 calc(50% - 50vw)` only when needed.
   - **Where**: `inc/hm-blocks/blocks/*/style.css`, `assets/css/mega-menu.css`

13. **Inline critical CSS for header + hero**
   - **Impact**: LCP (Med)
   - **Risk**: Medium
   - **Why**: Reduces render-blocking CSS on mobile.
   - **Where**: `functions.php` enqueue pipeline

14. **Use `defer`/`async` for low-priority scripts**
   - **Impact**: INP (Low)
   - **Risk**: Medium
   - **Where**: `functions.php`, `inc/woo/variation-multi-gallery.php`

15. **Add caching headers for Google Fonts**
   - **Impact**: TTFB (Low)
   - **Risk**: Low
   - **Why**: Improves repeat visits and CDN cache.
   - **Where**: server/CDN configuration (outside theme)

---

## 5) Quick wins (≤ 1 hour)
1. Fix undefined variables in Product Full Tabs block.
2. Add `srcset`/`sizes` to Promo Grid and Instagram Story images.
3. Add `fetchpriority="high"` to first hero image (or preload it).
4. Conditional enqueue for mega menu assets.
5. Conditional enqueue for HM Pro block CSS if no block is used.

---

## 6) Safe patch list (suggested diffs, no breaking changes)

> These are minimal, additive patches. Apply selectively.

### A) Fix undefined variables in Product Full Tabs
```diff
--- a/inc/hm-blocks/blocks/product-full-tabs/render.php
+++ b/inc/hm-blocks/blocks/product-full-tabs/render.php
@@
-$tab_bg_active = isset( $attrs['tabBgActive'] ) ? (string) $attrs['tabBgActive'] : '';
-$tab_c_active  = isset( $attrs['tabColorActive'] ) ? (string) $attrs['tabColorActive'] : '';
+$tab_bg_active = isset( $attrs['tabBgActive'] ) ? (string) $attrs['tabBgActive'] : '';
+$tab_c_active  = isset( $attrs['tabColorActive'] ) ? (string) $attrs['tabColorActive'] : '';
+$tab_bg_hover  = isset( $attrs['tabBgHover'] ) ? (string) $attrs['tabBgHover'] : '';
+$tab_c_hover   = isset( $attrs['tabColorHover'] ) ? (string) $attrs['tabColorHover'] : '';
@@
-  $vars[] = '--hm-pft-tab-bg-hover:' . esc_attr( $tab_bg_hover );
-  $vars[] = '--hm-pft-tab-color-hover:' . esc_attr( $tab_c_hover );
+  if ( $tab_bg_hover ) $vars[] = '--hm-pft-tab-bg-hover:' . esc_attr( $tab_bg_hover );
+  if ( $tab_c_hover ) $vars[] = '--hm-pft-tab-color-hover:' . esc_attr( $tab_c_hover );
@@
-  <?php echo $link_open; ?>
+  <a href="<?php echo esc_url( $permalink ); ?>">
     <?php echo $img ? $img : ''; ?>
-  <?php echo $link_close; ?>
+  </a>
```

### B) Promo Grid: use responsive image markup
```diff
--- a/inc/hm-blocks/blocks/promo-grid/render.php
+++ b/inc/hm-blocks/blocks/promo-grid/render.php
@@
-$image_url = wp_get_attachment_image_url( (int) $tile['imageId'], 'full' );
+$image_html = wp_get_attachment_image( (int) $tile['imageId'], 'large', false, [
+  'loading' => 'lazy',
+  'class' => 'hmpro-pg__img',
+] );
@@
-echo '<img src="' . esc_url( $image_url ) . '" alt="' . esc_attr( $title ) . '" loading="lazy" />';
+echo $image_html;
```

### C) Hero Slider preload for LCP image
```diff
--- a/inc/hm-blocks/blocks/hero-slider/render.php
+++ b/inc/hm-blocks/blocks/hero-slider/render.php
@@
+if ( $resolved_url && $is_active ) {
+  echo '<link rel="preload" as="image" href="' . esc_url( $resolved_url ) . '">';
+}
```

---

## 7) Appendix: Key observations
- Full-bleed blocks use 100vw and negative margins; overflow clipping is handled in base styles but still risky on mobile if the browser includes scrollbars in viewport units.
- Some blocks (Promo Grid, Instagram Story) output images without explicit intrinsic sizing, which can cause CLS.
- Google Fonts are loaded dynamically and may become a render-blocking request on mobile if used widely.
