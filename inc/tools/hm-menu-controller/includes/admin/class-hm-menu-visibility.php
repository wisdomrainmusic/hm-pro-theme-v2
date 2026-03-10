<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class HM_MC_Menu_Visibility {

	public static function apply() : void {

		// Only for restricted users
		if ( ! HM_MC_Admin::current_user_is_restricted() ) {
			return;
		}

		$user_id = get_current_user_id();
		if ( $user_id <= 0 ) {
			return;
		}

		$hidden_slugs = HM_MC_Settings::get_effective_hidden_menu_slugs( (int) $user_id );
		if ( empty( $hidden_slugs ) ) {
			return;
		}

		global $menu, $submenu;

		// Remove top-level menus
		foreach ( $hidden_slugs as $slug ) {
			remove_menu_page( $slug );
		}

		// Remove submenus safely
		if ( is_array( $submenu ) ) {
			foreach ( $submenu as $parent_slug => $items ) {
				if ( ! is_array( $items ) ) {
					continue;
				}

				foreach ( $items as $item ) {
					if ( ! isset( $item[2] ) ) {
						continue;
					}

					$child_slug = (string) $item[2];

					if ( in_array( $child_slug, $hidden_slugs, true ) ) {
						remove_submenu_page( $parent_slug, $child_slug );
					}
				}
			}
		}
	}
}
