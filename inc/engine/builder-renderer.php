<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builder Renderer (Commit 018)
 * Renders the stored layout safely on frontend.
 */

/**
 * Convert hex color (#rrggbb or #rgb) to [r,g,b] array.
 *
 * @param string $hex Hex color string.
 * @return array<int,int>|null
 */
function hmpro_hex_to_rgb( $hex ) {
	$hex = ltrim( (string) $hex, '#' );
	if ( 3 === strlen( $hex ) ) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}
	if ( 6 !== strlen( $hex ) ) {
		return null;
	}
	$r = hexdec( substr( $hex, 0, 2 ) );
	$g = hexdec( substr( $hex, 2, 2 ) );
	$b = hexdec( substr( $hex, 4, 2 ) );
	return [ $r, $g, $b ];
}

add_action(
	'hmpro/header/render_region',
	function ( $region_key ) {
		hmpro_builder_render_region( 'header', $region_key );
	},
	10,
	1
);

add_action(
	'hmpro/footer/render_region',
	function ( $region_key ) {
		hmpro_builder_render_region( 'footer', $region_key );
	},
	10,
	1
);

add_action( 'wp_head', function () {
	$height        = absint( get_theme_mod( 'hmpro_logo_max_height', 56 ) );
	$mobile_height = absint( get_theme_mod( 'hmpro_mobile_logo_max_height', 64 ) );
	$footer_height = absint( get_theme_mod( 'hmpro_footer_logo_max_height', 96 ) );

	// Optional colors (leave empty to preserve existing styling).
	$top_bg   = sanitize_hex_color( get_theme_mod( 'hmpro_topbar_bg_color', '' ) );
	$top_text = sanitize_hex_color( get_theme_mod( 'hmpro_topbar_text_color', '' ) );
	$top_search_text = sanitize_hex_color( get_theme_mod( 'hmpro_topbar_search_text_color', '' ) );
	$top_search_ph   = sanitize_hex_color( get_theme_mod( 'hmpro_topbar_search_placeholder_color', '' ) );
	$top_h    = absint( get_theme_mod( 'hmpro_topbar_height', 0 ) );
	if ( $top_h && $top_h < 28 ) {
		$top_h = 28;
	}
	if ( $top_h > 240 ) {
		$top_h = 240;
	}
	$foot_bg  = sanitize_hex_color( get_theme_mod( 'hmpro_footer_bg_color', '' ) );
	$foot_txt = sanitize_hex_color( get_theme_mod( 'hmpro_footer_text_color', '' ) );
	$menu_text   = sanitize_hex_color( get_theme_mod( 'hmpro_menu_text_color', '' ) );
	$menu_text_overlay = sanitize_hex_color( get_theme_mod( 'hmpro_menu_text_color_overlay', '' ) );
	$menu_hover  = sanitize_hex_color( get_theme_mod( 'hmpro_menu_hover_color', '' ) );
	$menu_active = sanitize_hex_color( get_theme_mod( 'hmpro_menu_active_color', '' ) );
	$mega_bg     = sanitize_hex_color( get_theme_mod( 'hmpro_mega_menu_bg_color', '' ) );
	$mega_link   = sanitize_hex_color( get_theme_mod( 'hmpro_mega_menu_link_color', '' ) );
	$show_logo   = (int) get_theme_mod( 'hmpro_show_header_logo', 1 );
	$hdr_backdrop_enable  = (int) get_theme_mod( 'hmpro_header_backdrop_enable', 0 );
	$hdr_backdrop_color   = sanitize_hex_color( get_theme_mod( 'hmpro_header_backdrop_color', '' ) );
	$hdr_backdrop_opacity = absint( get_theme_mod( 'hmpro_header_backdrop_opacity', 85 ) );

	// Social icon style overrides (Social Icon Button).
	$soc_color    = sanitize_hex_color( get_theme_mod( 'hmpro_social_icon_color', '' ) );
	$soc_bg       = sanitize_hex_color( get_theme_mod( 'hmpro_social_icon_bg', '' ) );
	$soc_border   = sanitize_hex_color( get_theme_mod( 'hmpro_social_icon_border', '' ) );
	$soc_h_color  = sanitize_hex_color( get_theme_mod( 'hmpro_social_icon_hover_color', '' ) );
	$soc_h_bg     = sanitize_hex_color( get_theme_mod( 'hmpro_social_icon_hover_bg', '' ) );
	$soc_h_border = sanitize_hex_color( get_theme_mod( 'hmpro_social_icon_hover_border', '' ) );
	$soc_contrast = sanitize_hex_color( get_theme_mod( 'hmpro_social_icon_contrast', '' ) );
	$soc_size     = absint( get_theme_mod( 'hmpro_social_icon_size', 34 ) );
	$soc_radius   = absint( get_theme_mod( 'hmpro_social_icon_radius', 999 ) );
	$soc_svg      = absint( get_theme_mod( 'hmpro_social_icon_svg_size', 18 ) );

	$css = ':root{--hmpro-logo-max-height:' . $height . 'px;--hmpro-logo-max-height-mobile:' . $mobile_height . 'px;--hmpro-footer-logo-max-height:' . $footer_height . 'px;}';

	// Top Bar height override (header_top region).
	if ( $top_h ) {
		$css .= ':root{--hmpro-topbar-height:' . $top_h . 'px;}';
		$css .= '.hmpro-header-builder .hmpro-builder-region.hmpro-region-header_top .hmpro-builder-row{min-height:' . $top_h . 'px;padding-top:0;padding-bottom:0;}';
	}

	// Header logo visibility (Header Builder: main region logo component).
	if ( 0 === $show_logo ) {
		$css .= '.hmpro-header-builder .hmpro-region-header_main .hmpro-logo-wrap{display:none!important;}';
	}

	// Header Backdrop Panel behind Top + Main regions.
	if ( $hdr_backdrop_enable ) {
		$base = $hdr_backdrop_color ? $hdr_backdrop_color : ( $top_bg ? $top_bg : '' );
		$rgb  = $base ? hmpro_hex_to_rgb( $base ) : null;
		$op   = max( 0, min( 100, (int) $hdr_backdrop_opacity ) ) / 100;
		if ( $rgb ) {
			$rgba = 'rgba(' . (int) $rgb[0] . ',' . (int) $rgb[1] . ',' . (int) $rgb[2] . ',' . $op . ')';
			/**
			 * NOTE:
			 * header.css currently forces `.hmpro-builder-region { background: transparent !important; }`
			 * so we must override with equal/greater strength.
			 */
			$css .= '#site-header.hmpro-hb-enabled .hmpro-builder-region.hmpro-region-header_top{background:' . $rgba . ' !important;}';
			$css .= '#site-header.hmpro-hb-enabled .hmpro-builder-region.hmpro-region-header_main{background:' . $rgba . ' !important;}';

			// Keep inner layers transparent so the panel reads as one unified surface.
			$css .= '#site-header.hmpro-hb-enabled .hmpro-builder-region.hmpro-region-header_top .hmpro-builder-row{background:transparent !important;}';
			$css .= '#site-header.hmpro-hb-enabled .hmpro-builder-region.hmpro-region-header_main .hmpro-builder-row{background:transparent !important;}';
		}
	}

	// Social icon CSS variables.
	if ( $soc_color ) {
		$css .= ':root{--hmpro-social-icon-color:' . $soc_color . ';}';
	}
	if ( $soc_bg ) {
		$css .= ':root{--hmpro-social-icon-bg:' . $soc_bg . ';}';
	}
	if ( $soc_border ) {
		$css .= ':root{--hmpro-social-icon-border:' . $soc_border . ';}';
	}
	if ( $soc_h_color ) {
		$css .= ':root{--hmpro-social-icon-hover-color:' . $soc_h_color . ';}';
	}
	if ( $soc_h_bg ) {
		$css .= ':root{--hmpro-social-icon-hover-bg:' . $soc_h_bg . ';}';
	}
	if ( $soc_h_border ) {
		$css .= ':root{--hmpro-social-icon-hover-border:' . $soc_h_border . ';}';
	}
	if ( $soc_contrast ) {
		$css .= ':root{--hmpro-social-icon-contrast:' . $soc_contrast . ';}';
	}
	if ( $soc_size ) {
		$css .= ':root{--hmpro-social-icon-size:' . $soc_size . 'px;}';
	}
	if ( $soc_radius ) {
		$css .= ':root{--hmpro-social-icon-radius:' . $soc_radius . 'px;}';
	}
	if ( $soc_svg ) {
		$css .= ':root{--hmpro-social-icon-svg-size:' . $soc_svg . 'px;}';
	}

	// Header Builder: Top region (Top Bar)
	if ( $top_bg ) {
		$css .= '.hmpro-region-header_top{background:' . $top_bg . ';}';
	}
	if ( $top_text ) {
		$css .= '.hmpro-region-header_top,.hmpro-region-header_top a,.hmpro-region-header_top .hmpro-builder-comp{color:' . $top_text . ';}';
		$css .= '.hmpro-region-header_top svg{color:' . $top_text . ';}';
	}

	// Top Bar Search input readability (text + placeholder).
	if ( $top_search_text ) {
		$css .= '.hmpro-region-header_top .hmpro-search-field{color:' . $top_search_text . ';}';
	}
	if ( $top_search_ph ) {
		$css .= '.hmpro-region-header_top .hmpro-search-field::placeholder{color:' . $top_search_ph . ';}';
		$css .= '.hmpro-region-header_top .hmpro-search-field::-webkit-input-placeholder{color:' . $top_search_ph . ';}';
		$css .= '.hmpro-region-header_top .hmpro-search-field::-moz-placeholder{color:' . $top_search_ph . ';opacity:1;}';
		$css .= '.hmpro-region-header_top .hmpro-search-field:-ms-input-placeholder{color:' . $top_search_ph . ';}';
		$css .= '.hmpro-region-header_top .hmpro-search-field::-ms-input-placeholder{color:' . $top_search_ph . ';}';
	}

	// Primary Menu (Header Builder: main nav) via CSS variables.
	if ( $menu_text ) {
		$css .= ':root{--hmpro-menu-text-color:' . $menu_text . ';}';
	}

	// If the Header Background Banner is active for this request, optionally override
	// the primary menu text color to improve contrast on top of dark hero imagery.
	if ( $menu_text_overlay && function_exists( 'hmpro_header_bg_banner_is_enabled' ) && hmpro_header_bg_banner_is_enabled() ) {
		$css .= ':root{--hmpro-menu-text-color:' . $menu_text_overlay . ';}';
	}
	if ( $menu_hover ) {
		$css .= ':root{--hmpro-menu-hover-color:' . $menu_hover . ';}';
	}
	if ( $menu_active ) {
		$css .= ':root{--hmpro-menu-active-color:' . $menu_active . ';}';
	}
	// Mega Menu v2 colors (dropdown panel only) via CSS variables.
	if ( $mega_bg ) {
		$css .= ':root{--hmpro-mega-bg-color:' . $mega_bg . ';}';
	}
	if ( $mega_link ) {
		$css .= ':root{--hmpro-mega-link-color:' . $mega_link . ';}';
	}


	// Footer Builder wrapper + regions
	if ( $foot_bg ) {
		$css .= '#site-footer.hmpro-footer-builder{background:' . $foot_bg . ';}';
		$css .= '#site-footer.hmpro-footer-builder .hmpro-builder-region{background:transparent;}';
	}
	if ( $foot_txt ) {
		$css .= '#site-footer.hmpro-footer-builder,#site-footer.hmpro-footer-builder a,#site-footer.hmpro-footer-builder .hmpro-builder-comp{color:' . $foot_txt . ';}';
		$css .= '#site-footer.hmpro-footer-builder svg{color:' . $foot_txt . ';}';
	}

	echo '<style>' . $css . '</style>';
}, 20 );

