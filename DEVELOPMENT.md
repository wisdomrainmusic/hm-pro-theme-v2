# HM Pro Theme — Development Rules (Stability Guide)
## 2026-01-27 — External Performance & Risk Audit (Codex)

An external, report-only performance and risk audit was completed for HM Pro Theme
with a mobile-first Google PageSpeed Insights focus.

### Summary
- Theme architecture is modular, stable, and production-ready.
- No deprecated WordPress or WooCommerce APIs were identified.
- No critical security issues were found.
- Mobile performance is limited primarily by global asset loading strategy,
  not by architectural flaws.
- Hero slider and block system were identified as optional optimization targets,
  not blockers for release.

### Key Findings
- Some render-blocking CSS is globally enqueued (base, header, footer, Woo CSS).
- Google Fonts loading can impact mobile LCP without preconnect/self-hosting.
- Translation tool assets are injected globally and could be scoped further.
- One Customizer reset AJAX endpoint lacks an explicit capability check.
- No high-risk correctness or stability issues were identified.

### Decisions
- No code changes were merged as part of this audit.
- Optimization opportunities were logged for future iterations.
- Theme deemed technically ready for market release.

Status: Audit completed, no blocking issues.

2026-01-27 — HM Pro Theme Performance & Risk Audit (Report Only)

Scope: Mobile-first (Google PageSpeed Insights)
Branch status: Report only — no code changes merged to main

Summary

HM Pro Theme mimarisi modüler, stabil ve production-ready.

Mobil PageSpeed performansı 70+ bandına çıkarıldı; masaüstü 90+ seviyesinde.

Mevcut skor kayıpları mimari hatadan değil, global asset yükleme stratejisinden kaynaklanıyor.

Tema, Astra ve benzeri ticari temaların mobil performansını yakalamış veya aşmıştır.

Key Findings

Hero slider LCP öğesi mobilde ana darboğazdır; erişilebilirlik denemeleri sırasında layout riskleri oluşmuştur.

Block ve widget CSS/JS dosyalarının global enqueue edilmesi PSI’da “unused JS/CSS” uyarılarına yol açmaktadır.

CLS riski oluşturan alanlar sınırlıdır ve kritik seviyede değildir.

Accessibility skorları 90+ seviyesine çıkarılmıştır.

Decisions

Hero slider erişilebilirlik ve dot revizyonları ertelendi (layout regression riski).

Son erişilebilirlik diff’leri main branch’e alınmadı.

Tema stabilitesi korunarak optimizasyon süreci duraklatıldı.

Next Possible Steps (Deferred)

Hero slider için LCP odaklı ayrı sprint

Block bazlı conditional enqueue

Translate script lazy-load (menu trigger sonrası)

Status: No further action required at this stage.
Gutenberg Blocks: InnerBlocks + PHP Render Gotcha

When developing HM blocks that use InnerBlocks together with PHP-based rendering (render.php), it is critical to correctly serialize child blocks into post_content.

The Issue We Hit

HM Features Row uses InnerBlocks to contain hmpro/feature-item.

The block is rendered dynamically in PHP (render.php).

However, the block’s save() function returned null.

Result:

Inner blocks (hmpro/feature-item) were not reliably serialized into post_content.

In the editor, everything looked correct.

On the frontend, $content passed to render.php was empty → Feature Items did not appear.

Other HM blocks worked because they:

did not use InnerBlocks, or

stored all data in attributes instead of child blocks.

The Correct Pattern

If a block:

uses InnerBlocks, and

relies on PHP (render.php) for frontend markup,

then save() must serialize the inner blocks, even if the wrapper markup is generated in PHP.

Correct save() Implementation
save() {
	return <InnerBlocks.Content />;
}


Key point:

PHP renders the wrapper/layout.

Gutenberg must still store inner blocks so they are available as $content in PHP.

What NOT to Do
save() {
	return null;
}


Returning null is only safe when:

the block has no InnerBlocks, or

all frontend data is stored in attributes and rebuilt entirely in PHP.

Rule of Thumb

InnerBlocks + PHP render = always serialize InnerBlocks.Content.

If child blocks disappear on frontend but exist in editor, check save() first.

CSS, enqueue, cache, and widget logic are secondary until serialization is confirmed.

This guideline prevents editor ↔ frontend mismatches and ensures block content survives caching, templates, and dynamic rendering.
2026-01-26 – HM Product Tabs (Gutenberg) – Taxonomy Search Fix

