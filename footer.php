<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
do_action( 'hmpro/footer/before' );
?>

<?php if ( function_exists( 'hmpro_has_builder_layout' ) && hmpro_has_builder_layout( 'footer' ) ) : ?>

	<?php do_action( 'hmpro/footer/builder/before' ); ?>

	<footer id="site-footer" class="hmpro-footer-builder">
		<?php hmpro_render_builder_region( 'footer_top', 'footer' ); ?>
		<?php hmpro_render_builder_region( 'footer_main', 'footer' ); ?>
		<?php hmpro_render_builder_region( 'footer_bottom', 'footer' ); ?>
	</footer>

	<?php do_action( 'hmpro/footer/builder/after' ); ?>

<?php else : ?>

	<footer class="site-footer">
		<div class="hmpro-container">
			<nav class="footer-nav" aria-label="<?php esc_attr_e( 'Footer Menu', 'hmpro' ); ?>">
				<?php
				wp_nav_menu( [
					'theme_location' => 'hm_footer',
					'container'      => false,
					'fallback_cb'    => '__return_false',
				] );
				?>
			</nav>
			<p class="hmpro-copyright">
				&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?>
			</p>
		</div>
	</footer>

<?php endif; ?>

<?php do_action( 'hmpro/footer/after' ); ?>

<?php wp_footer(); ?>


</body>
</html>
