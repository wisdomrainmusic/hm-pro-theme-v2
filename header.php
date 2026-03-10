<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'hmpro_render_legacy_header' ) ) {
	function hmpro_render_legacy_header() {
		$hmpro_hb_enabled = function_exists( 'hmpro_header_bg_banner_is_enabled' ) && hmpro_header_bg_banner_is_enabled();
		$hmpro_hb_hide_m  = $hmpro_hb_enabled && (int) get_theme_mod( 'hmpro_hb_hide_mobile', 0 ) === 1;

		$hmpro_header_classes = 'site-header';
		if ( $hmpro_hb_enabled ) {
			$hmpro_header_classes .= ' hmpro-hb-enabled';
		}
		if ( $hmpro_hb_hide_m ) {
			$hmpro_header_classes .= ' hmpro-hb-hide-mobile';
		}

		$hmpro_hb_header_style = '';
		if ( $hmpro_hb_enabled ) {
			$gap = absint( get_theme_mod( 'hmpro_hb_after_gap', 0 ) );
			if ( $gap > 1600 ) {
				$gap = 1600;
			}
			$hmpro_hb_header_style = ' style="--hmpro-hb-after-gap:' . esc_attr( (string) $gap ) . 'px;"';
		}
		?>
		<header class="<?php echo esc_attr( $hmpro_header_classes ); ?>"<?php echo $hmpro_hb_header_style; ?>>
			<?php
			// Header Background Banner must also work in legacy header mode.
			if ( $hmpro_hb_enabled && function_exists( 'hmpro_render_header_bg_banner' ) ) {
				hmpro_render_header_bg_banner();
			}
			?>
			<div class="hmpro-container">
				<a class="site-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>">
					<?php bloginfo( 'name' ); ?>
				</a>

				<nav class="site-nav" aria-label="<?php esc_attr_e( 'Primary Menu', 'hmpro' ); ?>">
					<?php
					wp_nav_menu( [
						'theme_location' => 'hm_primary',
						'container'      => false,
						'fallback_cb'    => '__return_false',
					] );
					?>
				</nav>
			</div>
		</header>
		<?php
	}
}

do_action( 'hmpro/header/before' );
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<?php
$hmpro_account_url = home_url( '/hesabim/' );

$hmpro_is_woo_context = false;
if ( class_exists( 'WooCommerce' ) ) {
	$hmpro_is_woo_context =
		( function_exists( 'is_woocommerce' ) && is_woocommerce() ) ||
		( function_exists( 'is_cart' ) && is_cart() ) ||
		( function_exists( 'is_checkout' ) && is_checkout() ) ||
		( function_exists( 'is_account_page' ) && is_account_page() );
}

/*
 * Determine login state (Ultimate Member / WordPress)
 */
$hmpro_is_logged_in = is_user_logged_in();

/*
 * Mobile account menu links
 */
if ( $hmpro_is_logged_in ) {
	$hmpro_mobile_account_links = [
		[ 'label' => __( 'Account', 'hm-pro-theme' ), 'url' => home_url( '/account' ) ],
		[ 'label' => __( 'Logout', 'hm-pro-theme' ), 'url' => home_url( '/logout' ) ],
	];
} else {
	$hmpro_mobile_account_links = [
		[ 'label' => __( 'Login', 'hm-pro-theme' ), 'url' => home_url( '/login' ) ],
		[ 'label' => __( 'Register', 'hm-pro-theme' ), 'url' => home_url( '/register' ) ],
	];
}
?>

<?php
// Inline SVG icons (consistent across fonts/browsers)
function hmpro_icon_hamburger() {
	return '<svg class="hmpro-icon hmpro-icon-burger" width="22" height="22" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
		<path d="M4 7h16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
		<path d="M4 12h16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
		<path d="M4 17h16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
	</svg>';
}
function hmpro_icon_close() {
	return '<svg class="hmpro-icon hmpro-icon-close" width="20" height="20" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
		<path d="M6 6l12 12" fill="none" stroke="currentColor" stroke-width="2.8" stroke-linecap="round"/>
		<path d="M18 6L6 18" fill="none" stroke="currentColor" stroke-width="2.8" stroke-linecap="round"/>
	</svg>';
}
?>

