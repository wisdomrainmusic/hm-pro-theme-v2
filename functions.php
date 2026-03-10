<?php
// phpcs:ignoreFile
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PHP 8.1+ Deprecation Guard (Admin only)
 *
 * Some environments emit "Deprecated: strpos()/str_replace() passing null..." from WP core
 * when a theme/plugin passes null into sanitizers. This does NOT break the site, but clutters
 * admin screens when WP_DEBUG_DISPLAY is enabled.
 *
 * We suppress ONLY these two noisy deprecations in wp-admin output and log a backtrace
 * to debug.log so we can pinpoint and permanently fix the culprit safely.
 */
if ( is_admin() && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
	set_error_handler(
		function ( $errno, $errstr, $errfile, $errline ) {
			if ( E_DEPRECATED !== $errno ) {
				return false; // let PHP handle others
			}

			$msg = (string) $errstr;
			// Only target the two known noisy deprecations.
			if ( false === strpos( $msg, 'strpos()' ) && false === strpos( $msg, 'str_replace()' ) ) {
				return false;
			}

			// Log an actionable trace (requires WP_DEBUG_LOG).
			if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
				$trace = '';
				if ( function_exists( 'wp_debug_backtrace_summary' ) ) {
					// Use pretty=true for readable frames; normalize to a string below.
					$trace = wp_debug_backtrace_summary( null, 0, true );
				}
				$trace = is_array( $trace ) ? implode( ' | ', array_map( 'strval', $trace ) ) : (string) $trace;
				error_log(
					'[HMPRO PHP8.1 Deprecated Guard] ' . $msg .
					' | at ' . $errfile . ':' . $errline .
					( $trace !== '' ? ' | trace: ' . $trace : '' )
				);
			}

			return true; // handled: suppress from screen
		}
	);
}

/**
 * REST JSON Output Guard
 * Prevents stray PHP output (warnings/notices/whitespace/BOM) from corrupting REST JSON responses,
 * which breaks Gutenberg with: "Response is not a valid JSON response."
 */
if (
	( defined( 'REST_REQUEST' ) && REST_REQUEST ) ||
	( isset( $_SERVER['REQUEST_URI'] ) && false !== strpos( (string) $_SERVER['REQUEST_URI'], '/wp-json/' ) )
) {
	if ( ! ob_get_level() ) {
		ob_start();
	}
	add_filter( 'rest_pre_serve_request', function ( $served ) {
		if ( ob_get_level() ) {
			// Discard anything printed before REST served JSON.
			ob_clean();
		}
		return $served;
	}, 0 );
}

/**
 * REST JSON Guard (Dev/Local Safety Net)
 *
 * On some local stacks (XAMPP, display_errors=On), ANY PHP warning/notice/output
 * printed before REST responses will break the JSON payload and Gutenberg shows:
 * "Yayınlanamadı. Yanıt geçerli bir JSON yanıtı değil."
 *
 * This guard buffers output for REST requests and discards any accidental output
 * right before WP serves the REST response.
 *
 * NOTE: This does NOT hide real fatal errors; it only prevents notices/warnings
 * or stray echoes/whitespace from corrupting JSON responses.
 */
add_action( 'init', function () {
	$is_rest = ( defined( 'REST_REQUEST' ) && REST_REQUEST );
	if ( ! $is_rest && isset( $_SERVER['REQUEST_URI'] ) ) {
		// Fallback for edge cases where REST_REQUEST isn't defined early enough.
		$is_rest = ( false !== strpos( (string) $_SERVER['REQUEST_URI'], '/wp-json/' ) );
	}
	if ( ! $is_rest ) {
		return;
	}

	// Start a buffer as early as possible for this request.
	if ( ! ob_get_level() ) {
		ob_start();
	}
}, 0 );

add_filter( 'rest_pre_serve_request', function ( $served, $result, $request, $server ) {
	// If anything was echoed/printed (warnings, notices, BOM, whitespace), clean it.
	if ( ob_get_level() ) {
		$garbage = ob_get_contents();
		if ( is_string( $garbage ) && $garbage !== '' ) {
			// Optional: keep a trace in debug.log when WP_DEBUG_LOG is enabled.
			if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
				error_log( '[HMPRO REST GUARD] Stray output discarded (len=' . strlen( $garbage ) . ')' );
			}
			ob_clean();
		}
	}
	return $served;
}, 0, 4 );

