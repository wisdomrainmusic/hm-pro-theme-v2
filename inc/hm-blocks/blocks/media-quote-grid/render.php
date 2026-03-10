<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * HM Media Quote Grid render (hybrid).
 */

$preset = isset( $attributes['preset'] ) ? (string) $attributes['preset'] : 'two_equal';

$limits = array(
	'one_feature'        => 1,
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
	'six_mosaic_left'    => 5,
	'six_mosaic_right'   => 5,
);

$max_tiles = isset( $limits[ $preset ] ) ? (int) $limits[ $preset ] : 3;

$grid_gap    = isset( $attributes['gridGap'] ) ? (int) $attributes['gridGap'] : 20;
$card_radius = isset( $attributes['cardRadius'] ) ? (int) $attributes['cardRadius'] : 18;
$card_pad    = isset( $attributes['cardPadding'] ) ? (int) $attributes['cardPadding'] : 22;

$tiles = array();
if ( isset( $attributes['tiles'] ) && is_array( $attributes['tiles'] ) ) {
	$tiles = $attributes['tiles'];
}

// Fallback tiles if none provided (editor safety / front-end resilience).
if ( empty( $tiles ) ) {
	for ( $i = 1; $i <= $max_tiles; $i++ ) {
		$tiles[] = array(
			'show'          => true,
			'title'         => sprintf( 'Tile %d Title', $i ),
			'paragraph'     => 'Add paragraph here.',
			'quote'         => 'Add quote here.',
			'cite'          => '',
			'mediaPosition' => ( $i % 2 === 0 ) ? 'right' : 'left',
			'imageId'       => 0,
			'imageUrl'      => '',
			'imageAlt'      => '',
		);
	}
}

$tiles = array_slice( $tiles, 0, $max_tiles );

// Wrapper classes.
$classes   = array( 'hmpro-media-quote-grid' );
$classes[] = 'align' . ( isset( $attributes['align'] ) ? $attributes['align'] : 'wide' );

$block_wrapper_attributes = get_block_wrapper_attributes( array(
	'class' => implode( ' ', array_filter( $classes ) ),
) );

// Inline style vars.
$style = sprintf(
	'--hm-mq-gap:%dpx;--hm-mq-radius:%dpx;--hm-mq-pad:%dpx;',
	$grid_gap,
	$card_radius,
	$card_pad
);

echo '<div ' . $block_wrapper_attributes . ' style="' . esc_attr( $style ) . '">';
echo '<div class="hmpro-mq__inner">';
echo '<div class="hmpro-mq__grid hmpro-mq__grid--' . esc_attr( $preset ) . '">';

$letters = array( 'a', 'b', 'c', 'd', 'e', 'f' );

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

	$title     = isset( $tile['title'] ) ? wp_kses_post( $tile['title'] ) : '';
	$paragraph = isset( $tile['paragraph'] ) ? wp_kses_post( $tile['paragraph'] ) : '';
	$quote     = isset( $tile['quote'] ) ? wp_kses_post( $tile['quote'] ) : '';
	$cite      = isset( $tile['cite'] ) ? wp_kses_post( $tile['cite'] ) : '';

	$pos = isset( $tile['mediaPosition'] ) && 'right' === $tile['mediaPosition'] ? 'right' : 'left';

	$image_id     = ! empty( $tile['imageId'] ) ? (int) $tile['imageId'] : 0;
	$image_url    = '';
	$image_alt    = '';
	$image_markup = '';

	if ( $image_id ) {
		$image_markup = wp_get_attachment_image(
			$image_id,
			'large',
			false,
			array(
				'class'   => 'hmpro-mq__img',
				'loading' => 'lazy',
				'decoding'=> 'async',
			)
		);
		$image_alt = trim( (string) get_post_meta( $image_id, '_wp_attachment_image_alt', true ) );
	} elseif ( ! empty( $tile['imageUrl'] ) ) {
		$image_url = esc_url( $tile['imageUrl'] );
		$image_alt = ! empty( $tile['imageAlt'] ) ? esc_attr( $tile['imageAlt'] ) : '';
		$image_markup = '<img class="hmpro-mq__img" src="' . $image_url . '" alt="' . $image_alt . '" loading="lazy" decoding="async" />';
	}

	$area = isset( $letters[ $i - 1 ] ) ? $letters[ $i - 1 ] : 'a';

	echo '<article class="hmpro-mq__tile hmpro-mq__tile--' . esc_attr( $area ) . '">';
	echo '<div class="hmpro-mq__card ' . ( 'right' === $pos ? 'hmpro-mq__card--media-right' : '' ) . '">';

	// Media.
	echo '<div class="hmpro-mq__media">';
	if ( $image_markup ) {
		echo $image_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	} else {
		// Keep layout stable when no image is set.
		echo '<div class="hmpro-mq__media-placeholder" aria-hidden="true"></div>';
	}
	echo '</div>';

	// Content.
	echo '<div class="hmpro-mq__content">';
	if ( $title ) {
		echo '<h3 class="hmpro-mq__title">' . $title . '</h3>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	if ( $paragraph ) {
		echo '<p class="hmpro-mq__desc">' . $paragraph . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	if ( $quote ) {
		echo '<p class="hmpro-mq__quote">“' . $quote . '”</p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	if ( $cite ) {
		echo '<div class="hmpro-mq__cite">— ' . $cite . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	echo '</div>';

	echo '</div>';
	echo '</article>';
}

echo '</div>';
echo '</div>';
echo '</div>';
