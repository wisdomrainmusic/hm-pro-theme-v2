<?php
/**
 * Default Sidebar
 *
 * Fallback for themes/components that call get_sidebar().
 * Uses "Blog Sidebar" widget area (hmpro-blog-sidebar).
 *
 * @package HMPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! is_active_sidebar( 'hmpro-blog-sidebar' ) ) {
	return;
}
?>

<aside id="secondary" class="widget-area hmpro-sidebar hmpro-blog-sidebar" role="complementary">
	<?php dynamic_sidebar( 'hmpro-blog-sidebar' ); ?>
</aside>