<?php if ( function_exists( 'hmpro_header_builder_has_layout' ) && hmpro_header_builder_has_layout() ) : ?>

	<?php do_action( 'hmpro/header/builder/before' ); ?>

	<?php
	$hmpro_hb_enabled = function_exists( 'hmpro_header_bg_banner_is_enabled' ) && hmpro_header_bg_banner_is_enabled();
	$hmpro_hb_hide_m = $hmpro_hb_enabled && (int) get_theme_mod( 'hmpro_hb_hide_mobile', 0 ) === 1;
	$hmpro_hb_classes = 'hmpro-header-builder';
	if ( $hmpro_hb_enabled ) {
		$hmpro_hb_classes .= ' hmpro-hb-enabled';
	}
	if ( $hmpro_hb_hide_m ) {
		$hmpro_hb_classes .= ' hmpro-hb-hide-mobile';
	}
	?>
	<?php
	$hmpro_hb_header_style = '';
	if ( $hmpro_hb_enabled ) {
		$gap = absint( get_theme_mod( 'hmpro_hb_after_gap', 0 ) );
		if ( $gap > 1600 ) {
			$gap = 1600;
		}
		$hmpro_hb_header_style = ' style="--hmpro-hb-after-gap:' . esc_attr( (string) $gap ) . 'px;"';
	}
	?>
	<header id="site-header" class="<?php echo esc_attr( $hmpro_hb_classes ); ?>"<?php echo $hmpro_hb_header_style; ?>>
		<?php
		// Header Background Banner (Top + Main) lives inside the header wrapper.
		if ( $hmpro_hb_enabled && function_exists( 'hmpro_render_header_bg_banner' ) ) {
			hmpro_render_header_bg_banner();
		}
		?>
		<?php hmpro_render_builder_region( 'header_top', 'header' ); ?>
		<?php hmpro_render_builder_region( 'header_main', 'header' ); ?>
		<?php hmpro_render_builder_region( 'header_bottom', 'header' ); ?>

		<?php
		/**
		 * NOTE:
		 * The account CTA is intentionally NOT injected as a fixed desktop element.
		 * Use Header Builder components (HTML/Button) to place the CTA inside header_top/header_main.
		 * Mobile CTA remains inside the drawer for a consistent UX.
		 */
		?>

		<!-- Mobile hamburger toggle -->
		<button
			type="button"
			class="hmpro-mobile-menu-toggle"
			aria-label="<?php echo esc_attr__( 'Menüyü Aç', 'hm-pro-theme' ); ?>"
			aria-controls="hmpro-mobile-drawer"
			aria-expanded="false"
		>
			<?php echo hmpro_icon_hamburger(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</button>
	</header>

	<!-- Mobile drawer (right side) -->
	<div id="hmpro-mobile-drawer" class="hmpro-mobile-drawer" aria-hidden="true">
		<div class="hmpro-mobile-drawer-overlay" data-hmpro-close="1"></div>
		<div class="hmpro-mobile-drawer-panel" role="dialog" aria-modal="true" aria-label="<?php echo esc_attr__( 'Mobil Menü', 'hm-pro-theme' ); ?>">
			<div class="hmpro-mobile-drawer-head<?php echo $hmpro_is_woo_context ? '' : ' hmpro-mobile-drawer-head--account-menu'; ?>">
				<?php if ( $hmpro_is_woo_context ) : ?>
					<a class="hmpro-mobile-account-cta" href="<?php echo esc_url( $hmpro_account_url ); ?>">
						<?php echo esc_html__( 'Giriş Yap / Kayıt Ol', 'hm-pro-theme' ); ?>
					</a>
				<?php else : ?>
					<div class="hmpro-mobile-account-menu" aria-label="<?php echo esc_attr__( 'Hesap Menüsü', 'hm-pro-theme' ); ?>">
						<?php foreach ( $hmpro_mobile_account_links as $hmpro_link ) : ?>
							<a class="hmpro-mobile-account-menu__item" href="<?php echo esc_url( $hmpro_link['url'] ); ?>">
								<span class="hmpro-mobile-account-menu__label"><?php echo esc_html( $hmpro_link['label'] ); ?></span>
								<span class="hmpro-mobile-account-menu__arrow" aria-hidden="true"></span>
							</a>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
				<button type="button" class="hmpro-mobile-drawer-close" data-hmpro-close="1" aria-label="<?php echo esc_attr__( 'Menüyü Kapat', 'hm-pro-theme' ); ?>">
					<?php echo hmpro_icon_close(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</button>
			</div>
			<?php
			// Header Builder "Mobile Drawer" region (place HTML/Shortcode here, e.g. HM translate inline).
			?>
			<div class="hmpro-mobile-drawer-builder">
				<?php hmpro_render_builder_region( 'header_drawer', 'header' ); ?>
			</div>
			<nav class="hmpro-mobile-nav" aria-label="<?php echo esc_attr__( 'Mobil Menü', 'hm-pro-theme' ); ?>">
				<?php
				$hmpro_rendered_menu_locations = [];

				/*
				 * 1) Topbar menu
				 * Show it first if assigned.
				 */
				$hmpro_topbar_location = '';
				$hmpro_topbar_candidates = [
					'topbar',
				];

				foreach ( $hmpro_topbar_candidates as $hmpro_menu_location ) {
					if ( has_nav_menu( $hmpro_menu_location ) ) {
						$hmpro_topbar_location = $hmpro_menu_location;
						break;
					}
				}

				if ( $hmpro_topbar_location ) {
					wp_nav_menu( [
						'theme_location' => $hmpro_topbar_location,
						'container'      => false,
						'menu_class'     => 'hmpro-mobile-menu',
						'depth'          => 3,
					] );
					$hmpro_rendered_menu_locations[] = $hmpro_topbar_location;
				}

				/*
				 * 2) Main/mobile menu
				 * Prefer dedicated mobile menu, then primary, then legacy primary.
				 */
				$hmpro_main_location = '';
				$hmpro_main_candidates = [
					'mobile_menu',
					'primary',
					'hm_primary',
				];

				foreach ( $hmpro_main_candidates as $hmpro_menu_location ) {
					if ( has_nav_menu( $hmpro_menu_location ) ) {
						$hmpro_main_location = $hmpro_menu_location;
						break;
					}
				}

				if ( $hmpro_main_location && ! in_array( $hmpro_main_location, $hmpro_rendered_menu_locations, true ) ) {
					wp_nav_menu( [
						'theme_location' => $hmpro_main_location,
						'container'      => false,
						'menu_class'     => 'hmpro-mobile-menu',
						'depth'          => 3,
					] );
					$hmpro_rendered_menu_locations[] = $hmpro_main_location;
				}

				/*
				 * 3) Final fallback
				 * If no assigned menu location exists, show pages so drawer never stays empty.
				 */
				if ( empty( $hmpro_rendered_menu_locations ) ) {
					wp_page_menu( [
						'menu_class' => 'hmpro-mobile-menu',
						'show_home'  => true,
						'depth'      => 2,
						'echo'       => true,
					] );
				}

				/*
				 * 4) Extra rescue fallback
				 * If some installs have menus created but not assigned to a location,
				 * render the first menu term as a last resort.
				 */
				if ( empty( $hmpro_rendered_menu_locations ) ) {
					$hmpro_fallback_menus = wp_get_nav_menus();
					if ( ! empty( $hmpro_fallback_menus ) && ! is_wp_error( $hmpro_fallback_menus ) ) {
						wp_nav_menu( [
							'menu'       => $hmpro_fallback_menus[0]->term_id,
							'container'  => false,
							'menu_class' => 'hmpro-mobile-menu',
							'depth'      => 3,
						] );
					}
				}
				?>
			</nav>
		</div>
	</div>

	<?php do_action( 'hmpro/header/builder/after' ); ?>

<?php endif; ?>

<?php
if ( ! function_exists( 'hmpro_header_builder_has_layout' ) || ! hmpro_header_builder_has_layout() ) {
	hmpro_render_legacy_header();
}
?>

<?php do_action( 'hmpro/header/after' ); ?>
