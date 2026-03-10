<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Theme upgrade routine (runs once when HMPRO_VERSION increases)
 *
 * Goals:
 * - Remove deprecated Customizer/theme_mod keys (Hero Banner remnants).
 * - Normalize Header Background Banner settings so legacy DB values don't block rendering.
 * - Make updates resilient when theme is updated on existing sites.
 */
add_action( 'after_setup_theme', function () {
	$opt_key  = 'hmpro_theme_version';
	$stored   = get_option( $opt_key, '' );
	$current  = (string) ( defined( 'HMPRO_VERSION' ) ? HMPRO_VERSION : '0.0.0' );

	if ( $stored === '' ) {
		// First run on this site.
		update_option( $opt_key, $current, false );
		return;
	}

	if ( version_compare( (string) $stored, $current, '>=' ) ) {
		return;
	}

	/**
	 * 1) Cleanup deprecated Hero Banner keys (removed feature)
	 */
	$deprecated_mods = [
		// Transparent header + hero section (old)
		'hmpro_transparent_header_home',
		'hmpro_transparent_header_offset',
		'hmpro_transparent_header_logo',
		// Old hero media/text/cta/typography/group settings
		'hmpro_th_hero_image',
		'hmpro_th_hero_use_video',
		'hmpro_th_hero_video',
		'hmpro_th_hero_height',
		'hmpro_th_hero_hide_mobile',
		'hmpro_th_hero_height_mobile',
		'hmpro_th_hero_overlay',
		'hmpro_th_hero_title',
		'hmpro_th_hero_text',
		'hmpro_th_hero_btn_text',
		'hmpro_th_hero_btn_url',
		'hmpro_th_hero_btn_newtab',
		'hmpro_th_hero_title_color',
		'hmpro_th_hero_text_color',
		'hmpro_th_hero_btn_bg',
		'hmpro_th_hero_btn_color',
		'hmpro_th_hero_title_size',
		'hmpro_th_hero_text_size',
		'hmpro_th_hero_font_family',
		'hmpro_th_hero_group_scale',
		'hmpro_th_hero_group_scale_mobile',
		'hmpro_th_hero_group_top_mobile',
		'hmpro_th_hero_group_x',
		'hmpro_th_hero_group_y',
	];

	foreach ( $deprecated_mods as $key ) {
		if ( get_theme_mod( $key, null ) !== null ) {
			remove_theme_mod( $key );
		}
	}

	/**
	 * 2) Normalize Header Background Banner settings (legacy-safe)
	 * - If banner has image/video values but enabled is off/unset, auto-enable.
	 * - Cast toggles to 0/1 to avoid string/array edge-cases.
	 */
	$hb_enabled = (int) get_theme_mod( 'hmpro_hb_enabled', 0 );
	$hb_image   = (string) get_theme_mod( 'hmpro_hb_image', '' );
	$hb_video   = (string) get_theme_mod( 'hmpro_hb_video', '' );

	if ( $hb_enabled !== 1 && ( $hb_image !== '' || $hb_video !== '' ) ) {
		set_theme_mod( 'hmpro_hb_enabled', 1 );
	}

	$toggle_keys = [
		'hmpro_hb_enabled',
		'hmpro_hb_use_video',
		'hmpro_hb_hide_mobile',
		'hmpro_hb_btn_newtab',
	];
	foreach ( $toggle_keys as $k ) {
		$val = get_theme_mod( $k, null );
		if ( $val !== null ) {
			set_theme_mod( $k, (int) ( $val ? 1 : 0 ) );
		}
	}

	// Mark upgrade complete.
	update_option( $opt_key, $current, false );
}, 5 );

