<?php
/**
 * Server-side render for HM Product Tabs block.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Convert hex colors to rgba with opacity.
 */
if ( ! function_exists( 'hmpro_pft_hex_to_rgba' ) ) {
	function hmpro_pft_hex_to_rgba( $hex, $opacity ) {
		$hex = trim( (string) $hex );
		$opacity = max( 0, min( 1, (float) $opacity ) );

		if ( $hex === '' ) {
			return '';
		}
		// If it's already an rgb/rgba string, return as-is.
		if ( preg_match( '/^rgba?\(/i', $hex ) ) {
			return $hex;
		}

		// Accept 3 or 6 digit hex.
		$hex = ltrim( $hex, '#' );
		if ( strlen( $hex ) === 3 ) {
			$r = hexdec( str_repeat( $hex[0], 2 ) );
			$g = hexdec( str_repeat( $hex[1], 2 ) );
			$b = hexdec( str_repeat( $hex[2], 2 ) );
		} elseif ( strlen( $hex ) === 6 ) {
			$r = hexdec( substr( $hex, 0, 2 ) );
			$g = hexdec( substr( $hex, 2, 2 ) );
			$b = hexdec( substr( $hex, 4, 2 ) );
		} else {
			// Unsupported format; return original (might be named color).
			return '#' . $hex;
		}

		return sprintf( 'rgba(%d,%d,%d,%.3f)', $r, $g, $b, $opacity );
	}
}

$full_width     = ! empty( $attributes['fullWidth'] );
$tabs_layout   = isset( $attributes['tabsLayout'] ) ? (string) $attributes['tabsLayout'] : 'horizontal';
$tabs_layout   = in_array( $tabs_layout, array( 'horizontal', 'vertical' ), true ) ? $tabs_layout : 'horizontal';
$grid_max_width = isset( $attributes['gridMaxWidth'] ) ? absint( $attributes['gridMaxWidth'] ) : 1200;
$columns_desktop = isset( $attributes['columnsDesktop'] ) ? absint( $attributes['columnsDesktop'] ) : 4;
$grid_gap       = isset( $attributes['gridGap'] ) ? absint( $attributes['gridGap'] ) : 20;
$tabs_align     = isset( $attributes['tabsAlign'] ) ? sanitize_text_field( $attributes['tabsAlign'] ) : 'center';
$tabs           = ( isset( $attributes['tabs'] ) && is_array( $attributes['tabs'] ) ) ? $attributes['tabs'] : array();

$header_title   = isset( $attributes['headerTitle'] ) ? sanitize_text_field( (string) $attributes['headerTitle'] ) : '';
$header_subtitle = isset( $attributes['headerSubtitle'] ) ? sanitize_text_field( (string) $attributes['headerSubtitle'] ) : '';
$header_align   = isset( $attributes['headerAlign'] ) ? sanitize_text_field( (string) $attributes['headerAlign'] ) : 'center';

$panel_bg_color   = isset( $attributes['panelBgColor'] ) ? trim( (string) $attributes['panelBgColor'] ) : '';
$panel_bg_opacity = isset( $attributes['panelBgOpacity'] ) ? (float) $attributes['panelBgOpacity'] : 1.0;
$panel_border_color = isset( $attributes['panelBorderColor'] ) ? trim( (string) $attributes['panelBorderColor'] ) : '';
$panel_border_width = isset( $attributes['panelBorderWidth'] ) ? (float) $attributes['panelBorderWidth'] : 0;
$panel_radius     = isset( $attributes['panelRadius'] ) ? (float) $attributes['panelRadius'] : 16;
	// (intentionally empty) Tabs are already normalized above.

$styles = sprintf(
	'--hmpro-pft-columns:%d; --hmpro-pft-gap:%dpx; --hmpro-pft-tabs-align:%s;',
	$columns_desktop > 0 ? $columns_desktop : 4,
	$grid_gap,
	esc_attr( $tabs_align )
);

