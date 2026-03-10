<?php
/**
 * HM Campaign Bar (dynamic)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$text            = isset( $attributes['text'] ) ? wp_kses_post( $attributes['text'] ) : '';
$link_url        = isset( $attributes['linkUrl'] ) ? esc_url_raw( $attributes['linkUrl'] ) : '';
$open_new        = ! empty( $attributes['openInNewTab'] );
$nofollow        = ! empty( $attributes['nofollow'] );
$text_mode       = isset( $attributes['textMode'] ) ? sanitize_key( $attributes['textMode'] ) : 'static';
$is_marquee      = ( 'marquee' === $text_mode );
$full_width      = ! empty( $attributes['fullWidth'] );

$speed           = isset( $attributes['marqueeSpeed'] ) ? (float) $attributes['marqueeSpeed'] : 18;
$gap             = isset( $attributes['marqueeGap'] ) ? (float) $attributes['marqueeGap'] : 48;
$height          = isset( $attributes['height'] ) ? (float) $attributes['height'] : 56;
$padding_x       = isset( $attributes['paddingX'] ) ? (float) $attributes['paddingX'] : 18;
$radius          = isset( $attributes['borderRadius'] ) ? (float) $attributes['borderRadius'] : 10;
$bg              = isset( $attributes['bgColor'] ) ? sanitize_text_field( $attributes['bgColor'] ) : '#000000';
$text_color      = isset( $attributes['textColor'] ) ? sanitize_text_field( $attributes['textColor'] ) : '#ffffff';

// clamp
$speed     = max( 5, min( 120, $speed ) );
$gap       = max( 8, min( 180, $gap ) );
$height    = max( 30, min( 140, $height ) );
$padding_x = max( 0, min( 80, $padding_x ) );
$radius    = max( 0, min( 50, $radius ) );

$classes = array( 'hmpro-cb' );
if ( $full_width ) {
	$classes[] = 'is-fullwidth';
}
if ( $is_marquee ) {
	$classes[] = 'is-marquee';
}
if ( ! empty( $link_url ) ) {
	$classes[] = 'has-link';
}

$style = array();
$style[] = '--hm-cb-bg:' . $bg;
$style[] = '--hm-cb-text:' . $text_color;
$style[] = '--hm-cb-gap:' . $gap . 'px';
$style[] = '--hm-cb-speed:' . $speed . 's';
$style[] = '--hm-cb-h:' . $height . 'px';
$style[] = '--hm-cb-padx:' . $padding_x . 'px';
$style[] = '--hm-cb-radius:' . $radius . 'px';

$tag = 'div';
$link_attrs = '';
if ( ! empty( $link_url ) ) {
	$tag = 'a';
	$rels = array();
	if ( $nofollow ) {
		$rels[] = 'nofollow';
	}
	if ( $open_new ) {
		$rels[] = 'noopener';
		$rels[] = 'noreferrer';
	}
	$link_attrs .= ' href="' . esc_url( $link_url ) . '"';
	if ( $open_new ) {
		$link_attrs .= ' target="_blank"';
	}
	if ( ! empty( $rels ) ) {
		$link_attrs .= ' rel="' . esc_attr( implode( ' ', array_unique( $rels ) ) ) . '"';
	}
}

?>
<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" style="<?php echo esc_attr( implode( ';', $style ) ); ?>">
	<<?php echo $tag; ?> class="hmpro-cb__link"<?php echo $link_attrs; ?>>
		<span class="hmpro-cb__inner">
			<?php if ( $is_marquee ) : ?>
				<span class="hmpro-cb__marquee">
					<span class="hmpro-cb__marqueeContent">
						<?php for ( $i = 0; $i < 6; $i++ ) : ?>
							<span class="hmpro-cb__text"><?php echo $text; ?></span>
						<?php endfor; ?>
					</span>
				</span>
			<?php else : ?>
				<span class="hmpro-cb__text"><?php echo $text; ?></span>
			<?php endif; ?>
		</span>
	</<?php echo $tag; ?>>
</div>
