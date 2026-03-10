<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Astra-like "Hide Title" toggle.
 *
 * Stores per-post meta: _hmpro_hide_title (0/1)
 * - Classic editor: eye button near the Title field + a side metabox checkbox
 * - Block editor: sidebar toggle (PluginDocumentSettingPanel)
 * - Frontend: theme template skips the title + fallback CSS class
 */

const HMPRO_HIDE_TITLE_META_KEY = '_hmpro_hide_title';

function hmpro_is_title_hidden( $post_id ) {
	return (bool) get_post_meta( (int) $post_id, HMPRO_HIDE_TITLE_META_KEY, true );
}

add_action( 'init', function () {
	// Register meta for REST so Gutenberg can edit it.
	register_post_meta(
		'',
		HMPRO_HIDE_TITLE_META_KEY,
		[
			'type'              => 'boolean',
			'single'            => true,
			'default'           => false,
			'sanitize_callback' => static function ( $value ) {
				return (bool) $value;
			},
			'auth_callback'     => static function () {
				return current_user_can( 'edit_posts' );
			},
			'show_in_rest'      => true,
		]
	);
} );

// Frontend: add a body class (fallback if any template still outputs titles).
add_filter( 'body_class', function ( $classes ) {
	if ( is_singular() ) {
		$post_id = get_queried_object_id();
		if ( $post_id && hmpro_is_title_hidden( $post_id ) ) {
			$classes[] = 'hmpro-hide-title';
		}
	}
	return $classes;
} );

// Classic editor: metabox checkbox (source of truth for non-Gutenberg screens).
add_action( 'add_meta_boxes', function () {
	foreach ( [ 'page', 'post' ] as $screen ) {
		add_meta_box(
			'hmpro_hide_title_box',
			__( 'Title Display', 'hm-pro-theme' ),
			static function ( $post ) {
				$value = (int) get_post_meta( $post->ID, HMPRO_HIDE_TITLE_META_KEY, true );
				wp_nonce_field( 'hmpro_hide_title_save', 'hmpro_hide_title_nonce' );
				echo '<label style="display:flex;align-items:center;gap:8px;">';
				echo '<input type="checkbox" name="hmpro_hide_title" value="1" ' . checked( 1, $value, false ) . ' />';
				echo esc_html__( 'Hide title on frontend', 'hm-pro-theme' );
				echo '</label>';
			},
			$screen,
			'side',
			'default'
		);
	}
} );

add_action( 'save_post', function ( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! isset( $_POST['hmpro_hide_title_nonce'] ) ) {
		return;
	}
	$nonce = sanitize_text_field( wp_unslash( $_POST['hmpro_hide_title_nonce'] ) );
	if ( ! wp_verify_nonce( $nonce, 'hmpro_hide_title_save' ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$hide = isset( $_POST['hmpro_hide_title'] ) ? 1 : 0;
	update_post_meta( $post_id, HMPRO_HIDE_TITLE_META_KEY, $hide );
} );

// Admin assets (post editor only).
add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) {
		return;
	}

	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || ! in_array( $screen->post_type, [ 'page', 'post' ], true ) ) {
		return;
	}

	wp_enqueue_style(
		'hmpro-title-visibility',
		HMPRO_URL . '/assets/admin-title-visibility.css',
		[ 'hmpro-admin' ],
		HMPRO_VERSION
	);

	// Classic editor: eye icon near title.
	wp_enqueue_script(
		'hmpro-title-visibility',
		HMPRO_URL . '/assets/admin-title-visibility.js',
		[ 'jquery' ],
		HMPRO_VERSION,
		true
	);

	// Block editor: sidebar toggle.
	if ( function_exists( 'use_block_editor_for_post_type' ) && use_block_editor_for_post_type( $screen->post_type ) ) {
		wp_enqueue_script(
			'hmpro-title-visibility-block',
			HMPRO_URL . '/assets/admin-title-visibility-block.js',
			[ 'wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data', 'wp-i18n' ],
			HMPRO_VERSION,
			true
		);
	}
} );