define( 'HMPRO_VERSION', '0.1.0' );
define( 'HMPRO_PATH', get_template_directory() );
define( 'HMPRO_URL', get_template_directory_uri() );

/**
 * Asset version helper (cache-bust)
 * Uses filemtime() so updated themes always bypass stale CSS/JS cache on legacy sites.
 */
function hmpro_asset_ver( $relative_path ) {
	$relative_path = ltrim( (string) $relative_path, '/' );
	$path          = trailingslashit( HMPRO_PATH ) . $relative_path;
	if ( file_exists( $path ) ) {
		return (string) filemtime( $path );
	}
	return (string) HMPRO_VERSION;
}

// Theme upgrade/migration routine (one-time when version increases)
require_once HMPRO_PATH . '/inc/core/upgrade.php';

require_once HMPRO_PATH . '/inc/core/setup.php';
require_once HMPRO_PATH . '/inc/core/widgets.php';
require_once HMPRO_PATH . '/inc/core/enqueue.php';
require_once HMPRO_PATH . '/inc/compat/mn-overflow-footer-gap.php';
require_once HMPRO_PATH . '/inc/core/customizer.php';
require_once HMPRO_PATH . '/inc/core/template-tags.php';

// WooCommerce tweaks (single product gallery UX).
require_once HMPRO_PATH . '/inc/woocommerce/gallery-tweaks.php';
require_once HMPRO_PATH . '/inc/woocommerce/hm-pvi-youtube-normalize.php';
require_once HMPRO_PATH . '/inc/woocommerce/hm-pvi-modal-bridge.php';
require_once HMPRO_PATH . '/inc/woocommerce/sticky-add-to-cart.php';
// WooCommerce checkout UX (ship-to-different-address + accordion).
require_once HMPRO_PATH . '/inc/woocommerce/checkout-tweaks.php';
// WooCommerce extensions.
require_once HMPRO_PATH . '/inc/woo/variation-long-desc.php';
require_once HMPRO_PATH . '/inc/woo/variation-multi-gallery.php';
require_once HMPRO_PATH . '/inc/woo/product-media-standard.php';

require_once HMPRO_PATH . '/inc/engine/presets.php';
require_once HMPRO_PATH . '/inc/engine/css-engine.php';
require_once HMPRO_PATH . '/inc/engine/import-export.php';
require_once HMPRO_PATH . '/inc/engine/typography.php';

// Integrations (optional).
// NOTE: We intentionally do NOT write into Elementor Kit meta/settings.
// Doing so is version-sensitive and can break Elementor CSS generation.
// Instead, we bridge typography via CSS variables + safe selectors.
require_once HMPRO_PATH . '/inc/engine/builder-storage.php';
require_once HMPRO_PATH . '/inc/engine/builder-renderer.php';
require_once HMPRO_PATH . '/inc/engine/mega-menu-library.php';
require_once HMPRO_PATH . '/inc/engine/mega-menu-canvas.php';
require_once HMPRO_PATH . '/inc/engine/mega-menu-menuitem-meta.php';

require_once HMPRO_PATH . '/inc/tools/tools-loader.php';

// HM Pro Gutenberg blocks (landing-page only). Woo templates are untouched.
require_once HMPRO_PATH . '/inc/hm-blocks/hm-blocks.php';

require_once HMPRO_PATH . '/inc/admin/admin-menu.php';
require_once HMPRO_PATH . '/inc/admin/actions.php';
require_once HMPRO_PATH . '/inc/admin/mega-menu-ajax.php';
require_once HMPRO_PATH . '/inc/admin/presets-page.php';
require_once HMPRO_PATH . '/inc/admin/preset-edit.php';
require_once HMPRO_PATH . '/inc/admin/builder-pages.php';
require_once HMPRO_PATH . '/inc/admin/title-visibility.php';