add_action( 'wp_footer', function () {
	hmpro_builder_output_social_sprite();
	if ( function_exists( 'hmpro_builder_output_footer_accordion_script' ) ) {
		hmpro_builder_output_footer_accordion_script();
	}
}, 20 );

/**
 * Generic renderer for layout rows (used by header/footer regions + mega layouts).
 * $context:
 * - header/footer: existing components
 * - mega: includes mega_column_menu + image
 */
function hmpro_builder_render_layout_rows( array $rows, $context = 'header', $region_key = '' ) {
	if ( empty( $rows ) ) {
		return;
	}

	$region_class = '';
	if ( is_string( $region_key ) && '' !== $region_key ) {
		$region_class = ' hmpro-region-' . sanitize_html_class( $region_key );
	}

	echo '<div class="hmpro-builder-region hmpro-builder-region-generic hmpro-context-' . esc_attr( $context ) . $region_class . '">';

	foreach ( $rows as $row ) {
		if ( ! is_array( $row ) || empty( $row['columns'] ) || ! is_array( $row['columns'] ) ) {
			continue;
		}
		$row_id = isset( $row['id'] ) ? sanitize_key( $row['id'] ) : '';
		echo '<div class="hmpro-builder-row" data-row="' . esc_attr( $row_id ) . '">';

		$col_index = 0;
		foreach ( $row['columns'] as $col ) {
			if ( ! is_array( $col ) ) {
				continue;
			}
			$col_id = isset( $col['id'] ) ? sanitize_key( $col['id'] ) : '';
			$width  = isset( $col['width'] ) ? absint( $col['width'] ) : 12;
			if ( $width < 1 || $width > 12 ) {
				$width = 12;
			}

			// Alignment class: keep legacy behavior for 3 columns, neutral for mega.
			$align_class = 'hmpro-align-left';
			if ( 'mega' !== $context ) {
				if ( 1 === $col_index ) {
					$align_class = 'hmpro-align-center';
				} elseif ( 2 === $col_index ) {
					$align_class = 'hmpro-align-right';
				}
			}

			echo '<div class="hmpro-builder-col hmpro-col-' . esc_attr( (string) $width ) . ' ' . esc_attr( $align_class ) . '" data-col="' . esc_attr( $col_id ) . '">';

			$components = isset( $col['components'] ) && is_array( $col['components'] ) ? $col['components'] : [];
			foreach ( $components as $comp ) {
				hmpro_builder_render_component( $comp, $context );
			}

			echo '</div>';
			$col_index++;
		}

		echo '</div>';
	}

	echo '</div>';
}

