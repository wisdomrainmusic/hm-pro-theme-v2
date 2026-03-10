<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Mega Menu Library (Commit 3A)
 * - CPT: hm_mega_menu
 * - Layout JSON stored in post meta: _hmpro_mega_layout
 * - Settings stored in post meta: _hmpro_mega_settings
 * - Shortcode: [hm_mega_menu id="123"]
 */

add_action( 'init', function () {
	$labels = [
		'name'               => __( 'Mega Menus', 'hmpro' ),
		'singular_name'      => __( 'Mega Menu', 'hmpro' ),
		'add_new'            => __( 'Add Mega Menu', 'hmpro' ),
		'add_new_item'       => __( 'Add New Mega Menu', 'hmpro' ),
		'edit_item'          => __( 'Edit Mega Menu', 'hmpro' ),
		'new_item'           => __( 'New Mega Menu', 'hmpro' ),
		'view_item'          => __( 'View Mega Menu', 'hmpro' ),
		'search_items'       => __( 'Search Mega Menus', 'hmpro' ),
		'not_found'          => __( 'No mega menus found.', 'hmpro' ),
		'not_found_in_trash' => __( 'No mega menus found in Trash.', 'hmpro' ),
	];

	register_post_type( 'hm_mega_menu', [
		'labels'              => $labels,
		'public'              => false,
		'show_ui'             => true,
		'show_in_menu'        => false, // we surface via HM Pro Theme menu
		'supports'            => [ 'title' ],
		'capability_type'     => 'post',
		'map_meta_cap'        => true,
		'menu_position'       => 60,
		'menu_icon'           => 'dashicons-menu',
		'exclude_from_search' => true,
	] );
}, 10 );

function hmpro_mega_default_layout_schema() {
	return [
		'schema_version' => 1,
		'regions'        => [
			'mega_content' => [],
		],
	];
}

function hmpro_mega_default_settings() {
	return [
		'height_mode'     => 'auto', // auto|compact|showcase
		'secondary_menu' => 0, // WP nav menu term_id
	];
}

function hmpro_mega_menu_get_layout( $post_id ) {
	$post_id = absint( $post_id );
	if ( $post_id < 1 ) {
		return hmpro_mega_default_layout_schema();
	}

	$raw = get_post_meta( $post_id, '_hmpro_mega_layout', true );
	if ( empty( $raw ) || ! is_array( $raw ) ) {
		return hmpro_mega_default_layout_schema();
	}

	$raw['schema_version'] = isset( $raw['schema_version'] ) ? absint( $raw['schema_version'] ) : 1;
	$raw['regions']        = isset( $raw['regions'] ) && is_array( $raw['regions'] ) ? $raw['regions'] : [];

	$default       = hmpro_mega_default_layout_schema();
	$raw['regions'] = array_merge( $default['regions'], $raw['regions'] );

	return $raw;
}

function hmpro_mega_menu_update_layout( $post_id, array $layout ) {
	$post_id = absint( $post_id );
	if ( $post_id < 1 ) {
		return false;
	}
	if ( empty( $layout['regions'] ) || ! is_array( $layout['regions'] ) ) {
		return false;
	}
	return update_post_meta( $post_id, '_hmpro_mega_layout', $layout );
}

function hmpro_mega_menu_get_settings( $post_id ) {
	$post_id = absint( $post_id );
	$raw     = ( $post_id > 0 ) ? get_post_meta( $post_id, '_hmpro_mega_settings', true ) : [];
	if ( empty( $raw ) || ! is_array( $raw ) ) {
		$raw = [];
	}
	$default = hmpro_mega_default_settings();
	$out     = array_merge( $default, $raw );

	$mode = isset( $out['height_mode'] ) ? sanitize_key( (string) $out['height_mode'] ) : 'auto';
	if ( ! in_array( $mode, [ 'auto', 'compact', 'showcase' ], true ) ) {
		$mode = 'auto';
	}
	$out['height_mode'] = $mode;
	$out['secondary_menu'] = isset( $out['secondary_menu'] ) ? absint( $out['secondary_menu'] ) : 0;
	return $out;
}

