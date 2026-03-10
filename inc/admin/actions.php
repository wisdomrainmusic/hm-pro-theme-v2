<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builder Save Handler (Commit 017)
 */
add_action( 'admin_init', function () {
	if ( ! is_admin() ) {
		return;
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( empty( $_POST['hmpro_action'] ) ) {
		return;
	}

	$action = sanitize_key( wp_unslash( $_POST['hmpro_action'] ) );

	/**
	 * Mega Menu Builder save
	 */
	if ( 'hmpro_save_mega_menu' === $action ) {
		$mega_id = isset( $_POST['hmpro_mega_id'] ) ? absint( $_POST['hmpro_mega_id'] ) : 0;
		if ( $mega_id < 1 ) {
			wp_die( esc_html__( 'Invalid mega menu ID.', 'hmpro' ) );
		}

		$nonce = isset( $_POST['hmpro_mega_builder_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['hmpro_mega_builder_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'hmpro_mega_builder_' . $mega_id ) ) {
			wp_die( esc_html__( 'Security check failed.', 'hmpro' ) );
		}

		$json    = isset( $_POST['hmpro_mega_layout'] ) ? wp_unslash( $_POST['hmpro_mega_layout'] ) : '';
		$decoded = json_decode( (string) $json, true );

		$clean = function_exists( 'hmpro_mega_sanitize_layout' )
			? hmpro_mega_sanitize_layout( is_array( $decoded ) ? $decoded : [] )
			: hmpro_mega_default_layout_schema();

		$did_update = false;
		if ( function_exists( 'hmpro_mega_menu_update_layout' ) ) {
			$did_update = (bool) hmpro_mega_menu_update_layout( $mega_id, $clean );
		}

		$height_mode = isset( $_POST['hmpro_mega_height_mode'] ) ? sanitize_key( wp_unslash( $_POST['hmpro_mega_height_mode'] ) ) : 'auto';
		$secondary_menu = isset( $_POST['hmpro_mega_secondary_menu'] ) ? absint( $_POST['hmpro_mega_secondary_menu'] ) : 0;

		if ( function_exists( 'hmpro_mega_menu_update_settings' ) ) {
			hmpro_mega_menu_update_settings( $mega_id, [
				'height_mode'     => $height_mode,
				'secondary_menu' => $secondary_menu,
			] );
		}

		$redirect_args = [
			'page'    => 'hmpro-mega-menu-builder',
			'mega_id' => $mega_id,
			'saved'   => $did_update ? '1' : '0',
		];

		wp_safe_redirect( add_query_arg( $redirect_args, admin_url( 'admin.php' ) ) );
		exit;
	}

	if ( 'hmpro_save_builder' !== $action ) {
		return;
	}

	$area = isset( $_POST['hmpro_builder_area'] ) ? sanitize_key( wp_unslash( $_POST['hmpro_builder_area'] ) ) : '';
	$area = ( 'footer' === $area ) ? 'footer' : 'header';

	$nonce = isset( $_POST['hmpro_builder_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['hmpro_builder_nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'hmpro_builder_' . $area ) ) {
		wp_die( esc_html__( 'Security check failed.', 'hmpro' ) );
	}

	$json = isset( $_POST['hmpro_builder_layout'] ) ? wp_unslash( $_POST['hmpro_builder_layout'] ) : '';
	$decoded = json_decode( (string) $json, true );

	// Always sanitize + save, even if payload is partial.
	// This prevents "Save" from silently doing nothing for certain components/settings.
	$payload = is_array( $decoded ) ? $decoded : array();
	$clean   = function_exists( 'hmpro_builder_sanitize_layout' )
		? hmpro_builder_sanitize_layout( $area, $payload )
		: hmpro_builder_default_schema( $area );

	$did_update = false;
	if ( function_exists( 'hmpro_builder_update_layout' ) ) {
		$did_update = (bool) hmpro_builder_update_layout( $area, $clean );
	}

	$redirect = ( 'footer' === $area ) ? 'hmpro-footer-builder' : 'hmpro-header-builder';
	$redirect_args = array(
		'page'  => $redirect,
		'saved' => $did_update ? '1' : '0',
	);

	wp_safe_redirect( add_query_arg( $redirect_args, admin_url( 'admin.php' ) ) );
	exit;
} );
add_action( 'admin_init', 'hmpro_handle_admin_actions' );

function hmpro_handle_admin_actions() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// Handle preset save (POST) early to allow redirects before admin output.
	if ( isset( $_GET['page'] ) && 'hmpro-preset-edit' === sanitize_key( wp_unslash( $_GET['page'] ) ) && isset( $_POST['hmpro_save_preset'] ) ) {
		$preset_id = isset( $_GET['preset'] ) ? sanitize_key( wp_unslash( $_GET['preset'] ) ) : '';

		check_admin_referer( 'hmpro_save_preset_' . $preset_id );

		$data = [
			'name'         => isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '',
			'primary'      => isset( $_POST['primary'] ) ? sanitize_text_field( wp_unslash( $_POST['primary'] ) ) : '',
			'dark'         => isset( $_POST['dark'] ) ? sanitize_text_field( wp_unslash( $_POST['dark'] ) ) : '',
			'bg'           => isset( $_POST['bg'] ) ? sanitize_text_field( wp_unslash( $_POST['bg'] ) ) : '',
			'footer'       => isset( $_POST['footer'] ) ? sanitize_text_field( wp_unslash( $_POST['footer'] ) ) : '',
			'link'         => isset( $_POST['link'] ) ? sanitize_text_field( wp_unslash( $_POST['link'] ) ) : '',
			'body_font'    => isset( $_POST['body_font'] ) ? sanitize_text_field( wp_unslash( $_POST['body_font'] ) ) : 'system',
			'heading_font' => isset( $_POST['heading_font'] ) ? sanitize_text_field( wp_unslash( $_POST['heading_font'] ) ) : 'system',
		];

		$ok = hmpro_update_preset( $preset_id, $data );

		$redirect = admin_url( 'admin.php?page=hmpro-preset-edit&preset=' . rawurlencode( $preset_id ) . '&hmpro_saved=' . ( $ok ? '1' : '0' ) );
		wp_safe_redirect( $redirect );
		exit;
	}

	// Handle CSV import (POST) early.
	if (
		isset( $_GET['page'] )
		&& in_array( sanitize_key( wp_unslash( $_GET['page'] ) ), [ 'hmpro-presets', 'hmpro-theme' ], true )
		&& isset( $_POST['hmpro_import_csv'] )
	) {
		check_admin_referer( 'hmpro_import_csv' );

		$mode = isset( $_POST['import_mode'] ) ? sanitize_key( wp_unslash( $_POST['import_mode'] ) ) : 'update';
		$mode = ( 'create' === $mode ) ? 'create' : 'update';

		if ( empty( $_FILES['csv_file']['tmp_name'] ) ) {
			$back = wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=hmpro-presets' );
			wp_safe_redirect( add_query_arg( [ 'hmpro_notice' => 'csv_missing' ], $back ) );
			exit;
		}

		$tmp = (string) $_FILES['csv_file']['tmp_name'];
		$res = hmpro_import_presets_csv( $tmp, $mode );

		$back = wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=hmpro-presets' );
		$back = remove_query_arg( [ 'hmpro_notice' ], $back );
		$back = add_query_arg(
			[
				'hmpro_notice' => 'csv_imported',
				'i'            => (int) $res['imported'],
				'u'            => (int) $res['updated'],
				'c'            => (int) $res['created'],
				's'            => (int) $res['skipped'],
			],
			$back
		);

		wp_safe_redirect( $back );
		exit;
	}

	$action = '';
	if ( ! empty( $_POST['hmpro_action'] ) ) {
		$action = sanitize_key( wp_unslash( $_POST['hmpro_action'] ) );
	} elseif ( ! empty( $_GET['hmpro_action'] ) ) {
		$action = sanitize_key( wp_unslash( $_GET['hmpro_action'] ) );
	}

	if ( '' === $action ) {
		return;
	}

	// Apply a typography combo to the currently active preset.
	if ( 'apply_typography_preset' === $action ) {
		// Nonce is generated via wp_nonce_url() on the Presets page.
		$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
		if ( '' === $nonce || ! wp_verify_nonce( $nonce, 'hmpro_apply_typography' ) ) {
			wp_safe_redirect( add_query_arg( [ 'hmpro_notice' => 'nonce_failed' ], admin_url( 'admin.php?page=hmpro-presets' ) ) );
			exit;
		}

		$key = isset( $_GET['preset_key'] ) ? sanitize_key( wp_unslash( $_GET['preset_key'] ) ) : '';
		$all = function_exists( 'hmpro_typography_presets' ) ? hmpro_typography_presets() : [];
		if ( empty( $key ) || empty( $all[ $key ] ) ) {
			wp_safe_redirect( add_query_arg( [ 'hmpro_notice' => 'typo_invalid' ], admin_url( 'admin.php?page=hmpro-presets' ) ) );
			exit;
		}

		$active_id = hmpro_get_active_preset_id();
		if ( ! hmpro_get_preset_by_id( $active_id ) ) {
			wp_safe_redirect( add_query_arg( [ 'hmpro_notice' => 'preset_not_found' ], admin_url( 'admin.php?page=hmpro-presets' ) ) );
			exit;
		}
		$data      = [
			'body_font'    => hmpro_normalize_font_token( $all[ $key ]['body_font'] ?? 'system' ),
			'heading_font' => hmpro_normalize_font_token( $all[ $key ]['heading_font'] ?? 'system' ),
		];

		$ok = hmpro_update_preset( $active_id, $data );

		// NOTE: We do not write into Elementor Kit settings here.
		// Typography bridging is handled via CSS variables and safe selectors in the theme.

		$back = admin_url( 'admin.php?page=hmpro-presets' );
		wp_safe_redirect( add_query_arg( [ 'hmpro_notice' => ( $ok ? 'typo_applied' : 'typo_failed' ) ], $back ) );
		exit;
	}

	if ( 'update_preset' === $action ) {
		if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'hmpro_update_preset' ) ) {
			$back = wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=hmpro-presets' );
			wp_safe_redirect( add_query_arg( [ 'hmpro_notice' => 'nonce_failed' ], $back ) );
			exit;
		}

		$preset_id = isset( $_POST['preset_id'] ) ? sanitize_key( wp_unslash( $_POST['preset_id'] ) ) : '';
		$fields    = [
			'name',
			'primary',
			'dark',
			'bg',
			'footer',
			'link',
			'body_font',
			'heading_font',
		];
		$data      = [];

		foreach ( $fields as $field ) {
			if ( isset( $_POST[ $field ] ) ) {
				$data[ $field ] = sanitize_text_field( wp_unslash( $_POST[ $field ] ) );
			}
		}

		$ok   = hmpro_update_preset( $preset_id, $data );
		$back = admin_url( 'admin.php?page=hmpro-preset-edit&preset=' . rawurlencode( $preset_id ) );
		wp_safe_redirect( add_query_arg( [ 'hmpro_notice' => ( $ok ? 'updated' : 'update_failed' ) ], $back ) );
		exit;
	}

	if ( 'seed_presets' === $action ) {
		if ( empty( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'hmpro_seed_presets' ) ) {
			$back = wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=hmpro-presets' );
			wp_safe_redirect( add_query_arg( [ 'hmpro_notice' => 'nonce_failed' ], $back ) );
			exit;
		}

		$ok   = hmpro_seed_sample_presets();
		$back = wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=hmpro-presets' );
		$back = remove_query_arg( [ 'hmpro_action', '_wpnonce' ], $back );
		wp_safe_redirect( add_query_arg( [ 'hmpro_notice' => ( $ok ? 'seeded' : 'already_seeded' ) ], $back ) );
		exit;
	}

	if ( 'download_csv_template' === $action ) {
		hmpro_download_csv_template();
	}

	if ( 'delete_preset' === $action ) {
		$preset_id = isset( $_GET['preset'] ) ? sanitize_key( wp_unslash( $_GET['preset'] ) ) : '';

		if ( empty( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'hmpro_delete_preset' ) ) {
			$back = wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=hmpro-presets' );
			wp_safe_redirect( add_query_arg( [ 'hmpro_notice' => 'nonce_failed' ], $back ) );
			exit;
		}

		$active_id = hmpro_get_active_preset_id();
		if ( $active_id === $preset_id ) {
			$back = wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=hmpro-presets' );
			wp_safe_redirect( add_query_arg( [ 'hmpro_notice' => 'preset_delete_active' ], $back ) );
			exit;
		}

		$ok   = hmpro_delete_preset( $preset_id );
		$back = wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=hmpro-presets' );
		$back = remove_query_arg( [ 'hmpro_action', 'preset', '_wpnonce' ], $back );
		wp_safe_redirect( add_query_arg( [ 'hmpro_notice' => ( $ok ? 'preset_deleted' : 'preset_delete_failed' ) ], $back ) );
		exit;
	}

	if ( 'set_active' !== $action ) {
		return;
	}

	$preset_id = isset( $_GET['preset'] ) ? sanitize_key( wp_unslash( $_GET['preset'] ) ) : '';

	if ( empty( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'hmpro_set_active_preset' ) ) {
		$back = wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=hmpro-theme' );
		wp_safe_redirect( add_query_arg( [ 'hmpro_notice' => 'nonce_failed' ], $back ) );
		exit;
	}

	$ok = hmpro_set_active_preset_id( $preset_id );

	// Sync Header Builder Top Bar / Footer color defaults from preset
	// only if the user hasn't overridden them in Customizer.
	if ( $ok && function_exists( 'hmpro_sync_header_footer_color_mods_from_preset_if_empty' ) ) {
		hmpro_sync_header_footer_color_mods_from_preset_if_empty( $preset_id );
	}

	$back = wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=hmpro-theme' );
	$back = remove_query_arg( [ 'hmpro_action', 'preset', '_wpnonce' ], $back );

	wp_safe_redirect( add_query_arg( [ 'hmpro_notice' => ( $ok ? 'preset_activated' : 'preset_not_found' ) ], $back ) );
	exit;
}
