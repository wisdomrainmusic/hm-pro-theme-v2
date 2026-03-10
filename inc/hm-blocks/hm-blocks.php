<?php
/**
 * HM Pro Blocks (theme module)
 *
 * Location: /inc/hm-blocks/
 *
 * This module registers custom Gutenberg blocks used for landing pages.
 * It is intentionally theme-embedded ("closed package").
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'HMPRO_VERSION' ) ) {
	// Fallback in case constants are not loaded yet.
	define( 'HMPRO_VERSION', '0.1.0' );
}

// Paths.
if ( ! defined( 'HMPRO_BLOCKS_PATH' ) ) {
	define( 'HMPRO_BLOCKS_PATH', trailingslashit( get_template_directory() ) . 'inc/hm-blocks' );
}
if ( ! defined( 'HMPRO_BLOCKS_URL' ) ) {
	define( 'HMPRO_BLOCKS_URL', trailingslashit( get_template_directory_uri() ) . 'inc/hm-blocks' );
}

// Shared renderer utilities.
require_once HMPRO_BLOCKS_PATH . '/render/shared/helpers.php';
require_once HMPRO_BLOCKS_PATH . '/render/blog-grid.php';
require_once HMPRO_BLOCKS_PATH . '/render/shared/sanitize.php';
require_once HMPRO_BLOCKS_PATH . '/render/shared/svg-presets.php';
require_once HMPRO_BLOCKS_PATH . '/render/shared/templates.php';

/**
 * Register "HM Pro" block category in the inserter.
 */
add_filter( 'block_categories_all', function ( $categories ) {
	$slug = 'hmpro';
	$categories = array_values( array_filter( $categories, function ( $cat ) use ( $slug ) {
		return ! ( isset( $cat['slug'] ) && $cat['slug'] === $slug );
	} ) );

	array_unshift( $categories, [
		'slug'  => $slug,
		'title' => __( 'HM Pro Blocks', 'hm-pro-theme' ),
		'icon'  => null,
	] );

	return $categories;
}, 20 );

/**
 * Register shared assets for HM Pro blocks.
 */
function hmpro_blocks_register_shared_assets() {
	wp_register_style(
		'hmpro-blocks',
		HMPRO_BLOCKS_URL . '/assets/css/blocks.css',
		[],
		file_exists( HMPRO_BLOCKS_PATH . '/assets/css/blocks.css' ) ? filemtime( HMPRO_BLOCKS_PATH . '/assets/css/blocks.css' ) : HMPRO_VERSION
	);

	wp_register_style(
		'hmpro-blocks-editor',
		HMPRO_BLOCKS_URL . '/assets/css/editor.css',
		[ 'wp-edit-blocks' ],
		file_exists( HMPRO_BLOCKS_PATH . '/assets/css/editor.css' ) ? filemtime( HMPRO_BLOCKS_PATH . '/assets/css/editor.css' ) : HMPRO_VERSION
	);

	wp_register_script(
		'hmpro-blocks-editor',
		HMPRO_BLOCKS_URL . '/assets/js/editor.js',
		[ 'wp-blocks', 'wp-dom-ready', 'wp-edit-post' ],
		file_exists( HMPRO_BLOCKS_PATH . '/assets/js/editor.js' ) ? filemtime( HMPRO_BLOCKS_PATH . '/assets/js/editor.js' ) : HMPRO_VERSION,
		true
	);
}
add_action( 'init', 'hmpro_blocks_register_shared_assets', 5 );

/**
 * Enqueue shared frontend styles for any HM Pro block.
 */