function hmpro_mega_menu_update_settings( $post_id, array $settings ) {
	$post_id = absint( $post_id );
	if ( $post_id < 1 ) {
		return false;
	}

	$mode = isset( $settings['height_mode'] ) ? sanitize_key( (string) $settings['height_mode'] ) : 'auto';
	if ( ! in_array( $mode, [ 'auto', 'compact', 'showcase' ], true ) ) {
		$mode = 'auto';
	}

	$secondary = isset( $settings['secondary_menu'] ) ? absint( $settings['secondary_menu'] ) : 0;
	if ( $secondary > 0 ) {
		$menu_obj = wp_get_nav_menu_object( $secondary );
		if ( ! $menu_obj ) {
			$secondary = 0;
		}
	}

	$clean = [
		'height_mode'     => $mode,
		'secondary_menu' => $secondary,
	];
	return update_post_meta( $post_id, '_hmpro_mega_settings', $clean );
}

function hmpro_mega_sanitize_layout( $payload ) {
	// For 3A: minimal, safe base. 3B/3C will expand types/settings allowlist.
	$out = hmpro_mega_default_layout_schema();
	$out['schema_version'] = 1;

	if ( ! is_array( $payload ) ) {
		return $out;
	}

	$regions = isset( $payload['regions'] ) && is_array( $payload['regions'] ) ? $payload['regions'] : [];
	$rows    = isset( $regions['mega_content'] ) && is_array( $regions['mega_content'] ) ? $regions['mega_content'] : [];

	$clean_rows = [];
	foreach ( $rows as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}
		$row_id = isset( $row['id'] ) ? sanitize_key( $row['id'] ) : '';
		if ( '' === $row_id ) {
			continue;
		}

		$columns    = isset( $row['columns'] ) && is_array( $row['columns'] ) ? $row['columns'] : [];
		$clean_cols = [];
		foreach ( $columns as $col ) {
			if ( ! is_array( $col ) ) {
				continue;
			}
			$col_id = isset( $col['id'] ) ? sanitize_key( $col['id'] ) : '';
			if ( '' === $col_id ) {
				continue;
			}
			$width = isset( $col['width'] ) ? absint( $col['width'] ) : 3;
			if ( $width < 1 || $width > 12 ) {
				$width = 3;
			}

			$components       = isset( $col['components'] ) && is_array( $col['components'] ) ? $col['components'] : [];
			$clean_components = [];
			foreach ( $components as $comp ) {
				if ( ! is_array( $comp ) ) {
					continue;
				}
				$comp_id = isset( $comp['id'] ) ? sanitize_key( $comp['id'] ) : '';
				$type    = isset( $comp['type'] ) ? sanitize_key( $comp['type'] ) : '';
				if ( '' === $comp_id || '' === $type ) {
					continue;
				}
				// allowed types (mega builder set)
				if ( ! in_array( $type, [ 'mega_column_menu', 'image', 'html', 'button', 'spacer' ], true ) ) {
					continue;
				}

				$settings       = isset( $comp['settings'] ) && is_array( $comp['settings'] ) ? $comp['settings'] : [];
				$clean_settings = [];

				if ( 'mega_column_menu' === $type ) {
					$clean_settings['source']          = 'wp_menu';
					$clean_settings['menu_id']         = isset( $settings['menu_id'] ) ? absint( $settings['menu_id'] ) : 0;
					$clean_settings['root_item_id']    = isset( $settings['root_item_id'] ) ? absint( $settings['root_item_id'] ) : 0;
					$clean_settings['max_depth']       = isset( $settings['max_depth'] ) ? max( 1, min( 6, absint( $settings['max_depth'] ) ) ) : 2;
					$clean_settings['show_root_title'] = ! empty( $settings['show_root_title'] ) ? 1 : 0;
					$clean_settings['max_items']       = isset( $settings['max_items'] ) ? max( 1, min( 50, absint( $settings['max_items'] ) ) ) : 8;
					$clean_settings['show_more']       = ! empty( $settings['show_more'] ) ? 1 : 0;
					$clean_settings['flatten']         = ! empty( $settings['flatten'] ) ? 1 : 0;
					$clean_settings['more_text']       = isset( $settings['more_text'] ) ? sanitize_text_field( (string) $settings['more_text'] ) : 'Daha Fazla Gör';
					$mode                             = isset( $settings['more_mode'] ) ? sanitize_key( (string) $settings['more_mode'] ) : 'expand';
					if ( ! in_array( $mode, [ 'expand', 'link' ], true ) ) {
						$mode = 'expand';
					}
					$clean_settings['more_mode'] = $mode;
					$clean_settings['less_text'] = isset( $settings['less_text'] )
						? sanitize_text_field( (string) $settings['less_text'] )
						: 'Daha Az Göster';
				} elseif ( 'image' === $type ) {
					$clean_settings['attachment_id'] = isset( $settings['attachment_id'] ) ? absint( $settings['attachment_id'] ) : 0;
					$clean_settings['url']           = isset( $settings['url'] ) ? esc_url_raw( (string) $settings['url'] ) : '';
					$clean_settings['alt']           = isset( $settings['alt'] ) ? sanitize_text_field( (string) $settings['alt'] ) : '';
					$clean_settings['link']          = isset( $settings['link'] ) ? esc_url_raw( (string) $settings['link'] ) : '';
					$clean_settings['new_tab']       = ! empty( $settings['new_tab'] ) ? 1 : 0;

					$size = isset( $settings['size'] ) ? sanitize_key( (string) $settings['size'] ) : 'large';
					if ( ! in_array( $size, [ 'medium', 'large', 'full' ], true ) ) {
						$size = 'large';
					}
					$clean_settings['size'] = $size;

					$aspect = isset( $settings['aspect'] ) ? sanitize_key( (string) $settings['aspect'] ) : 'landscape';
					if ( ! in_array( $aspect, [ 'landscape', 'square', 'portrait' ], true ) ) {
						$aspect = 'landscape';
					}
					$clean_settings['aspect'] = $aspect;

					$fit = isset( $settings['fit'] ) ? sanitize_key( (string) $settings['fit'] ) : 'cover';
					if ( ! in_array( $fit, [ 'cover', 'contain' ], true ) ) {
						$fit = 'cover';
					}
					$clean_settings['fit'] = $fit;
				} elseif ( 'button' === $type ) {
					$clean_settings['text'] = isset( $settings['text'] ) ? sanitize_text_field( (string) $settings['text'] ) : '';
					$clean_settings['url']  = isset( $settings['url'] ) ? esc_url_raw( (string) $settings['url'] ) : '';
				} elseif ( 'html' === $type ) {
					$clean_settings['content'] = isset( $settings['content'] ) ? (string) $settings['content'] : '';
				} elseif ( 'spacer' === $type ) {
					$clean_settings['width']  = isset( $settings['width'] ) ? absint( $settings['width'] ) : 0;
					$clean_settings['height'] = isset( $settings['height'] ) ? absint( $settings['height'] ) : 0;
				}

				$clean_components[] = [
					'id'       => $comp_id,
					'type'     => $type,
					'settings' => $clean_settings,
				];
			}

			$clean_cols[] = [
				'id'         => $col_id,
				'width'      => $width,
				'components' => $clean_components,
			];
		}

		$clean_rows[] = [
			'id'      => $row_id,
			'columns' => $clean_cols,
		];
	}

	$out['regions']['mega_content'] = $clean_rows;
	return $out;
}

