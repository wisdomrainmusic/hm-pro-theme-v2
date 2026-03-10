<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$columns       = isset( $attributes['columns'] ) ? (int) $attributes['columns'] : 3;
$gap           = isset( $attributes['gap'] ) ? (int) $attributes['gap'] : 20;
$content_width = isset( $attributes['contentWidth'] ) ? sanitize_key( $attributes['contentWidth'] ) : 'wide';
$is_surface    = ! empty( $attributes['isSurface'] );
$stack_mobile  = ! empty( $attributes['stackOnMobile'] );

$columns = max( 1, min( 6, $columns ) );
$gap     = max( 0, min( 200, $gap ) );
$content_width = in_array( $content_width, array( 'wide', 'narrow' ), true ) ? $content_width : 'wide';

$classes = array( 'hmpro-block', 'hmpro-features-row' );
if ( $is_surface ) {
	$classes[] = 'hmpro-surface';
}
if ( $stack_mobile ) {
	$classes[] = 'is-stack-mobile';
}

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => implode( ' ', $classes ),
		'style' => '--hmpro-fr-cols:' . $columns . ';--hmpro-fr-gap:' . $gap . 'px;',
	)
);

echo '<div ' . $wrapper_attributes . '>';
	echo '<div class="hmpro-features-row__inner is-' . esc_attr( $content_width ) . '">';
		// Inner blocks render (Feature Item blocks).
		echo $content;
	echo '</div>';
echo '</div>';
