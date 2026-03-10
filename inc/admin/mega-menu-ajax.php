<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Mega Menu Builder AJAX handlers
 *
 * IMPORTANT:
 * These must be loaded on admin-ajax.php requests.
 * (Putting wp_ajax hooks only inside the builder page file won't run on AJAX.)
 */

add_action( 'wp_ajax_hmpro_get_menu_root_items', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'forbidden', 403 );
	}

	$nonce = isset( $_POST['_ajax_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_ajax_nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'hmpro_mega_builder' ) ) {
		wp_send_json_error( 'nonce', 403 );
	}

	$menu_id = isset( $_POST['menu_id'] ) ? absint( $_POST['menu_id'] ) : 0;
	if ( $menu_id < 1 ) {
		wp_send_json_success( [] );
	}

	$items = wp_get_nav_menu_items( $menu_id );
	if ( empty( $items ) || ! is_array( $items ) ) {
		wp_send_json_success( [] );
	}

	// Keep WP order.
	usort( $items, function( $a, $b ) {
		return (int) $a->menu_order <=> (int) $b->menu_order;
	} );

	$by_id = [];
	foreach ( $items as $it ) {
		if ( is_object( $it ) && ! empty( $it->ID ) ) {
			$by_id[ (int) $it->ID ] = $it;
		}
	}

	$depth_cache = [];
	$calc_depth = function( $id ) use ( &$calc_depth, &$depth_cache, $by_id ) {
		$id = (int) $id;
		if ( isset( $depth_cache[ $id ] ) ) {
			return (int) $depth_cache[ $id ];
		}
		if ( empty( $by_id[ $id ] ) ) {
			$depth_cache[ $id ] = 0;
			return 0;
		}
		$parent = isset( $by_id[ $id ]->menu_item_parent ) ? (int) $by_id[ $id ]->menu_item_parent : 0;
		if ( $parent <= 0 || $parent === $id ) {
			$depth_cache[ $id ] = 0;
			return 0;
		}
		$d = 1 + $calc_depth( $parent );
		if ( $d > 12 ) {
			$d = 12;
		}
		$depth_cache[ $id ] = $d;
		return $d;
	};

	$out = [];
	foreach ( $items as $it ) {
		if ( ! is_object( $it ) || empty( $it->ID ) ) {
			continue;
		}
		$id    = (int) $it->ID;
		$title = (string) $it->title;
		$depth = $calc_depth( $id );

		$prefix = '';
		if ( $depth > 0 ) {
			$prefix = str_repeat( '— ', $depth );
		}

		$out[] = [
			'id'    => $id,
			'title' => $prefix . $title,
		];
	}

	wp_send_json_success( $out );
} );