add_shortcode( 'hm_mega_menu', function ( $atts ) {
	$atts    = shortcode_atts( [ 'id' => 0 ], $atts, 'hm_mega_menu' );
	$post_id = absint( $atts['id'] );
	if ( $post_id < 1 ) {
		return '';
	}

	$layout   = hmpro_mega_menu_get_layout( $post_id );
	$settings = hmpro_mega_menu_get_settings( $post_id );

	ob_start();
	echo '<div class="hmpro-mega-layout hmpro-mega-height-' . esc_attr( $settings['height_mode'] ) . '" data-mega-id="' . esc_attr( (string) $post_id ) . '">';
	/**
	 * DEBUG: Force v2 ON by default so we can verify canvas output is being rendered.
	 * Once confirmed, we can revert to the toggle-based behavior.
	 */
	$use_v2 = (int) get_theme_mod( 'hmpro_enable_mega_menu_v2', 1 );
	if ( 1 === $use_v2 && function_exists( 'hmpro_render_mega_canvas' ) ) {
		hmpro_render_mega_canvas( $post_id );
	} elseif ( function_exists( 'hmpro_builder_render_layout_rows' ) ) {
		hmpro_builder_render_layout_rows( $layout['regions']['mega_content'] ?? [], 'mega' );
	}

	if ( ! empty( $settings['secondary_menu'] ) ) {
		$menu_id = absint( $settings['secondary_menu'] );
		if ( $menu_id > 0 ) {
			echo '<div class="hmpro-mega-secondary">';
			wp_nav_menu( [
				'menu'        => $menu_id,
				'container'   => false,
				'fallback_cb' => '__return_empty_string',
				'depth'       => 1,
			] );
			echo '</div>';
		}
	}
	echo '</div>';
	return (string) ob_get_clean();
} );
