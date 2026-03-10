<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Mega Menu Canvas (v2)
 * Frontend-only renderer that outputs a deterministic 4-column canvas.
 */
function hmpro_mega_canvas_render_widget( array $comp ) {
	$type = isset( $comp['type'] ) ? sanitize_key( $comp['type'] ) : '';
	$set  = isset( $comp['settings'] ) && is_array( $comp['settings'] ) ? $comp['settings'] : [];
	$id   = isset( $comp['id'] ) ? sanitize_key( $comp['id'] ) : '';

	$widget_type = '';
	if ( 'mega_column_menu' === $type ) {
		$widget_type = 'menu';
	} elseif ( 'image' === $type ) {
		$widget_type = 'image';
	} elseif ( 'html' === $type ) {
		$widget_type = 'text';
	}

	if ( '' === $widget_type ) {
		return;
	}

	echo '<div class="hmpro-mega-widget hmpro-widget-' . esc_attr( $widget_type ) . '" data-widget="' . esc_attr( $id ) . '">';

	if ( 'menu' === $widget_type && function_exists( 'hmpro_builder_comp_mega_column_menu' ) ) {
		hmpro_builder_comp_mega_column_menu( $set );
	} elseif ( 'image' === $widget_type && function_exists( 'hmpro_builder_comp_image' ) ) {
		hmpro_builder_comp_image( $set );
	} elseif ( 'text' === $widget_type && function_exists( 'hmpro_builder_comp_html' ) ) {
		hmpro_builder_comp_html( $set );
	}

	echo '</div>';
}

function hmpro_render_mega_canvas( $mega_id ) {
	$mega_id = absint( $mega_id );
	if ( $mega_id < 1 ) {
		return;
	}

	$rows = [];
	if ( function_exists( 'hmpro_mega_menu_get_layout' ) ) {
		$layout = hmpro_mega_menu_get_layout( $mega_id );
		if ( isset( $layout['regions']['mega_content'] ) && is_array( $layout['regions']['mega_content'] ) ) {
			$rows = $layout['regions']['mega_content'];
		}
	}

	$columns = [
		1 => [],
		2 => [],
		3 => [],
		4 => [],
	];

	foreach ( $rows as $row ) {
		if ( ! is_array( $row ) || empty( $row['columns'] ) || ! is_array( $row['columns'] ) ) {
			continue;
		}

		$col_index = 0;
		foreach ( $row['columns'] as $col ) {
			if ( ! is_array( $col ) ) {
				$col_index++;
				continue;
			}

			$target = $col_index + 1;
			if ( $target > 4 ) {
				$col_index++;
				continue;
			}

			$components = isset( $col['components'] ) && is_array( $col['components'] ) ? $col['components'] : [];
			foreach ( $components as $component ) {
				$columns[ $target ][] = $component;
			}

			$col_index++;
		}
	}

	echo '<div class="hmpro-mega-canvas" data-mega-id="' . esc_attr( (string) $mega_id ) . '">';
	for ( $i = 1; $i <= 4; $i++ ) {
		echo '<div class="hmpro-mega-col col-' . esc_attr( (string) $i ) . '" data-col="' . esc_attr( (string) $i ) . '">';
		if ( ! empty( $columns[ $i ] ) ) {
			foreach ( $columns[ $i ] as $component ) {
				if ( is_array( $component ) ) {
					hmpro_mega_canvas_render_widget( $component );
				}
			}
		}
		echo '</div>';
	}
	echo '</div>';
}
