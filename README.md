✅ Session Check: Header, Presets \& Header Banner Mobile Optimization (Fixed17)



This update finalizes a major stability and UX improvement pass across header styling, preset behavior, and header banner responsiveness.



Header \& Preset System



Header Top Bar and Footer colors are now fully integrated with Presets.



Presets apply Top Bar / Footer colors only if Customizer overrides are empty.



Customizer includes a Reset button to clear Header/Footer color overrides and return to preset defaults.



Search input text and placeholder colors are configurable for Top Bar to ensure readability on dark backgrounds.



Desktop-only “Login / Register” CTA removed; CTA is now managed via Header Builder components.



Mobile hamburger menu CTA remains intact.



Header Background Banner (Desktop, Tablet, Mobile)



Desktop banner content supports extended X/Y positioning up to ±1200px.



Mobile and Tablet:



Banner content X/Y positioning is disabled to prevent layout breakage.



Content is anchored to the top (no vertical centering shift).



Separate Mobile banner content scale setting added.



Mobile/Tablet banner content top offset added for fine alignment control.



Optional mobile banner hide toggle available.



Mobile banner height is configurable independently from desktop.



Result



Banner content no longer shifts unpredictably on mobile/tablet when scaling.



Buttons remain visible across all breakpoints.



Preset + Customizer interaction is predictable and fail-safe.



Overall behavior now matches premium theme standards (Astra / Kadence-level UX).



Status



System stable.



No breaking changes to existing layouts.



Ready for demo packaging and further feature development.



✅ CHECKPOINT – Header \& Banner Refactor (Jan 2026)
Summary

This checkpoint finalizes a major refactor around the header, transparent header logic, and header banner behavior.
The previous scroll-based transparent header approach was intentionally removed in favor of a clean, Customizer-driven Header Background Banner system that works independently of Elementor.

What Was Tried

Astra-like transparent header with menu overlay on the banner

Scroll-based state switching (transparent → opaque)

Header overlay logic dependent on page structure

Result

These approaches introduced unnecessary complexity and unclear UX behavior in a custom theme context.

Final Decision

The transparent header scroll logic was fully removed.

Instead, the theme now uses:

A Customizer-controlled Header Background Banner (image or video)

Fixed, predictable header layout

No scroll-based header state changes

This results in a simpler, more stable, and more maintainable architecture.

Current System (Active)

Header Background Banner is rendered within the header layout

Configurable from Customizer

Elementor is not required

Header remains consistent across scroll states

Customizer Features

Header background image

Optional background video (mp4 / webm)

Adjustable banner height (px)

Overlay darkness control

Optional title \& description fields

Mobile-safe behavior (video muted / optional fallback image)

Removed / Deprecated

Scroll-to-opaque header option

Scroll offset / threshold controls

Header scroll JS logic

Transparent header color state switching

These options were removed because they produced no meaningful or reliable UX benefit in the current theme architecture.

Why This Matters

Reduces CSS/JS complexity

Prevents layout edge cases

Makes demos more predictable

Keeps the theme flexible for Elementor and non-Elementor users

Establishes a solid base for future banner + CTA enhancements

Next Possible Enhancements

Banner content alignment (center / left)

CTA button support in the header banner

Per-page banner override (optional)

Preset banner styles for demos
HM Pro Theme — Surgical Checkpoint (Developer)
Repository snapshot
Theme root: hm-pro-theme-main/
Version constant: HMPRO\_VERSION = 0.1.0 (functions.php)
Entry points:
functions.php loads core + engine + admin modules
header.php renders either Builder Header or Legacy Header fallback
footer.php (builder-driven)
High-level architecture

1. Core
   inc/core/setup.php
   Theme supports: custom-logo, title-tag, post-thumbnails, responsive-embeds, woocommerce, wc gallery features
   Registers nav menu locations:
   primary, topbar, footer, mobile\_menu
   legacy: hm\_primary, hm\_footer
   Builder region helpers:
   hmpro\_get\_builder\_regions()
   hmpro\_has\_builder\_layout(), hmpro\_header\_builder\_has\_layout()
   inc/core/enqueue.php
   Enqueues style.css via get\_stylesheet\_uri() (hmpro-style)
   NOTE: functions.php also enqueues base/header/footer/mega-menu/woo styles.
2. Admin UI
   inc/admin/admin-menu.php
   Adds parent: hmpro-theme
   Subpages: Presets, Header Builder, Footer Builder, Mega Menu Builder, Importers, etc.
   assets/admin-builder.js + assets/admin-builder.css
   Builder admin UI (drag/drop, component settings, save)
   inc/admin/title-visibility.php
   Registers post meta: \_hmpro\_hide\_title (REST enabled)
   Classic editor metabox + Gutenberg document panel toggle
   Adds body\_class: hmpro-hide-title
   CSS fallback in assets/css/base.css
3. Builder engine (Header/Footer)
   inc/engine/builder-storage.php
   Options:
   hmpro\_header\_layout
   hmpro\_footer\_layout
   Schema:
   schema\_version: 1
   regions: { region\_key => \[rows] }
   Sanitization:
   Allowlisted component types + settings keys
   URL normalization for social URLs
   SVG sanitization via wp\_kses for custom icons
   inc/engine/builder-renderer.php
   Hooks used by templates:
   do\_action('hmpro/header/render\_region', region\_key)
   do\_action('hmpro/footer/render\_region', region\_key)
   Component renderers:
   hmpro\_builder\_comp\_logo/menu/search/cart/button/html/spacer/footer\_menu/footer\_info/social/social\_icon\_button
   HTML block uses do\_shortcode() + custom kses allowlist (scripts blocked)
   Social icon preset loader:
   hmpro\_load\_social\_svg\_preset() searches assets/icon/social/ then assets/icons/social/
