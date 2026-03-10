<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue Product Video Image modal bridge assets on product pages.
 */
function hmpro_pvi_modal_bridge_enqueue_assets() {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}

	$product_id = get_queried_object_id();
	if ( ! $product_id ) {
		return;
	}

	wp_enqueue_style(
		'hmpro-pvi-modal',
		HMPRO_URL . '/assets/css/hm-pvi-modal.css',
		[],
		hmpro_asset_ver( 'assets/css/hm-pvi-modal.css' )
	);

	wp_enqueue_script(
		'hmpro-pvi-modal',
		HMPRO_URL . '/assets/js/hm-pvi-modal.js',
		[],
		hmpro_asset_ver( 'assets/js/hm-pvi-modal.js' ),
		true
	);

	wp_localize_script(
		'hmpro-pvi-modal',
		'hmPviModalData',
		[
			'enabled'      => (string) get_post_meta( $product_id, '_hm_pvi_enabled', true ),
			'type'         => (string) get_post_meta( $product_id, '_hm_pvi_type', true ),
			'youtube_url'  => (string) get_post_meta( $product_id, '_hm_pvi_youtube_url', true ),
			'mp4_url'      => (string) get_post_meta( $product_id, '_hm_pvi_mp4_url', true ),
			'lightbox'     => (string) get_post_meta( $product_id, '_hm_pvi_lightbox', true ),
			'button_label' => (string) get_post_meta( $product_id, '_hm_pvi_button_label', true ),
			'i18nClose'    => __( 'Close video', 'hmpro' ),
		]
	);
}
add_action( 'wp_enqueue_scripts', 'hmpro_pvi_modal_bridge_enqueue_assets', 40 );

/**
 * Bridge footer hook reserved for product-only output (kept intentionally empty).
 */
function hmpro_pvi_modal_bridge_footer_hook() {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}
}
add_action( 'wp_footer', 'hmpro_pvi_modal_bridge_footer_hook', 99 );
