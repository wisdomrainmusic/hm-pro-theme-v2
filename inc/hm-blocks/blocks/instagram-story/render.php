<?php
/**
 * Server-side render for HM Instagram Story.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$block_url = trailingslashit( HMPRO_BLOCKS_URL ) . 'blocks/instagram-story/';

// Ensure frontend JS is available even on WP installs that don't support block.json "viewScript".
if ( ! is_admin() ) {
	$handle = 'hmpro-instagram-story-view';
	if ( ! wp_script_is( $handle, 'registered' ) ) {
		wp_register_script(
			$handle,
			$block_url . 'view.js',
			array(),
			defined( 'HMPRO_VERSION' ) ? HMPRO_VERSION : null,
			true
		);
	}
	wp_enqueue_script( $handle );
}

$attrs = isset( $attributes ) && is_array( $attributes ) ? $attributes : array();

$full_width = ! empty( $attrs['fullWidth'] );
$bubble     = isset( $attrs['bubbleSize'] ) ? absint( $attrs['bubbleSize'] ) : 68;
$gap        = isset( $attrs['itemGap'] ) ? absint( $attrs['itemGap'] ) : 24;
$label_fs   = isset( $attrs['labelFontSize'] ) ? absint( $attrs['labelFontSize'] ) : 13;

$label_color      = isset( $attrs['labelColor'] ) ? sanitize_text_field( $attrs['labelColor'] ) : '#111111';
$modal_title_col  = isset( $attrs['modalTitleColor'] ) ? sanitize_text_field( $attrs['modalTitleColor'] ) : '#ffffff';
$modal_link_col   = isset( $attrs['modalLinkColor'] ) ? sanitize_text_field( $attrs['modalLinkColor'] ) : '#ffffff';
$modal_link_bg    = isset( $attrs['modalLinkBg'] ) ? sanitize_text_field( $attrs['modalLinkBg'] ) : 'rgba(0,0,0,0.55)';
$auto_time        = isset( $attrs['autoTime'] ) ? max( 1200, absint( $attrs['autoTime'] ) ) : 4000;

$content_scale_d  = isset( $attrs['contentScaleDesktop'] ) && is_numeric( $attrs['contentScaleDesktop'] ) ? (float) $attrs['contentScaleDesktop'] : 1;
$content_scale_m  = isset( $attrs['contentScaleMobile'] ) && is_numeric( $attrs['contentScaleMobile'] ) ? (float) $attrs['contentScaleMobile'] : 1;

// Clamp to a sensible range.
$content_scale_d = max( 0.6, min( 1.4, $content_scale_d ) );
$content_scale_m = max( 0.6, min( 1.4, $content_scale_m ) );

$stories = isset( $attrs['stories'] ) && is_array( $attrs['stories'] ) ? $attrs['stories'] : array();
if ( empty( $stories ) ) {
	return '';
}

$stories_data = array();

foreach ( $stories as $story ) {
	if ( ! is_array( $story ) ) {
		continue;
	}

	$slides_raw = isset( $story['slides'] ) && is_array( $story['slides'] ) ? $story['slides'] : array();
	$slides = array();

	foreach ( $slides_raw as $slide ) {
		if ( ! is_array( $slide ) ) {
			continue;
		}

		$image = isset( $slide['imageUrl'] ) ? esc_url_raw( $slide['imageUrl'] ) : '';
		if ( ! $image ) {
			continue;
		}

		$slides[] = array(
			'image'            => $image,
			'title'            => sanitize_text_field( $slide['title'] ?? '' ),
			'link_text'        => sanitize_text_field( $slide['linkText'] ?? '' ),
			'link_url'         => isset( $slide['linkUrl'] ) ? esc_url_raw( $slide['linkUrl'] ) : '',
			'link_is_external' => ! empty( $slide['newTab'] ),
			'link_nofollow'    => ! empty( $slide['nofollow'] ),
		);
	}

	if ( empty( $slides ) ) {
		continue;
	}

	$thumb = '';
	if ( ! empty( $story['thumbnailUrl'] ) ) {
		$thumb = esc_url_raw( $story['thumbnailUrl'] );
	} else {
		$thumb = $slides[0]['image'];
	}

	$stories_data[] = array(
		'label'     => sanitize_text_field( $story['label'] ?? '' ),
		'thumbnail' => $thumb,
		'slides'    => $slides,
	);
}

if ( empty( $stories_data ) ) {
	return '';
}

$classes = array(
	'hmpro-block',
	'hmpro-instagram-story',
	$full_width ? 'hmpro-is--fullwidth' : '',
);
$classes = implode( ' ', array_filter( $classes ) );

$style = array();
$style[] = '--hm-is-bubble:' . $bubble . 'px';
$style[] = '--hm-is-gap:' . $gap . 'px';
$style[] = '--hm-is-label-color:' . $label_color;
$style[] = '--hm-is-label-fs:' . $label_fs . 'px';
$style[] = '--hm-is-modal-title:' . $modal_title_col;
$style[] = '--hm-is-modal-link:' . $modal_link_col;
$style[] = '--hm-is-modal-link-bg:' . $modal_link_bg;
$style[] = '--hm-is-content-scale-d:' . $content_scale_d;
$style[] = '--hm-is-content-scale-m:' . $content_scale_m;
$style_attr = esc_attr( implode( ';', $style ) );

$data_attr = esc_attr( wp_json_encode( $stories_data ) );

?>
<div class="<?php echo esc_attr( $classes ); ?>">
	<div class="hmpro-is-wrapper" style="<?php echo $style_attr; ?>" data-hm-stories="<?php echo $data_attr; ?>" data-hm-auto="<?php echo esc_attr( (string) $auto_time ); ?>">

		<div class="hmpro-is-arrows">
			<button class="hmpro-is-arrow prev" type="button"><span>&larr;</span></button>
			<button class="hmpro-is-arrow next" type="button"><span>&rarr;</span></button>
		</div>

		<div class="hmpro-is-list">
			<?php foreach ( $stories_data as $index => $s ) : ?>
				<div class="hmpro-is-item" data-story-index="<?php echo esc_attr( (string) $index ); ?>">
					<div class="hmpro-is-thumb-wrapper">
						<div class="hmpro-is-thumb">
							<img src="<?php echo esc_url( $s['thumbnail'] ); ?>" alt="<?php echo esc_attr( $s['label'] ); ?>">
						</div>
					</div>
					<?php if ( ! empty( $s['label'] ) ) : ?>
						<div class="hmpro-is-label"><?php echo esc_html( $s['label'] ); ?></div>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>

		<!-- MODAL -->
		<div class="hmpro-is-modal" aria-hidden="true">
			<div class="hmpro-is-modal-inner">
				<div class="hmpro-is-modal-header">
					<div class="hmpro-is-modal-progress"></div>
					<div class="hmpro-is-modal-top-row">
						<div class="hmpro-is-modal-label"></div>
						<button class="hmpro-is-modal-close" type="button">&times;</button>
					</div>
				</div>

				<div class="hmpro-is-modal-body">
					<div class="hmpro-is-modal-click hmpro-is-modal-click-prev"></div>

					<div class="hmpro-is-modal-center">
						<button class="hmpro-is-modal-arrow modal-prev" type="button">&larr;</button>
						<button class="hmpro-is-modal-arrow modal-next" type="button">&rarr;</button>

						<img class="hmpro-is-modal-image" src="" alt="">
						<div class="hmpro-is-modal-text">
							<div class="hmpro-is-modal-title"></div>
							<a href="#" class="hmpro-is-modal-link" target="_blank" rel="nofollow noopener"></a>
						</div>
					</div>

					<div class="hmpro-is-modal-click hmpro-is-modal-click-next"></div>
				</div>
			</div>
		</div>

	</div>
</div>