$tab_text       = isset( $attributes['tabTextColor'] ) ? trim( (string) $attributes['tabTextColor'] ) : '';
$tab_bg         = isset( $attributes['tabBgColor'] ) ? trim( (string) $attributes['tabBgColor'] ) : '';
$tab_text_hover = isset( $attributes['tabTextHoverColor'] ) ? trim( (string) $attributes['tabTextHoverColor'] ) : '';
$tab_bg_hover   = isset( $attributes['tabBgHoverColor'] ) ? trim( (string) $attributes['tabBgHoverColor'] ) : '';
$tab_text_active = isset( $attributes['tabTextActiveColor'] ) ? trim( (string) $attributes['tabTextActiveColor'] ) : '#ffffff';
$tab_bg_active  = isset( $attributes['tabBgActiveColor'] ) ? trim( (string) $attributes['tabBgActiveColor'] ) : 'rgba(0,0,0,0.65)';

if ( $tab_text !== '' ) {
	$styles .= '--hmpro-pft-tab-text:' . esc_attr( $tab_text ) . ';';
}
if ( $tab_bg !== '' ) {
	$styles .= '--hmpro-pft-tab-bg:' . esc_attr( $tab_bg ) . ';';
}
if ( $tab_text_hover !== '' ) {
	$styles .= '--hmpro-pft-tab-text-hover:' . esc_attr( $tab_text_hover ) . ';';
}
if ( $tab_bg_hover !== '' ) {
	$styles .= '--hmpro-pft-tab-bg-hover:' . esc_attr( $tab_bg_hover ) . ';';
}
if ( $tab_text_active !== '' ) {
	$styles .= '--hmpro-pft-tab-text-active:' . esc_attr( $tab_text_active ) . ';';
}
if ( $tab_bg_active !== '' ) {
	$styles .= '--hmpro-pft-tab-bg-active:' . esc_attr( $tab_bg_active ) . ';';
}

if ( $header_align !== '' ) {
	$styles .= '--hmpro-pft-header-align:' . esc_attr( $header_align ) . ';';
}

// Panel styling vars.
if ( $panel_bg_color !== '' ) {
	$styles .= '--hmpro-pft-panel-bg:' . esc_attr( hmpro_pft_hex_to_rgba( $panel_bg_color, $panel_bg_opacity ) ) . ';';
}
if ( $panel_border_color !== '' ) {
	$styles .= '--hmpro-pft-panel-border-color:' . esc_attr( $panel_border_color ) . ';';
}
$styles .= '--hmpro-pft-panel-border-width:' . esc_attr( (string) max( 0, $panel_border_width ) ) . 'px;';
$styles .= '--hmpro-pft-panel-radius:' . esc_attr( (string) max( 0, $panel_radius ) ) . 'px;';

// Expose the boxed content width as a CSS variable.
// The outer wrapper must remain 100% width so the "Full Width (Hero Like)" mode
// can render a full-viewport bleed layer without fighting theme containers.
if ( $grid_max_width > 0 ) {
	$styles .= '--hmpro-pft-maxw:' . esc_attr( (string) $grid_max_width ) . 'px;';
}

if ( ! $full_width ) {
	$styles .= 'width:100%;';
}

$wrapper_classes = 'hmpro-pft';
if ( $tabs_layout === 'vertical' ) {
	$wrapper_classes .= ' hmpro-pft--layout-vertical';
} else {
	$wrapper_classes .= ' hmpro-pft--layout-horizontal';
}
if ( $full_width ) {
	$wrapper_classes .= ' is-fullwidth';
}
if ( in_array( $header_align, array( 'left', 'right' ), true ) ) {
	$wrapper_classes .= ' has-header-align-' . $header_align;
}

/**
 * Render products list for a tab.
 *
 * render.php can be included multiple times in a single request (multiple block instances,
 * REST previews, editor renders). If we declare a global function unguarded, PHP will fatal
 * with "Cannot redeclare ..." and break the REST save request.
 */
if ( ! function_exists( 'hmpro_pft_render_products' ) ) {
function hmpro_pft_render_products( $taxonomy, $term_id, $per_page, $page ) {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return '<div class="hmpro-pft__empty">WooCommerce aktif değil.</div>';
	}

	$args = array(
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'posts_per_page' => max( 1, absint( $per_page ) ),
		'paged'          => max( 1, absint( $page ) ),
		'no_found_rows'  => true,
	);

	$term_id = absint( $term_id );
	$taxonomy = sanitize_key( $taxonomy );

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
		return '<div class="hmpro-pft__empty">Bu sekme için ürün bulunamadı.</div>';
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
				$hmpro_pft_count++;
			?>
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
	return ob_get_clean();
}
}

