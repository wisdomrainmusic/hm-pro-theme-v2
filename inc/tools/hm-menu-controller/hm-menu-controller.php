<?php
/**
 * HM Menu Controller (Embedded)
 *
 * Embedded into HM Pro Theme as an admin tool.
 * Controls admin sidebar menu visibility per-user (UI-only; no access restriction).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Avoid double-loading.
if ( defined( 'HM_MC_EMBEDDED' ) ) {
	return;
}

// Mark as loaded (idempotent).
if ( ! defined( 'HM_MC_EMBEDDED' ) ) {
	define( 'HM_MC_EMBEDDED', true );
}

// Idempotent constant definitions (prevents "already defined" warnings on some hosts).
if ( ! defined( 'HM_MC_VERSION' ) ) {
	define( 'HM_MC_VERSION', '0.1.0' );
}

// Base path for embedded tool.
if ( ! defined( 'HM_MC_PATH' ) ) {
	define( 'HM_MC_PATH', trailingslashit( __DIR__ ) );
}

if ( ! defined( 'HM_MC_TEXTDOMAIN' ) ) {
	define( 'HM_MC_TEXTDOMAIN', 'hm-menu-controller' );
}

require_once HM_MC_PATH . 'includes/class-hm-loader.php';

// Initialize immediately in wp-admin so our admin_menu hooks are registered
// before WordPress builds the sidebar menus.
if ( is_admin() ) {
    HM_MC_Loader::instance()->init();
}

// Backward-safety: also init on admin_init in case another loader defers theme files.
add_action( 'admin_init', static function () {
    if ( is_admin() ) {
        HM_MC_Loader::instance()->init();
    }
}, 1 );
