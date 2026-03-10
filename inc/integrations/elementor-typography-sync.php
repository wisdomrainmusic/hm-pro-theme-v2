<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sync HM Pro Theme typography presets to Elementor's Active Kit typography.
 *
 * Why this exists:
 * - Elementor often generates per-page CSS with hard-coded font-family values.
 * - Changing only CSS variables may not update existing generated CSS.
 * - Updating the Active Kit settings + clearing Elementor CSS cache forces regeneration.
 *
 * Scope:
 * - This runs ONLY when Typography Presets are applied from HM Pro Theme → Presets.
 * - It does not touch any other HM Pro Theme admin modules.
 */

function hmpro_elementor_sync_active_kit_typography( $body_token, $heading_token ) {
	if ( ! class_exists( '\\Elementor\\Plugin' ) ) {
		return;
	}

	$kit_id = (int) get_option( 'elementor_active_kit' );
	if ( $kit_id <= 0 ) {
		return;
	}

	$body_stack    = hmpro_font_token_to_stack( (string) $body_token );
	$heading_stack = hmpro_font_token_to_stack( (string) $heading_token );

	// Elementor expects a font-family string (not a full stack in many places).
	// We keep stacks as-is (safe + quoted) because Elementor accepts it in settings,
	// and it preserves consistent rendering when the Google font fails to load.
	$settings = get_post_meta( $kit_id, '_elementor_page_settings', true );
	if ( ! is_array( $settings ) ) {
		$settings = [];
	}

	// Helper to set a typography group.
	$set_group = function ( &$group, $font_family ) {
		if ( ! is_array( $group ) ) {
			$group = [];
		}
		// Elementor commonly uses these keys.
		$group['typography_typography']   = $group['typography_typography'] ?? 'custom';
		$group['typography_font_family']  = $font_family;
		$group['font_family']             = $font_family;
	};

	// Most common: system_typography[primary|secondary|text|accent].
	if ( ! isset( $settings['system_typography'] ) || ! is_array( $settings['system_typography'] ) ) {
		$settings['system_typography'] = [];
	}

	$set_group( $settings['system_typography']['primary'], $heading_stack );
	$set_group( $settings['system_typography']['secondary'], $heading_stack );
	$set_group( $settings['system_typography']['text'], $body_stack );
	$set_group( $settings['system_typography']['accent'], $body_stack );

	// Some Elementor versions also store global settings here.
	if ( isset( $settings['global_typography'] ) && is_array( $settings['global_typography'] ) ) {
		$set_group( $settings['global_typography']['primary'], $heading_stack );
		$set_group( $settings['global_typography']['secondary'], $heading_stack );
		$set_group( $settings['global_typography']['text'], $body_stack );
		$set_group( $settings['global_typography']['accent'], $body_stack );
	}

	update_post_meta( $kit_id, '_elementor_page_settings', $settings );

	// Clear Elementor generated CSS so pages pick up the new kit settings.
	try {
		\Elementor\Plugin::$instance->files_manager->clear_cache();
	} catch ( Exception $e ) {
		// Silent fail: we don't want this to break admin actions.
	}
}
