<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Menu item binding for Mega Menus (Commit 3D)
 * - Adds "Mega Menu Layout" dropdown to Appearance -> Menus items
 * - Saves to: post meta on nav_menu_item: _hmpro_mega_menu_id
 * - Frontend: inject mega panel markup for top-level items with meta
 */

/**
 * Add a stable <li> class for items bound to a mega menu.
 * We cannot rely on :has() CSS selectors; this makes hiding the normal submenu reliable.
 */
add_filter( 'nav_menu_css_class', function ( $classes, $item, $args, $depth ) {
	if ( ! is_array( $classes ) ) {
		$classes = [];
	}
	$mega_id = (int) get_post_meta( $item->ID, '_hmpro_mega_menu_id', true );
	if ( $mega_id > 0 ) {
		$classes[] = 'hmpro-li-has-mega';
		$classes[] = 'hmpro-mega-id-' . $mega_id;
	}
	return $classes;
}, 10, 4 );

add_action( 'wp_nav_menu_item_custom_fields', function ( $item_id, $item ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$mega_id = (int) get_post_meta( $item_id, '_hmpro_mega_menu_id', true );

	$posts = get_posts( [
		'post_type'      => 'hm_mega_menu',
		'post_status'    => 'publish',
		'posts_per_page' => 50,
		'orderby'        => 'ID',
		'order'          => 'ASC',
	] );

	echo '<p class="description description-wide hmpro-mega-bind-field">';
	echo '<label for="edit-menu-item-hmpro-mega-' . esc_attr( (string) $item_id ) . '">';
	echo esc_html__( 'Mega Menu Layout', 'hmpro' ) . '<br />';
	echo '<select id="edit-menu-item-hmpro-mega-' . esc_attr( (string) $item_id ) . '" class="widefat code edit-menu-item-hmpro-mega" name="menu-item-hmpro-mega[' . esc_attr( (string) $item_id ) . ']">';
	echo '<option value="0">' . esc_html__( '— None —', 'hmpro' ) . '</option>';

	foreach ( $posts as $p ) {
		$pid = (int) $p->ID;
		printf(
			'<option value="%d" %s>%s</option>',
			$pid,
			selected( $mega_id, $pid, false ),
			esc_html( get_the_title( $pid ) )
		);
	}

	echo '</select>';
	echo '</label>';
	echo '</p>';
}, 10, 2 );

add_action( 'wp_update_nav_menu_item', function ( $menu_id, $menu_item_db_id ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( ! isset( $_POST['menu-item-hmpro-mega'] ) || ! is_array( $_POST['menu-item-hmpro-mega'] ) ) {
		delete_post_meta( $menu_item_db_id, '_hmpro_mega_menu_id' );
		return;
	}

	$raw = wp_unslash( $_POST['menu-item-hmpro-mega'] );
	$val = isset( $raw[ $menu_item_db_id ] ) ? absint( $raw[ $menu_item_db_id ] ) : 0;

	if ( $val > 0 ) {
		update_post_meta( $menu_item_db_id, '_hmpro_mega_menu_id', $val );
	} else {
		delete_post_meta( $menu_item_db_id, '_hmpro_mega_menu_id' );
	}
}, 10, 2 );

/**
 * Frontend injection:
 * Add a full-width mega panel under the menu item (top-level only).
 */
add_filter( 'walker_nav_menu_start_el', function ( $item_output, $item, $depth, $args ) {
	if ( is_admin() ) {
		return $item_output;
	}

	if ( 0 !== $depth ) {
		return $item_output;
	}

	$mega_id = (int) get_post_meta( $item->ID, '_hmpro_mega_menu_id', true );
	if ( $mega_id < 1 ) {
		return $item_output;
	}

	$settings = function_exists( 'hmpro_mega_menu_get_settings' ) ? hmpro_mega_menu_get_settings( $mega_id ) : [ 'height_mode' => 'auto' ];
	$mode     = isset( $settings['height_mode'] ) ? sanitize_key( (string) $settings['height_mode'] ) : 'auto';
	if ( ! in_array( $mode, [ 'auto', 'compact', 'showcase' ], true ) ) {
		$mode = 'auto';
	}

	// Mark the link for CSS hover targeting.
	$item_output = preg_replace_callback(
		'/<a\\b([^>]*)>/',
		function ( $matches ) use ( $mega_id ) {
			$attrs = $matches[1];
			if ( preg_match( '/class=(\"|\')(.*?)\\1/', $attrs, $class_match ) ) {
				$new_class = trim( $class_match[2] . ' hmpro-has-mega' );
				$attrs     = str_replace( $class_match[0], 'class="' . esc_attr( $new_class ) . '"', $attrs );
			} else {
				$attrs .= ' class="hmpro-has-mega"';
			}
			$attrs .= ' data-hmpro-mega="' . esc_attr( (string) $mega_id ) . '"';
			return '<a' . $attrs . '>';
		},
		$item_output,
		1
	);

	$panel = '';
	if ( shortcode_exists( 'hm_mega_menu' ) ) {
		$panel .= '<div class="hmpro-mega-panel hmpro-mega-height-' . esc_attr( $mode ) . '" data-mega-id="' . esc_attr( (string) $mega_id ) . '">';
		$panel .= do_shortcode( '[hm_mega_menu id="' . (int) $mega_id . '"]' );
		$panel .= '</div>';
	}

	// Add a real toggle target inside the anchor so click-to-open can be bound to the caret only.
	// (Pseudo-element carets cannot be targeted reliably in JS.)
	$toggle = '<span class="hmpro-mega-toggle" aria-hidden="true"></span>';

	// Append toggle + panel inside the <li> by injecting before and after </a>.
	$item_output = str_replace( '</a>', $toggle . '</a>' . $panel, $item_output );

	return $item_output;
}, 10, 4 );
