<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$layout     = isset( $attributes['layout'] ) ? sanitize_key( $attributes['layout'] ) : 'top';
$align      = isset( $attributes['align'] ) ? sanitize_key( $attributes['align'] ) : 'left';
$icon_mode  = isset( $attributes['iconMode'] ) ? sanitize_key( $attributes['iconMode'] ) : 'preset';
$preset     = isset( $attributes['iconPreset'] ) ? sanitize_key( $attributes['iconPreset'] ) : 'check';
$custom_svg = isset( $attributes['customSvg'] ) ? (string) $attributes['customSvg'] : '';
$icon_size  = isset( $attributes['iconSize'] ) ? (int) $attributes['iconSize'] : 28;
$title_size = isset( $attributes['titleSize'] ) ? (int) $attributes['titleSize'] : 18;
$text_size  = isset( $attributes['textSize'] ) ? (int) $attributes['textSize'] : 14;
$icon_color  = isset( $attributes['iconColor'] ) ? hmpro_sanitize_css_color( (string) $attributes['iconColor'] ) : '';
$title_color = isset( $attributes['titleColor'] ) ? hmpro_sanitize_css_color( (string) $attributes['titleColor'] ) : '';
$text_color  = isset( $attributes['textColor'] ) ? hmpro_sanitize_css_color( (string) $attributes['textColor'] ) : '';
$link_color  = isset( $attributes['linkColor'] ) ? hmpro_sanitize_css_color( (string) $attributes['linkColor'] ) : '';
$bg_color    = isset( $attributes['bgColor'] ) ? hmpro_sanitize_css_color( (string) $attributes['bgColor'] ) : '';
$title_ff    = isset( $attributes['titleFontFamily'] ) ? sanitize_text_field( (string) $attributes['titleFontFamily'] ) : '';
$title_fw    = isset( $attributes['titleFontWeight'] ) ? sanitize_text_field( (string) $attributes['titleFontWeight'] ) : '';
$text_ff     = isset( $attributes['textFontFamily'] ) ? sanitize_text_field( (string) $attributes['textFontFamily'] ) : '';
$text_fw     = isset( $attributes['textFontWeight'] ) ? sanitize_text_field( (string) $attributes['textFontWeight'] ) : '';
$link_ff     = isset( $attributes['linkFontFamily'] ) ? sanitize_text_field( (string) $attributes['linkFontFamily'] ) : '';
$link_fw     = isset( $attributes['linkFontWeight'] ) ? sanitize_text_field( (string) $attributes['linkFontWeight'] ) : '';
$title      = isset( $attributes['title'] ) ? (string) $attributes['title'] : '';
$text       = isset( $attributes['text'] ) ? (string) $attributes['text'] : '';
$link_url   = isset( $attributes['linkUrl'] ) ? esc_url_raw( (string) $attributes['linkUrl'] ) : '';
$link_label = isset( $attributes['linkLabel'] ) ? (string) $attributes['linkLabel'] : '';

$layout = in_array( $layout, array( 'top', 'left' ), true ) ? $layout : 'top';
$align  = in_array( $align, array( 'left', 'center' ), true ) ? $align : 'left';
$icon_size = max( 14, min( 128, $icon_size ) );
$title_size = max( 12, min( 48, $title_size ) );
$text_size  = max( 10, min( 32, $text_size ) );

$classes = array(
	'hmpro-block',
	'hmpro-feature-item',
	'is-layout-' . $layout,
	'is-align-' . $align,
);

$icon_html = '';
if ( $icon_mode === 'custom' && trim( $custom_svg ) !== '' ) {
	$icon_html = hmpro_kses_svg( $custom_svg );
} elseif ( $icon_mode === 'preset' ) {
	$icon_html = hmpro_kses_svg( hmpro_get_svg_preset( $preset ) );
}

$wrap_style = array(
	'--hmpro-fi-icon:' . $icon_size . 'px',
	'--hmpro-fi-title:' . $title_size . 'px',
	'--hmpro-fi-text:' . $text_size . 'px',
);
if ( $icon_color ) {
	$wrap_style[] = '--hmpro-fi-ic:' . $icon_color;
}
if ( $title_color ) {
	$wrap_style[] = '--hmpro-fi-tc:' . $title_color;
}
if ( $text_color ) {
	$wrap_style[] = '--hmpro-fi-xc:' . $text_color;
}
if ( $link_color ) {
	$wrap_style[] = '--hmpro-fi-lc:' . $link_color;
}
if ( $bg_color ) {
	$wrap_style[] = '--hmpro-fi-bg:' . $bg_color;
}
if ( $title_ff ) {
	$wrap_style[] = '--hmpro-fi-title-ff:' . $title_ff;
}
if ( $title_fw ) {
	$wrap_style[] = '--hmpro-fi-title-fw:' . $title_fw;
}
if ( $text_ff ) {
	$wrap_style[] = '--hmpro-fi-text-ff:' . $text_ff;
}
if ( $text_fw ) {
	$wrap_style[] = '--hmpro-fi-text-fw:' . $text_fw;
}
if ( $link_ff ) {
	$wrap_style[] = '--hmpro-fi-link-ff:' . $link_ff;
}
if ( $link_fw ) {
	$wrap_style[] = '--hmpro-fi-link-fw:' . $link_fw;
}

$inline_icon_style  = $icon_color ? ' style="color:' . esc_attr( $icon_color ) . ';"' : '';
$inline_title_style = ' style="font-size:' . esc_attr( $title_size ) . 'px;' . ( $title_color ? 'color:' . esc_attr( $title_color ) . ';' : '' ) . ( $title_ff ? 'font-family:' . esc_attr( $title_ff ) . ';' : '' ) . ( $title_fw ? 'font-weight:' . esc_attr( $title_fw ) . ';' : '' ) . '"';
$inline_text_style  = ' style="font-size:' . esc_attr( $text_size ) . 'px;' . ( $text_color ? 'color:' . esc_attr( $text_color ) . ';' : '' ) . ( $text_ff ? 'font-family:' . esc_attr( $text_ff ) . ';' : '' ) . ( $text_fw ? 'font-weight:' . esc_attr( $text_fw ) . ';' : '' ) . '"';
$inline_link_style  = ( $link_color || $link_ff || $link_fw ) ? ' style="' . ( $link_color ? 'color:' . esc_attr( $link_color ) . ';' : '' ) . ( $link_ff ? 'font-family:' . esc_attr( $link_ff ) . ';' : '' ) . ( $link_fw ? 'font-weight:' . esc_attr( $link_fw ) . ';' : '' ) . '"' : '';

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => implode( ' ', $classes ),
		'style' => implode( ';', $wrap_style ) . ';' . ( $bg_color ? 'background:' . $bg_color . ';' : '' ),
	)
);

echo '<div ' . $wrapper_attributes . '>';
	if ( $icon_html ) {
		echo '<div class="hmpro-feature-item__icon"' . $inline_icon_style . '>' . $icon_html . '</div>';
	}

	echo '<div class="hmpro-feature-item__content">';
		if ( $title !== '' ) {
			echo '<h3 class="hmpro-feature-item__title"' . $inline_title_style . '>' . esc_html( $title ) . '</h3>';
		}
		if ( $text !== '' ) {
			echo '<p class="hmpro-feature-item__text"' . $inline_text_style . '>' . esc_html( $text ) . '</p>';
		}
		if ( $link_url && $link_label ) {
			echo '<a class="hmpro-feature-item__link" href="' . esc_url( $link_url ) . '"' . $inline_link_style . '>' . esc_html( $link_label ) . '</a>';
		}
	echo '</div>';
echo '</div>';