Problem
HM Product Tabs block’unda kategori / etiket seçimi için kullanılan Gutenberg ComboboxControl, büyük taxonomy listelerinde preload (tüm terimleri baştan çekme) yaklaşımıyla stabil çalışmadı.
REST pagination, header stripping, cache ve script timing sorunları nedeniyle bazı kategoriler (örn. Sevgililer Günü) editörde hiç görünmüyordu.

Root Cause
Gutenberg, çok büyük taxonomy setleri için “tüm terimleri baştan yükle + client-side filtrele” modeline uygun değil.
Elementor widget’larının stabil olmasının sebebi, bu modeli kullanmaması.

Solution
Elementor-style server-side search (remote search) yaklaşımına geçildi:

Editörde kullanıcı yazdıkça (onFilterValueChange)

admin-ajax.php üzerinden

get_terms() + search parametresi ile

Sadece eşleşen terimler çekiliyor

Bu sayede:

Büyük taxonomy’lerde performans ve stabilite sağlandı

REST / pagination / header sorunları tamamen aşıldı

Block widget Elementor bağımlılığı olmadan kullanılabilir hale geldi

Decision / Guideline

Gutenberg block’larda ağır taxonomy / ürün seçimleri için preload yaklaşımı kULLANILMAYACAK

Bunun yerine her zaman server-side search (AJAX + search) tercih edilecek

Bu pattern, diğer HM block’lar için de referans kabul edilecek

Status
✅ Fixed
✅ Tested with large product category sets
✅ Elementor parity achieved without reverting to Elementor
Date

2026-01-26

Module

HM Product Tabs Block (inc/hm-blocks/blocks/product-tabs/)

Summary

Fixed missing product categories/tags in the "Select Term" searchable dropdown by ensuring the editor can always load the full taxonomy term list.

Details

Term fetching now:

Uses an admin-ajax endpoint (`hmpro_pft_get_terms`) to return all product categories/tags in a single response (Elementor-style; avoids REST header stripping / namespace blocking).
Keeps a REST fallback (`/hmpro/v1/terms`) plus wp/v2 pagination fallback for compatibility.
Uses a smaller REST payload via `_fields=id,name` (faster + less memory on large catalogs).

Orders results by name for a stable, predictable dropdown.

Treats "invalid page" REST responses as end-of-list in headerless pagination mode, instead of stopping early.

Backward Compatibility

✅ No breaking changes
✅ Only affects editor term dropdown fetching
✅ Front-end rendering unchanged

2026-01-25

Module

HM Hero Slider Block (inc/hm-blocks/blocks/hero-slider/)

Summary

Added Typography Presets dropdown to the Hero Slider block to allow fast application of curated font combinations (Title / Subtitle / Button) without manual input.

Details

Introduced a Typography Preset selector in the block inspector.

Presets apply font-family only via CSS variables (no layout or JS changes).

Existing manual typography controls (weight / size / mobile size) remain fully functional.

Selecting Reset (default) clears all typography-related attributes and falls back to theme defaults.

All changes are additive; no existing render logic or slider behavior was modified.

Presets Included

Modern Store (Inter / Poppins)

Editorial / Fashion (Inter / Playfair Display)

Soft Elegant (Lato / Poppins)

Signature Brand – Handwritten (Inter / Dancing Script)

Technical Notes

Presets map to block attributes and are rendered via CSS variables.

Fonts must be loaded by the theme or site (no automatic font loading included).

Asset versions bumped to ensure cache refresh.

Backward Compatibility

✅ Fully backward compatible
✅ No breaking changes
✅ Existing pages remain visually unchanged unless a preset is selected

Bu not:

Demo / müşteri tesliminde “neden böyle yapıldı”yı açıklar

İleride font loader eklenirse referans olur

Temayı devralan başka biri için altın değerinde
📅 2026-01-24 — HM Hero Slider Major Update (Gutenberg)
Summary

HM Hero Slider block was significantly improved for Gutenberg to match and exceed previous Elementor capabilities, with a strong focus on responsive behavior, performance, and editor UX. Desktop behavior is now stable and finalized; mobile behavior follows a clear and simplified rule set.

Key Changes
1. Slide Count Extension

Slide limit increased from 1–6 to 1–12.

Editor UI and logic updated accordingly.

Asset version bumped to prevent stale editor cache issues.

2. Image Fit Control

Added Image Fit option:

Cover (fill) – default, fills frame (may crop)

Contain (show full image) – optional, may letterbox

Default behavior remains unchanged for backward compatibility.

3. Per-Device Images (Elementor-like)

Each slide now supports separate images per device:

Desktop image (required)

Tablet image (optional, falls back to desktop)

Mobile image (optional, falls back to tablet → desktop)

Editor preview switches images automatically when toggling Desktop / Tablet / Mobile view.

Front-end renders correct image per breakpoint without breaking existing slides.

