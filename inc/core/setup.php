<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * i18n
 *
 * WP 6.7+ can emit "_load_textdomain_just_in_time" notices if translations are triggered
 * before the theme textdomain is loaded.
 *
 * This theme historically used both 'hmpro' and 'hm-pro-theme' domains in strings,
 * so we load both to avoid just-in-time loading warnings.
 */
add_action( 'after_setup_theme', function () {
	load_theme_textdomain( 'hmpro', HMPRO_PATH . '/languages' );
	load_theme_textdomain( 'hm-pro-theme', HMPRO_PATH . '/languages' );
}, 0 );

add_action( 'after_setup_theme', function () {
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 80,
			'width'       => 240,
			'flex-height' => true,
			'flex-width'  => true,
		)
	);
}, 5 );

/**
 * Ensure menu locations include slots needed for builder menus.
 * (Keeps existing locations; adds topbar/footer if missing.)
 */
add_action( 'after_setup_theme', function () {
	// If theme already registers nav menus elsewhere, this won't hurt;
	// WordPress merges locations by key.
	register_nav_menus(
		array(
			'primary'     => __( 'Primary Menu', 'hmpro' ),
			'topbar'      => __( 'Top Bar Menu', 'hmpro' ),
			'footer'      => __( 'Footer Menu', 'hmpro' ),
			'mobile_menu' => __( 'Mobil Menü', 'hm-pro-theme' ),
		)
	);
}, 20 );

/**
 * Register Header/Footer Builder Regions
 */
function hmpro_get_builder_regions() {
	return array(
		'header' => array(
			'header_top'    => __( 'Header Top', 'hm-pro-theme' ),
			'header_main'   => __( 'Header Main', 'hm-pro-theme' ),
			'header_bottom' => __( 'Header Bottom', 'hm-pro-theme' ),
		),
		'footer' => array(
			'footer_top'    => __( 'Footer Top', 'hm-pro-theme' ),
			'footer_main'   => __( 'Footer Main', 'hm-pro-theme' ),
			'footer_bottom' => __( 'Footer Bottom', 'hm-pro-theme' ),
		),
	);
}

/**
 * Check if builder layout exists
 */
function hmpro_has_builder_layout( $area ) {
	$area = ( 'footer' === $area ) ? 'footer' : 'header';

	// Commit 017: layout stored in wp_options
	if ( function_exists( 'hmpro_builder_get_layout' ) ) {
		$layout = hmpro_builder_get_layout( $area );
		$regions = isset( $layout['regions'] ) && is_array( $layout['regions'] ) ? $layout['regions'] : array();

		foreach ( $regions as $region_rows ) {
			if ( ! empty( $region_rows ) && is_array( $region_rows ) ) {
				return (bool) apply_filters( 'hmpro/has_builder_layout', true, $area );
			}
		}
	}

	return (bool) apply_filters( 'hmpro/has_builder_layout', false, $area );
}

function hmpro_header_builder_has_layout() {
	return function_exists( 'hmpro_has_builder_layout' ) && hmpro_has_builder_layout( 'header' );
}

/**
 * Render a builder region
 * Renderer will be attached in Commit 018
 */
function hmpro_render_builder_region( $region_key, $area ) {
	do_action( 'hmpro/' . $area . '/render_region', $region_key );
}

add_action( 'after_setup_theme', function () {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'woocommerce' );

	// WooCommerce single product gallery features.
	// Enables FlexSlider carousel, PhotoSwipe lightbox and zoom.
	add_theme_support( 'wc-product-gallery-slider' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-zoom' );

	register_nav_menus( [
		// Legacy keys (older header fallback expects these).
		// Keep for backward compatibility, but builder renderer will prefer 'primary/topbar/footer'
		// and fall back to these if assigned.
		'hm_primary' => __( 'Primary Menu (Legacy)', 'hmpro' ),
		'hm_footer'  => __( 'Footer Menu (Legacy)', 'hmpro' ),
	] );

} );
