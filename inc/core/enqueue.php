<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_enqueue_scripts', function () {

	wp_enqueue_style(
		'hmpro-style',
		get_stylesheet_uri(),
		[],
		file_exists( get_stylesheet_directory() . '/style.css' )
			? (string) filemtime( get_stylesheet_directory() . '/style.css' )
			: (string) HMPRO_VERSION
	);
} );
