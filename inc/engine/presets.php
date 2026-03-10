<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Options keys
 */
function hmpro_presets_option_key() {
	return 'hmpro_presets';
}

function hmpro_active_preset_option_key() {
	return 'hmpro_active_preset';
}

/**
 * Default preset (used if storage is empty).
 */
function hmpro_get_default_preset() {
	return [
		'id'           => 'default',
		'name'         => 'Default',
		'primary'      => '#111111',
		'dark'         => '#0B1220',
		'bg'           => '#ffffff',
		'surface'      => '#f7f7f7',
		'text'         => '#222222',
		'muted'        => '#777777',
		'link'         => '#111111',
		'border'       => '#e5e5e5',
		'footer'       => '#0B1220',
		'body_font'    => 'system',
		'heading_font' => 'system',
		'created_at'   => gmdate( 'c' ),
		'updated_at'   => gmdate( 'c' ),
	];
}

/**
 * Read presets from wp_options.
 */
function hmpro_get_presets() {
	$presets = get_option( hmpro_presets_option_key(), [] );
	$changed = false;

	if ( ! is_array( $presets ) || empty( $presets ) ) {
		$presets = [ hmpro_get_default_preset() ];
		update_option( hmpro_presets_option_key(), $presets, false );
	}

	// Normalize: ensure each preset has a sanitized id (and persist if needed).
	$seen = [];
	foreach ( $presets as $i => $p ) {
		$name = isset( $p['name'] ) ? (string) $p['name'] : ( 'Preset ' . ( $i + 1 ) );

		$raw_id = isset( $p['id'] ) ? (string) $p['id'] : '';
		$id     = sanitize_key( $raw_id );

		if ( empty( $id ) ) {
			$id = sanitize_key( $name );
		}

		if ( empty( $id ) ) {
			$id = 'preset_' . $i;
		}

		// Ensure uniqueness.
		$base = $id;
		$n    = 2;
		while ( isset( $seen[ $id ] ) ) {
			$id = $base . '_' . $n;
			$n++;
		}
		$seen[ $id ] = true;

		if ( empty( $p['id'] ) || $p['id'] !== $id ) {
			$presets[ $i ]['id'] = $id;
			$changed = true;
		}

		if ( empty( $presets[ $i ]['name'] ) || $presets[ $i ]['name'] !== $name ) {
			$presets[ $i ]['name'] = $name;
			$changed = true;
		}
	}

	if ( $changed ) {
		update_option( hmpro_presets_option_key(), $presets, false );
	}

	return $presets;
}

/**
 * Find preset by id.
 */
function hmpro_get_preset_by_id( $preset_id ) {
	$preset_id = sanitize_key( (string) $preset_id );
	$presets   = hmpro_get_presets();

	foreach ( $presets as $preset ) {
		$pid = isset( $preset['id'] ) ? sanitize_key( (string) $preset['id'] ) : '';
		if ( $pid && $pid === $preset_id ) {
			return $preset;
		}
	}

	return null;
}

/**
 * Get active preset id (ensure it exists).
 */
function hmpro_get_active_preset_id() {
	$active = get_option( hmpro_active_preset_option_key(), 'default' );
	$active = sanitize_key( (string) $active );

	if ( hmpro_get_preset_by_id( $active ) ) {
		return $active;
	}

	// Fallback to default if active not found.
	update_option( hmpro_active_preset_option_key(), 'default', false );
	return 'default';
}

/**
 * Get active preset data.
 */
function hmpro_get_active_preset() {
	$preset_id = hmpro_get_active_preset_id();
	$preset    = hmpro_get_preset_by_id( $preset_id );

	if ( $preset ) {
		return $preset;
	}

	return null;
}

/**
 * Set active preset id (only if exists).
 */