function hmpro_builder_render_region( $area, $region_key ) {
	$area = ( 'footer' === $area ) ? 'footer' : 'header';
	if ( ! function_exists( 'hmpro_builder_get_layout' ) ) {
		return;
	}

	$layout  = hmpro_builder_get_layout( $area );
	$regions = isset( $layout['regions'] ) && is_array( $layout['regions'] ) ? $layout['regions'] : array();
	$rows    = isset( $regions[ $region_key ] ) && is_array( $regions[ $region_key ] ) ? $regions[ $region_key ] : array();

	if ( empty( $rows ) ) {
		return;
	}
	hmpro_builder_render_layout_rows( $rows, $area, $region_key );
}

function hmpro_builder_render_component( $comp, $context = 'header' ) {
	if ( ! is_array( $comp ) ) {
		return;
	}

	$type = isset( $comp['type'] ) ? sanitize_key( $comp['type'] ) : '';
	// Back-compat: normalize dashed component types (e.g. "footer-info")
	// to underscore variants used by the renderer switch.
	if ( '' !== $type && false !== strpos( $type, '-' ) ) {
		$type = str_replace( '-', '_', $type );
	}
	$id   = isset( $comp['id'] ) ? sanitize_key( $comp['id'] ) : '';
	$set  = isset( $comp['settings'] ) && is_array( $comp['settings'] ) ? $comp['settings'] : array();

	$classes = array( 'hmpro-builder-comp', 'hmpro-comp-' . $type );
	echo '<div class="' . esc_attr( implode( ' ', $classes ) ) . '" data-comp="' . esc_attr( $id ) . '">';

	switch ( $type ) {
		case 'mega_column_menu':
			if ( 'mega' === $context ) {
				hmpro_builder_comp_mega_column_menu( $set );
			}
			break;
		case 'image':
			if ( 'mega' === $context ) {
				hmpro_builder_comp_image( $set );
			}
			break;
		case 'logo':
			hmpro_builder_comp_logo();
			break;
		case 'footer_info':
			hmpro_builder_comp_footer_info( $set );
			break;
		case 'footer_menu':
			hmpro_builder_comp_footer_menu( $set );
			break;
		case 'menu':
		case 'header_menu':
		case 'primary_menu':
			hmpro_builder_comp_menu( $set, $context );
			break;
		case 'search':
			hmpro_builder_comp_search( $set, $id );
			break;
		case 'cart':
			if ( class_exists( 'WooCommerce' ) ) {
				hmpro_builder_comp_cart();
			}
			break;
		case 'button':
			hmpro_builder_comp_button( $set );
			break;
		case 'html':
			hmpro_builder_comp_html( $set );
			break;
		case 'footer_image':
			hmpro_builder_comp_footer_image( $set );
			break;
		case 'spacer':
			hmpro_builder_comp_spacer( $set );
			break;
		case 'social':
			hmpro_builder_comp_social( $set );
			break;
		case 'social_icon_button':
			hmpro_builder_comp_social_icon_button( $set );
			break;
		default:
			do_action( 'hmpro/builder/render_component', $type, $set );
			break;
	}

	echo '</div>';
}

function hmpro_builder_comp_image( array $set ) {
	$attachment_id = isset( $set['attachment_id'] ) ? absint( $set['attachment_id'] ) : 0;
	$size          = isset( $set['size'] ) ? sanitize_key( (string) $set['size'] ) : 'large';
	if ( ! in_array( $size, [ 'medium', 'large', 'full' ], true ) ) {
		$size = 'large';
	}

	$aspect = isset( $set['aspect'] ) ? sanitize_key( (string) $set['aspect'] ) : 'landscape';
	if ( ! in_array( $aspect, [ 'landscape', 'square', 'portrait' ], true ) ) {
		$aspect = 'landscape';
	}

	$fit = isset( $set['fit'] ) ? sanitize_key( (string) $set['fit'] ) : 'cover';
	if ( ! in_array( $fit, [ 'cover', 'contain' ], true ) ) {
		$fit = 'cover';
	}

	$alt     = isset( $set['alt'] ) ? esc_attr( (string) $set['alt'] ) : '';
	$link    = isset( $set['link'] ) ? esc_url( (string) $set['link'] ) : '';
	$new_tab = ! empty( $set['new_tab'] );

	$img_html = '';

	if ( $attachment_id > 0 ) {
		$img_html = wp_get_attachment_image(
			$attachment_id,
			$size,
			false,
			[
				'class'   => 'hmpro-mega-img',
				'loading' => 'lazy',
				'alt'     => $alt,
			]
		);
	} else {
		$url = isset( $set['url'] ) ? esc_url( (string) $set['url'] ) : '';
		if ( '' === $url ) {
			return;
		}
		$img_html = '<img class="hmpro-mega-img" src="' . $url . '" alt="' . $alt . '" loading="lazy" />';
	}

	if ( '' === $img_html ) {
		return;
	}

	if ( '' !== $link ) {
		$attrs    = $new_tab ? ' target="_blank" rel="noopener noreferrer"' : '';
		$img_html = '<a href="' . $link . '"' . $attrs . '>' . $img_html . '</a>';
	}

	$classes = 'hmpro-mega-image is-' . $aspect . ' fit-' . $fit;

	echo '<div class="' . esc_attr( $classes ) . '">' . $img_html . '</div>';
}

