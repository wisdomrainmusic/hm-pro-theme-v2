<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class HM_MC_Menu_Snapshot {

    /**
     * Returns normalized admin menu tree.
     *
     * Output structure:
     * [
     *   [
     *     'parent_slug' => 'edit.php',
     *     'label'       => 'Posts',
     *     'children'    => [
     *        ['slug' => 'edit.php', 'label' => 'All Posts', 'parent_slug' => 'edit.php'],
     *        ...
     *     ],
     *   ],
     *   ...
     * ]
     */
    public static function get_tree() : array {
        global $menu, $submenu;

        if ( ! is_array( $menu ) ) {
            $menu = array();
        }
        if ( ! is_array( $submenu ) ) {
            $submenu = array();
        }

        $tree = array();

        foreach ( $menu as $item ) {
            if ( ! is_array( $item ) || ! isset( $item[2] ) ) {
                continue;
            }

            $parent_slug = (string) $item[2];
            $label       = self::normalize_label( $item[0] ?? '' );

            // Skip separators (separator1, separator2, separator-last)
            if ( 0 === strpos( $parent_slug, 'separator' ) ) {
                continue;
            }

            $node = array(
                'parent_slug' => $parent_slug,
                'label'       => $label,
                'children'    => array(),
            );

            $seen = array();

            // Parent as a child row (useful for UI: checkbox on parent itself)
            $node['children'][] = array(
                'slug'        => $parent_slug,
                'label'       => self::label_fallback_for_parent_child( $label, $parent_slug ),
                'parent_slug' => $parent_slug,
                'is_parent'   => true,
            );

            $seen[ $parent_slug ] = true;

            // Submenus
            if ( isset( $submenu[ $parent_slug ] ) && is_array( $submenu[ $parent_slug ] ) ) {
                foreach ( $submenu[ $parent_slug ] as $sub_item ) {
                    if ( ! is_array( $sub_item ) || ! isset( $sub_item[2] ) ) {
                        continue;
                    }

                    $child_slug  = (string) $sub_item[2];
                    $child_label = self::normalize_label( $sub_item[0] ?? '' );

                    if ( isset( $seen[ $child_slug ] ) ) {
                        continue;
                    }
                    $seen[ $child_slug ] = true;

                    $node['children'][] = array(
                        'slug'        => $child_slug,
                        'label'       => $child_label,
                        'parent_slug' => $parent_slug,
                        'is_parent'   => false,
                    );
                }
            }

            $tree[] = $node;
        }

        // Ensure stable ordering by label (optional but helps UI)
        usort(
            $tree,
            static function ( $a, $b ) {
                return strcmp( (string) ( $a['label'] ?? '' ), (string) ( $b['label'] ?? '' ) );
            }
        );

        return $tree;
    }

    private static function normalize_label( $raw ) : string {
        $raw = (string) $raw;

        // WP menus sometimes contain <span class="update-plugins"> etc.
        $raw = wp_strip_all_tags( $raw );

        // WP sometimes has entities or weird whitespace
        $raw = html_entity_decode( $raw, ENT_QUOTES, get_bloginfo( 'charset' ) );
        $raw = trim( preg_replace( '/\s+/', ' ', $raw ) );

        return $raw;
    }

    private static function label_fallback_for_parent_child( string $parent_label, string $parent_slug ) : string {
        if ( '' !== $parent_label ) {
            return $parent_label;
        }

        // Fallback if label empty for some reason.
        return $parent_slug;
    }

    public static function get_all_slugs_flat() : array {
        $tree  = self::get_tree();
        $slugs = array();

        foreach ( $tree as $node ) {
            if ( empty( $node['children'] ) || ! is_array( $node['children'] ) ) {
                continue;
            }
            foreach ( $node['children'] as $child ) {
                $slug = isset( $child['slug'] ) ? (string) $child['slug'] : '';
                $slug = trim( $slug );
                if ( '' !== $slug ) {
                    $slugs[] = $slug;
                }
            }
        }

        $slugs = array_values( array_unique( $slugs ) );
        sort( $slugs );
        return $slugs;
    }
}
