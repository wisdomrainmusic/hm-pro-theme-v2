<?php
/**
 * HM Pro - Product media standardization
 * - Archive (shop/category/tag): wrap only the thumbnail in a 2:3 canvas (1200x1800 feel)
 * - Single handled in CSS (no markup change needed)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Wrap ONLY the product thumbnail output in archives so we can apply aspect-ratio
 * without breaking the link/title/price layout.
 *
 * Default Woo order:
 * - woocommerce_template_loop_product_thumbnail @ priority 10
 */
function hmpro_open_product_thumb_canvas() {
	echo '<div class="hmpro-product-thumb-canvas">';
}

function hmpro_close_product_thumb_canvas() {
	echo '</div>';
}

add_action( 'woocommerce_before_shop_loop_item_title', 'hmpro_open_product_thumb_canvas', 9 );
add_action( 'woocommerce_before_shop_loop_item_title', 'hmpro_close_product_thumb_canvas', 11 );