if ( ! function_exists( 'hmpro_pft_count_products' ) ) {
function hmpro_pft_count_products( $taxonomy, $term_id ) {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return 0;
	}

	$args = array(
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'no_found_rows'  => false,
	);

	$term_id  = absint( $term_id );
	$taxonomy = sanitize_key( $taxonomy );

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
	return isset( $q->found_posts ) ? absint( $q->found_posts ) : 0;
}
}

?>
<div
	class="<?php echo esc_attr( $wrapper_classes ); ?>"
	style="<?php echo esc_attr( $styles ); ?>"
	data-ajax-url="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>"
	data-nonce="<?php echo esc_attr( wp_create_nonce( 'hmpro_pft' ) ); ?>"
>
	<div class="hmpro-pft__frame">
		<div class="hmpro-pft__bleed" aria-hidden="true"></div>
		<div class="hmpro-pft__panel">
		<?php if ( $header_title !== '' || $header_subtitle !== '' ) : ?>
			<div class="hmpro-pft__header">
				<?php if ( $header_title !== '' ) : ?>
					<div class="hmpro-pft__header-title"><?php echo esc_html( $header_title ); ?></div>
				<?php endif; ?>
				<?php if ( $header_subtitle !== '' ) : ?>
					<div class="hmpro-pft__header-subtitle"><?php echo esc_html( $header_subtitle ); ?></div>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<div class="hmpro-pft__body">
		<div class="hmpro-pft__tabs" role="tablist">
		<?php foreach ( $tabs as $i => $tab ) : ?>
			<?php
				$title = isset( $tab['title'] ) ? (string) $tab['title'] : '';
				$title = $title !== '' ? $title : sprintf( 'Tab %d', (int) $i + 1 );
			?>
			<button
				type="button"
				class="hmpro-pft__tab<?php echo ( 0 === (int) $i ) ? ' is-active' : ''; ?>"
				data-tab-index="<?php echo esc_attr( (int) $i ); ?>"
				role="tab"
				aria-selected="<?php echo ( 0 === (int) $i ) ? 'true' : 'false'; ?>"
			>
				<?php echo esc_html( $title ); ?>
			</button>
		<?php endforeach; ?>
	</div>

	<div class="hmpro-pft__panes">
		<?php foreach ( $tabs as $i => $tab ) : ?>
			<?php
				$query_type = isset( $tab['queryType'] ) ? (string) $tab['queryType'] : 'category';
				$term_id    = isset( $tab['termId'] ) ? absint( $tab['termId'] ) : 0;
				$per_page   = isset( $tab['perPage'] ) ? absint( $tab['perPage'] ) : 8;

				$taxonomy = ( 'tag' === $query_type ) ? 'product_tag' : 'product_cat';
				$total_posts = hmpro_pft_count_products( $taxonomy, $term_id );
				$total_pages = $per_page > 0 ? (int) ceil( $total_posts / max( 1, $per_page ) ) : 1;
				$total_pages = max( 1, $total_pages );
			?>
			<div
				class="hmpro-pft__pane<?php echo ( 0 === (int) $i ) ? ' is-active' : ''; ?>"
				data-tab-index="<?php echo esc_attr( (int) $i ); ?>"
				data-taxonomy="<?php echo esc_attr( $taxonomy ); ?>"
				data-term-id="<?php echo esc_attr( $term_id ); ?>"
				data-per-page="<?php echo esc_attr( max( 1, $per_page ) ); ?>"
				data-current-page="1"
				data-total-pages="<?php echo esc_attr( $total_pages ); ?>"
				role="tabpanel"
			>
				<?php echo hmpro_pft_render_products( $taxonomy, $term_id, $per_page, 1 ); ?>
				<?php if ( $total_pages > 1 ) : ?>
					<div class="hmpro-pft__pager" role="navigation" aria-label="Ürün sayfalama">
						<button type="button" class="hmpro-pft__pager-btn hmpro-pft__pager-prev" disabled>Geri</button>
						<span class="hmpro-pft__pager-status">1 / <?php echo esc_html( (string) $total_pages ); ?></span>
						<button type="button" class="hmpro-pft__pager-btn hmpro-pft__pager-next">Daha Fazla</button>
					</div>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	</div>
		</div>
		</div>
	</div>
</div>