<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * HM Pro Theme - Widget Areas
 *
 * WordPress only shows Appearance > Widgets if at least one sidebar is registered.
 * We register a minimal set so the theme can support WooCommerce filters (shop sidebar)
 * and future footer widgets without touching existing builder modules.
 */
add_action( 'widgets_init', function () {
	// WooCommerce Shop Sidebar (filters, categories, search, etc.)
	register_sidebar( array(
		'name'          => __( 'Shop Sidebar', 'hmpro' ),
		'id'            => 'hmpro-shop-sidebar',
		'description'   => __( 'Widgets shown on WooCommerce shop/category/tag pages.', 'hmpro' ),
		'before_widget' => '<section id="%1$s" class="widget %2$s hmpro-widget">',
		'after_widget'  => '</section>',
		'before_title'  => '<h3 class="widget-title hmpro-widget-title">',
		'after_title'   => '</h3>',
	) );

	// Blog Sidebar (optional, but useful)
	register_sidebar( array(
		'name'          => __( 'Blog Sidebar', 'hmpro' ),
		'id'            => 'hmpro-blog-sidebar',
		'description'   => __( 'Widgets shown on blog posts and archives.', 'hmpro' ),
		'before_widget' => '<section id="%1$s" class="widget %2$s hmpro-widget">',
		'after_widget'  => '</section>',
		'before_title'  => '<h3 class="widget-title hmpro-widget-title">',
		'after_title'   => '</h3>',
	) );

	// Footer widget areas (future-proof)
	register_sidebar( array(
		'name'          => __( 'Footer Column 1', 'hmpro' ),
		'id'            => 'hmpro-footer-1',
		'description'   => __( 'Footer widget area - column 1.', 'hmpro' ),
		'before_widget' => '<section id="%1$s" class="widget %2$s hmpro-footer-widget">',
		'after_widget'  => '</section>',
		'before_title'  => '<h4 class="widget-title hmpro-footer-title">',
		'after_title'   => '</h4>',
	) );

	register_sidebar( array(
		'name'          => __( 'Footer Column 2', 'hmpro' ),
		'id'            => 'hmpro-footer-2',
		'description'   => __( 'Footer widget area - column 2.', 'hmpro' ),
		'before_widget' => '<section id="%1$s" class="widget %2$s hmpro-footer-widget">',
		'after_widget'  => '</section>',
		'before_title'  => '<h4 class="widget-title hmpro-footer-title">',
		'after_title'   => '</h4>',
	) );

	register_sidebar( array(
		'name'          => __( 'Footer Column 3', 'hmpro' ),
		'id'            => 'hmpro-footer-3',
		'description'   => __( 'Footer widget area - column 3.', 'hmpro' ),
		'before_widget' => '<section id="%1$s" class="widget %2$s hmpro-footer-widget">',
		'after_widget'  => '</section>',
		'before_title'  => '<h4 class="widget-title hmpro-footer-title">',
		'after_title'   => '</h4>',
	) );
}, 20 );
