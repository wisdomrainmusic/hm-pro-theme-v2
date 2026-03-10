<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sanitize a CSS font-family stack for inline <style> output.
 * Allows only safe characters and preserves quotes (required for families with spaces).
 */
function hmpro_sanitize_css_font_stack( $value ) {
	$value = (string) $value;
	$value = trim( $value );

	// Allow letters, numbers, spaces, commas, quotes, hyphen, underscore, parentheses.
	$value = preg_replace( '/[^a-zA-Z0-9,\s\'"\-\_\(\)\/]/', '', $value );

	// Collapse whitespace
	$value = preg_replace( '/\s+/', ' ', $value );

	return $value;
}

/**
 * Convert a hex color to rgba string.
 */
function hmpro_hex_to_rgba( $hex, $alpha = 1 ) {
	$hex = trim( (string) $hex );
	$hex = ltrim( $hex, '#' );

	if ( 3 === strlen( $hex ) ) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}

	if ( 6 !== strlen( $hex ) || ! ctype_xdigit( $hex ) ) {
		return 'rgba(0,0,0,' . $alpha . ')';
	}

	$r = hexdec( substr( $hex, 0, 2 ) );
	$g = hexdec( substr( $hex, 2, 2 ) );
	$b = hexdec( substr( $hex, 4, 2 ) );

	$alpha = max( 0, min( 1, (float) $alpha ) );

	return 'rgba(' . $r . ',' . $g . ',' . $b . ',' . $alpha . ')';
}

/**
 * Output CSS variables for the active preset.
 */
add_action( 'wp_head', 'hmpro_output_css_variables', 20 );

function hmpro_output_css_variables() {
	$preset = hmpro_get_active_preset();
	if ( ! $preset ) {
		return;
	}

	echo '<style id="hmpro-css-vars">';
	echo ':root {';

	$accent = $preset['primary'] ?? '#111111';

	echo '--hm-primary: ' . esc_html( $accent ) . ';';
	echo '--hm-dark: ' . esc_html( $preset['dark'] ?? '#000000' ) . ';';
	echo '--hm-bg: ' . esc_html( $preset['bg'] ?? '#ffffff' ) . ';';
	echo '--hm-footer: ' . esc_html( $preset['footer'] ?? '#111111' ) . ';';
	echo '--hm-link: ' . esc_html( $preset['link'] ?? '#2271b1' ) . ';';
	echo '--hmpro-accent: ' . esc_html( $accent ) . ';';
	echo '--hmpro-icon-bg: ' . esc_html( hmpro_hex_to_rgba( $accent, 0.1 ) ) . ';';
	echo '--hmpro-icon-border: ' . esc_html( hmpro_hex_to_rgba( $accent, 0.25 ) ) . ';';
	echo '--hm-body-font: ' . hmpro_sanitize_css_font_stack( hmpro_font_token_to_stack( (string) ( $preset['body_font'] ?? 'system' ) ) ) . ';';
	echo '--hm-heading-font: ' . hmpro_sanitize_css_font_stack( hmpro_font_token_to_stack( (string) ( $preset['heading_font'] ?? 'system' ) ) ) . ';';

	echo '}';
	echo '</style>';
}

/**
 * Elementor integration: map active preset colors/typography to Elementor Global variables.
 *
 * This restores the old behavior where Elementor widgets inherit preset styling by default,
 * while still allowing per-widget overrides via Elementor's style controls.
 */
// NOTE: Elementor defines globals on `.elementor-kit-XX` (body class), not only `:root`.
// To reliably override, we print our variables late AND scope them to `body[class*="elementor-kit-"]`.
add_action( 'wp_head', 'hmpro_output_elementor_global_vars', 21 );
add_action( 'wp_footer', 'hmpro_output_elementor_global_vars', 999 );
add_action( 'elementor/frontend/after_enqueue_styles', 'hmpro_enqueue_elementor_global_vars', 5 );
add_action( 'elementor/editor/after_enqueue_styles', 'hmpro_enqueue_elementor_global_vars', 5 );