function hmpro_set_active_preset_id( $preset_id ) {
	$preset_id = sanitize_key( (string) $preset_id );

	if ( ! hmpro_get_preset_by_id( $preset_id ) ) {
		return false;
	}

	update_option( hmpro_active_preset_option_key(), $preset_id, false );
	return true;
}

/**
 * ---------------------------------------------
 * Header/Top Bar + Footer Customizer color sync
 * ---------------------------------------------
 *
 * Presets provide global palette defaults.
 * Customizer controls can override Top Bar / Footer colors.
 *
 * Rules:
 * - If user has NOT set a customizer value (theme_mod is empty), we may seed/sync from the active preset.
 * - If user has set a value, we never overwrite it.
 */

function hmpro_hex_luminance( $hex ) {
	$hex = ltrim( (string) $hex, '#' );
	if ( 3 === strlen( $hex ) ) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}
	if ( 6 !== strlen( $hex ) || ! ctype_xdigit( $hex ) ) {
		return 0;
	}
	$r = hexdec( substr( $hex, 0, 2 ) ) / 255;
	$g = hexdec( substr( $hex, 2, 2 ) ) / 255;
	$b = hexdec( substr( $hex, 4, 2 ) ) / 255;

	// Convert sRGB to linear.
	$r = ( $r <= 0.03928 ) ? ( $r / 12.92 ) : pow( ( ( $r + 0.055 ) / 1.055 ), 2.4 );
	$g = ( $g <= 0.03928 ) ? ( $g / 12.92 ) : pow( ( ( $g + 0.055 ) / 1.055 ), 2.4 );
	$b = ( $b <= 0.03928 ) ? ( $b / 12.92 ) : pow( ( ( $b + 0.055 ) / 1.055 ), 2.4 );

	return ( 0.2126 * $r ) + ( 0.7152 * $g ) + ( 0.0722 * $b );
}

function hmpro_pick_contrast_color( $bg_hex, $dark = '#111111', $light = '#ffffff' ) {
	$l = hmpro_hex_luminance( $bg_hex );
	// Threshold tuned for UI readability.
	return ( $l > 0.55 ) ? $dark : $light;
}

function hmpro_sync_header_footer_color_mods_from_preset_if_empty( $preset_id ) {
	$preset = hmpro_get_preset_by_id( $preset_id );
	if ( ! $preset ) {
		return;
	}

	$footer_bg = sanitize_hex_color( $preset['footer'] ?? '' );
	if ( empty( $footer_bg ) ) {
		$footer_bg = sanitize_hex_color( $preset['dark'] ?? '' );
	}

	$footer_txt = hmpro_pick_contrast_color( $footer_bg );

	$map = [
		'hmpro_footer_bg_color'   => $footer_bg,
		'hmpro_footer_text_color' => $footer_txt,
		// Top Bar defaults to Footer palette (premium theme convention).
		'hmpro_topbar_bg_color'   => $footer_bg,
		'hmpro_topbar_text_color' => $footer_txt,
		// Search field readability inside Top Bar.
		'hmpro_topbar_search_text_color'        => $footer_txt,
		'hmpro_topbar_search_placeholder_color' => $footer_txt,
	];

	foreach ( $map as $mod_key => $value ) {
		$current = (string) get_theme_mod( $mod_key, '' );
		if ( '' === trim( $current ) && '' !== trim( (string) $value ) ) {
			set_theme_mod( $mod_key, $value );
		}
	}
}

/**
 * Seed default header/footer colors once on first run.
 */
function hmpro_seed_header_footer_colors_once() {
	$flag_key = 'hmpro_builder_colors_initialized';
	if ( get_option( $flag_key ) ) {
		return;
	}

	$preset_id = hmpro_get_active_preset_id();
	hmpro_sync_header_footer_color_mods_from_preset_if_empty( $preset_id );
	update_option( $flag_key, 1, false );
}

add_action( 'after_setup_theme', 'hmpro_seed_header_footer_colors_once', 20 );

/**
 * Seed a few sample presets for testing.
 */
