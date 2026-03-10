<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Mega Menu Builder Page (Commit 3B)
 * UI cloned from Header/Footer builder, adapted to:
 * - Left panel: Mega Menu 1..5 (CPT hm_mega_menu)
 * - Canvas: 4 zones (Left / Center-left / Center-right / Right)
 * - Elements: Mega Column Menu, Image, HTML, Button, Spacer
 */

function hmpro_mega_get_or_seed_library_posts() {
	$q = new WP_Query( [
		'post_type'      => 'hm_mega_menu',
		'post_status'    => 'publish',
		'posts_per_page' => 20,
		'orderby'        => 'ID',
		'order'          => 'ASC',
		'no_found_rows'  => true,
	] );

	$posts = $q->posts;

	// Ensure we have at least 5 entries (Mega Menu 1..5).
	if ( count( $posts ) < 5 ) {
		$need = 5 - count( $posts );
		for ( $i = 0; $i < $need; $i++ ) {
			$index = count( $posts ) + 1;
			$id = wp_insert_post( [
				'post_type'   => 'hm_mega_menu',
				'post_status' => 'publish',
				'post_title'  => 'Mega Menu ' . $index,
			] );
			if ( $id && ! is_wp_error( $id ) ) {
				$posts[] = get_post( $id );
			}
		}
	}

	// Normalize titles to Mega Menu N (without forcing edits if user renamed; keep as-is).
	return array_filter( $posts );
}