add_action( 'admin_enqueue_scripts', function () {
	wp_enqueue_style(
		'hmpro-admin',
		HMPRO_URL . '/assets/admin.css',
		[],
		hmpro_asset_ver( 'assets/admin.css' )
	);

	wp_enqueue_script(
		'hmpro-admin',
		HMPRO_URL . '/assets/admin.js',
		[],
		hmpro_asset_ver( 'assets/admin.js' ),
		true
	);

	// Builder-specific assets (only on builder screens).
	$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
	if ( in_array( $page, [ 'hmpro-header-builder', 'hmpro-footer-builder', 'hmpro-mega-menu-builder' ], true ) ) {
		// ✅ Media Library is needed for builder image pickers (Mega Menu + Footer Image).
		if ( in_array( $page, [ 'hmpro-mega-menu-builder', 'hmpro-footer-builder' ], true ) ) {
			wp_enqueue_media();
		}

		wp_enqueue_style(
			'hmpro-admin-builder',
			HMPRO_URL . '/assets/admin-builder.css',
			[ 'hmpro-admin' ],
			filemtime( HMPRO_PATH . '/assets/admin-builder.css' )
		);

		wp_enqueue_script(
			'hmpro-admin-builder',
			HMPRO_URL . '/assets/admin-builder.js',
			[ 'hmpro-admin' ],
			filemtime( HMPRO_PATH . '/assets/admin-builder.js' ),
			true
		);
	}
} );

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style(
		'hmpro-base',
		HMPRO_URL . '/assets/css/base.css',
		[],
		hmpro_asset_ver( 'assets/css/base.css' )
	);

	wp_enqueue_style(
		'hmpro-header',
		HMPRO_URL . '/assets/css/header.css',
		[ 'hmpro-base' ],
		hmpro_asset_ver( 'assets/css/header.css' )
	);

	wp_enqueue_style(
		'hmpro-footer',
		HMPRO_URL . '/assets/css/footer.css',
		[ 'hmpro-base' ],
		hmpro_asset_ver( 'assets/css/footer.css' )
	);

	/**
	 * Conditional assets (mobile-first performance):
	 * - Mega menu assets: only when a Primary menu is assigned (filterable).
	 * - Mobile header JS: only when a mobile menu exists OR header builder layout is present (filterable).
	 */
	$should_enqueue_mega = has_nav_menu( 'primary' ) || has_nav_menu( 'hm_primary' ) || has_nav_menu( 'topbar' );
	$should_enqueue_mega = (bool) apply_filters( 'hmpro/should_enqueue_mega_menu_assets', $should_enqueue_mega );
	if ( $should_enqueue_mega ) {
		wp_enqueue_style(
			'hmpro-mega-menu',
			HMPRO_URL . '/assets/css/mega-menu.css',
			[ 'hmpro-base' ],
			hmpro_asset_ver( 'assets/css/mega-menu.css' )
		);

		wp_enqueue_script(
			'hmpro-mega-menu',
			get_template_directory_uri() . '/assets/js/mega-menu.js',
			array(),
			hmpro_asset_ver( 'assets/js/mega-menu.js' ),
			true
		);
	}

	/*
	 * Ensure mobile header drawer JS always loads when needed.
	 * This fixes hamburger menu not opening on some installs.
	 */
	$should_enqueue_mobile_header =
		has_nav_menu( 'mobile_menu' ) ||
		has_nav_menu( 'primary' ) ||
		has_nav_menu( 'hm_primary' ) ||
		has_nav_menu( 'topbar' );
	if ( ! $should_enqueue_mobile_header && function_exists( 'hmpro_has_builder_layout' ) ) {
		$should_enqueue_mobile_header = (bool) hmpro_has_builder_layout( 'header' );
	}
	$should_enqueue_mobile_header = (bool) apply_filters( 'hmpro/should_enqueue_mobile_header_js', $should_enqueue_mobile_header );
	if ( $should_enqueue_mobile_header ) {
		// Mobile header hamburger + drawer (right side)
		wp_enqueue_script(
			'hmpro-mobile-header',
			get_template_directory_uri() . '/assets/js/mobile-header.js',
			array(),
			hmpro_asset_ver( 'assets/js/mobile-header.js' ),
			true
		);
	}

	
	// Lightweight accessibility fixes (list structure normalization, etc.)
	wp_enqueue_script(
		'hmpro-a11y',
		get_template_directory_uri() . '/assets/js/a11y.js',
		array(),
		hmpro_asset_ver( 'assets/js/a11y.js' ),
		true
	);

if ( class_exists( 'WooCommerce' ) ) {
		wp_enqueue_style(
			'hmpro-woo',
			HMPRO_URL . '/assets/css/woocommerce.css',
			[ 'hmpro-base' ],
			hmpro_asset_ver( 'assets/css/woocommerce.css' )
		);
	}
} );

