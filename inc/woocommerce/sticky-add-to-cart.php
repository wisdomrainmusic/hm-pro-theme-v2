<?php
// phpcs:ignoreFile
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * HM Sticky Add To Cart (WooCommerce)
 *
 * Mobile-first sticky bar shown on single product pages once the native add-to-cart
 * area scrolls out of view. Improves conversion without altering Woo templates.
 *
 * Additive module: safe to drop-in; does nothing if WooCommerce is inactive.
 */

/**
 * Try to read WhatsApp number from "Click to Chat" plugin options (if installed).
 * Falls back to empty string when not found.
 *
 * @return string Digits-only international number, e.g. 905301234567
 */
function hmpro_get_click_to_chat_number() {
	$option_names = array(
		'ht_ctc_chat_options',
		'ht_ctc_chat_options2',
		'click_to_chat_options',
		'ccw_options',
	);

	$raw = '';
	foreach ( $option_names as $name ) {
		$opt = get_option( $name );
		if ( is_array( $opt ) ) {
			foreach ( array( 'number', 'whatsapp_number', 'phone', 'mobile', 'whatsapp', 'wa_number' ) as $k ) {
				if ( ! empty( $opt[ $k ] ) ) {
					$raw = (string) $opt[ $k ];
					break 2;
				}
			}
		} elseif ( is_string( $opt ) && $opt ) {
			// Some plugins may store a plain string.
			$raw = $opt;
			break;
		}
	}

	$digits = preg_replace( '/\D+/', '', (string) $raw );
	if ( ! $digits ) {
		return '';
	}

	// Normalize common TR local format: 05xxxxxxxxx -> 90xxxxxxxxxx
	if ( strlen( $digits ) === 11 && strpos( $digits, '0' ) === 0 ) {
		$digits = '90' . substr( $digits, 1 );
	}
	// If someone entered 0090..., normalize to 90...
	if ( strpos( $digits, '00' ) === 0 ) {
		$digits = ltrim( $digits, '0' );
	}

	return $digits;
}

add_action( 'wp_enqueue_scripts', function () {
	if ( ! function_exists( 'is_product' ) || ! function_exists( 'WC' ) ) {
		return;
	}
	if ( ! is_product() ) {
		return;
	}

	wp_enqueue_style(
		'hmpro-sticky-atc',
		HMPRO_URL . '/assets/hm-sticky-atc.css',
		[],
		hmpro_asset_ver( 'assets/hm-sticky-atc.css' )
	);

	wp_enqueue_script(
		'hmpro-sticky-atc',
		HMPRO_URL . '/assets/hm-sticky-atc.js',
		[],
		hmpro_asset_ver( 'assets/hm-sticky-atc.js' ),
		true
	);
}, 50 );

add_action( 'wp_footer', function () {
	if ( ! function_exists( 'is_product' ) || ! function_exists( 'WC' ) ) {
		return;
	}
	if ( ! is_product() ) {
		return;
	}

	global $product;
	if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
		return;
	}

	// Basic data.
	$title      = $product->get_name();
	$price_html = $product->get_price_html();
	$thumb_id   = $product->get_image_id();
	$thumb_url  = $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'thumbnail' ) : '';
	$thumb_alt  = $thumb_id ? get_post_meta( $thumb_id, '_wp_attachment_image_alt', true ) : '';
	$thumb_alt  = $thumb_alt ? $thumb_alt : $title;

// WhatsApp "Ask a question" (integrates with Click to Chat if available).
$wa_number = hmpro_get_click_to_chat_number();
$wa_url    = '';
if ( $wa_number ) {
	$permalink = get_permalink( $product->get_id() );
	$message   = sprintf(
		/* translators: 1: product title, 2: product URL */
		'Merhaba, %1$s ürünü hakkında bilgi almak istiyorum. Ürün linki: %2$s',
		$title,
		$permalink
	);
	$wa_url = 'https://wa.me/' . rawurlencode( $wa_number ) . '?text=' . rawurlencode( $message );
}


	// Only show when purchasable.
	if ( ! $product->is_purchasable() ) {
		return;
	}

	// Button label.
	$btn_label = esc_html__( 'Add to cart', 'hmpro' );
	if ( $product->is_type( 'variable' ) ) {
		$btn_label = esc_html__( 'Select options', 'hmpro' );
	} elseif ( $product->is_type( 'external' ) ) {
		$btn_label = esc_html__( 'Buy product', 'hmpro' );
	}

	?>
	<div class="hmpro-sticky-atc" id="hmpro-sticky-atc" aria-hidden="true">
		<div class="hmpro-sticky-atc__inner">
			<div class="hmpro-sticky-atc__left">
				<?php if ( $thumb_url ) : ?>
					<img class="hmpro-sticky-atc__thumb" src="<?php echo esc_url( $thumb_url ); ?>" alt="<?php echo esc_attr( $thumb_alt ); ?>" loading="lazy" />
				<?php endif; ?>
				<div class="hmpro-sticky-atc__meta">
					<div class="hmpro-sticky-atc__title"><?php echo esc_html( $title ); ?></div>
					<?php if ( $price_html ) : ?>
						<div class="hmpro-sticky-atc__price"><?php echo wp_kses_post( $price_html ); ?></div>
					<?php endif; ?>
					<div class="hmpro-sticky-atc__note" id="hmpro-sticky-atc-note" aria-live="polite"></div>
				</div>
			</div>
<div class="hmpro-sticky-atc__right">
	<div class="hmpro-sticky-atc__actions">
		<button type="button" class="hmpro-sticky-atc__btn" id="hmpro-sticky-atc-btn">
			<?php echo esc_html( $btn_label ); ?>
		</button>
		<?php if ( $wa_url && function_exists( 'hmpro_svg_presets' ) ) : ?>
			<?php $svgs = hmpro_svg_presets(); ?>
			<a class="hmpro-sticky-atc__wa hmpro-socialicon" href="<?php echo esc_url( $wa_url ); ?>" target="_blank" rel="noopener" aria-label="<?php echo 'WhatsApp ile sor'; ?>">
				<?php echo isset( $svgs['whatsapp'] ) ? $svgs['whatsapp'] : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</a>
		<?php endif; ?>
	</div>
</div>
		</div>
	</div>
	<?php
}, 50 );