4. Mobile & Tablet Layout Optimization

Mobile and tablet layouts were refined via CSS only (no JS/PHP logic changes):

Hero height capped using viewport units on smaller screens.

Improved padding, typography scaling, and control sizing.

Prevents “overly tall” hero sections on phones.

5. Final Responsive Strategy (Locked Decision)

To avoid over-complexity and maintain long-term stability:

Desktop + Tablet

Use the same 16:9 image (e.g. 1920×1080, 1600×900).

Mobile

Use 9:16 (portrait / reels-style) images (e.g. 1080×1920).

Image fit should remain Cover.

Mobile height should be kept compact (≈56–60vh) to avoid excessive vertical space.

Square (1:1) images were tested but rejected due to poor visual balance.
9:16 provides the most natural and modern mobile experience.

Notes

All changes were implemented without breaking existing content.

Desktop behavior is considered final and stable.

Mobile behavior is intentionally simplified to reduce maintenance and avoid over-engineering.

This block now fully replaces the previous Elementor-based hero workflow.
## 2026-01-24 – Woo Variation Gallery Operational Rule (Variation Image Required)

Decision:
To ensure stable variation image switching and avoid edge-case blank gallery states,
we enforce an operational rule: every product variation must have its own featured
image set (variation image is required).

Context:
A gallery “blank/empty” state was observed only when a variation had no image and
relied on WooCommerce fallback behavior. When all variations have images, the
frontend behavior remains stable across products and does not require additional
fallback hacks.

Rationale:
This approach minimizes regression risk and keeps the system predictable for clients
using the theme/importer in production.

Action:
Update product data entry/import guidelines to require a featured image for each
variation. Custom variation galleries remain optional.

## 2026-01-24 – Woo Variation Gallery Race Condition Fix

Issue:
Variation change caused a brief correct image display followed by
an immediate revert to the parent product image.

Root Cause:
Custom frontend script (woo-variation-gallery.js) was restoring the
parent gallery whenever a variation had no custom hmpro_gallery,
overriding WooCommerce’s native variation image handling and causing
a race condition.

Resolution:
Introduced state-based logic in the frontend:
- Parent gallery is restored only if a custom variation gallery
  was previously applied.
- Variations without a custom gallery now rely entirely on
  WooCommerce default behavior.

Architectural Decision:
- Variation images must be set at import time (_thumbnail_id).
- Frontend must not compensate for missing variation images.
- WooCommerce default fallback is trusted.

Result:
Stable variation image switching without visual glitches.
This theme is designed to be updated frequently. To prevent regressions during updates, follow the rules below.
These rules are based on real production incidents (double-load, PHP 8.1+ deprecations, admin callback fatals).

---

## 1) One Entry Point for Tools

All internal tools must be loaded ONLY via:

- `inc/tools/tools-loader.php`

Do not include tool files from multiple places (functions.php, admin files, block files, etc.).

**Rule**
- If you add a new tool, register it in `tools-loader.php` (single source of truth).
- Avoid additional `require/include` calls elsewhere.

---

## 2) Never `return;` from a Tool File

Tool files often define:
- Classes
- Admin page callbacks (render functions)
- Helper functions used by admin menus

If you `return;` early, helper callbacks will not exist, and WordPress will fatal when the menu tries to call them.

**BAD**
```php
if ( class_exists( 'My_Tool_Class', false ) ) {
  return; // breaks helper functions defined later
}
```

**GOOD**
```php
if ( ! class_exists( 'My_Tool_Class', false ) ) {
  class My_Tool_Class { /* ... */ }
}
// helper functions remain available below
```

---

## 3) Make Loaders Idempotent

Some hosts/bootstrap orders can evaluate loader files more than once.
All loader files should be safe to run multiple times.

**Rule**

Add a single "loaded" constant guard at the top of loaders:

```php
if ( defined( 'HMPRO_SOMETHING_LOADED' ) ) {
  return;
}

define( 'HMPRO_SOMETHING_LOADED', true );
```

And

Wrap any `define()` calls with `if ( ! defined( ... ) )`.

---

## 4) Admin Menus: Do Not Pass `null` parent_slug

PHP 8.1+ can emit deprecations through WP core when `add_submenu_page()` gets null as parent slug.
This can cascade into `plugin_basename()` / `wp_normalize_path()` warnings/deprecations.

**BAD**
```php
add_submenu_page( null, 'Edit', 'Edit', 'manage_options', 'hm-edit', 'render_cb' );
```

**GOOD (Hidden page pattern)**
```php
add_submenu_page( 'hmpro-theme', 'Edit', 'Edit', 'manage_options', 'hm-edit', 'render_cb' );
remove_submenu_page( 'hmpro-theme', 'hm-edit' ); // still accessible via URL
```