function hmpro_blocks_enqueue_block_assets() {
	// Keep editor compatibility: enqueue shared styles in the block editor.
	if ( is_admin() ) {
		wp_enqueue_style( 'hmpro-blocks' );
	} else {
		return;
	}

	/**
	 * Block styles from block.json may enqueue too late when blocks are rendered
	 * from widget areas (dynamic_sidebar) after wp_head(). That causes editor ↔
	 * frontend mismatch for HM Features Row / Feature Item.
	 *
	 * Preload their frontend styles so layout/gap/icon sizing/colors match on the homepage.
	 */
	$preload = array(
		'features-row' => array(
			'path' => HMPRO_BLOCKS_PATH . '/blocks/features-row/style.css',
			'url'  => HMPRO_BLOCKS_URL  . '/blocks/features-row/style.css',
		),
		'feature-item' => array(
			'path' => HMPRO_BLOCKS_PATH . '/blocks/feature-item/style.css',
			'url'  => HMPRO_BLOCKS_URL  . '/blocks/feature-item/style.css',
		),
		'blog-grid' => array(
			'path' => HMPRO_BLOCKS_PATH . '/blocks/blog-grid/style.css',
			'url'  => HMPRO_BLOCKS_URL  . '/blocks/blog-grid/style.css',
		),
	);

	foreach ( $preload as $key => $asset ) {
		wp_enqueue_style(
			'hmpro-block-' . $key,
			$asset['url'],
			array( 'hmpro-blocks' ),
			file_exists( $asset['path'] ) ? filemtime( $asset['path'] ) : HMPRO_VERSION
		);
	}
}
add_action( 'enqueue_block_assets', 'hmpro_blocks_enqueue_block_assets' );

/**
 * Enqueue HM Pro block frontend styles only when needed.
 * Builder-aware: homepage and header/footer builder regions may render blocks outside post_content.
 */
function hmpro_blocks_enqueue_frontend_assets() {
	if ( is_admin() ) {
		return;
	}

	$should = false;
	// Homepage often renders landing blocks via builder regions.
	if ( is_front_page() ) {
		$should = true;
	}

	// Blog "Posts page" (is_home) may be built with HM Pro blocks.
	if ( ! $should && is_home() && ! is_front_page() ) {
		$posts_page_id = (int) get_option( 'page_for_posts' );
		if ( $posts_page_id > 0 ) {
			$content = (string) get_post_field( 'post_content', $posts_page_id );
			$should  = ( $content && has_block( 'hmpro/', $content ) );
		}
	}

	// Singular content: load if any hmpro/* block is present in post content.
	if ( ! $should && is_singular() ) {
		$should = has_block( 'hmpro/' );
	}

	// Builder-aware fallback: if header/footer builder layout exists, load to avoid late widget renders.
	if ( ! $should && function_exists( 'hmpro_has_builder_layout' ) ) {
		$should = (bool) ( hmpro_has_builder_layout( 'header' ) || hmpro_has_builder_layout( 'footer' ) );
	}

	$should = (bool) apply_filters( 'hmpro/should_enqueue_blocks_css', $should );
	if ( ! $should ) {
		return;
	}

	wp_enqueue_style( 'hmpro-blocks' );

	// Preload critical block styles (see note above about widget/builder renders after wp_head).
	$preload = array(
		'features-row' => array(
			'path' => HMPRO_BLOCKS_PATH . '/blocks/features-row/style.css',
			'url'  => HMPRO_BLOCKS_URL  . '/blocks/features-row/style.css',
		),
		'feature-item' => array(
			'path' => HMPRO_BLOCKS_PATH . '/blocks/feature-item/style.css',
			'url'  => HMPRO_BLOCKS_URL  . '/blocks/feature-item/style.css',
		),
		'blog-grid' => array(
			'path' => HMPRO_BLOCKS_PATH . '/blocks/blog-grid/style.css',
			'url'  => HMPRO_BLOCKS_URL  . '/blocks/blog-grid/style.css',
		),
	);

	foreach ( $preload as $key => $asset ) {
		wp_enqueue_style(
			'hmpro-block-' . $key,
			$asset['url'],
			array( 'hmpro-blocks' ),
			file_exists( $asset['path'] ) ? filemtime( $asset['path'] ) : HMPRO_VERSION
		);
	}
}
add_action( 'wp_enqueue_scripts', 'hmpro_blocks_enqueue_frontend_assets', 9 );

/**
 * Enqueue editor-only assets.
 */
