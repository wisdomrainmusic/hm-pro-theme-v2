<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class HM_MC_Loader {

    private static $instance = null;
    private $did_init = false;

    public static function instance() : HM_MC_Loader {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {}

    public function init() : void {

        if ( $this->did_init ) {
            return;
        }
        $this->did_init = true;

        if ( is_admin() ) {
            require_once HM_MC_PATH . 'includes/admin/class-hm-admin.php';
            HM_MC_Admin::instance()->init();
        }

    }

    private function __clone() {}
    public function __wakeup() {}
}