function hmpro_builder_comp_mega_column_menu( array $set ) {
	$menu_id      = isset( $set['menu_id'] ) ? absint( $set['menu_id'] ) : 0;
	$root_item_id = isset( $set['root_item_id'] ) ? absint( $set['root_item_id'] ) : 0;
	$max_depth    = isset( $set['max_depth'] ) ? max( 1, min( 6, absint( $set['max_depth'] ) ) ) : 2;
	$show_root    = ! empty( $set['show_root_title'] );
	$max_items    = isset( $set['max_items'] ) ? max( 1, min( 50, absint( $set['max_items'] ) ) ) : 8;
	$show_more    = ! empty( $set['show_more'] );
	$flatten      = ! empty( $set['flatten'] );
	$more_text    = isset( $set['more_text'] ) ? sanitize_text_field( (string) $set['more_text'] ) : 'Daha Fazla Gör';
	$more_mode    = isset( $set['more_mode'] ) ? sanitize_key( (string) $set['more_mode'] ) : 'expand';
	if ( ! in_array( $more_mode, [ 'expand', 'link' ], true ) ) {
		$more_mode = 'expand';
	}
	$less_text = isset( $set['less_text'] ) ? sanitize_text_field( (string) $set['less_text'] ) : 'Daha Az Göster';

	if ( $menu_id < 1 || $root_item_id < 1 ) {
		return;
	}

	$items = wp_get_nav_menu_items( $menu_id );
	if ( empty( $items ) || ! is_array( $items ) ) {
		return;
	}

	$by_parent  = [];
	$root_title = '';
	$root_url   = '';
	foreach ( $items as $it ) {
		$pid = absint( $it->menu_item_parent );
		$iid = absint( $it->ID );
		if ( $iid === $root_item_id ) {
			$root_title = (string) $it->title;
			$root_url   = ! empty( $it->url ) ? (string) $it->url : '';
		}
		if ( ! isset( $by_parent[ $pid ] ) ) {
			$by_parent[ $pid ] = [];
		}
		$by_parent[ $pid ][] = $it;
	}

	// Build parent map for ancestor checks
	$parent_map = [];
	$order_map  = [];
	foreach ( $items as $it ) {
		$parent_map[ (int) $it->ID ] = (int) $it->menu_item_parent;
		$order_map[ (int) $it->ID ]  = (int) $it->menu_order;
	}

	$collect_flat_descendants = function () use ( $items, $root_item_id, $parent_map, $order_map, $max_depth ) {
		$out = [];

		foreach ( $items as $it ) {
			$id = (int) $it->ID;
			if ( $id === (int) $root_item_id ) {
				continue;
			}

			// Walk up to see if root_item_id is an ancestor
			$depth = 0;
			$p     = isset( $parent_map[ $id ] ) ? (int) $parent_map[ $id ] : 0;

			while ( $p > 0 && $p !== (int) $root_item_id && $depth < 50 ) {
				$depth++;
				$p = isset( $parent_map[ $p ] ) ? (int) $parent_map[ $p ] : 0;
			}

			if ( $p === (int) $root_item_id ) {
				// child depth from root = $depth + 1
				$child_depth = $depth + 1;
				if ( $child_depth <= (int) $max_depth ) {
					$out[] = $it;
				}
			}
		}

		// Sort by menu_order to mirror WP menu editor order
		usort( $out, function ( $a, $b ) {
			$ao = (int) $a->menu_order;
			$bo = (int) $b->menu_order;
			if ( $ao === $bo ) {
				return 0;
			}
			return ( $ao < $bo ) ? -1 : 1;
		} );

		return $out;
	};

	$render_list = function ( $parent_id, $depth ) use ( &$render_list, $by_parent, $max_depth, $max_items, $show_more, $more_text, $less_text, $more_mode, $root_url, $root_item_id ) {
		if ( $depth > $max_depth ) {
			return;
		}
		if ( empty( $by_parent[ $parent_id ] ) ) {
			return;
		}

		$items = $by_parent[ $parent_id ];

		$apply_limit = ( $parent_id === $root_item_id && 1 === $depth );
		$total       = count( $items );

		$visible = $items;
		$hidden  = [];

		if ( $apply_limit && $total > $max_items ) {
			$visible = array_slice( $items, 0, $max_items );
			$hidden  = array_slice( $items, $max_items );
		}

		echo '<ul class="hmpro-mega-col-list hmpro-depth-' . esc_attr( (string) $depth ) . '">';

		foreach ( $visible as $it ) {
			$url   = ! empty( $it->url ) ? esc_url( (string) $it->url ) : '#';
			$title = esc_html( (string) $it->title );
			echo '<li class="hmpro-mega-col-item">';
			echo '<a class="hmpro-mega-col-link" href="' . $url . '">' . $title . '</a>';
			$render_list( absint( $it->ID ), $depth + 1 );
			echo '</li>';
		}

		if ( $apply_limit && ! empty( $hidden ) && $show_more && 'expand' === $more_mode ) {
			foreach ( $hidden as $it ) {
				$url   = ! empty( $it->url ) ? esc_url( (string) $it->url ) : '#';
				$title = esc_html( (string) $it->title );
				echo '<li class="hmpro-mega-col-item hmpro-mega-hidden" aria-hidden="true">';
				echo '<a class="hmpro-mega-col-link" href="' . $url . '">' . $title . '</a>';
				$render_list( absint( $it->ID ), $depth + 1 );
				echo '</li>';
			}
		}

		if ( $apply_limit && $show_more && $total > $max_items ) {
			if ( 'link' === $more_mode ) {
				$more_url = $root_url ? esc_url( $root_url ) : '#';
				echo '<li class="hmpro-mega-col-item hmpro-mega-more">';
				echo '<a class="hmpro-mega-col-link hmpro-mega-more-link" href="' . $more_url . '">' . esc_html( $more_text ) . '</a>';
				echo '</li>';
			} else {
				echo '<li class="hmpro-mega-col-item hmpro-mega-more">';
				echo '<a href="#" class="hmpro-mega-col-link hmpro-mega-more-toggle" aria-expanded="false" data-more="' . esc_attr( $more_text ) . '" data-less="' . esc_attr( $less_text ) . '">';
				echo esc_html( $more_text );
				echo '</a>';
				echo '</li>';
			}
		}

		echo '</ul>';
	};

	echo '<div class="hmpro-mega-column-menu hmpro-more-mode-' . esc_attr( $more_mode ) . '" data-menu-id="' . esc_attr( (string) $menu_id ) . '" data-root-item="' . esc_attr( (string) $root_item_id ) . '">';
	if ( $show_root && '' !== $root_title ) {
		echo '<div class="hmpro-mega-root-title">' . esc_html( $root_title ) . '</div>';
	}

	if ( $flatten ) {
		$flat_items = $collect_flat_descendants();

		$total   = count( $flat_items );
		$visible = $flat_items;
		$hidden  = [];

		if ( $total > $max_items ) {
			$visible = array_slice( $flat_items, 0, $max_items );
			$hidden  = array_slice( $flat_items, $max_items );
		}

		echo '<ul class="hmpro-mega-col-list hmpro-depth-1">';

		foreach ( $visible as $it ) {
			$url   = ! empty( $it->url ) ? esc_url( (string) $it->url ) : '#';
			$title = esc_html( (string) $it->title );
			echo '<li class="hmpro-mega-col-item">';
			echo '<a class="hmpro-mega-col-link" href="' . $url . '">' . $title . '</a>';
			echo '</li>';
		}

		// Hidden items for expand mode (Trendyol)
		if ( ! empty( $hidden ) && $show_more && 'expand' === $more_mode ) {
			foreach ( $hidden as $it ) {
				$url   = ! empty( $it->url ) ? esc_url( (string) $it->url ) : '#';
				$title = esc_html( (string) $it->title );
				echo '<li class="hmpro-mega-col-item hmpro-mega-hidden" aria-hidden="true">';
				echo '<a class="hmpro-mega-col-link" href="' . $url . '">' . $title . '</a>';
				echo '</li>';
			}
		}

		// More control
		if ( $show_more && $total > $max_items ) {
			if ( 'link' === $more_mode ) {
				$more_url = $root_url ? esc_url( $root_url ) : '#';
				echo '<li class="hmpro-mega-col-item hmpro-mega-more">';
				echo '<a class="hmpro-mega-col-link hmpro-mega-more-link" href="' . $more_url . '">' . esc_html( $more_text ) . '</a>';
				echo '</li>';
			} else {
				echo '<li class="hmpro-mega-col-item hmpro-mega-more">';
				echo '<a href="#" class="hmpro-mega-col-link hmpro-mega-more-toggle" aria-expanded="false" data-more="' . esc_attr( $more_text ) . '" data-less="' . esc_attr( $less_text ) . '">';
				echo esc_html( $more_text );
				echo '</a>';
				echo '</li>';
			}
		}

		echo '</ul>';
		echo '</div>'; // close hmpro-mega-column-menu
		return;
	}

	$render_list( $root_item_id, 1 );
	echo '</div>';
}

