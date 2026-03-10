<?php
/**
 * WooCommerce Shop Sidebar
 *
 * Loaded by WooCommerce via get_sidebar( 'shop' ).
 * Renders the "Shop Sidebar" widget area (hmpro-shop-sidebar).
 *
 * @package HMPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! is_active_sidebar( 'hmpro-shop-sidebar' ) ) {
	return;
}
?>

<aside id="secondary" class="widget-area hmpro-sidebar hmpro-shop-sidebar" role="complementary">
	<?php dynamic_sidebar( 'hmpro-shop-sidebar' ); ?>
</aside>