function hmpro_seed_sample_presets() {
	$presets = hmpro_get_presets();

	// If we already have more than 1 preset, don't spam.
	if ( count( $presets ) > 1 ) {
		return false;
	}

	$now = gmdate( 'c' );

	$samples = [
		[
			'id'           => 'midnight_pro',
			'name'         => 'Midnight Pro',
			'primary'      => '#0B1220',
			'dark'         => '#0B1220',
			'bg'           => '#FFFFFF',
			'surface'      => '#F4F6FA',
			'text'         => '#101828',
			'muted'        => '#667085',
			'link'         => '#0B1220',
			'border'       => '#E4E7EC',
			'footer'       => '#0B1220',
			'body_font'    => 'system',
			'heading_font' => 'system',
			'created_at'   => $now,
			'updated_at'   => $now,
		],
		[
			'id'           => 'rose_elegance',
			'name'         => 'Rose Elegance',
			'primary'      => '#D97C8A',
			'dark'         => '#7A3340',
			'bg'           => '#FFF6F7',
			'surface'      => '#FFFFFF',
			'text'         => '#2B2B2B',
			'muted'        => '#7A6670',
			'link'         => '#D97C8A',
			'border'       => '#F2D7DB',
			'footer'       => '#7A3340',
			'body_font'    => 'system',
			'heading_font' => 'system',
			'created_at'   => $now,
			'updated_at'   => $now,
		],
		[
			'id'           => 'sand_latte',
			'name'         => 'Sand Latte',
			'primary'      => '#8B6B4F',
			'dark'         => '#2A241F',
			'bg'           => '#FBF5EF',
			'surface'      => '#FFFFFF',
			'text'         => '#2A241F',
			'muted'        => '#6B5E55',
			'link'         => '#8B6B4F',
			'border'       => '#E9DED6',
			'footer'       => '#2A241F',
			'body_font'    => 'system',
			'heading_font' => 'system',
			'created_at'   => $now,
			'updated_at'   => $now,
		],
	];

	// Keep Default + add samples
	$new = array_merge( $presets, $samples );
	update_option( hmpro_presets_option_key(), $new, false );
	return true;
}

/**
 * Update a preset by id (whitelisted fields).
 */
function hmpro_update_preset( $preset_id, array $data ) {
	$preset_id = sanitize_key( (string) $preset_id );
	$presets   = hmpro_get_presets();

	$allowed = [
		'name',
		'primary',
		'dark',
		'bg',
		'footer',
		'link',
		'body_font',
		'heading_font',
	];

	$found = false;

	foreach ( $presets as $i => $p ) {
		$pid = isset( $p['id'] ) ? sanitize_key( (string) $p['id'] ) : '';
		if ( $pid !== $preset_id ) {
			continue;
		}

		foreach ( $allowed as $k ) {
			if ( array_key_exists( $k, $data ) ) {
				$presets[ $i ][ $k ] = is_string( $data[ $k ] ) ? trim( $data[ $k ] ) : $data[ $k ];
			}
		}

		$presets[ $i ]['updated_at'] = gmdate( 'c' );
		$found = true;
		break;
	}

	if ( ! $found ) {
		return false;
	}

	update_option( hmpro_presets_option_key(), $presets, false );
	return true;
}

/**
 * Delete a preset by id.
 */
function hmpro_delete_preset( $preset_id ) {
	$preset_id = sanitize_key( (string) $preset_id );
	$presets   = hmpro_get_presets();
	$updated   = [];
	$found     = false;

	foreach ( $presets as $preset ) {
		$pid = isset( $preset['id'] ) ? sanitize_key( (string) $preset['id'] ) : '';
		if ( $pid && $pid === $preset_id ) {
			$found = true;
			continue;
		}

		$updated[] = $preset;
	}

	if ( ! $found ) {
		return false;
	}

	update_option( hmpro_presets_option_key(), $updated, false );
	return true;
}
