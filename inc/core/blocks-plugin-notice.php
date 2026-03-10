<?php
/**
 * HM Pro Blocks Plugin Notice
 *
 * Blocks were removed from the theme and will be provided via plugin.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_notices', function () {
	// Only show for admins who can manage plugins.
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	// Check if the plugin is active (future plugin slug).
	if ( ! function_exists( 'is_plugin_active' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	$plugin_file = 'hm-pro-blocks/hm-pro-blocks.php';
	if ( function_exists( 'is_plugin_active' ) && is_plugin_active( $plugin_file ) ) {
		return;
	}

	echo '<div class="notice notice-warning"><p><strong>HM Pro Blocks</strong> have been removed from the theme and will be delivered as a plugin. Install/activate <code>HM Pro Blocks</code> to restore <code>hmpro/*</code> Gutenberg blocks.</p></div>';
} );
