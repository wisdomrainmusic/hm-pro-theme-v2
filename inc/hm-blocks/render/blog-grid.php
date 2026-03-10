<?php
/**
 * Server-side renderer for HM Blog Grid
 * (WP version-safe: uses render_callback instead of block.json "render")
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'hmpro_render_blog_grid_block' ) ) :
	function hmpro_render_blog_grid_block( $attributes, $content = '', $block = null ) {
		$attrs = is_array( $attributes ) ? $attributes : array();

		$posts_per_page = isset( $attrs['postsPerPage'] ) ? max( 1, min( 24, (int) $attrs['postsPerPage'] ) ) : 9;
		$orderby        = isset( $attrs['orderby'] ) ? sanitize_key( $attrs['orderby'] ) : 'date';
		$order          = isset( $attrs['order'] ) ? strtoupper( sanitize_text_field( $attrs['order'] ) ) : 'DESC';
		$category       = isset( $attrs['category'] ) ? (int) $attrs['category'] : 0;

		$cols_d = isset( $attrs['columnsDesktop'] ) ? max( 1, min( 6, (int) $attrs['columnsDesktop'] ) ) : 3;
		$cols_t = isset( $attrs['columnsTablet'] ) ? max( 1, min( 6, (int) $attrs['columnsTablet'] ) ) : 2;
		$cols_m = isset( $attrs['columnsMobile'] ) ? max( 1, min( 3, (int) $attrs['columnsMobile'] ) ) : 1;
		$gap    = isset( $attrs['gap'] ) ? max( 0, min( 64, (int) $attrs['gap'] ) ) : 24;

		$show_image     = ! empty( $attrs['showImage'] );
		$image_ratio    = isset( $attrs['imageRatio'] ) ? sanitize_text_field( $attrs['imageRatio'] ) : '16:9';
		$show_excerpt   = ! empty( $attrs['showExcerpt'] );
		$excerpt_length = isset( $attrs['excerptLength'] ) ? max( 5, min( 80, (int) $attrs['excerptLength'] ) ) : 24;
		$show_meta      = ! empty( $attrs['showMeta'] );
		$meta_style     = isset( $attrs['metaStyle'] ) ? sanitize_key( $attrs['metaStyle'] ) : 'date';

		$card_style   = isset( $attrs['cardStyle'] ) ? sanitize_key( $attrs['cardStyle'] ) : 'soft';
		$button_label = isset( $attrs['buttonLabel'] ) ? sanitize_text_field( $attrs['buttonLabel'] ) : 'Read more';

		$enable_tabs   = ! empty( $attrs['enableCategoryTabs'] );
		$tabs_position = isset( $attrs['tabsPosition'] ) ? sanitize_key( $attrs['tabsPosition'] ) : 'top';
		$tabs_source   = isset( $attrs['tabsSource'] ) ? sanitize_key( $attrs['tabsSource'] ) : 'all';
		$selected_cats = isset( $attrs['selectedCategories'] ) && is_array( $attrs['selectedCategories'] ) ? array_map( 'intval', $attrs['selectedCategories'] ) : array();
		$all_label     = isset( $attrs['allTabLabel'] ) ? sanitize_text_field( $attrs['allTabLabel'] ) : 'All';

		$enable_pagination = array_key_exists( 'enablePagination', $attrs ) ? (bool) $attrs['enablePagination'] : true;
		$prev_label        = isset( $attrs['paginationPrevLabel'] ) ? sanitize_text_field( $attrs['paginationPrevLabel'] ) : 'Prev';
		$next_label        = isset( $attrs['paginationNextLabel'] ) ? sanitize_text_field( $attrs['paginationNextLabel'] ) : 'Next';

		$allowed_orderby = array( 'date', 'modified', 'title', 'comment_count', 'rand' );
		if ( ! in_array( $orderby, $allowed_orderby, true ) ) {
			$orderby = 'date';
		}
		if ( ! in_array( $order, array( 'ASC', 'DESC' ), true ) ) {
			$order = 'DESC';
		}

		// Ratio -> CSS aspect-ratio value.
		$aspect = '16/9';
		if ( preg_match( '/^(\d+)\s*[:\/]\s*(\d+)$/', $image_ratio, $m ) ) {
			$a = max( 1, (int) $m[1] );
			$b = max( 1, (int) $m[2] );
			$aspect = $a . '/' . $b;
		}

		// Category can be overridden by query string when tabs are enabled.
		$active_cat = $category;
		if ( $enable_tabs && isset( $_GET['cat'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$active_cat = (int) $_GET['cat']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		$paged = 1;
		$paged_qv = (int) get_query_var( 'paged' );
		if ( $paged_qv > 0 ) {
			$paged = $paged_qv;
		} elseif ( isset( $_GET['paged'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$paged = max( 1, (int) $_GET['paged'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		$args = array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'ignore_sticky_posts' => true,
			'posts_per_page'      => $posts_per_page,
			'orderby'             => $orderby,
			'order'               => $order,
			'paged'               => $paged,
		);
		if ( $active_cat > 0 ) {
			$args['cat'] = $active_cat;
		}

		$q = new WP_Query( $args );

		$classes = array( 'hmpro-blog-grid', 'is-style-' . $card_style );
		if ( $enable_tabs ) {
			$classes[] = 'has-tabs';
			$classes[] = 'tabs-' . $tabs_position;
		}

		$wrapper_attributes = function_exists( 'get_block_wrapper_attributes' )
			? get_block_wrapper_attributes(
				array(
					'class' => implode( ' ', array_filter( $classes ) ),
					'style' => sprintf(
						'--hmpro-bg-cols-d:%d;--hmpro-bg-cols-t:%d;--hmpro-bg-cols-m:%d;--hmpro-bg-gap:%dpx;--hmpro-bg-aspect:%s;',
						$cols_d,
						$cols_t,
						$cols_m,
						$gap,
						esc_attr( $aspect )
					),
				)
			)
			: 'class="' . esc_attr( implode( ' ', array_filter( $classes ) ) ) . '"';

		ob_start();
		// Current URL helper (query-arg friendly; safe for pagination + tabs).
		$current_url = '';
		if ( isset( $_SERVER['HTTP_HOST'], $_SERVER['REQUEST_URI'] ) ) {
			$scheme      = is_ssl() ? 'https://' : 'http://';
			$current_url = esc_url_raw( $scheme . wp_unslash( $_SERVER['HTTP_HOST'] ) . wp_unslash( $_SERVER['REQUEST_URI'] ) );
		}
		$base_url = $current_url ? remove_query_arg( array( 'paged' ), $current_url ) : '';
		?>
		<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<?php
			if ( $enable_tabs ) {
				$term_args = array(
					'taxonomy'   => 'category',
					'hide_empty' => false,
					'orderby'    => 'name',
					'order'      => 'ASC',
				);
				if ( 'selected' === $tabs_source && ! empty( $selected_cats ) ) {
					$term_args['include'] = $selected_cats;
				}
				$terms = get_terms( $term_args );
				if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
					$tabs_classes = array( 'hmpro-blog-grid__tabs', 'pos-' . $tabs_position );
					?>
					<div class="<?php echo esc_attr( implode( ' ', $tabs_classes ) ); ?>">
						<?php
						$all_url = $base_url ? remove_query_arg( array( 'cat' ), $base_url ) : '';
						$all_active = ( (int) $active_cat <= 0 );
						?>
						<a class="hmpro-blog-grid__tab <?php echo $all_active ? 'is-active' : ''; ?>" href="<?php echo esc_url( $all_url ); ?>"><?php echo esc_html( $all_label ? $all_label : 'All' ); ?></a>
						<?php foreach ( $terms as $term ) :
							$tid = (int) $term->term_id;
							if ( $tid <= 0 ) { continue; }
							$url = $base_url ? add_query_arg( array( 'cat' => $tid ), $base_url ) : '';
							$active = ( (int) $active_cat === $tid );
							?>
							<a class="hmpro-blog-grid__tab <?php echo $active ? 'is-active' : ''; ?>" href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $term->name ); ?></a>
						<?php endforeach; ?>
					</div>
					<?php
				}
			}
			?>
			<?php if ( $q->have_posts() ) : ?>
				<div class="hmpro-blog-grid__inner">
					<?php
					while ( $q->have_posts() ) :
						$q->the_post();
						$post_id   = get_the_ID();
						$permalink = get_permalink( $post_id );
						$title     = get_the_title( $post_id );

						$img_html = '';
						if ( $show_image && has_post_thumbnail( $post_id ) ) {
							$img_html = get_the_post_thumbnail( $post_id, 'large', array( 'loading' => 'lazy' ) );
						}

						$excerpt = '';
						if ( $show_excerpt ) {
							$raw     = get_the_excerpt( $post_id );
							$excerpt = wp_trim_words( wp_strip_all_tags( $raw ), $excerpt_length, '…' );
						}

						$meta = '';
						if ( $show_meta ) {
							switch ( $meta_style ) {
								case 'author':
									$meta = get_the_author_meta( 'display_name', (int) get_post_field( 'post_author', $post_id ) );
									break;
								case 'date_author':
									$meta = get_the_date( '', $post_id ) . ' · ' . get_the_author_meta( 'display_name', (int) get_post_field( 'post_author', $post_id ) );
									break;
								case 'date':
								default:
									$meta = get_the_date( '', $post_id );
									break;
							}
						}
						?>
						<article class="hmpro-blog-card">
							<?php if ( $img_html ) : ?>
								<a class="hmpro-blog-card__media" href="<?php echo esc_url( $permalink ); ?>" aria-label="<?php echo esc_attr( $title ); ?>">
									<?php echo $img_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								</a>
							<?php endif; ?>

							<div class="hmpro-blog-card__content">
								<?php if ( $meta ) : ?>
									<div class="hmpro-blog-card__meta"><?php echo esc_html( $meta ); ?></div>
								<?php endif; ?>

								<h3 class="hmpro-blog-card__title"><a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $title ); ?></a></h3>

								<?php if ( $excerpt ) : ?>
									<p class="hmpro-blog-card__excerpt"><?php echo esc_html( $excerpt ); ?></p>
								<?php endif; ?>

								<a class="hmpro-blog-card__button" href="<?php echo esc_url( $permalink ); ?>">
									<?php echo esc_html( $button_label ); ?>
								</a>
							</div>
						</article>
					<?php endwhile; ?>
				</div>
				<?php if ( $enable_pagination && (int) $q->max_num_pages > 1 ) :
					$paginate_base = $base_url ? add_query_arg( array( 'paged' => '%#%' ), $base_url ) : '';
					$links = paginate_links( array(
						'base'      => $paginate_base,
						'format'    => '',
						'current'   => max( 1, (int) $paged ),
						'total'     => (int) $q->max_num_pages,
						'prev_text' => $prev_label,
						'next_text' => $next_label,
						'type'      => 'array',
					) );
					if ( is_array( $links ) && ! empty( $links ) ) : ?>
						<nav class="hmpro-blog-grid__pagination" aria-label="<?php echo esc_attr__( 'Pagination', 'hm-pro-theme' ); ?>">
							<ul>
								<?php foreach ( $links as $link ) : ?>
									<li><?php echo $link; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></li>
								<?php endforeach; ?>
							</ul>
						</nav>
					<?php endif;
				endif; ?>
			<?php else : ?>
				<div class="hmpro-blog-grid__empty"><?php echo esc_html__( 'No posts found.', 'hm-pro-theme' ); ?></div>
			<?php endif; ?>
		</div>
		<?php

		wp_reset_postdata();
		return ob_get_clean();
	}
endif;
