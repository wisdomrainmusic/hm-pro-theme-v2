<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builder Storage (Commit 017)
 * Options:
 * - hmpro_header_layout
 * - hmpro_footer_layout
 *
 * Schema:
 * {
 *   schema_version: 1,
 *   regions: {
 *     header_top: [rows], header_main: [rows], header_bottom: [rows], header_drawer: [rows]
 *   }
 * }
 *
 * rows: [{ id, columns: [{ id, width, components: [{ id, type, settings }] }] }]
 */

function hmpro_builder_option_key( $area ) {
	$area = ( 'footer' === $area ) ? 'footer' : 'header';
	return ( 'footer' === $area ) ? 'hmpro_footer_layout' : 'hmpro_header_layout';
}

function hmpro_builder_default_schema( $area ) {
	$area = ( 'footer' === $area ) ? 'footer' : 'header';

	$regions = ( 'footer' === $area )
		? array( 'footer_top', 'footer_main', 'footer_bottom' )
		: array( 'header_top', 'header_main', 'header_bottom', 'header_drawer' );

	$out = array(
		'schema_version' => 1,
		'regions'        => array(),
	);

	foreach ( $regions as $key ) {
		$out['regions'][ $key ] = array();
	}

	return $out;
}

function hmpro_builder_get_layout( $area ) {
	$key = hmpro_builder_option_key( $area );
	$raw = get_option( $key, null );

	if ( empty( $raw ) || ! is_array( $raw ) ) {
		return hmpro_builder_default_schema( $area );
	}

	// Merge with defaults to ensure all regions exist.
	$default = hmpro_builder_default_schema( $area );
	$raw['schema_version'] = isset( $raw['schema_version'] ) ? absint( $raw['schema_version'] ) : 1;
	$raw['regions']        = isset( $raw['regions'] ) && is_array( $raw['regions'] ) ? $raw['regions'] : array();

	$raw['regions'] = array_merge( $default['regions'], $raw['regions'] );

	return $raw;
}

function hmpro_builder_update_layout( $area, array $layout ) {
	if ( empty( $layout['regions'] ) || ! is_array( $layout['regions'] ) ) {
		return false;
	}
	$key = hmpro_builder_option_key( $area );
	return update_option( $key, $layout, false );
}

/**
 * Sanitize layout payload coming from admin.
 */