function hmpro_builder_comp_logo() {
	$home = home_url( '/' );

	// Constrain logo growth to a dedicated slot so changing logo max-height in the
	// Customizer does not push/shift the header navigation.
	echo '<div class="hmpro-logo-wrap">';

	if ( function_exists( 'get_custom_logo' ) && has_custom_logo() ) {
		$logo = get_custom_logo(); // returns <a class="custom-logo-link"><img class="custom-logo"></a>
		if ( is_string( $logo ) && '' !== $logo ) {
			if ( false === strpos( $logo, 'hmpro-logo' ) ) {
				$logo = preg_replace( '/class=("|\')custom-logo-link(.*?)("|\')/i', 'class=$1custom-logo-link hmpro-logo$2$3', $logo );
			}
			echo $logo; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '</div>';
			return;
		}
		echo '</div>';
		return;
	}

	echo '<a class="hmpro-logo hmpro-logo-text" href="' . esc_url( $home ) . '" rel="home">';
	echo esc_html( get_bloginfo( 'name' ) );
	echo '</a>';
	echo '</div>';
}

/**
 * Pick a menu location that actually has a menu assigned.
 * Prefer builder-friendly locations first, but support legacy keys too.
 */
function hmpro_builder_pick_menu_location( array $preferred_keys ) {
	$locations = (array) get_nav_menu_locations(); // [ location_key => menu_id ]
	if ( empty( $locations ) ) {
		return '';
	}

	// 1) Preferred keys that have a menu_id.
	foreach ( $preferred_keys as $key ) {
		$key = sanitize_key( (string) $key );
		if ( isset( $locations[ $key ] ) && absint( $locations[ $key ] ) > 0 ) {
			return $key;
		}
	}

	// 2) Any assigned location.
	foreach ( $locations as $key => $menu_id ) {
		if ( absint( $menu_id ) > 0 ) {
			return sanitize_key( (string) $key );
		}
	}

	return '';
}

function hmpro_builder_comp_menu( array $set, $context = 'header' ) {
	// Defaults: primary (later we add settings modal)
	$location = isset( $set['location'] ) ? sanitize_key( (string) $set['location'] ) : '';
	// IMPORTANT:
	// Default depth 4 so nested category trees (e.g. Kategoriler > Ambalaj > Kutular)
	// can render even before Mega Menu binding is applied.
	// For footer we keep it calmer by default (vertical lists). User can still override later.
	$default_depth = ( 'footer' === $context ) ? 2 : 4;
	$depth         = isset( $set['depth'] ) ? absint( $set['depth'] ) : $default_depth;
	if ( $depth < 1 || $depth > 5 ) {
		$depth = $default_depth;
	}

	$locations = (array) get_nav_menu_locations();

	// If a location was set explicitly, but no menu is assigned to it, ignore and auto-pick.
	if ( '' !== $location ) {
		if ( ! isset( $locations[ $location ] ) || absint( $locations[ $location ] ) <= 0 ) {
			$location = '';
		}
	}

	// Smart pick: support both new builder keys and legacy theme keys.
	if ( '' === $location ) {
		// Footer should prefer footer locations first; header prefers primary/topbar first.
		if ( 'footer' === $context ) {
			$preferred = array( 'footer', 'hm_footer', 'primary', 'hm_primary', 'topbar' );
		} else {
			$preferred = array( 'primary', 'hm_primary', 'topbar', 'footer', 'hm_footer' );
		}
		$location = hmpro_builder_pick_menu_location( $preferred );
	}

	$location = sanitize_key( (string) $location );
	if ( '' === $location ) {
		return;
	}

	// Context-aware wrappers:
	// - Header: keep .hmpro-primary-nav (Mega Menu depends on this)
	// - Footer: use .hmpro-footer-nav + vertical list class so footer stays “footer-like”
	if ( 'footer' === $context ) {
		echo '<nav class="hmpro-footer-nav" aria-label="Footer menu">';
		wp_nav_menu(
			array(
				'theme_location' => $location,
				'container'      => false,
				'menu_class'     => 'hmpro-footer-menu',
				'fallback_cb'    => '__return_empty_string',
				'depth'          => $depth,
			)
		);
		echo '</nav>';
		return;
	}

	// Header / default:
	echo '<nav class="hmpro-primary-nav" aria-label="Primary menu">';
	wp_nav_menu(
		array(
			'theme_location' => $location,
			// Ensure a stable class so our header CSS + Customizer color variables apply reliably.
			'menu_class'     => 'hmpro-menu',
			'container'      => false,
			'fallback_cb'    => '__return_empty_string',
			'depth'          => $depth,
		)
	);
	echo '</nav>';
}

/**
 * Footer Info widget: prints optional title + multi-line text (address/phone/email).
 * Settings:
 * - title (string)
 * - lines (textarea, newline separated)
 */
function hmpro_builder_comp_footer_info( array $set ) {
	$title = isset( $set['title'] ) ? (string) $set['title'] : '';
	$lines = isset( $set['lines'] ) ? (string) $set['lines'] : '';

	$title = trim( wp_strip_all_tags( $title ) );
	$lines = trim( (string) $lines );

	echo '<div class="hmpro-footer-info">';
	if ( '' !== $title ) {
		echo '<div class="hmpro-footer-info-title">' . esc_html( $title ) . '</div>';
	}

	if ( '' !== $lines ) {
		$rows = preg_split( "/\\r\\n|\\r|\\n/", $lines );
		$rows = is_array( $rows ) ? $rows : array();
		echo '<div class="hmpro-footer-info-lines">';
		foreach ( $rows as $row ) {
			$row = trim( wp_strip_all_tags( (string) $row ) );
			if ( '' === $row ) {
				continue;
			}
			echo '<div class="hmpro-footer-info-line">' . esc_html( $row ) . '</div>';
		}
		echo '</div>';
	}
	echo '</div>';
}

