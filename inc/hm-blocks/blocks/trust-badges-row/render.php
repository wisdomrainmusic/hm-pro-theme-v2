<?php
/**
 * Render: HM Trust Badges Row
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$align       = isset( $attributes['align'] ) ? (string) $attributes['align'] : 'center';
$items       = isset( $attributes['items'] ) && is_array( $attributes['items'] ) ? $attributes['items'] : array();
$columns     = isset( $attributes['columns'] ) ? (int) $attributes['columns'] : 4;
$icon_size   = isset( $attributes['iconSize'] ) ? (int) $attributes['iconSize'] : 22;
$title_size  = isset( $attributes['titleSize'] ) ? (int) $attributes['titleSize'] : 14;
$text_size   = isset( $attributes['textSize'] ) ? (int) $attributes['textSize'] : 12;

$icon_color  = isset( $attributes['iconColor'] ) ? hmpro_sanitize_css_color( (string) $attributes['iconColor'] ) : '';
$title_color = isset( $attributes['titleColor'] ) ? hmpro_sanitize_css_color( (string) $attributes['titleColor'] ) : '';
$text_color  = isset( $attributes['textColor'] ) ? hmpro_sanitize_css_color( (string) $attributes['textColor'] ) : '';
$bg_color    = isset( $attributes['bgColor'] ) ? hmpro_sanitize_css_color( (string) $attributes['bgColor'] ) : '';
$bd_color    = isset( $attributes['borderColor'] ) ? hmpro_sanitize_css_color( (string) $attributes['borderColor'] ) : '';

$columns = max( 2, min( 6, $columns ) );
if ( ! empty( $items ) ) {
	$columns = max( 2, min( 6, count( $items ) ) );
}

$style = array();
$style[] = '--hmpro-tb-cols:' . $columns;
$style[] = '--hmpro-tb-icon:' . max( 14, min( 48, $icon_size ) ) . 'px';
$style[] = '--hmpro-tb-title:' . max( 12, min( 24, $title_size ) ) . 'px';
$style[] = '--hmpro-tb-text:' . max( 10, min( 20, $text_size ) ) . 'px';
if ( $icon_color )  { $style[] = '--hmpro-tb-ic:' . $icon_color; }
if ( $title_color ) { $style[] = '--hmpro-tb-tc:' . $title_color; }
if ( $text_color )  { $style[] = '--hmpro-tb-xc:' . $text_color; }
if ( $bg_color )    { $style[] = '--hmpro-tb-bg:' . $bg_color; }
if ( $bd_color )    { $style[] = '--hmpro-tb-bd:' . $bd_color; }

$classes = array( 'hmpro-block', 'hmpro-trust-badges', 'is-align-' . ( $align === 'left' ? 'left' : 'center' ) );

?><div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" style="<?php echo esc_attr( implode( ';', $style ) ); ?>">
	<div class="hmpro-tb__grid">
		<?php foreach ( $items as $item ) :
			$icon_key = isset( $item['icon'] ) ? sanitize_key( (string) $item['icon'] ) : 'check';
			$title    = isset( $item['title'] ) ? wp_kses_post( (string) $item['title'] ) : '';
			$text     = isset( $item['text'] ) ? wp_kses_post( (string) $item['text'] ) : '';
			$svg      = hmpro_kses_svg( hmpro_get_svg_preset( $icon_key ) );
		?>
			<div class="hmpro-tb__item">
				<div class="hmpro-tb__icon" aria-hidden="true"><?php echo $svg; ?></div>
				<div class="hmpro-tb__content">
					<?php if ( $title ) : ?><div class="hmpro-tb__title"><?php echo $title; ?></div><?php endif; ?>
					<?php if ( $text ) : ?><div class="hmpro-tb__text"><?php echo $text; ?></div><?php endif; ?>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
</div>