4. Mega Menu engine
   inc/engine/mega-menu-library.php
   Registers CPT: hm\_mega\_menu
   Stores layout/meta:
   \_hmpro\_mega\_layout
   \_hmpro\_mega\_settings
   inc/engine/mega-menu-menuitem-meta.php
   Adds dropdown field to nav menu items
   Saves binding meta: \_hmpro\_mega\_menu\_id (nav\_menu\_item post meta)
   Frontend injection via walker\_nav\_menu\_start\_el
   Adds li classes: hmpro-li-has-mega + hmpro-mega-id-{id}
   assets/js/mega-menu.js
   Interaction mode can be set via customizer:
   theme\_mod: hmpro\_mega\_menu\_interaction (hover|click)
   body\_class adds hmpro-mega-click when click mode enabled
5. Presets + CSS engine
   inc/engine/presets.php
   Options:
   hmpro\_presets
   hmpro\_active\_preset
   inc/engine/css-engine.php
   Prints CSS variables (accent, contrast, etc.)
   inc/engine/typography.php
   Typography utilities (variable application)
6. Embedded tools (inside theme)
   inc/tools/tools-loader.php
   Embeds modules:
   category-importer
   slug-menu-builder
   product-importer
   hm-menu-controller
   hm-basic-ceviri-inline
   Moves HM Basic Translate page under HM Pro Theme menu.
7. WooCommerce tweaks
   inc/woocommerce/gallery-tweaks.php
   inc/woocommerce/checkout-tweaks.php
   Template behavior
   Header (header.php)
   If builder layout exists → renders builder regions + adds two extra UI elements:
   Desktop persistent account CTA (absolute positioned)
   Mobile hamburger toggle + right-side drawer
   If builder layout missing → legacy header fallback (hm\_primary menu location).
   Mobile drawer
   Markup is always present when builder header is active.
   Visibility controlled by CSS media query (max-width: 768px) and JS (assets/js/mobile-header.js).
   Critical issues / risks (actionable)
   Text domain inconsistency
   style.css declares Text Domain: hm-pro-theme
   load\_theme\_textdomain() is called with 'hmpro'
   Strings use both 'hmpro' and 'hm-pro-theme'
   Impact: translation and string management becomes fragmented.
   Fix: pick ONE domain (recommend: hm-pro-theme) and refactor.
   Language consistency
   Mixed Turkish/English strings across admin + frontend.
   Impact: conflicts with “English-only site/admin” preference; also makes demo packaging harder.
   Fix: normalize UI strings and defaults (placeholder, button labels, menu labels).
   Hardcoded My Account path
   header.php uses home\_url('/hesabim/')
   Impact: breaks if slug differs, or on non-TR installs.
   Fix:
   if WooCommerce active: get\_permalink( wc\_get\_page\_id('myaccount') )
   else: fallback to wp\_login\_url() / wp\_registration\_url()
   Missing preset asset
   LinkedIn SVG is referenced in allowed presets but assets/icon/social/linkedin.svg is absent.
   Impact: fallback badge appears (not fatal) but inconsistent.
   Fix: add linkedin.svg or remove from preset list.
   Duplicate conditional branch in builder-storage sanitization
   builder-storage.php contains duplicated menu\_id/depth/width/height handling.
   Impact: not a security issue; increases maintenance risk.
   Fix: clean up branches and add unit-ish tests (payload samples).
   Duplicate style enqueues
   inc/core/enqueue.php enqueues style.css; functions.php enqueues base/header/footer/mega-menu/woo styles.
   Impact: not fatal; but can cause override confusion and extra requests.
   Fix: either merge style.css into base.css or keep style.css minimal and document CSS layering.
   Recommended “next commit” plan (surgical, low risk)
   Normalize text domain + strings
   Set load\_theme\_textdomain('hm-pro-theme')
   Replace \_\_('...', 'hmpro') usages
   Convert Turkish strings to English (or wrap with translations consistently)
   Fix My Account link resolution
   Create helper:
   hmpro\_get\_account\_url()
   Use WooCommerce lookup if available, fallback otherwise.
   Add linkedin.svg or remove LinkedIn from presets
   Refactor builder-storage sanitizer
   Remove duplicate branches
   Keep allowlists identical
   Add sample payload tests (even as PHP arrays in a dev-only file or wp-cli command)
   Export/import readiness notes
   If you want a demo installer to fully reproduce a site, the following must be packaged and remapped:
   Options:
   hmpro\_header\_layout
   hmpro\_footer\_layout
   hmpro\_presets
   hmpro\_active\_preset
   CPT + meta:
   hm\_mega\_menu posts + \_hmpro\_mega\_layout + \_hmpro\_mega\_settings
   Nav menu bindings:
   nav\_menu\_item meta: \_hmpro\_mega\_menu\_id
   IMPORTANT: mega menu IDs change after import; must remap old->new.
   Best remap key: mega menu slug (post\_name) or a stable custom GUID meta.
