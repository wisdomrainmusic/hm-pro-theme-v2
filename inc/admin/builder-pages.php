<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin screens: Header/Footer Builder (UI shell only)
 * - Commit 017 will add storage
 * - Commit 019 will add drag/drop + settings
 */

function hmpro_render_header_builder_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to access this page.', 'hmpro' ) );
	}

	hmpro_render_builder_shell( 'header' );
}

function hmpro_render_footer_builder_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to access this page.', 'hmpro' ) );
	}

	hmpro_render_builder_shell( 'footer' );
}

function hmpro_render_mega_menu_builder_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to access this page.', 'hmpro' ) );
	}

	if ( ! function_exists( 'hmpro_render_mega_builder_shell' ) ) {
		require_once HMPRO_PATH . '/inc/admin/mega-menu-builder-page.php';
	}

	hmpro_render_mega_builder_shell();
}

/**
 * Shared builder UI shell (no persistence in this commit).
 */
function hmpro_render_builder_shell( $area ) {
	$area = ( 'footer' === $area ) ? 'footer' : 'header';

	$layout = function_exists( 'hmpro_builder_get_layout' ) ? hmpro_builder_get_layout( $area ) : array();

/**
 * IMPORTANT:
 * The admin builder JS expects an object like:
 * { schema_version: 1, regions: { ... } }
 *
 * If we pass an empty array ("[]"), JSON.parse() yields an Array.
 * JSON.stringify(Array) will ignore custom properties (like "regions"),
 * causing "Save Layout" to silently submit "[]" and lose the layout.
 */
if ( empty( $layout ) || ! is_array( $layout ) || empty( $layout['regions'] ) ) {
	$layout = [
		'schema_version' => 1,
		// Force JSON object: {} (not [])
		'regions'        => new stdClass(),
	];
}

$layout_json = wp_json_encode( $layout );

	// Zones:
	// - Header: 3 zones (left/center/right)
	// - Footer: 4 zones (left/center_left/center_right/right) like Mega Menu Builder
	$zones = ( 'footer' === $area )
		? [ 'left', 'center_left', 'center_right', 'right' ]
		: [ 'left', 'center', 'right' ];

	$title = ( 'footer' === $area )
		? __( 'Footer Builder', 'hmpro' )
		: __( 'Header Builder', 'hmpro' );

	$sections = ( 'footer' === $area )
		? [
			'footer_top'    => __( 'Top', 'hmpro' ),
			'footer_main'   => __( 'Main', 'hmpro' ),
			'footer_bottom' => __( 'Bottom', 'hmpro' ),
		]
		: [
			'header_top'    => __( 'Top', 'hmpro' ),
			'header_main'   => __( 'Main', 'hmpro' ),
			'header_bottom' => __( 'Bottom', 'hmpro' ),
			'header_drawer' => __( 'Mobile Drawer', 'hmpro' ),
		];

	$elements = [
		'logo'   => __( 'Logo', 'hmpro' ),
		'menu'   => __( 'Menu', 'hmpro' ),
		'search' => __( 'Search', 'hmpro' ),
		'social_icon_button' => __( 'Social Icon Button', 'hmpro' ),
		'cart'   => __( 'Cart', 'hmpro' ),
		'button' => __( 'Button', 'hmpro' ),
		'html'   => __( 'HTML', 'hmpro' ),
		'spacer' => __( 'Spacer', 'hmpro' ),
	];
	if ( 'footer' === $area ) {
		$elements['footer_image'] = __( 'Footer Image', 'hmpro' );
		$elements['footer_menu'] = __( 'Footer Column Menu', 'hmpro' );
		$elements['footer_info'] = __( 'Footer Info Text', 'hmpro' );
	}

	?>
	<div class="wrap hmpro-builder-wrap" data-area="<?php echo esc_attr( $area ); ?>">
		<h1><?php echo esc_html( $title ); ?></h1>

		<?php if ( isset( $_GET['saved'] ) && '1' === (string) $_GET['saved'] ) : ?>
			<div class="notice notice-success is-dismissible">
				<p><?php esc_html_e( 'Builder layout saved.', 'hmpro' ); ?></p>
			</div>
		<?php endif; ?>

		<form method="post" action="">
			<?php wp_nonce_field( 'hmpro_builder_' . $area, 'hmpro_builder_nonce' ); ?>
			<input type="hidden" name="hmpro_action" value="hmpro_save_builder" />
			<input type="hidden" name="hmpro_builder_area" value="<?php echo esc_attr( $area ); ?>" />
			<input type="hidden" id="hmproBuilderLayoutField" name="hmpro_builder_layout" value="<?php echo esc_attr( $layout_json ); ?>" />

			<div class="hmpro-builder-layout">

				<aside class="hmpro-builder-panel hmpro-builder-sections">
					<h2><?php esc_html_e( 'Sections', 'hmpro' ); ?></h2>
					<ul class="hmpro-builder-list">
						<?php foreach ( $sections as $key => $label ) : ?>
							<li>
								<button type="button" class="button hmpro-builder-section-btn" data-section="<?php echo esc_attr( $key ); ?>">
									<?php echo esc_html( $label ); ?>
								</button>
							</li>
						<?php endforeach; ?>
					</ul>
					<p class="description">
						<?php esc_html_e( 'Select a section, then add components from the Elements panel. Save Layout writes to wp_options.', 'hmpro' ); ?>
					</p>
				</aside>

				<main class="hmpro-builder-panel hmpro-builder-canvas" aria-label="<?php esc_attr_e( 'Builder Canvas', 'hmpro' ); ?>">
					<div class="hmpro-builder-canvas-inner">
						<div class="hmpro-builder-canvas-head">
							<h2 class="hmpro-builder-canvas-title"><?php esc_html_e( 'Canvas', 'hmpro' ); ?></h2>
							<div class="hmpro-builder-canvas-meta">
								<span class="hmpro-builder-editing">
									<?php esc_html_e( 'Editing:', 'hmpro' ); ?>
									<strong class="hmpro-builder-editing-key"><?php echo esc_html( ( 'footer' === $area ) ? 'footer_top' : 'header_top' ); ?></strong>
								</span>
								<span class="hmpro-zone-hint"><?php esc_html_e( 'Drag components between zones.', 'hmpro' ); ?></span>
							</div>
						</div>

						<?php if ( 'footer' === $area ) : ?>
							<div class="hmpro-zones hmpro-zones-4" id="hmproZones">
								<div class="hmpro-zone" data-zone="left">
									<div class="hmpro-zone-title"><?php esc_html_e( 'Left', 'hmpro' ); ?></div>
									<ul class="hmpro-canvas-list" id="hmproZoneLeft"></ul>
								</div>
								<div class="hmpro-zone" data-zone="center_left">
									<div class="hmpro-zone-title"><?php esc_html_e( 'Center-left', 'hmpro' ); ?></div>
									<ul class="hmpro-canvas-list" id="hmproZoneCenterLeft"></ul>
								</div>
								<div class="hmpro-zone" data-zone="center_right">
									<div class="hmpro-zone-title"><?php esc_html_e( 'Center-right', 'hmpro' ); ?></div>
									<ul class="hmpro-canvas-list" id="hmproZoneCenterRight"></ul>
								</div>
								<div class="hmpro-zone" data-zone="right">
									<div class="hmpro-zone-title"><?php esc_html_e( 'Right', 'hmpro' ); ?></div>
									<ul class="hmpro-canvas-list" id="hmproZoneRight"></ul>
								</div>
							</div>
						<?php else : ?>
							<div class="hmpro-zones" id="hmproZones">
								<div class="hmpro-zone" data-zone="left">
									<div class="hmpro-zone-title"><?php esc_html_e( 'Left', 'hmpro' ); ?></div>
									<ul class="hmpro-canvas-list" id="hmproZoneLeft"></ul>
								</div>
								<div class="hmpro-zone" data-zone="center">
									<div class="hmpro-zone-title"><?php esc_html_e( 'Center', 'hmpro' ); ?></div>
									<ul class="hmpro-canvas-list" id="hmproZoneCenter"></ul>
								</div>
								<div class="hmpro-zone" data-zone="right">
									<div class="hmpro-zone-title"><?php esc_html_e( 'Right', 'hmpro' ); ?></div>
									<ul class="hmpro-canvas-list" id="hmproZoneRight"></ul>
								</div>
							</div>
						<?php endif; ?>
					</div>
				</main>

				<aside class="hmpro-builder-panel hmpro-builder-elements">
					<h2><?php esc_html_e( 'Elements', 'hmpro' ); ?></h2>
					<ul class="hmpro-builder-list">
						<?php foreach ( $elements as $type => $label ) : ?>
							<li>
								<button type="button" class="button hmpro-builder-element-btn" data-type="<?php echo esc_attr( $type ); ?>">
									<?php echo esc_html( $label ); ?>
								</button>
							</li>
						<?php endforeach; ?>
					</ul>
					<p class="description">
						<?php esc_html_e( 'Click to add. Drag & drop between zones. Click a component to edit settings.', 'hmpro' ); ?>
					</p>
				</aside>

			</div>

			<p style="margin-top:12px;">
				<button type="submit" class="button button-primary">
					<?php esc_html_e( 'Save Layout', 'hmpro' ); ?>
				</button>
			</p>
		</form>

		<div class="hmpro-modal" id="hmproCompModal" aria-hidden="true">
			<div class="hmpro-modal__overlay" data-modal-close="1"></div>
			<div class="hmpro-modal__panel" role="dialog" aria-modal="true" aria-labelledby="hmproModalTitle">
				<div class="hmpro-modal__head">
					<strong id="hmproModalTitle"><?php esc_html_e( 'Settings', 'hmpro' ); ?></strong>
					<button type="button" class="button" data-modal-close="1">×</button>
				</div>
				<div class="hmpro-modal__body" id="hmproModalBody"></div>
				<div class="hmpro-modal__foot">
					<button type="button" class="button button-primary" id="hmproModalSave"><?php esc_html_e( 'Save', 'hmpro' ); ?></button>
					<button type="button" class="button" data-modal-close="1" data-modal-cancel="1"><?php esc_html_e( 'Cancel', 'hmpro' ); ?></button>
				</div>
			</div>
		</div>
	</div>
	<?php

	$menu_locations = get_registered_nav_menus();
	if ( ! is_array( $menu_locations ) ) {
		$menu_locations = [];
	}

	$menus_data = [];
	$menus      = wp_get_nav_menus();
	if ( is_array( $menus ) ) {
		foreach ( $menus as $menu ) {
			if ( ! is_object( $menu ) ) {
				continue;
			}
			$menus_data[] = [
				'id'   => (int) $menu->term_id,
				'name' => (string) $menu->name,
			];
		}
	}

	wp_localize_script(
		'hmpro-admin-builder',
			'hmproBuilderData',
			[
				'area'          => $area,
				'zones'         => $zones,
				'layout'        => $layout,
				'menuLocations' => $menu_locations,
				'wp_menus'      => $menus_data,
			'i18n'          => [
				'editing'      => __( 'Editing:', 'hmpro' ),
				'empty'        => __( 'Drop components here.', 'hmpro' ),
				'settings'     => __( 'Settings', 'hmpro' ),
				'save'         => __( 'Save', 'hmpro' ),
				'cancel'       => __( 'Cancel', 'hmpro' ),
				'menuLocation' => __( 'Menu location', 'hmpro' ),
			],
		]
	);
}