/**
 * Dedicated Footer Menu widget (legacy behavior):
 * - Selects a WP menu (nav_menu term) instead of theme_location.
 * - Renders as vertical list.
 */
function hmpro_builder_comp_footer_menu( array $set ) {
	$menu_id    = isset( $set['menu_id'] ) ? absint( $set['menu_id'] ) : 0;
	$show_title = ! empty( $set['show_title'] );

	if ( $menu_id <= 0 ) {
		return;
	}

	$menu_obj = wp_get_nav_menu_object( $menu_id );
	if ( ! $menu_obj || is_wp_error( $menu_obj ) ) {
		return;
	}

	echo '<div class="hmpro-footer-menu-widget">';
	if ( $show_title ) {
		echo '<div class="hmpro-footer-menu-title">' . esc_html( (string) $menu_obj->name ) . '</div>';
	}
	wp_nav_menu(
		[
			'menu'        => $menu_id,
			'container'   => false,
			'menu_class'  => 'hmpro-footer-menu-list',
			'fallback_cb' => '__return_empty_string',
			'depth'       => 2,
		]
	);
	echo '</div>';
}

function hmpro_builder_comp_search( array $set, $comp_id = '' ) {
	$ph = isset( $set['placeholder'] ) ? sanitize_text_field( (string) $set['placeholder'] ) : __( 'Ara…', 'hmpro' );
	$comp_id  = sanitize_key( (string) $comp_id );
	$field_id = 'hmpro-search-field';
	if ( '' !== $comp_id ) {
		$field_id = 'hmpro-search-field-' . $comp_id;
	}
	echo '<form class="hmpro-search" role="search" method="get" action="' . esc_url( home_url( '/' ) ) . '">';
	echo '<label class="screen-reader-text" for="' . esc_attr( $field_id ) . '">' . esc_html__( 'Search for:', 'hmpro' ) . '</label>';
	echo '<input id="' . esc_attr( $field_id ) . '" class="hmpro-search-field" type="search" name="s" value="' . esc_attr( get_search_query() ) . '" placeholder="' . esc_attr( $ph ) . '" required />';
	echo '<button class="hmpro-search-submit" type="submit">' . esc_html__( 'Ara', 'hmpro' ) . '</button>';
	echo '</form>';
}

function hmpro_builder_comp_cart() {
	if ( ! function_exists( 'wc_get_cart_url' ) ) {
		return;
	}
	$url = wc_get_cart_url();
	if ( ! $url ) {
		return;
	}
	$count = ( function_exists( 'WC' ) && WC()->cart ) ? (int) WC()->cart->get_cart_contents_count() : 0;

	$classes = 'hmpro-cart';
	if ( $count <= 0 ) {
		$classes .= ' hmpro-cart--empty';
	}

	echo '<a class="' . esc_attr( $classes ) . '" href="' . esc_url( $url ) . '" aria-label="' . esc_attr__( 'Cart', 'hmpro' ) . '">';

	// Icon (inline SVG; uses currentColor so it matches header text color).
	echo '<span class="hmpro-cart-icon" aria-hidden="true">';
	echo '<svg viewBox="0 0 24 24" width="22" height="22" focusable="false" aria-hidden="true">';
	echo '<path fill="currentColor" d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zm10 0c-1.1 0-1.99.9-1.99 2S15.9 22 17 22s2-.9 2-2-.9-2-2-2zM7.2 14h9.9c.75 0 1.4-.41 1.75-1.03l3.24-5.88A1 1 0 0 0 21.2 6H6.21L5.27 4H2a1 1 0 1 0 0 2h2l3.6 7.59-1.35 2.44C5.52 17.37 6.48 19 8 19h12a1 1 0 1 0 0-2H8l1.2-2z"/>';
	echo '</svg>';
	echo '</span>';

	// Badge only when count > 0 (prevents "Cart 0" ugliness).
	if ( $count > 0 ) {
		echo '<span class="hmpro-cart-count" aria-hidden="true">' . esc_html( (string) $count ) . '</span>';
		echo '<span class="screen-reader-text">' . esc_html( sprintf( __( '%d items in cart', 'hmpro' ), $count ) ) . '</span>';
	} else {
		echo '<span class="screen-reader-text">' . esc_html__( 'Cart is empty', 'hmpro' ) . '</span>';
	}

	echo '</a>';
}

function hmpro_builder_comp_button( array $set ) {
	$text = isset( $set['text'] ) ? sanitize_text_field( (string) $set['text'] ) : __( 'Button', 'hmpro' );
	$url  = isset( $set['url'] ) ? esc_url( (string) $set['url'] ) : '#';
	echo '<a class="hmpro-btn" href="' . esc_url( $url ) . '">' . esc_html( $text ) . '</a>';
}

function hmpro_builder_comp_html( array $set ) {
	$content = isset( $set['content'] ) ? (string) $set['content'] : '';
	if ( '' === trim( $content ) ) {
		return;
	}

	// Allow shortcodes inside builder HTML blocks (admin-controlled content).
	// Note: wp_kses_post() strips <select>/<option> etc. (needed by some inline widgets),
	// so we use a tighter custom allowlist that still keeps scripts out.
	$rendered = do_shortcode( $content );
	$allowed  = array(
		'div'    => array( 'class' => true, 'id' => true, 'style' => true, 'data-*' => true, 'role' => true, 'aria-*' => true ),
		'span'   => array( 'class' => true, 'id' => true, 'style' => true, 'data-*' => true, 'role' => true, 'aria-*' => true, 'translate' => true ),
		'a'      => array( 'href' => true, 'class' => true, 'id' => true, 'target' => true, 'rel' => true, 'aria-*' => true ),
		'p'      => array( 'class' => true, 'id' => true, 'style' => true ),
		'br'     => array(),
		'strong' => array(),
		'em'     => array(),
		'ul'     => array( 'class' => true, 'id' => true ),
		'ol'     => array( 'class' => true, 'id' => true ),
		'li'     => array( 'class' => true, 'id' => true ),
		// Form controls (used by inline switchers/search widgets).
		'select' => array( 'class' => true, 'id' => true, 'name' => true, 'aria-*' => true, 'translate' => true ),
		'option' => array( 'class' => true, 'value' => true, 'selected' => true, 'translate' => true ),
		'label'  => array( 'class' => true, 'for' => true, 'aria-*' => true ),
		'input'  => array( 'class' => true, 'id' => true, 'name' => true, 'type' => true, 'value' => true, 'placeholder' => true, 'checked' => true, 'aria-*' => true ),
		'button' => array( 'class' => true, 'id' => true, 'type' => true, 'value' => true, 'aria-*' => true ),
	);

	echo '<div class="hmpro-html">' . wp_kses( $rendered, $allowed ) . '</div>';
}