function hmpro_blocks_enqueue_editor_assets() {
	wp_enqueue_style( 'hmpro-blocks-editor' );
	wp_enqueue_script( 'hmpro-blocks-editor' );

	// Provide a reliable term loader for editor controls (admin-ajax), to avoid REST header stripping
	// or security plugins that block custom REST namespaces.
	$payload = array(
		'ajaxUrl' => admin_url( 'admin-ajax.php' ),
		'nonce'   => wp_create_nonce( 'hmpro_pft_terms' ),
	);

	$inline = 'window.hmproPft=' . wp_json_encode( $payload ) . ';';

	/**
	 * CRITICAL: Inject via core 'wp-blocks' which is always enqueued in the block editor.
	 * This avoids timing/handle mismatches where our block script runs before the inline
	 * config exists (and the admin-ajax term request never fires).
	 */
	wp_add_inline_script( 'wp-blocks', $inline, 'before' );

	// Keep also on our bundle (harmless redundancy).
	wp_add_inline_script( 'hmpro-blocks-editor', $inline, 'before' );
}
add_action( 'enqueue_block_editor_assets', 'hmpro_blocks_enqueue_editor_assets' );

/**
 * AJAX: Return ALL WooCommerce terms for editor dropdowns.
 *
 * This mirrors the Elementor approach (server-side get_terms) and avoids issues with
 * REST pagination headers and custom REST namespace blocks.
 */
function hmpro_pft_ajax_get_terms() {
	// Nonce is optional: some installs may not receive localized config due to caching/script order.
	// Since this is wp-admin only (wp_ajax_) and we enforce capability check, it's safe to proceed.
	$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
	if ( $nonce && ! wp_verify_nonce( $nonce, 'hmpro_pft_terms' ) ) {
		wp_send_json_error( array( 'message' => 'Invalid nonce.' ), 403 );
	}

	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error( array( 'message' => 'Forbidden.' ), 403 );
	}

	$taxonomy = isset( $_POST['taxonomy'] ) ? sanitize_key( wp_unslash( $_POST['taxonomy'] ) ) : '';
	if ( ! in_array( $taxonomy, array( 'product_cat', 'product_tag' ), true ) ) {
		wp_send_json_error( array( 'message' => 'Invalid taxonomy.' ), 400 );
	}

	$search = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
	$search = trim( $search );
	if ( strlen( $search ) > 80 ) {
		$search = substr( $search, 0, 80 );
	}

	$terms = get_terms(
		array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
			'orderby'    => 'name',
			'order'      => 'ASC',
			// Elementor-style: allow server-side search for the editor combobox.
			'search'     => $search ? $search : '',
			'number'     => $search ? 50 : 0,
		)
	);

	if ( is_wp_error( $terms ) ) {
		wp_send_json_error( array( 'message' => $terms->get_error_message() ), 500 );
	}

	$data = array();
	foreach ( $terms as $term ) {
		$data[] = array(
			'id'     => (int) $term->term_id,
			'name'   => (string) $term->name,
			'parent' => (int) $term->parent,
		);
	}

	wp_send_json_success( $data );
}
add_action( 'wp_ajax_hmpro_pft_get_terms', 'hmpro_pft_ajax_get_terms' );

/**
 * Register blocks located under /inc/hm-blocks/blocks/*
 *
 * Each block folder is expected to contain a block.json.
 */
function hmpro_blocks_register_blocks() {
	$blocks_dir = trailingslashit( HMPRO_BLOCKS_PATH ) . 'blocks';
	if ( ! is_dir( $blocks_dir ) ) {
		return;
	}

	$entries = glob( $blocks_dir . '/*', GLOB_ONLYDIR );
	if ( empty( $entries ) ) {
		return;
	}

	foreach ( $entries as $dir ) {
		$block_json = trailingslashit( $dir ) . 'block.json';
		if ( file_exists( $block_json ) ) {
			$slug = basename( $dir );

			// WP version-safe: blog-grid must use render_callback (block.json "render" is not honored everywhere).
			if ( 'blog-grid' === $slug && function_exists( 'hmpro_render_blog_grid_block' ) ) {
				if ( function_exists( 'register_block_type_from_metadata' ) ) {
					register_block_type_from_metadata( $dir, array(
						'render_callback' => 'hmpro_render_blog_grid_block',
					) );
				} else {
					register_block_type( $dir, array(
						'render_callback' => 'hmpro_render_blog_grid_block',
					) );
				}
				continue;
			}

			register_block_type( $dir );
		}
	}
}
add_action( 'init', 'hmpro_blocks_register_blocks', 20 );