add_action( 'customize_register', function ( $wp_customize ) {
	$section_id = 'hmpro_header_settings';

	if ( ! $wp_customize->get_section( $section_id ) ) {
		$wp_customize->add_section( $section_id, [
			'title'    => __( 'Header & Navigation', 'hm-pro-theme' ),
			'priority' => 30,
		] );
	}

	$wp_customize->add_setting( 'hmpro_mega_menu_interaction', [
		'default'           => 'hover',
		'sanitize_callback' => function ( $value ) {
			return in_array( $value, [ 'hover', 'click' ], true ) ? $value : 'hover';
		},
		'transport'         => 'refresh',
	] );

	$wp_customize->add_control( 'hmpro_mega_menu_interaction', [
		'label'   => __( 'Mega Menu Interaction Mode', 'hm-pro-theme' ),
		'section' => $section_id,
		'type'    => 'radio',
		'choices' => [
			'hover' => __( 'Hover (Default)', 'hm-pro-theme' ),
			'click' => __( 'Click to Open', 'hm-pro-theme' ),
		],
	] );
} );

add_filter( 'body_class', function ( $classes ) {
	$mode = get_theme_mod( 'hmpro_mega_menu_interaction', 'hover' );

	if ( 'click' === $mode ) {
		$classes[] = 'hmpro-mega-click';
	}

	return $classes;
} );

// Header Background Banner helpers.
add_filter( 'body_class', function ( $classes ) {
	if ( function_exists( 'hmpro_header_bg_banner_is_enabled' ) && hmpro_header_bg_banner_is_enabled() ) {
		$classes[] = 'hmpro-hb-active';
	}
	return $classes;
}, 20 );

add_action( 'wp_enqueue_scripts', function () {
	// Slider script (only when enabled).
	if ( function_exists( 'hmpro_header_bg_banner_is_enabled' ) && hmpro_header_bg_banner_is_enabled() ) {
		$slider_enabled = (int) get_theme_mod( 'hmpro_hb_slider_enable', 0 ) === 1;
		$use_video      = (int) get_theme_mod( 'hmpro_hb_use_video', 0 ) === 1;
		if ( $slider_enabled && ! $use_video ) {
			wp_enqueue_script(
				'hmpro-hb-slider',
				get_template_directory_uri() . '/assets/js/hb-slider.js',
				[],
				hmpro_asset_ver( 'assets/js/hb-slider.js' ),
				true
			);
		}
	}
}, 30 );

/**
 * LiteSpeed Cache - Front page purge helper
 *
 * Some setups don't reliably purge the cached homepage when Gutenberg blocks
 * are edited (especially block widgets / global sections). This creates an
 * editor ↔ frontend mismatch where changes look correct in wp-admin but the
 * public homepage remains stale.
 *
 * We only trigger targeted purges (home URL + front-page permalink when relevant).
 * If LiteSpeed Cache plugin is not active, these actions are no-ops.
 */
function hmpro_litespeed_purge_home_if_needed( $post_id = 0 ) {
	// Always purge the site homepage URL (covers "latest posts" homepage too).
	$home_url = home_url( '/' );
	do_action( 'litespeed_purge_url', $home_url );

	// If a specific post/page is provided, purge it and also purge homepage if it's the front page.
	$post_id = (int) $post_id;
	if ( $post_id > 0 ) {
		do_action( 'litespeed_purge_post', $post_id );

		$front_id = (int) get_option( 'page_on_front' );
		if ( $front_id > 0 && $front_id === $post_id ) {
			$front_url = get_permalink( $front_id );
			if ( $front_url ) {
				do_action( 'litespeed_purge_url', $front_url );
			}
		}
	}
}

// Purge homepage when a published page is updated (covers block edits).
add_action( 'save_post', function ( $post_id, $post, $update ) {
	if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
		return;
	}
	if ( ! $update ) {
		return;
	}
	if ( ! $post || 'publish' !== $post->post_status ) {
		return;
	}
	if ( 'page' !== $post->post_type ) {
		return;
	}

	hmpro_litespeed_purge_home_if_needed( $post_id );
}, 10, 3 );

// Purge homepage when widgets change (block widgets often affect homepage).
add_action( 'updated_option', function ( $option, $old_value, $value ) {
	if ( 'sidebars_widgets' === $option || 'widget_block' === $option ) {
		hmpro_litespeed_purge_home_if_needed( 0 );
	}
}, 10, 3 );