/**
 * Footer Image (Footer Builder)
 * Simple image renderer for footer logos / payment icons / trust badges.
 */
function hmpro_builder_comp_footer_image( array $set ) {
	$attachment_id = isset( $set['attachment_id'] ) ? absint( $set['attachment_id'] ) : 0;
	$url           = isset( $set['url'] ) ? esc_url( (string) $set['url'] ) : '';
	$max_w         = isset( $set['max_width'] ) ? absint( $set['max_width'] ) : 140;
	$link          = isset( $set['link'] ) ? esc_url( (string) $set['link'] ) : '';
	$new_tab       = ! empty( $set['new_tab'] );

	if ( $max_w < 10 ) {
		$max_w = 10;
	}
	if ( $max_w > 2000 ) {
		$max_w = 2000;
	}

	$img_html = '';
	if ( $attachment_id > 0 ) {
		$img_html = wp_get_attachment_image(
			$attachment_id,
			'full',
			false,
			[
				'class' => 'hmpro-footer-image__img',
				'alt'   => '',
			]
		);
	} elseif ( '' !== $url ) {
		$img_html = '<img class="hmpro-footer-image__img" src="' . esc_url( $url ) . '" alt="" loading="lazy" />';
	}

	if ( '' === $img_html ) {
		return;
	}

	if ( '' !== $link ) {
		$attrs    = $new_tab ? ' target="_blank" rel="noopener noreferrer"' : '';
		$img_html = '<a class="hmpro-footer-image__link" href="' . esc_url( $link ) . '"' . $attrs . '>' . $img_html . '</a>';
	}

	$style = 'max-width:' . (int) $max_w . 'px;';
	echo '<div class="hmpro-footer-image" style="' . esc_attr( $style ) . '">' . $img_html . '</div>';
}

function hmpro_builder_comp_spacer( array $set ) {
	$w     = isset( $set['width'] ) ? absint( $set['width'] ) : 0;
	$h     = isset( $set['height'] ) ? absint( $set['height'] ) : 0;
	$style = '';
	if ( $w ) {
		$style .= 'width:' . $w . 'px;';
	}
	if ( $h ) {
		$style .= 'height:' . $h . 'px;';
	}
	echo '<span class="hmpro-spacer" style="' . esc_attr( $style ) . '"></span>';
}

function hmpro_load_social_svg_preset( string $preset ): string {
	static $cache = array();

	$preset = sanitize_key( $preset );
	if ( isset( $cache[ $preset ] ) ) {
		return $cache[ $preset ];
	}

	$map = array(
		'facebook'  => 'facebook.svg',
		'x'         => 'x.svg',
		'twitter'   => 'x.svg',
		'instagram' => 'instagram.svg',
		'linkedin'  => 'linkedin.svg',
		'youtube'   => 'youtube.svg',
		'tiktok'    => 'tiktok.svg',
		'whatsapp'  => 'whatsapp.svg',
		'telegram'  => 'telegram.svg',
	);

	if ( empty( $map[ $preset ] ) ) {
		$cache[ $preset ] = '';
		return '';
	}

	$rel_candidates = array(
		'assets/icon/social/' . $map[ $preset ],
		'assets/icons/social/' . $map[ $preset ],
	);

	$path = '';
	foreach ( $rel_candidates as $rel ) {
		$p = trailingslashit( get_stylesheet_directory() ) . $rel;
		if ( file_exists( $p ) ) {
			$path = $p;
			break;
		}
	}

	if ( '' === $path ) {
		$cache[ $preset ] = '';
		return '';
	}

	$svg = (string) file_get_contents( $path );
	if ( '' === $svg ) {
		$cache[ $preset ] = '';
		return '';
	}

	$allowed = array(
		'svg'    => array(
			'xmlns'       => true,
			'viewBox'     => true,
			'aria-hidden' => true,
			'focusable'   => true,
			'role'        => true,
			'width'       => true,
			'height'      => true,
		),
		'path'   => array(
			'd'    => true,
			'fill' => true,
		),
		'g'      => array( 'fill' => true ),
		'circle' => array(
			'cx'   => true,
			'cy'   => true,
			'r'    => true,
			'fill' => true,
		),
		'rect'   => array(
			'x'      => true,
			'y'      => true,
			'width'  => true,
			'height' => true,
			'rx'     => true,
			'ry'     => true,
			'fill'   => true,
		),
	);

	$svg = wp_kses( $svg, $allowed );

	$cache[ $preset ] = $svg;
	return $svg;
}

/**
 * Social icon button component.
 */
function hmpro_builder_comp_social_icon_button( array $set ) {
	$url = isset( $set['url'] ) ? esc_url( (string) $set['url'] ) : '';
	if ( '' === $url ) {
		return;
	}

	$new_tab = ! empty( $set['new_tab'] );
	$icon_mode = isset( $set['icon_mode'] ) ? sanitize_key( (string) $set['icon_mode'] ) : 'preset';
	$icon_preset = isset( $set['icon_preset'] ) ? sanitize_key( (string) $set['icon_preset'] ) : 'facebook';
	$custom_icon = isset( $set['custom_icon'] ) ? (string) $set['custom_icon'] : '';
	$transparent = ! empty( $set['transparent'] );

	$allowed_presets = array( 'facebook', 'instagram', 'linkedin', 'x', 'twitter', 'youtube', 'tiktok', 'whatsapp', 'telegram' );
	if ( ! in_array( $icon_preset, $allowed_presets, true ) ) {
		$icon_preset = 'facebook';
	}

	$label_map = array(
		'facebook' => 'Facebook',
		'instagram' => 'Instagram',
		'linkedin' => 'LinkedIn',
		'x' => 'X',
		'twitter' => 'X',
		'youtube' => 'YouTube',
		'tiktok' => 'TikTok',
		'whatsapp' => 'WhatsApp',
		'telegram' => 'Telegram',
	);
	$label = isset( $label_map[ $icon_preset ] ) ? $label_map[ $icon_preset ] : __( 'Social link', 'hmpro' );

	$attrs = $new_tab ? ' target="_blank" rel="noopener noreferrer"' : '';
	$cls = 'hmpro-socialicon hmpro-socialicon--' . esc_attr( $icon_preset );
	if ( $transparent ) {
		$cls .= ' is-transparent';
	}
	echo '<a class="' . esc_attr( $cls ) . '" href="' . esc_url( $url ) . '" aria-label="' . esc_attr( $label ) . '"' . $attrs . '>';
	$icon_html = '';
	if ( 'custom' === $icon_mode && '' !== trim( $custom_icon ) ) {
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
		$icon_html = wp_kses( $custom_icon, $allowed_svg_tags );
	} else {
		$icon_html = hmpro_load_social_svg_preset( $icon_preset );
	}

	if ( '' === $icon_html ) {
		$badge_map = array(
			'facebook'  => 'f',
			'instagram' => 'IG',
			'linkedin'  => 'in',
			'x'         => 'X',
			'twitter'   => 'X',
			'youtube'   => 'YT',
			'tiktok'    => 'TT',
			'whatsapp'  => 'WA',
			'telegram'  => 'TG',
		);
		$badge = isset( $badge_map[ $icon_preset ] ) ? $badge_map[ $icon_preset ] : strtoupper( substr( $icon_preset, 0, 2 ) );
		$icon_html = '<span class="hmpro-socialicon__badge">' . esc_html( $badge ) . '</span>';
	} else {
		$icon_html = '<span class="hmpro-socialicon__svg" aria-hidden="true">' . $icon_html . '</span>';
	}

	echo $icon_html;
	echo '</a>';
}


