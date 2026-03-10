<?php
/**
 * The main template file
 *
 * @package HMPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<main id="primary" class="site-main">
	<div class="hmpro-container">
		<?php
		/**
		 * Blog / archives:
		 * - is_home(): Posts page (page_for_posts) OR "Your latest posts"
		 * - is_archive(), is_search(): archive listings
		 *
		 * WordPress "Posts page" ignores the page content by default.
		 * We intentionally render that page's block content here so users can build the blog
		 * layout with HM Pro blocks (e.g., HM Blog Grid).
		 */
		if ( is_home() || is_archive() || is_search() ) :

			$rendered_posts_page = false;
			$posts_page_had_grid = false;

			// If this is the Posts page, render its block content first.
			if ( is_home() && ! is_front_page() ) {
				$posts_page_id = (int) get_option( 'page_for_posts' );

				if ( $posts_page_id > 0 ) {
					$posts_page = get_post( $posts_page_id );

					if ( $posts_page && 'publish' === $posts_page->post_status ) {

						global $post;
						$__hmpro_prev_post = $post;
						$page_content = (string) $posts_page->post_content;

						// Track whether the page contains the grid block.
						$posts_page_had_grid = has_block( 'hmpro/blog-grid', $page_content );
						?>
						<article id="page-<?php echo esc_attr( $posts_page_id ); ?>" <?php post_class( 'hmpro-posts-page', $posts_page_id ); ?>>
							<header class="entry-header">
								<?php
								if ( ! function_exists( 'hmpro_is_title_hidden' ) || ! hmpro_is_title_hidden( $posts_page_id ) ) {
									echo '<h1 class="entry-title">' . esc_html( get_the_title( $posts_page_id ) ) . '</h1>';
								}
								?>
							</header>
							<div class="entry-content">
								<?php
								/**
								 * IMPORTANT:
								 * Render posts-page content with correct global $post context
								 * so block rendering behaves exactly like a normal page.
								 */
								$post = $posts_page; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
								setup_postdata( $post );

								$raw_content = (string) $posts_page->post_content;

								// Render blocks directly (do not rely on the_content filters in posts-page context).
								$rendered = '';
								if ( function_exists( 'do_blocks' ) && $raw_content !== '' ) {
									$rendered = do_blocks( $raw_content );
								}

								// Fallback for classic content or if blocks returned empty for any reason.
								if ( trim( (string) $rendered ) === '' ) {
									$rendered = apply_filters( 'the_content', $raw_content );
								}

								// Always output the page content (could include hero/heading etc).
								echo $rendered; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

								/**
								 * Decide whether we should SKIP fallback listing.
								 * Only skip fallback if:
								 * - page contains the grid block
								 * - AND rendered output is not empty (grid actually produced markup)
								 */
								$rendered_posts_page = ( $posts_page_had_grid && trim( wp_strip_all_tags( (string) $rendered ) ) !== '' );

								wp_reset_postdata();
								$post = $__hmpro_prev_post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
								?>
							</div>
						</article>
						<?php
					}
				}
			}

			// If there is no custom Posts page grid, fall back to a safe default listing.
			if ( ! $rendered_posts_page ) :
				if ( have_posts() ) :
					?>
					<div class="hmpro-archive-list">
						<?php
						while ( have_posts() ) :
							the_post();
							?>
							<article id="post-<?php the_ID(); ?>" <?php post_class( 'hmpro-archive-card' ); ?>>
								<?php if ( has_post_thumbnail() ) : ?>
									<a class="hmpro-archive-card__thumb" href="<?php the_permalink(); ?>" aria-label="<?php the_title_attribute(); ?>">
										<?php the_post_thumbnail( 'large', array( 'loading' => 'lazy' ) ); ?>
									</a>
								<?php endif; ?>

								<div class="hmpro-archive-card__body">
									<h2 class="hmpro-archive-card__title">
										<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
									</h2>

									<div class="hmpro-archive-card__meta">
										<?php echo esc_html( get_the_date() ); ?>
									</div>

									<div class="hmpro-archive-card__excerpt">
										<?php the_excerpt(); ?>
									</div>

									<a class="hmpro-archive-card__more" href="<?php the_permalink(); ?>">
										<?php esc_html_e( 'Read more', 'hmpro' ); ?>
									</a>
								</div>
							</article>
							<?php
						endwhile;
						?>
					</div>

					<?php the_posts_pagination(); ?>
					<?php
				else :
					?>
					<p><?php esc_html_e( 'No posts found.', 'hmpro' ); ?></p>
					<?php
				endif;
			endif;

		else :

			// Default (singular/page-like) rendering.
			if ( have_posts() ) :
				while ( have_posts() ) :
					the_post();
					?>
					<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
						<header class="entry-header">
							<?php
							if ( ! function_exists( 'hmpro_is_title_hidden' ) || ! hmpro_is_title_hidden( get_the_ID() ) ) {
								the_title( '<h1 class="entry-title">', '</h1>' );
							}
							?>
						</header>
						<div class="entry-content">
							<?php the_content(); ?>
						</div>
					</article>
					<?php
				endwhile;
			else :
				?>
				<p><?php esc_html_e( 'No content found.', 'hmpro' ); ?></p>
				<?php
			endif;

		endif;
		?>
	</div>
</main>

<?php
get_footer();