---

## 5) Callback Safety (Admin/AJAX/REST)

Any callback referenced by WordPress must exist at the time WP calls it:

- Admin menu page callbacks
- AJAX handlers
- REST endpoints

**Rule**

Ensure callbacks are defined unconditionally (not behind early returns).

Optionally add defensive checks before registering:

```php
if ( function_exists( 'hmpro_render_page' ) ) {
  add_submenu_page( ..., 'hmpro_render_page' );
}
```

---

## 6) PHP 8.1+ Null-Safety Rules

Avoid passing null into string functions:

- `strpos()`, `str_replace()`, `trim()`, etc.

Use `?? ''` or cast to string:

```php
$value = (string) ( $value ?? '' );
```

When reading from options/meta:

```php
$x = get_option( 'my_option', '' );
$x = is_string( $x ) ? $x : '';
```

---

## 7) Textdomain Loading (WP 6.7+)

WP 6.7+ can show `_load_textdomain_just_in_time` warnings if translations are triggered too early.

**Rule**

Load theme textdomain on `after_setup_theme` with early priority (0).

Avoid calling `__()` / `_e()` before `after_setup_theme` when possible.

---

## 8) Additive Changes Only (Regression-Proof)

When fixing bugs:

- Do not relocate or remove working functions unless necessary.
- Prefer additive patches (guards, additional conditionals, wrappers).
- Keep function names and existing hooks stable.

---

## 9) Admin Menu Callback Guarantee (Critical)

Admin menu callbacks must be safe even if tool files are not loaded yet due to host/bootstrap order.
On some environments, WordPress can attempt to execute menu callbacks while a tool file failed to load
or was skipped. If a callback is missing, WP will fatal.

**Mandatory Rules**
1. Ensure tools are loaded before registering admin menu pages:

   In the admin menu registration file (e.g. `inc/admin/admin-menu.php`), require_once the tools loader.

   The tools loader must be idempotent (Rule #3), so requiring it is safe.

2. Never bind `add_submenu_page()` to a function that might not exist:

   Wrap callback binding with `function_exists()`.

   Provide a fallback renderer to prevent fatal crashes.

**Recommended Pattern**
```php
// 1) Ensure tool callbacks are available (idempotent loader).
require_once HMPRO_PATH . '/inc/tools/tools-loader.php';

// 2) Fallback to prevent fatal crashes.
function hmpro_render_missing_tool_page( $title, $details = '' ) {
  echo '<div class="wrap">';
  echo '<h1>' . esc_html( $title ) . '</h1>';
  echo '<div class="notice notice-error"><p><strong>Tool page callback is missing.</strong></p>';

  if ( $details ) {
    echo '<p>' . esc_html( $details ) . '</p>';
  }

  echo '</div></div>';
}

// 3) Safe callback binding.
$cb = function_exists( 'hmpro_render_category_importer_page' )
  ? 'hmpro_render_category_importer_page'
  : function () {
    hmpro_render_missing_tool_page(
      'Category Importer',
      'Missing: hmpro_render_category_importer_page'
    );
  };

add_submenu_page(
  'hmpro-theme',
  'Category Importer',
  'Category Importer',
  'manage_options',
  'hmpro-category-importer',
  $cb
);
```

**Goal**
- Admin must never crash.
- Missing tool callbacks should show an error notice instead of a fatal error.

---

## Mini Smoke Test Checklist (3–10 minutes)

Run this after every update/commit (especially on a clean WP install).

### A) Clean Install / Activation

Install a clean WordPress instance.

Enable:

- `WP_DEBUG = true`
- `WP_DEBUG_LOG = true`
- `WP_DEBUG_DISPLAY = true` (optional)

Activate HM Pro Theme.

Confirm:

- No fatal errors
- Admin loads

### B) Admin Pages

Go to: Appearance → Themes

Open theme menus (HM Pro Theme, HM Mega Menu, Tools pages).

Verify tool pages load without callback fatals.

### C) Frontend

Visit homepage.

Confirm header/footer render.

Confirm no console errors (basic check).

### D) Logs

Check `/wp-content/debug.log`

Must NOT contain:

- Fatal error
- Deprecated
- Warning
- Notice (theme-caused)

If the log contains only plugin/host notices (not theme paths), note and ignore.

### Quick Rule of Thumb

If a log line contains:

- `wp-content/themes/hm-pro-theme-main/` → OUR issue (must fix)
- `wp-content/plugins/` → plugin issue
- `wp-content/mu-plugins/` → hosting/mandatory plugin issue
