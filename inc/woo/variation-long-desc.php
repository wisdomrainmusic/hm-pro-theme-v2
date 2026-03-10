<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * HMPRO: Variation Long Description (per variation) + dynamic swap for the Description tab.
 */

// 1) Admin field
add_action( 'woocommerce_product_after_variable_attributes', function ( $loop, $variation_data, $variation ) {
	woocommerce_wp_textarea_input( [
		'id'            => "wr_var_long_desc_{$loop}",
		'name'          => "wr_var_long_desc[{$loop}]",
		'label'         => 'Variation Long Description',
		'description'   => 'Optional long description for this specific variation (size, fit, notes, etc.).',
		'desc_tip'      => true,
		'value'         => get_post_meta( $variation->ID, '_wr_var_long_desc', true ),
		'wrapper_class' => 'form-row form-row-full',
		'rows'          => 4,
	] );
}, 10, 3 );

// 2) Save field
add_action( 'woocommerce_save_product_variation', function ( $variation_id, $i ) {
	if ( ! isset( $_POST['wr_var_long_desc'][ $i ] ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $variation_id ) ) {
		return;
	}

	$raw   = wp_unslash( $_POST['wr_var_long_desc'][ $i ] );
	$clean = wp_kses_post( $raw );
	update_post_meta( $variation_id, '_wr_var_long_desc', $clean );
}, 10, 2 );

// 3) Expose to variation JSON
add_filter( 'woocommerce_available_variation', function ( $data, $product, $variation ) {
	$desc                 = get_post_meta( $variation->get_id(), '_wr_var_long_desc', true );
	$data['wr_var_long_desc'] = $desc ? wpautop( $desc ) : '';
	return $data;
}, 10, 3 );

// 4) Frontend script (enqueue reliably + after wc-add-to-cart-variation)
add_action( 'wp_enqueue_scripts', function () {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}

	// NOTE:
	// On wp_enqueue_scripts, the global $product is often NOT set yet.
	// So we must resolve the product from the queried object ID.
	if ( ! function_exists( 'wc_get_product' ) ) {
		return;
	}
	$product_id = get_queried_object_id();
	$product    = $product_id ? wc_get_product( $product_id ) : null;
	if ( ! $product || ! $product->is_type( 'variable' ) ) {
		return;
	}

	$handle = 'hmpro-variation-long-desc';
	wp_register_script(
		$handle,
		get_template_directory_uri() . '/assets/js/variation-long-desc.js',
		// Ensure the variation events exist before our listener runs.
		[ 'jquery', 'wc-add-to-cart-variation' ],
		'1.0.1',
		true
	);
	wp_enqueue_script( $handle );
}, 20 );