/**
 * REST: Return ALL WooCommerce terms (categories/tags) for editor controls.
 *
 * Some hosts/caches strip REST pagination headers which can cause incomplete term lists
 * in editor combobox controls. This endpoint returns the full list in a single response.
 */
function hmpro_pft_register_terms_rest_route() {
	register_rest_route(
		'hmpro/v1',
		'/terms',
		array(
			'methods'             => 'GET',
			'permission_callback' => function () {
				return current_user_can( 'edit_posts' );
			},
			'args'                => array(
				'taxonomy' => array(
					'required'          => true,
					'sanitize_callback' => 'sanitize_key',
				),
			),
			'callback'            => function ( WP_REST_Request $request ) {
				$taxonomy = $request->get_param( 'taxonomy' );
				if ( ! in_array( $taxonomy, array( 'product_cat', 'product_tag' ), true ) ) {
					return new WP_Error( 'hmpro_invalid_taxonomy', 'Invalid taxonomy.', array( 'status' => 400 ) );
				}

				$terms = get_terms(
					array(
						'taxonomy'   => $taxonomy,
						'hide_empty' => false,
						'orderby'    => 'name',
						'order'      => 'ASC',
					)
				);

				if ( is_wp_error( $terms ) ) {
					return $terms;
				}

				$data = array();
				foreach ( $terms as $term ) {
					$data[] = array(
						'id'     => (int) $term->term_id,
						'name'   => (string) $term->name,
						'parent' => (int) $term->parent,
					);
				}

				return rest_ensure_response( $data );
			},
		)
	);
}
add_action( 'rest_api_init', 'hmpro_pft_register_terms_rest_route' );

/**
 * AJAX: Fetch paged products HTML for HM Product Tabs.
 */
function hmpro_pft_ajax_fetch_products() {
	$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'hmpro_pft' ) ) {
		wp_send_json_error( array( 'message' => 'Invalid nonce.' ), 403 );
	}

	$taxonomy = isset( $_POST['taxonomy'] ) ? sanitize_key( wp_unslash( $_POST['taxonomy'] ) ) : 'product_cat';
	$term_id  = isset( $_POST['termId'] ) ? absint( $_POST['termId'] ) : 0;
	$per_page = isset( $_POST['perPage'] ) ? max( 1, absint( $_POST['perPage'] ) ) : 8;
	$page     = isset( $_POST['page'] ) ? max( 1, absint( $_POST['page'] ) ) : 1;

	if ( ! class_exists( 'WooCommerce' ) ) {
		wp_send_json_error( array( 'message' => 'WooCommerce is not active.' ), 400 );
	}

	$args = array(
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'posts_per_page' => $per_page,
		'paged'          => $page,
		'no_found_rows'  => true,
	);

	if ( $term_id > 0 && in_array( $taxonomy, array( 'product_cat', 'product_tag' ), true ) ) {
		$args['tax_query'] = array(
			array(
				'taxonomy' => $taxonomy,
				'field'    => 'term_id',
				'terms'    => $term_id,
			),
		);
	}

	$q = new WP_Query( $args );
	if ( ! $q->have_posts() ) {
		wp_send_json_success( array( 'html' => '<div class="hmpro-pft__empty">Ürün bulunamadı.</div>' ) );
	}

	ob_start();
	?>
	<div class="hmpro-pft__grid" role="list">
		<?php $hmpro_pft_count = 0; ?>
		<?php while ( $q->have_posts() ) : $q->the_post(); ?>
			<?php
				$product = wc_get_product( get_the_ID() );
				if ( ! $product ) {
					continue;
				}
			?>
			<?php $hmpro_pft_count++; ?>
			<a class="hmpro-pft__card" href="<?php echo esc_url( get_permalink() ); ?>" role="listitem">
				<div class="hmpro-pft__thumb">
					<?php echo $product->get_image( 'woocommerce_thumbnail' ); ?>
				</div>
				<div class="hmpro-pft__meta">
					<div class="hmpro-pft__title"><?php echo esc_html( get_the_title() ); ?></div>
					<div class="hmpro-pft__price"><?php echo wp_kses_post( $product->get_price_html() ); ?></div>
				</div>
			</a>
		<?php endwhile; ?>
		<?php
			$hmpro_pft_target = max( 1, absint( $per_page ) );
			for ( $hmpro_pft_i = $hmpro_pft_count; $hmpro_pft_i < $hmpro_pft_target; $hmpro_pft_i++ ) {
				echo '<div class="hmpro-pft__card hmpro-pft__card--placeholder" aria-hidden="true"></div>';
			}
		?>
	</div>
	<?php
	wp_reset_postdata();
	$html = ob_get_clean();

	wp_send_json_success( array( 'html' => $html ) );
}

