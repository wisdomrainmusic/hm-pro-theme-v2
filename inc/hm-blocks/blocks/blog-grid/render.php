<?php
/**
 * Render: HM Blog Grid
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$attrs = isset( $attributes ) && is_array( $attributes ) ? $attributes : array();

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

$args = array(
	'post_type'           => 'post',
	'post_status'         => 'publish',
	'ignore_sticky_posts' => true,
	'posts_per_page'      => $posts_per_page,
	'orderby'             => $orderby,
	'order'               => $order,
);

if ( $category > 0 ) {
	$args['cat'] = $category;
}

$q = new WP_Query( $args );

$classes = array( 'hmpro-blog-grid', 'is-style-' . $card_style );

$wrapper_attributes = get_block_wrapper_attributes(
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
);

ob_start();
?>
<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	data-cols-d="<?php echo esc_attr( $cols_d ); ?>"
	data-cols-t="<?php echo esc_attr( $cols_t ); ?>"
	data-cols-m="<?php echo esc_attr( $cols_m ); ?>">

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
	<?php else : ?>
		<div class="hmpro-blog-grid__empty">
			<?php echo esc_html__( 'No posts found.', 'hm-pro-theme' ); ?>
		</div>
	<?php endif; ?>

</div>
<?php
wp_reset_postdata();
return ob_get_clean();
