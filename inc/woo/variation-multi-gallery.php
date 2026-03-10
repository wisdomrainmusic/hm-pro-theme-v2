<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// NOTE: File also handles frontend JSON enrichment + gallery swap script enqueue.
// Bail if WooCommerce not active.
if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

/**
 * Meta key for storing variation gallery attachment IDs (comma-separated).
 */
function hmpro_variation_gallery_meta_key() {
	return '_hmpro_variation_gallery_ids';
}

/**
 * Admin: enqueue media + admin JS/CSS on product edit screens.
 */
add_action( 'admin_enqueue_scripts', function( $hook ) {
	if ( ! function_exists( 'get_current_screen' ) ) {
		return;
	}
	$screen = get_current_screen();
	if ( ! $screen || 'product' !== $screen->post_type ) {
		return;
	}
	// Only where variations UI exists.
	if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
		return;
	}

	wp_enqueue_media();

	wp_enqueue_style(
		'hmpro-woo-variation-gallery-admin',
		HMPRO_URL . '/assets/css/woo-variation-gallery-admin.css',
		[],
		hmpro_asset_ver( 'assets/css/woo-variation-gallery-admin.css' )
	);

	wp_enqueue_script(
		'hmpro-woo-variation-gallery-admin',
		HMPRO_URL . '/assets/js/woo-variation-gallery-admin.js',
		[ 'jquery' ],
		hmpro_asset_ver( 'assets/js/woo-variation-gallery-admin.js' ),
		true
	);
}, 30 );

/**
 * Admin: render field inside each variation panel.
 */
add_action( 'woocommerce_product_after_variable_attributes', function( $loop, $variation_data, $variation ) {
	$variation_id = isset( $variation->ID ) ? (int) $variation->ID : 0;
	if ( ! $variation_id ) {
		return;
	}

	$raw = (string) get_post_meta( $variation_id, hmpro_variation_gallery_meta_key(), true );
	$raw = trim( $raw );
	$ids = [];
	if ( $raw !== '' ) {
		foreach ( explode( ',', $raw ) as $id ) {
			$id = (int) trim( $id );
			if ( $id > 0 ) {
				$ids[] = $id;
			}
		}
	}

	?>
	<div class="form-row form-row-full hmpro-var-gallery-row">
		<label><?php esc_html_e( 'Variation Gallery', 'hm-pro-theme' ); ?></label>

		<input
			type="hidden"
			class="hmpro-var-gallery-ids"
			name="hmpro_variation_gallery_ids[<?php echo esc_attr( $loop ); ?>]"
			value="<?php echo esc_attr( implode( ',', $ids ) ); ?>"
		/>

		<div class="hmpro-var-gallery-thumbs">
			<?php
			if ( ! empty( $ids ) ) :
				foreach ( $ids as $att_id ) :
					$src = wp_get_attachment_image_url( $att_id, 'thumbnail' );
					if ( ! $src ) {
						continue;
					}
					?>
					<div class="hmpro-var-thumb" data-id="<?php echo esc_attr( $att_id ); ?>">
						<img src="<?php echo esc_url( $src ); ?>" alt="" />
						<button type="button" class="button-link-delete hmpro-var-thumb-remove" aria-label="<?php esc_attr_e( 'Remove image', 'hm-pro-theme' ); ?>">×</button>
					</div>
					<?php
				endforeach;
			endif;
			?>
		</div>

		<p class="hmpro-var-gallery-actions">
			<button type="button" class="button hmpro-var-gallery-add"><?php esc_html_e( 'Add / Edit Gallery Images', 'hm-pro-theme' ); ?></button>
			<button type="button" class="button hmpro-var-gallery-clear"><?php esc_html_e( 'Clear', 'hm-pro-theme' ); ?></button>
		</p>

		<p class="description"><?php esc_html_e( 'Attach multiple images specific to this variation (Woodmart-like). These will replace the product gallery when the variation is selected.', 'hm-pro-theme' ); ?></p>
	</div>
	<?php
}, 10, 3 );