/**
 * Social Media Icons component (Commit 020)
 */
function hmpro_builder_comp_social( array $set ) {
	$urls    = isset( $set['urls'] ) && is_array( $set['urls'] ) ? $set['urls'] : array();
	$size    = isset( $set['size'] ) ? sanitize_key( (string) $set['size'] ) : 'normal';
	$gap     = isset( $set['gap'] ) ? sanitize_key( (string) $set['gap'] ) : 'normal';
	$new_tab = ! empty( $set['new_tab'] );

	$allowed_sizes = array( 'small', 'normal', 'large' );
	$allowed_gaps  = array( 'small', 'normal', 'large' );
	if ( ! in_array( $size, $allowed_sizes, true ) ) {
		$size = 'normal';
	}
	if ( ! in_array( $gap, $allowed_gaps, true ) ) {
		$gap = 'normal';
	}

	$map = array(
		'facebook'  => array( 'label' => 'Facebook', 'icon' => 'facebook' ),
		'instagram' => array( 'label' => 'Instagram', 'icon' => 'instagram' ),
		'x'         => array( 'label' => 'X', 'icon' => 'x' ),
		'youtube'   => array( 'label' => 'YouTube', 'icon' => 'youtube' ),
		'tiktok'    => array( 'label' => 'TikTok', 'icon' => 'tiktok' ),
		'linkedin'  => array( 'label' => 'LinkedIn', 'icon' => 'linkedin' ),
		'whatsapp'  => array( 'label' => 'WhatsApp', 'icon' => 'whatsapp' ),
		'telegram'  => array( 'label' => 'Telegram', 'icon' => 'telegram' ),
	);

	$items = array();
	foreach ( $map as $key => $meta ) {
		if ( empty( $urls[ $key ] ) ) {
			continue;
		}
		$url = esc_url( (string) $urls[ $key ] );
		if ( '' === $url ) {
			continue;
		}
		$items[] = array(
			'url'   => $url,
			'label' => $meta['label'],
			'icon'  => $meta['icon'],
		);
	}

	if ( empty( $items ) ) {
		return;
	}

	$classes = array( 'hmpro-social', 'hmpro-social--' . $size, 'hmpro-social--gap-' . $gap );
	echo '<nav class="' . esc_attr( implode( ' ', $classes ) ) . '" aria-label="' . esc_attr__( 'Social links', 'hmpro' ) . '">';

	foreach ( $items as $item ) {
		$attrs = '';
		if ( $new_tab ) {
			$attrs = ' target="_blank" rel="noopener noreferrer"';
		}
		echo '<a class="hmpro-social__link hmpro-social__' . esc_attr( $item['icon'] ) . '" href="' . esc_url( $item['url'] ) . '" aria-label="' . esc_attr( $item['label'] ) . '"' . $attrs . '>';
		echo '<svg class="hmpro-social__icon" aria-hidden="true" focusable="false"><use href="#hmpro-icon-' . esc_attr( $item['icon'] ) . '"></use></svg>';
		echo '<span class="screen-reader-text">' . esc_html( $item['label'] ) . '</span>';
		echo '</a>';
	}

	echo '</nav>';
}

/**
 * Outputs an inline SVG sprite (lightweight, no external dependencies).
 * For now: simple letter icons; can be replaced with full brand SVG paths later.
 */
function hmpro_builder_output_social_sprite() {
	static $done = false;
	if ( $done ) {
		return;
	}
	$done = true;

	echo '<svg xmlns="http://www.w3.org/2000/svg" style="position:absolute;width:0;height:0;overflow:hidden" aria-hidden="true" focusable="false">';
	echo '<symbol id="hmpro-icon-facebook" viewBox="0 0 24 24"><circle cx="12" cy="12" r="11" fill="none" stroke="currentColor" stroke-width="2"/><text x="12" y="16" text-anchor="middle" font-size="12" font-family="Arial" fill="currentColor">f</text></symbol>';
	echo '<symbol id="hmpro-icon-instagram" viewBox="0 0 24 24"><circle cx="12" cy="12" r="11" fill="none" stroke="currentColor" stroke-width="2"/><text x="12" y="16" text-anchor="middle" font-size="11" font-family="Arial" fill="currentColor">i</text></symbol>';
	echo '<symbol id="hmpro-icon-x" viewBox="0 0 24 24"><circle cx="12" cy="12" r="11" fill="none" stroke="currentColor" stroke-width="2"/><text x="12" y="16" text-anchor="middle" font-size="11" font-family="Arial" fill="currentColor">x</text></symbol>';
	echo '<symbol id="hmpro-icon-youtube" viewBox="0 0 24 24"><circle cx="12" cy="12" r="11" fill="none" stroke="currentColor" stroke-width="2"/><text x="12" y="16" text-anchor="middle" font-size="11" font-family="Arial" fill="currentColor">></text></symbol>';
	echo '<symbol id="hmpro-icon-tiktok" viewBox="0 0 24 24"><circle cx="12" cy="12" r="11" fill="none" stroke="currentColor" stroke-width="2"/><text x="12" y="16" text-anchor="middle" font-size="11" font-family="Arial" fill="currentColor">t</text></symbol>';
	echo '<symbol id="hmpro-icon-linkedin" viewBox="0 0 24 24"><circle cx="12" cy="12" r="11" fill="none" stroke="currentColor" stroke-width="2"/><text x="12" y="16" text-anchor="middle" font-size="10" font-family="Arial" fill="currentColor">in</text></symbol>';
	echo '<symbol id="hmpro-icon-whatsapp" viewBox="0 0 24 24"><circle cx="12" cy="12" r="11" fill="none" stroke="currentColor" stroke-width="2"/><text x="12" y="16" text-anchor="middle" font-size="10" font-family="Arial" fill="currentColor">w</text></symbol>';
	echo '<symbol id="hmpro-icon-telegram" viewBox="0 0 24 24"><circle cx="12" cy="12" r="11" fill="none" stroke="currentColor" stroke-width="2"/><text x="12" y="16" text-anchor="middle" font-size="10" font-family="Arial" fill="currentColor">tg</text></symbol>';
	echo '</svg>';
}