function hmpro_render_mega_builder_shell() {
	$posts = hmpro_mega_get_or_seed_library_posts();
	$current_id = isset( $_GET['mega_id'] ) ? absint( $_GET['mega_id'] ) : 0;
	if ( $current_id < 1 && ! empty( $posts[0] ) ) {
		$current_id = absint( $posts[0]->ID );
	}

	$layout = function_exists( 'hmpro_mega_menu_get_layout' ) ? hmpro_mega_menu_get_layout( $current_id ) : hmpro_mega_default_layout_schema();
	$layout_json = wp_json_encode( $layout );

	$settings = function_exists( 'hmpro_mega_menu_get_settings' ) ? hmpro_mega_menu_get_settings( $current_id ) : hmpro_mega_default_settings();
	$height_mode = isset( $settings['height_mode'] ) ? sanitize_key( (string) $settings['height_mode'] ) : 'auto';
	$secondary_menu = isset( $settings['secondary_menu'] ) ? absint( $settings['secondary_menu'] ) : 0;

	$elements = [
		'mega_column_menu' => __( 'Mega Column Menu', 'hmpro' ),
		'image'            => __( 'Image', 'hmpro' ),
		'html'             => __( 'HTML', 'hmpro' ),
		'button'           => __( 'Button', 'hmpro' ),
		'spacer'           => __( 'Spacer', 'hmpro' ),
	];

	?>
	<div class="wrap hmpro-builder-wrap" data-area="mega" data-mega-id="<?php echo esc_attr( (string) $current_id ); ?>">
		<h1><?php echo esc_html__( 'Mega Menu Builder', 'hmpro' ); ?></h1>

		<?php if ( isset( $_GET['saved'] ) && '1' === (string) $_GET['saved'] ) : ?>
			<div class="notice notice-success is-dismissible">
				<p><?php esc_html_e( 'Mega menu layout saved.', 'hmpro' ); ?></p>
			</div>
		<?php endif; ?>

		<form method="post" action="">
			<?php wp_nonce_field( 'hmpro_mega_builder_' . $current_id, 'hmpro_mega_builder_nonce' ); ?>
			<input type="hidden" name="hmpro_action" value="hmpro_save_mega_menu" />
			<input type="hidden" name="hmpro_mega_id" value="<?php echo esc_attr( (string) $current_id ); ?>" />
			<input type="hidden" id="hmproBuilderLayoutField" name="hmpro_mega_layout" value="<?php echo esc_attr( $layout_json ); ?>" />

			<div class="hmpro-builder-layout">
				<aside class="hmpro-builder-panel hmpro-builder-sections">
					<h2><?php esc_html_e( 'Sections', 'hmpro' ); ?></h2>
					<ul class="hmpro-builder-list">
						<?php foreach ( $posts as $p ) : ?>
							<?php
							$pid = absint( $p->ID );
							$is_active = ( $pid === $current_id );
							$url = add_query_arg(
								[ 'page' => 'hmpro-mega-menu-builder', 'mega_id' => $pid ],
								admin_url( 'admin.php' )
							);
							?>
							<li>
								<a class="button <?php echo $is_active ? 'button-primary' : ''; ?>" href="<?php echo esc_url( $url ); ?>">
									<?php echo esc_html( get_the_title( $pid ) ); ?>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>

					<div style="margin-top:14px;">
						<label for="hmproMegaHeightMode" style="display:block;font-weight:600;margin-bottom:6px;"><?php esc_html_e( 'Mega Menu height preset', 'hmpro' ); ?></label>
						<select name="hmpro_mega_height_mode" id="hmproMegaHeightMode">
							<option value="auto" <?php selected( $height_mode, 'auto' ); ?>><?php esc_html_e( 'Auto', 'hmpro' ); ?></option>
							<option value="compact" <?php selected( $height_mode, 'compact' ); ?>><?php esc_html_e( 'Compact (360px)', 'hmpro' ); ?></option>
							<option value="showcase" <?php selected( $height_mode, 'showcase' ); ?>><?php esc_html_e( 'Showcase (600px)', 'hmpro' ); ?></option>
						</select>
					</div>

					<div style="margin-top:14px;">
						<label for="hmproMegaSecondaryMenu" style="display:block;font-weight:600;margin-bottom:6px;"><?php esc_html_e( 'Secondary menu (optional)', 'hmpro' ); ?></label>
						<select name="hmpro_mega_secondary_menu" id="hmproMegaSecondaryMenu">
							<option value="0" <?php selected( $secondary_menu, 0 ); ?>><?php esc_html_e( 'None', 'hmpro' ); ?></option>
							<?php foreach ( wp_get_nav_menus() as $m ) : ?>
								<option value="<?php echo esc_attr( (string) $m->term_id ); ?>" <?php selected( $secondary_menu, (int) $m->term_id ); ?>>
									<?php echo esc_html( $m->name ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
				</aside>

				<main class="hmpro-builder-panel hmpro-builder-canvas" aria-label="<?php esc_attr_e( 'Builder Canvas', 'hmpro' ); ?>">
					<div class="hmpro-builder-canvas-inner">
						<div class="hmpro-builder-canvas-head">
							<h2 class="hmpro-builder-canvas-title"><?php esc_html_e( 'Canvas', 'hmpro' ); ?></h2>
							<div class="hmpro-builder-canvas-meta">
								<span class="hmpro-builder-editing">
									<?php esc_html_e( 'Editing:', 'hmpro' ); ?>
									<strong class="hmpro-builder-editing-key"><?php echo esc_html( 'mega_content' ); ?></strong>
								</span>
								<span class="hmpro-zone-hint"><?php esc_html_e( 'Drag components between zones.', 'hmpro' ); ?></span>
							</div>
						</div>

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
				</aside>
			</div>

			<p style="margin-top:12px;">
				<button type="submit" class="button button-primary"><?php esc_html_e( 'Save Layout', 'hmpro' ); ?></button>
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

	// Builder data for JS
	$menus = wp_get_nav_menus();
	$menu_choices = [];
	foreach ( $menus as $m ) {
		$menu_choices[] = [ 'id' => (int) $m->term_id, 'name' => $m->name ];
	}

	wp_localize_script(
		'hmpro-admin-builder',
		'hmproBuilderData',
		[
			'area'      => 'mega',
			'layout'    => $layout,
			'zones'     => [ 'left', 'center_left', 'center_right', 'right' ],
			'i18n'      => [
				'editing'  => __( 'Editing:', 'hmpro' ),
				'empty'    => __( 'Drop components here.', 'hmpro' ),
				'settings' => __( 'Settings', 'hmpro' ),
				'save'     => __( 'Save', 'hmpro' ),
				'cancel'   => __( 'Cancel', 'hmpro' ),
			],
			'megaMenus' => $menu_choices,
			'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
			'nonce'     => wp_create_nonce( 'hmpro_mega_builder' ),
		]
	);
}
