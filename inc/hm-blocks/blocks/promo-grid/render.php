<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * HM Promo Grid render (hybrid).
 */

$preset = isset( $attributes['preset'] ) ? (string) $attributes['preset'] : 'three_mosaic_left';

$limits = array(
	'two_equal'          => 2,
	'two_split_70_30'    => 2,
	'two_split_30_70'    => 2,
	'three_equal'        => 3,
	'three_mosaic_left'  => 3,
	'three_mosaic_right' => 3,
	'four_checker'       => 4,
	'four_mosaic_left'   => 4,
	'four_mosaic_right'  => 4,
	'six_grid'           => 6,
	'six_mosaic_left'    => 6,
	'six_mosaic_right'   => 6,
);
$max_tiles = isset( $limits[ $preset ] ) ? (int) $limits[ $preset ] : 6;

$tiles = isset( $attributes['tiles'] ) && is_array( $attributes['tiles'] )
	? array_slice( $attributes['tiles'], 0, $max_tiles )
	: array();

$areas_map = array(
	'two_equal'          => array( 'a', 'b' ),
	'two_split_70_30'    => array( 'a', 'b' ),
	'two_split_30_70'    => array( 'a', 'b' ),
	'three_equal'        => array( 'a', 'b', 'c' ),
	'three_mosaic_left'  => array( 'a', 'b', 'c' ),
	'three_mosaic_right' => array( 'a', 'b', 'c' ),
	'four_checker'       => array( 'a', 'b', 'c', 'd' ),
	'four_mosaic_left'   => array( 'a', 'b', 'c', 'd' ),
	'four_mosaic_right'  => array( 'a', 'b', 'c', 'd' ),
	'six_grid'           => array( 'a', 'b', 'c', 'd', 'e', 'f' ),
	'six_mosaic_left'    => array( 'a', 'b', 'c', 'd', 'e', 'f' ),
	'six_mosaic_right'   => array( 'a', 'b', 'c', 'd', 'e', 'f' ),
);
$areas = isset( $areas_map[ $preset ] ) ? $areas_map[ $preset ] : array( 'a','b','c','d','e','f' );

$full_width      = ! empty( $attributes['fullWidth'] );
$fixed_height    = ! empty( $attributes['fixedHeight'] );
$min_h           = isset( $attributes['containerMinHeight'] ) ? (int) $attributes['containerMinHeight'] : 360;
$height          = isset( $attributes['containerHeight'] ) ? (int) $attributes['containerHeight'] : 520;
$gap             = isset( $attributes['gridGap'] ) ? (int) $attributes['gridGap'] : 20;
$tile_min_h      = isset( $attributes['tileMinHeight'] ) ? (int) $attributes['tileMinHeight'] : 340;
$hover_effect    = isset( $attributes['hoverEffect'] ) ? (string) $attributes['hoverEffect'] : 'none';
$media_scale     = isset( $attributes['mediaScale'] ) ? (float) $attributes['mediaScale'] : 1.0;
$overlay_opacity = isset( $attributes['overlayOpacity'] ) ? (float) $attributes['overlayOpacity'] : 0.35;

$wrapper_classes = array(
	'hmpro-block',
	'hmpro-promo-grid',
);
if ( $full_width ) {
	$wrapper_classes[] = 'hmpro-pg--fullwidth';
}
if ( $fixed_height ) {
	$wrapper_classes[] = 'hmpro-pg--fixed-height';
}
if ( $hover_effect && 'none' !== $hover_effect ) {
	$wrapper_classes[] = 'hmpro-pg--hover-' . sanitize_html_class( $hover_effect );
}

$style = array();
$style[] = '--hm-pg-gap:' . $gap . 'px';
$style[] = '--hm-pg-minh:' . $min_h . 'px';
$style[] = '--hm-pg-tile-minh:' . $tile_min_h . 'px';
$style[] = '--hm-pg-media-scale:' . $media_scale;
$style[] = '--hm-pg-overlay-opacity:' . $overlay_opacity;
if ( $fixed_height ) {
	$style[] = 'height:' . $height . 'px';
}