function hmpro_builder_sanitize_layout( $area, $payload ) {
	$area = ( 'footer' === $area ) ? 'footer' : 'header';

	$allowed_types = array( 'logo', 'menu', 'search', 'social', 'social_icon_button', 'cart', 'button', 'html', 'spacer', 'footer_menu', 'footer_info', 'footer_image' );
	$allowed_icon_modes = array( 'preset', 'custom' );
	$allowed_icon_presets = array( 'facebook', 'instagram', 'linkedin', 'x', 'youtube', 'tiktok', 'whatsapp', 'telegram' );
	$allowed_svg_tags = array(
		'svg'      => array(
			'viewBox'    => true,
			'xmlns'      => true,
			'width'      => true,
			'height'     => true,
			'fill'       => true,
			'stroke'     => true,
			'aria-hidden'=> true,
			'role'       => true,
			'focusable'  => true,
		),
		'g'        => array( 'fill' => true, 'stroke' => true ),
		'path'     => array( 'd' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true ),
		'circle'   => array( 'cx' => true, 'cy' => true, 'r' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true ),
		'rect'     => array( 'x' => true, 'y' => true, 'width' => true, 'height' => true, 'rx' => true, 'ry' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true ),
		'line'     => array( 'x1' => true, 'y1' => true, 'x2' => true, 'y2' => true, 'stroke' => true, 'stroke-width' => true ),
		'polyline' => array( 'points' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true ),
		'polygon'  => array( 'points' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true ),
		'use'      => array( 'href' => true, 'xlink:href' => true ),
	);

	// Settings allowlist per component type (can expand in Commit 019).
	$allowed_settings = array(
		'logo'   => array( 'alignment', 'visibility', 'spacing' ),
		'menu'   => array( 'alignment', 'visibility', 'spacing', 'location', 'menu_id', 'depth', 'source' ),
		'search' => array( 'alignment', 'visibility', 'spacing', 'placeholder' ),
		// Social stores URLs in a nested array: settings.urls[network] = url.
		// Keep allowlist tight but compatible with admin UI + renderer.
		'social' => array(
			'alignment',
			'visibility',
			'spacing',
			'size',
			'gap',
			'new_tab',
			'urls',
		),
		'social_icon_button' => array(
			'alignment',
			'visibility',
			'spacing',
			'url',
			'new_tab',
			'transparent',
			'icon_mode',
			'icon_preset',
			'custom_icon',
		),
		'cart'   => array( 'alignment', 'visibility', 'spacing' ),
		'button' => array( 'alignment', 'visibility', 'spacing', 'text', 'url', 'rel', 'target' ),
		'html'   => array( 'visibility', 'spacing', 'content' ),
		'spacer' => array( 'visibility', 'spacing', 'width', 'height' ),
		'footer_menu' => array( 'alignment', 'visibility', 'spacing', 'menu_id', 'title', 'show_title' ),
		'footer_info' => array( 'alignment', 'visibility', 'spacing', 'title', 'lines' ),
		'footer_image' => array( 'visibility', 'spacing', 'attachment_id', 'url', 'max_width', 'link', 'new_tab' ),
	);

	$out = hmpro_builder_default_schema( $area );
	$out['schema_version'] = 1;

	if ( ! is_array( $payload ) ) {
		return $out;
	}

	$regions = isset( $payload['regions'] ) && is_array( $payload['regions'] ) ? $payload['regions'] : array();

	foreach ( $out['regions'] as $region_key => $_rows_default ) {
		$rows = isset( $regions[ $region_key ] ) && is_array( $regions[ $region_key ] ) ? $regions[ $region_key ] : array();
		$clean_rows = array();

		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$row_id = isset( $row['id'] ) ? sanitize_key( $row['id'] ) : '';
			if ( '' === $row_id ) {
				continue;
			}

			$columns = isset( $row['columns'] ) && is_array( $row['columns'] ) ? $row['columns'] : array();
			$clean_cols = array();

			foreach ( $columns as $col ) {
				if ( ! is_array( $col ) ) {
					continue;
				}

				$col_id = isset( $col['id'] ) ? sanitize_key( $col['id'] ) : '';
				if ( '' === $col_id ) {
					continue;
				}

				$width = isset( $col['width'] ) ? absint( $col['width'] ) : 12;
				if ( $width < 1 || $width > 12 ) {
					$width = 12;
				}

				$components = isset( $col['components'] ) && is_array( $col['components'] ) ? $col['components'] : array();
				$clean_components = array();

				foreach ( $components as $comp ) {
					if ( ! is_array( $comp ) ) {
						continue;
					}

					$comp_id = isset( $comp['id'] ) ? sanitize_key( $comp['id'] ) : '';
					$type    = isset( $comp['type'] ) ? sanitize_key( $comp['type'] ) : '';

					// Back-compat: older layouts may store component types with dashes
					// (e.g. "footer-info"). Normalize to underscore variants so sanitize + renderer
					// treat them as the same component.
					if ( '' !== $type && false !== strpos( $type, '-' ) ) {
						$type = str_replace( '-', '_', $type );
					}

					if ( '' === $comp_id || '' === $type || ! in_array( $type, $allowed_types, true ) ) {
						continue;
					}

					$settings = isset( $comp['settings'] ) && is_array( $comp['settings'] ) ? $comp['settings'] : array();
					$clean_settings = array();

					$type_allowed = isset( $allowed_settings[ $type ] ) ? $allowed_settings[ $type ] : array();
					foreach ( $type_allowed as $k ) {
						if ( ! array_key_exists( $k, $settings ) ) {
							continue;
						}

						$v = $settings[ $k ];

						// Basic sanitization by key.
						if ( in_array( $k, array( 'lines', 'text' ), true ) ) {
							// Preserve multi-line content for textarea-like fields.
							$clean_settings[ $k ] = is_scalar( $v ) ? sanitize_textarea_field( (string) $v ) : '';
						} elseif ( in_array( $k, array( 'placeholder', 'title', 'alignment', 'visibility', 'spacing', 'rel', 'target', 'source', 'location', 'size', 'gap' ), true ) ) {
							$clean_settings[ $k ] = is_scalar( $v ) ? sanitize_text_field( (string) $v ) : '';
					} elseif ( in_array( $k, array( 'menu_id', 'depth', 'width', 'height', 'root_item_id', 'attachment_id', 'max_width' ), true ) ) {
						$clean_settings[ $k ] = absint( $v );
					} elseif ( 'show_title' === $k ) {
						$clean_settings[ $k ] = ! empty( $v ) ? 1 : 0;
					} elseif ( 'new_tab' === $k ) {
							$clean_settings[ $k ] = ! empty( $v ) ? 1 : 0;
						} elseif ( 'transparent' === $k ) {
							$clean_settings[ $k ] = ! empty( $v ) ? 1 : 0;
					} elseif ( 'url' === $k || 'link' === $k ) {
							$clean_settings[ $k ] = is_scalar( $v ) ? esc_url_raw( (string) $v ) : '';
						} elseif ( 'icon_mode' === $k ) {
							$mode = is_scalar( $v ) ? sanitize_key( (string) $v ) : '';
							$clean_settings[ $k ] = in_array( $mode, $allowed_icon_modes, true ) ? $mode : 'preset';
						} elseif ( 'icon_preset' === $k ) {
							$preset = is_scalar( $v ) ? sanitize_key( (string) $v ) : '';
							$clean_settings[ $k ] = in_array( $preset, $allowed_icon_presets, true ) ? $preset : '';
						} elseif ( 'custom_icon' === $k ) {
							$raw_icon = is_scalar( $v ) ? (string) $v : '';
							$clean_settings[ $k ] = '' !== $raw_icon ? wp_kses( $raw_icon, $allowed_svg_tags ) : '';
						} elseif ( 'urls' === $k ) {
							$clean_urls = array();
							if ( is_array( $v ) ) {
								$allowed_social = array( 'facebook', 'instagram', 'x', 'youtube', 'tiktok', 'linkedin', 'whatsapp', 'telegram' );
								foreach ( $allowed_social as $nk ) {
									if ( empty( $v[ $nk ] ) ) {
										continue;
									}
									$raw_u = trim( (string) $v[ $nk ] );
									if ( '' !== $raw_u && ! preg_match( '~^https?://~i', $raw_u ) ) {
										$raw_u = 'https://' . ltrim( $raw_u, '/ ' );
									}
									$u = esc_url_raw( $raw_u );
									if ( '' === $u ) {
										$u = sanitize_text_field( $raw_u );
									}
									if ( '' !== $u ) {
										$clean_urls[ $nk ] = $u;
									}
								}
							}
							$clean_settings[ $k ] = $clean_urls;
						} elseif ( 'menu_id' === $k || 'depth' === $k || 'width' === $k || 'height' === $k ) {
							$clean_settings[ $k ] = absint( $v );
						} elseif ( 'content' === $k ) {
							// Stored raw; rendered with wp_kses_post in Commit 018.
							$clean_settings[ $k ] = is_scalar( $v ) ? (string) $v : '';
						}
					}

					$clean_components[] = array(
						'id'       => $comp_id,
						'type'     => $type,
						'settings' => $clean_settings,
					);
				}

				$clean_cols[] = array(
					'id'         => $col_id,
					'width'      => $width,
					'components' => $clean_components,
				);
			}

			$clean_rows[] = array(
				'id'      => $row_id,
				'columns' => $clean_cols,
			);
		}

		$out['regions'][ $region_key ] = $clean_rows;
	}

	return $out;
}
