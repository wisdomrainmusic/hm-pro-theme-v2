<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Bail early if WooCommerce isn't active.
if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

// Disable zoom completely (zoom overlay is what creates the "insane" scale on hover).
add_filter( 'woocommerce_single_product_zoom_enabled', '__return_false' );

// Disable PhotoSwipe lightbox so clicking the image won't open fullscreen viewer.
add_filter( 'woocommerce_single_product_photoswipe_enabled', '__return_false' );

/**
 * Ensure product gallery slider shows navigation arrows.
 * WooCommerce uses FlexSlider and respects these carousel options.
 */
add_filter( 'woocommerce_single_product_carousel_options', function ( $options ) {
	if ( ! is_array( $options ) ) {
		$options = [];
	}

	$options['directionNav'] = true;
	$options['controlNav']   = 'thumbnails';
	$options['smoothHeight'] = false;

	return $options;
} );

/**
 * Force arrows at runtime as well (some setups override the PHP options before init).
 * We inject BEFORE wc-single-product initializes the gallery.
 */
add_action( 'wp_enqueue_scripts', function () {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}

	if ( wp_script_is( 'wc-single-product', 'registered' ) ) {
		$js = <<<JS
if (typeof wc_single_product_params !== 'undefined' && wc_single_product_params.flexslider) {
	wc_single_product_params.flexslider.directionNav = true;
	wc_single_product_params.flexslider.prevText = '';
	wc_single_product_params.flexslider.nextText = '';
}

// Prevent default navigation to the raw image file when PhotoSwipe is disabled.
document.addEventListener('click', function(e){
	var a = e.target && e.target.closest ? e.target.closest('.woocommerce-product-gallery__wrapper a') : null;
	if (a) { e.preventDefault(); }
}, true);
JS;
		wp_add_inline_script( 'wc-single-product', $js, 'before' );
	}
}, 20 );