add_action( 'wp_ajax_hmpro_pft_fetch_products', 'hmpro_pft_ajax_fetch_products' );
add_action( 'wp_ajax_nopriv_hmpro_pft_fetch_products', 'hmpro_pft_ajax_fetch_products' );

/**
 * Fallback: ensure HMPro blocks render on the frontend even if the_content output
 * is overridden by a builder/template layer (e.g., Elementor, custom renderer, aggressive cache).
 *
 * We do NOT touch normal output when blocks already appear.
 * We only append rendered HMPro blocks if:
 * - raw post_content contains wp:hmpro/
 * - and final $content does NOT contain our block HTML markers
 */
function hmpro_render_only_hmpro_blocks_from_parsed( $blocks ) {
	$out = '';
	foreach ( (array) $blocks as $block ) {
		if ( empty( $block ) || ! is_array( $block ) ) {
			continue;
		}

		$name = isset( $block['blockName'] ) ? (string) $block['blockName'] : '';

		// If this is an HMPro block, render it fully (it will render its innerBlocks too).
		if ( $name && 0 === strpos( $name, 'hmpro/' ) ) {
			$out .= render_block( $block );
			continue;
		}

		// Otherwise, traverse inner blocks for HMPro blocks.
		if ( ! empty( $block['innerBlocks'] ) ) {
			$out .= hmpro_render_only_hmpro_blocks_from_parsed( $block['innerBlocks'] );
		}
	}
	return $out;
}

function hmpro_blocks_frontend_fallback_render( $content ) {
	if ( is_admin() || wp_doing_ajax() || wp_is_json_request() ) {
		return $content;
	}

	if ( ! is_singular() ) {
		return $content;
	}

	global $post;
	if ( ! $post instanceof WP_Post ) {
		return $content;
	}

	$raw = (string) $post->post_content;
	if ( $raw === '' ) {
		return $content;
	}

	// Only act when HMPro blocks exist in raw content.
	if ( false === strpos( $raw, 'wp:hmpro/' ) ) {
		return $content;
	}

	// If already rendered, do nothing.
	if (
		false !== strpos( $content, 'hmpro-features-row' ) ||
		false !== strpos( $content, 'hmpro-feature-item' ) ||
		false !== strpos( $content, 'hmpro-hero-slider' ) ||
		false !== strpos( $content, 'hmpro-promo-grid' )
	) {
		return $content;
	}

	// Render only HMPro blocks from parsed structure (prevents duplicating whole page content).
	$parsed = parse_blocks( $raw );
	if ( empty( $parsed ) ) {
		return $content;
	}

	$hmpro_out = hmpro_render_only_hmpro_blocks_from_parsed( $parsed );
	$hmpro_out = trim( (string) $hmpro_out );
	if ( $hmpro_out === '' ) {
		return $content;
	}

	// Append to the end (safe default). If needed, later we can insert before/after specific blocks.
	return $content . "\n\n" . $hmpro_out;
}
add_filter( 'the_content', 'hmpro_blocks_frontend_fallback_render', 999 );