function hmpro_get_elementor_global_vars_css() {
	$preset = hmpro_get_active_preset();
	if ( ! $preset ) {
		return '';
	}

	$primary = $preset['primary'] ?? '#111111';
	$dark    = $preset['dark'] ?? '#0B1220';
	$bg      = $preset['bg'] ?? '#ffffff';
	$text    = $preset['text'] ?? ( $preset['dark'] ?? '#222222' );
	$link    = $preset['link'] ?? $primary;

	$body_font    = hmpro_sanitize_css_font_stack( hmpro_font_token_to_stack( (string) ( $preset['body_font'] ?? 'system' ) ) );
	$heading_font = hmpro_sanitize_css_font_stack( hmpro_font_token_to_stack( (string) ( $preset['heading_font'] ?? 'system' ) ) );

	// Elementor usually sets these on `.elementor-kit-XX` (body class).
	// We set on both `:root` and `body[class*="elementor-kit-"]` so widgets inherit preset globals,
	// while still allowing per-widget overrides via Elementor controls.
	$vars  = '';
	$vars .= '--e-global-color-primary:' . esc_html( $primary ) . ';';
	$vars .= '--e-global-color-secondary:' . esc_html( $dark ) . ';';
	$vars .= '--e-global-color-text:' . esc_html( $text ) . ';';
	$vars .= '--e-global-color-accent:' . esc_html( $link ) . ';';
	$vars .= '--e-global-color-background:' . esc_html( $bg ) . ';';
	$vars .= '--e-global-typography-text-font-family:' . esc_html( $body_font ) . ';';
	$vars .= '--e-global-typography-primary-font-family:' . esc_html( $heading_font ) . ';';
	$vars .= '--e-global-typography-secondary-font-family:' . esc_html( $heading_font ) . ';';
	$vars .= '--e-global-typography-accent-font-family:' . esc_html( $body_font ) . ';';

	$css  = ':root{' . $vars . '}';
	$css .= 'body[class*="elementor-kit-"]{' . $vars . '}';

	return $css;
}

function hmpro_output_elementor_global_vars() {
	$css = hmpro_get_elementor_global_vars_css();
	if ( '' === $css ) {
		return;
	}

	echo '<style id="hmpro-elementor-globals">' . $css . '</style>';
}

function hmpro_enqueue_elementor_global_vars() {
	$css = hmpro_get_elementor_global_vars_css();
	if ( '' === $css ) {
		return;
	}

	// Frontend preview + editor iframe.
	if ( wp_style_is( 'elementor-frontend', 'enqueued' ) ) {
		wp_add_inline_style( 'elementor-frontend', $css );
	}

	// Elementor editor UI.
	if ( wp_style_is( 'elementor-editor', 'enqueued' ) ) {
		wp_add_inline_style( 'elementor-editor', $css );
	}
}

/**
 * Elementor typography bridge.
 *
 * Some Elementor widgets may end up with hard-coded font-family rules in generated CSS.
 * We keep this bridge narrow (headings + basic text widgets) to avoid changing other modules.
 */
add_action( 'wp_head', 'hmpro_output_elementor_typography_bridge', 22 );
// Also print at the very end of the page to win against late-loaded/generated Elementor CSS.
add_action( 'wp_footer', 'hmpro_output_elementor_typography_bridge', 999 );

function hmpro_output_elementor_typography_bridge() {
	// Only print if Elementor is present.
	if ( ! did_action( 'elementor/loaded' ) && ! class_exists( '\\Elementor\\Plugin' ) ) {
		return;
	}

	// Use the theme-level variables already computed from the active preset.
	// Keep selectors focused to Elementor output but wide enough to override generated per-element rules.
	$css  = '';

	// Headings.
	$css .= 'body .elementor .elementor-heading-title{font-family:var(--hm-heading-font)!important;}';
	$css .= 'body .elementor h1,body .elementor h2,body .elementor h3,body .elementor h4,body .elementor h5,body .elementor h6{font-family:var(--hm-heading-font)!important;}';
	$css .= 'body .elementor .elementor-widget-heading .elementor-heading-title{font-family:var(--hm-heading-font)!important;}';
	$css .= 'body .elementor .elementor-widget-theme-post-title .elementor-heading-title{font-family:var(--hm-heading-font)!important;}';
	$css .= 'body .elementor .elementor-widget-icon-box .elementor-icon-box-title,body .elementor .elementor-widget-icon-box .elementor-icon-box-title *{font-family:var(--hm-heading-font)!important;}';

	// Body / UI text.
	$css .= 'body .elementor .elementor-widget-text-editor,body .elementor .elementor-widget-text-editor *{font-family:var(--hm-body-font)!important;}';
	$css .= 'body .elementor p,body .elementor li,body .elementor a,body .elementor span{font-family:var(--hm-body-font)!important;}';
	$css .= 'body .elementor .elementor-widget-button .elementor-button{font-family:var(--hm-body-font)!important;}';
	$css .= 'body .elementor .elementor-widget-icon-box .elementor-icon-box-description,body .elementor .elementor-widget-icon-box .elementor-icon-box-description *{font-family:var(--hm-body-font)!important;}';

	echo '<style id="hmpro-elementor-typo-bridge">' . $css . '</style>';
}
