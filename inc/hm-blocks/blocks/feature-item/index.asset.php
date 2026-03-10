<?php
/**
 * Asset file for HM Feature Item editor script.
 */
return array(
	'dependencies' => array(
		'wp-blocks',
		'wp-element',
		'wp-i18n',
		'wp-components',
		'wp-block-editor',
		'wp-editor',
	),
	// Auto bust cache when index.js changes.
	'version' => file_exists( __DIR__ . '/index.js' ) ? filemtime( __DIR__ . '/index.js' ) : '0.1.0',
);