$wrapper_attributes = get_block_wrapper_attributes( array(
	'class' => implode( ' ', array_filter( $wrapper_classes ) ),
	'style' => implode( ';', $style ),
) );

echo '<section ' . $wrapper_attributes . ' data-preset="' . esc_attr( $preset ) . '">';
echo '<div class="hmpro-pg__inner">';
echo '<div class="hmpro-pg__grid hmpro-pg__grid--' . esc_attr( $preset ) . '">';

$i = 0;
foreach ( $tiles as $tile ) {
	$i++;
	if ( empty( $tile ) || ! is_array( $tile ) ) {
		continue;
	}
	$show = array_key_exists( 'show', $tile ) ? (bool) $tile['show'] : true;
	if ( ! $show ) {
		continue;
	}

	$image_id     = ! empty( $tile['imageId'] ) ? (int) $tile['imageId'] : 0;
	$image_url    = '';
	$image_markup = '';
	if ( $image_id ) {
		// Use responsive image markup (srcset/sizes + width/height) to reduce bytes and CLS.
		$image_markup = wp_get_attachment_image( $image_id, 'large', false, array(
			'class'    => 'hmpro-pg__img',
			'loading'  => 'lazy',
			'decoding' => 'async',
		) );
		$image_url = wp_get_attachment_image_url( $image_id, 'large' );
	}
	if ( empty( $image_url ) && ! empty( $tile['imageUrl'] ) ) {
		$image_url = (string) $tile['imageUrl'];
	}
	if ( empty( $image_url ) ) {
		continue;
	}

	$area_key  = isset( $areas[ $i - 1 ] ) ? $areas[ $i - 1 ] : 'a';
	$title     = isset( $tile['title'] ) ? (string) $tile['title'] : '';
	$subtitle  = isset( $tile['subtitle'] ) ? (string) $tile['subtitle'] : '';
	$btn_text  = isset( $tile['buttonText'] ) ? (string) $tile['buttonText'] : '';
	$link_url  = isset( $tile['linkUrl'] ) ? (string) $tile['linkUrl'] : '';
	$new_tab   = ! empty( $tile['newTab'] );
	$nofollow  = ! empty( $tile['nofollow'] );
	$overlay   = array_key_exists( 'overlay', $tile ) ? (bool) $tile['overlay'] : true;
	$pos       = isset( $tile['position'] ) ? (string) $tile['position'] : 'bottom-left';

	$offset_x  = isset( $tile['offsetX'] ) ? (int) $tile['offsetX'] : 0;
	$offset_y  = isset( $tile['offsetY'] ) ? (int) $tile['offsetY'] : 0;
	$maxw      = isset( $tile['contentMaxWidth'] ) ? (int) $tile['contentMaxWidth'] : 520;
	$pad       = isset( $tile['contentPadding'] ) ? (int) $tile['contentPadding'] : 18;
	// Locale-safe floats: "1,25" -> "1.25"
	$scale_d     = isset( $tile['contentScale'] ) ? (float) str_replace( ',', '.', (string) $tile['contentScale'] ) : 1.0;
	$scale_m_raw = isset( $tile['contentScaleMobile'] ) ? (float) str_replace( ',', '.', (string) $tile['contentScaleMobile'] ) : 0.0;
	$scale_m     = ( $scale_m_raw > 0 ) ? $scale_m_raw : $scale_d;
	$mob_off_y   = isset( $tile['mobileOffsetY'] ) ? (int) $tile['mobileOffsetY'] : 0;
	$title_color       = isset( $tile['titleColor'] ) ? sanitize_text_field( $tile['titleColor'] ) : '';
	$subtitle_color    = isset( $tile['subtitleColor'] ) ? sanitize_text_field( $tile['subtitleColor'] ) : '';
	$button_bg_color   = isset( $tile['buttonBgColor'] ) ? sanitize_text_field( $tile['buttonBgColor'] ) : '';
	$button_text_color = isset( $tile['buttonTextColor'] ) ? sanitize_text_field( $tile['buttonTextColor'] ) : '';
	$title_font_size   = isset( $tile['titleFontSize'] ) ? (int) $tile['titleFontSize'] : 0;

	$tile_tag = ! empty( $link_url ) ? 'a' : 'div';

	$tile_classes = array(
		'hmpro-pg__tile',
		'hmpro-pg__tile--' . sanitize_html_class( $area_key ),
		'hmpro-pg__tile-position-' . sanitize_html_class( $pos ),
	);

	$tile_style = array(
		'--hm-pg-offset-x:' . $offset_x . 'px',
		'--hm-pg-offset-y:' . $offset_y . 'px',
		'--hm-pg-content-maxw:' . $maxw . 'px',
		'--hm-pg-content-pad:' . $pad . 'px',
		'--hm-pg-content-scale:' . $scale_d,
		'--hm-pg-content-scale-m:' . $scale_m,
		'--hm-pg-mobile-offset-y:' . $mob_off_y . 'px',
	);
	if ( $title_color ) {
		$tile_style[] = '--hm-pg-title-color:' . $title_color;
	}
	if ( $subtitle_color ) {
		$tile_style[] = '--hm-pg-subtitle-color:' . $subtitle_color;
	}
	if ( $button_bg_color ) {
		$tile_style[] = '--hm-pg-btn-bg:' . $button_bg_color;
	}
	if ( $button_text_color ) {
		$tile_style[] = '--hm-pg-btn-color:' . $button_text_color;
	}
	if ( $title_font_size > 0 ) {
		$tile_style[] = '--hm-pg-title-size:' . $title_font_size . 'px';
	}

	$rel_parts = array();
	if ( $new_tab ) {
		$rel_parts[] = 'noopener';
		$rel_parts[] = 'noreferrer';
	}
	if ( $nofollow ) {
		$rel_parts[] = 'nofollow';
	}
	$rel_attr = ! empty( $rel_parts ) ? implode( ' ', array_unique( $rel_parts ) ) : '';

	echo '<' . $tile_tag;
	echo ' class="' . esc_attr( implode( ' ', $tile_classes ) ) . '"';
	echo ' style="' . esc_attr( implode( ';', $tile_style ) ) . '"';
	if ( 'a' === $tile_tag ) {
		echo ' href="' . esc_url( $link_url ) . '"';
		if ( $new_tab ) {
			echo ' target="_blank"';
		}
		if ( $rel_attr ) {
			echo ' rel="' . esc_attr( $rel_attr ) . '"';
		}
	}
	echo '>';

	echo '<div class="hmpro-pg__media">';
	if ( $image_markup ) {
		echo $image_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	} else {
		echo '<img class="hmpro-pg__img" src="' . esc_url( $image_url ) . '" alt="' . esc_attr( $title ) . '" loading="lazy" decoding="async" />';
	}
	echo '</div>';

	$overlay_classes = array( 'hmpro-pg__overlay' );
	if ( ! $overlay ) {
		$overlay_classes[] = 'is-disabled';
	}
	echo '<div class="' . esc_attr( implode( ' ', $overlay_classes ) ) . '">';
	echo '<div class="hmpro-pg__content hmpro-pg__content--' . esc_attr( $pos ) . '">';

	if ( $title ) {
		echo '<div class="hmpro-pg__title">' . esc_html( $title ) . '</div>';
	}
	if ( $subtitle ) {
		echo '<div class="hmpro-pg__subtitle">' . esc_html( $subtitle ) . '</div>';
	}

	if ( $btn_text ) {
		// Avoid nested anchors: if tile is <a>, render button as <span>.
		if ( 'a' === $tile_tag ) {
			echo '<span class="hmpro-pg__button">' . esc_html( $btn_text ) . '</span>';
		} else if ( $link_url ) {
			echo '<a class="hmpro-pg__button" href="' . esc_url( $link_url ) . '"' . ( $new_tab ? ' target="_blank"' : '' ) . ( $rel_attr ? ' rel="' . esc_attr( $rel_attr ) . '"' : '' ) . '>' . esc_html( $btn_text ) . '</a>';
		} else {
			echo '<span class="hmpro-pg__button">' . esc_html( $btn_text ) . '</span>';
		}
	}

	echo '</div>';
	echo '</div>';

	echo '</' . $tile_tag . '>';
}

echo '</div>';
echo '</div>';
echo '</section>';