/**
 * Admin: save variation meta.
 */
add_action( 'woocommerce_save_product_variation', function( $variation_id, $i ) {
	if ( ! isset( $_POST['hmpro_variation_gallery_ids'][ $i ] ) ) {
		delete_post_meta( $variation_id, hmpro_variation_gallery_meta_key() );
		return;
	}

	$raw = (string) wp_unslash( $_POST['hmpro_variation_gallery_ids'][ $i ] );
	$raw = trim( $raw );

	if ( $raw === '' ) {
		delete_post_meta( $variation_id, hmpro_variation_gallery_meta_key() );
		return;
	}

	$ids = [];
	foreach ( explode( ',', $raw ) as $id ) {
		$id = (int) trim( $id );
		if ( $id > 0 ) {
			$ids[] = $id;
		}
	}
	$ids = array_values( array_unique( $ids ) );

	if ( empty( $ids ) ) {
		delete_post_meta( $variation_id, hmpro_variation_gallery_meta_key() );
		return;
	}

	update_post_meta( $variation_id, hmpro_variation_gallery_meta_key(), implode( ',', $ids ) );
}, 10, 2 );

/**
 * Frontend: add variation gallery payload so JS can swap without extra requests.
 */
add_filter( 'woocommerce_available_variation', function( $data, $product, $variation ) {
	if ( empty( $variation ) || ! is_object( $variation ) ) {
		return $data;
	}

	// IMPORTANT: get_id is a method (not a property). If we fail to read it,
	// hmpro_gallery never reaches the frontend JSON.
	$variation_id = 0;
	if ( is_callable( [ $variation, 'get_id' ] ) ) {
		$variation_id = (int) $variation->get_id();
	} elseif ( isset( $variation->ID ) ) {
		$variation_id = (int) $variation->ID;
	}
	if ( ! $variation_id ) {
		return $data;
	}

	$raw = (string) get_post_meta( $variation_id, hmpro_variation_gallery_meta_key(), true );
	$raw = trim( $raw );
	if ( $raw === '' ) {
		$data['hmpro_gallery'] = [];
		return $data;
	}

	$ids = [];
	foreach ( explode( ',', $raw ) as $id ) {
		$id = (int) trim( $id );
		if ( $id > 0 ) {
			$ids[] = $id;
		}
	}
	$ids = array_values( array_unique( $ids ) );
	if ( empty( $ids ) ) {
		$data['hmpro_gallery'] = [];
		return $data;
	}

	$images = [];
	foreach ( $ids as $att_id ) {
		$full = wp_get_attachment_image_src( $att_id, 'full' );
		$src  = wp_get_attachment_image_src( $att_id, 'woocommerce_single' );
		if ( ! $full || ! $src ) {
			continue;
		}

		$images[] = [
			'id'     => $att_id,
			'src'    => $src[0],
			'full'   => $full[0],
			'srcset' => wp_get_attachment_image_srcset( $att_id, 'woocommerce_single' ),
			'sizes'  => wp_get_attachment_image_sizes( $att_id, 'woocommerce_single' ),
			'alt'    => get_post_meta( $att_id, '_wp_attachment_image_alt', true ),
		];
	}

	$data['hmpro_gallery'] = $images;
	return $data;
}, 10, 3 );

/**
 * Frontend: enqueue gallery swap script only on single product pages.
 */
add_action( 'wp_enqueue_scripts', function() {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}
	wp_enqueue_script(
		'hmpro-woo-variation-gallery',
		HMPRO_URL . '/assets/js/woo-variation-gallery.js',
		[ 'jquery', 'wc-single-product' ],
		hmpro_asset_ver( 'assets/js/woo-variation-gallery.js' ),
		true
	);

	wp_enqueue_style(
		'hmpro-woo-variation-gallery',
		HMPRO_URL . '/assets/css/woo-variation-gallery.css',
		[],
		hmpro_asset_ver( 'assets/css/woo-variation-gallery.css' )
	);
}, 40 );
