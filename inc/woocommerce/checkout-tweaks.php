<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

/**
 * Ensure "Ship to a different address?" is available like Astra.
 * Some setups return false for shipping address need; this forces it true.
 */
add_filter( 'woocommerce_cart_needs_shipping_address', function ( $needs ) {
	return true;
}, 50 );

/**
 * Checkout accordion behavior for shipping address section.
 */
add_action( 'wp_enqueue_scripts', function () {
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
		return;
	}

	$theme_ver = wp_get_theme()->get( 'Version' );

	// Enqueue small accordion script.
	wp_enqueue_script(
		'hmpro-checkout-accordion',
		get_template_directory_uri() . '/assets/js/checkout-accordion.js',
		[ 'jquery' ],
		$theme_ver,
		true
	);
}, 30 );
