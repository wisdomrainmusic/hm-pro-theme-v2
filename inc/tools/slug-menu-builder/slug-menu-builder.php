<?php
/**
 * Plugin Name: HM Product Category → Menu Builder
 * Description: Builds a WP nav menu from WooCommerce product categories (product_cat) and links them to their category archive URLs.
 * Version: 1.0.0
 * Author: HM
 */

if (!defined('ABSPATH')) exit;

// Safety: avoid re-declaring the class, BUT do not return from file
// because helper functions / callbacks below must remain available.
if ( ! class_exists( 'HM_Product_Cat_Menu_Builder', false ) ) :
final class HM_Product_Cat_Menu_Builder {
    const NONCE_ACTION = 'hm_pcm_build_menu';
    const OPTION_LAST_LOG = 'hm_pcm_last_log';

    public function __construct( $register_menu = true ) {
        if ( $register_menu ) {
            add_action('admin_menu', [$this, 'admin_menu']);
        }
        add_action('admin_post_hm_pcm_build', [$this, 'handle_build']);
    }

    public function admin_menu() {
        $parent = taxonomy_exists('product_cat') ? 'edit.php?post_type=product' : 'tools.php';

        add_submenu_page(
            $parent,
            'Category → Menu Builder',
            'Category → Menu Builder',
            'manage_options',
            'hm-product-cat-menu-builder',
            [$this, 'render_page']
        );
    }

    public function render_page() {
        if (!current_user_can('manage_options')) {
            wp_die('You do not have permission to access this page.');
        }

        $has_wc = taxonomy_exists('product_cat');
        $log = get_option(self::OPTION_LAST_LOG, '');

        $menus = wp_get_nav_menus();
        $locations = get_registered_nav_menus(); // location_key => label

        ?>
        <div class="wrap">
            <h1>HM Product Category → Menu Builder</h1>

            <?php if (!$has_wc): ?>
                <div class="notice notice-error"><p><strong>WooCommerce product categories (product_cat) not found.</strong> Install/activate WooCommerce first.</p></div>
            <?php endif; ?>

            <p>This tool creates/updates a WP menu from <code>product_cat</code> hierarchy and links items to their category URLs automatically.</p>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="hm_pcm_build">
                <?php wp_nonce_field(self::NONCE_ACTION, '_wpnonce'); ?>

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">Target Menu</th>
                        <td>
                            <label style="display:block;margin-bottom:6px;">
                                <input type="radio" name="target_mode" value="existing" checked>
                                Use existing menu:
                                <select name="existing_menu_id">
                                    <?php foreach ($menus as $m): ?>
                                        <option value="<?php echo (int)$m->term_id; ?>"><?php echo esc_html($m->name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>

                            <label style="display:block;">
                                <input type="radio" name="target_mode" value="new">
                                Create new menu with name:
                                <input type="text" name="new_menu_name" value="Shop Categories" style="min-width:260px;">
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">Assign Theme Location (optional)</th>
                        <td>
                            <select name="theme_location_key">
                                <option value="">— Do not assign —</option>
                                <?php foreach ($locations as $key => $label): ?>
                                    <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label . " ({$key})"); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">If selected, the menu will be assigned to this theme location key.</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">Build Options</th>
                        <td>
                            <label style="display:block;margin-bottom:6px;">
                                <input type="checkbox" name="clear_menu" value="1">
                                Clear target menu items first (rebuild from scratch)
                            </label>

                            <label style="display:block;margin-bottom:6px;">
                                <input type="checkbox" name="include_empty" value="1">
                                Include empty categories (hide_empty = false)
                            </label>

                            <label style="display:block;margin-bottom:6px;">
                                Max depth:
                                <select name="max_depth">
                                    <?php for ($i=1; $i<=6; $i++): ?>
                                        <option value="<?php echo $i; ?>" <?php selected($i, 4); ?>><?php echo $i; ?></option>
                                    <?php endfor; ?>
                                    <option value="99">Unlimited</option>
                                </select>
                            </label>

                            <label style="display:block;margin-bottom:6px;">
                                Sort by:
                                <select name="sort_by">
                                    <option value="name">Name (A–Z)</option>
                                    <option value="menu_order">Menu order (term_order)</option>
                                    <option value="id">ID</option>
                                </select>
                            </label>

                            <label style="display:block;margin-bottom:6px;">
                                <input type="checkbox" name="skip_existing_by_url" value="1" checked>
                                Skip creating an item if an item with same URL already exists in target menu
                            </label>

                            <label style="display:block;margin-bottom:6px;">
                                Exclude term IDs (comma-separated):
                                <input type="text" name="exclude_ids" value="" placeholder="12,45,78" style="min-width:260px;">
                            </label>

                            <label style="display:block;margin-bottom:6px;">
                                Dry run (no changes):
                                <input type="checkbox" name="dry_run" value="1">
                            </label>
                        </td>
                    </tr>
                </table>

                <?php submit_button('Build Menu From Product Categories', 'primary'); ?>
            </form>

            <?php if (!empty($log)) : ?>
                <hr>
                <h2>Last Build Log</h2>
                <pre style="background:#fff;border:1px solid #ccd0d4;padding:12px;max-height:520px;overflow:auto;"><?php echo esc_html($log); ?></pre>
            <?php endif; ?>
        </div>
        <?php
    }

    public function handle_build() {
        if (!current_user_can('manage_options')) {
            wp_die('You do not have permission to perform this action.');
        }
        check_admin_referer(self::NONCE_ACTION);

        if (!taxonomy_exists('product_cat')) {
            wp_die('WooCommerce product categories (product_cat) not found.');
        }

        $target_mode = isset($_POST['target_mode']) ? sanitize_text_field($_POST['target_mode']) : 'existing';
        $existing_menu_id = isset($_POST['existing_menu_id']) ? (int)$_POST['existing_menu_id'] : 0;
        $new_menu_name = isset($_POST['new_menu_name']) ? sanitize_text_field($_POST['new_menu_name']) : 'Shop Categories';
        $theme_location_key = isset($_POST['theme_location_key']) ? sanitize_text_field($_POST['theme_location_key']) : '';

        $clear_menu = !empty($_POST['clear_menu']);
        $include_empty = !empty($_POST['include_empty']);
        $max_depth = isset($_POST['max_depth']) ? (int)$_POST['max_depth'] : 4;
        if ($max_depth <= 0) $max_depth = 4;
        $sort_by = isset($_POST['sort_by']) ? sanitize_text_field($_POST['sort_by']) : 'name';
        $skip_existing_by_url = !empty($_POST['skip_existing_by_url']);
        $dry_run = !empty($_POST['dry_run']);

        $exclude_ids_raw = isset($_POST['exclude_ids']) ? sanitize_text_field($_POST['exclude_ids']) : '';
        $exclude_ids = $this->parse_ids($exclude_ids_raw);

        $log = [];
        $log[] = '=== HM Product Category → Menu Builder ===';
        $log[] = 'Target mode: ' . $target_mode;
        $log[] = 'Theme location key: ' . ($theme_location_key !== '' ? $theme_location_key : '(none)');
        $log[] = 'Clear menu first: ' . ($clear_menu ? 'YES' : 'NO');
        $log[] = 'Include empty: ' . ($include_empty ? 'YES' : 'NO');
        $log[] = 'Max depth: ' . ($max_depth === 99 ? 'Unlimited' : (string)$max_depth);
        $log[] = 'Sort by: ' . $sort_by;
        $log[] = 'Skip existing by URL: ' . ($skip_existing_by_url ? 'YES' : 'NO');
        $log[] = 'Exclude IDs: ' . (!empty($exclude_ids) ? implode(',', $exclude_ids) : '(none)');
        $log[] = 'Dry run: ' . ($dry_run ? 'YES' : 'NO');
        $log[] = '----------------------------------------';

        // Get or create menu
        $menu_id = 0;
        if ($target_mode === 'new') {
            if ($new_menu_name === '') $new_menu_name = 'Shop Categories';
            $maybe = wp_get_nav_menu_object($new_menu_name);
            if ($maybe && !is_wp_error($maybe)) {
                $menu_id = (int)$maybe->term_id;
                $log[] = "Found existing menu with same name: {$new_menu_name} (ID {$menu_id})";
            } else {
                if ($dry_run) {
                    $menu_id = -1;
                    $log[] = "DRY RUN: Would create new menu: {$new_menu_name}";
                } else {
                    $menu_id = (int)wp_create_nav_menu($new_menu_name);
                    $log[] = "Created new menu: {$new_menu_name} (ID {$menu_id})";
                }
            }
        } else {
            if ($existing_menu_id <= 0) {
                wp_die('Please select an existing menu.');
            }
            $menu_id = $existing_menu_id;
            $menu_obj = wp_get_nav_menu_object($menu_id);
            $log[] = "Using existing menu: " . ($menu_obj ? $menu_obj->name : '(unknown)') . " (ID {$menu_id})";
        }

        if ($menu_id === 0) {
            wp_die('Could not determine a target menu.');
        }

        // Assign location (optional)
        if ($theme_location_key !== '' && $menu_id > 0) {
            if ($dry_run) {
                $log[] = "DRY RUN: Would assign menu ID {$menu_id} to theme location {$theme_location_key}";
            } else {
                $locations = get_theme_mod('nav_menu_locations');
                if (!is_array($locations)) $locations = [];
                $locations[$theme_location_key] = $menu_id;
                set_theme_mod('nav_menu_locations', $locations);
                $log[] = "Assigned menu ID {$menu_id} to theme location {$theme_location_key}";
            }
        }

        // Build map of existing menu URLs to avoid duplicates
        $existing_url_map = [];
        if ($menu_id > 0) {
            $items = wp_get_nav_menu_items($menu_id);
            if (is_array($items)) {
                foreach ($items as $it) {
                    if (!empty($it->url)) {
                        $existing_url_map[$this->normalize_url($it->url)] = (int)$it->ID;
                    }
                }
            }
        }

        // Clear menu items if requested
        if ($clear_menu && $menu_id > 0) {
            if ($dry_run) {
                $log[] = "DRY RUN: Would delete all items from menu ID {$menu_id}";
            } else {
                $items = wp_get_nav_menu_items($menu_id);
                if (is_array($items)) {
                    foreach ($items as $it) {
                        wp_delete_post((int)$it->ID, true);
                    }
                }
                $existing_url_map = [];
                $log[] = "Cleared all menu items from menu ID {$menu_id}";
            }
        }

        // Fetch categories
        $args = [
            'taxonomy' => 'product_cat',
            'hide_empty' => $include_empty ? false : true,
            'orderby' => ($sort_by === 'id') ? 'id' : (($sort_by === 'menu_order') ? 'term_order' : 'name'),
            'order' => 'ASC',
        ];

        if (!empty($exclude_ids)) {
            $args['exclude'] = $exclude_ids;
        }

        $terms = get_terms($args);
        if (is_wp_error($terms)) {
            $log[] = 'ERROR: get_terms failed: ' . $terms->get_error_message();
            update_option(self::OPTION_LAST_LOG, implode("\n", $log));
            wp_safe_redirect($this->admin_page_url());
            exit;
        }

        // Build children index
        $children = [];
        $by_id = [];
        foreach ($terms as $t) {
            $by_id[(int)$t->term_id] = $t;
            $p = (int)$t->parent;
            if (!isset($children[$p])) $children[$p] = [];
            $children[$p][] = (int)$t->term_id;
        }

        // Sorting children lists consistently if needed (get_terms already sorted globally, but parent grouping can keep order)
        foreach ($children as $p => $ids) {
            $children[$p] = $ids; // already in global sort order
        }

        $created = 0;
        $skipped = 0;
        $errors = 0;

        // Map term_id -> menu_item_id
        $term_to_item = [];

        // Depth-first build from root (parent=0)
        $stack = $children[0] ?? [];
        $log[] = "Root categories: " . count($stack);

        foreach ($stack as $term_id) {
            $this->build_branch(
                $term_id,
                1,
                0,
                $max_depth,
                $menu_id,
                $children,
                $by_id,
                $term_to_item,
                $existing_url_map,
                $skip_existing_by_url,
                $dry_run,
                $created,
                $skipped,
                $errors,
                $log
            );
        }

        $log[] = '----------------------------------------';
        $log[] = "Done. Created: {$created} | Skipped: {$skipped} | Errors: {$errors}";
        $log[] = '=== END ===';

        update_option(self::OPTION_LAST_LOG, implode("\n", $log));

        wp_safe_redirect($this->admin_page_url());
        exit;
    }

    private function build_branch(
        int $term_id,
        int $depth,
        int $parent_menu_item_id,
        int $max_depth,
        int $menu_id,
        array $children,
        array $by_id,
        array &$term_to_item,
        array &$existing_url_map,
        bool $skip_existing_by_url,
        bool $dry_run,
        int &$created,
        int &$skipped,
        int &$errors,
        array &$log
    ) {
        if (!isset($by_id[$term_id])) return;

        if ($max_depth !== 99 && $depth > $max_depth) {
            return;
        }

        $term = $by_id[$term_id];
        $name = $term->name;

        $url = get_term_link($term, 'product_cat');
        if (is_wp_error($url)) {
            $errors++;
            $log[] = $this->indent($depth) . "ERROR: Term {$term_id} link failed: " . $url->get_error_message();
            return;
        }

        $norm = $this->normalize_url($url);

        // If URL already exists in menu, reuse it as parent anchor
        if ($skip_existing_by_url && isset($existing_url_map[$norm])) {
            $existing_item_id = (int)$existing_url_map[$norm];
            $term_to_item[$term_id] = $existing_item_id;
            $skipped++;
            $log[] = $this->indent($depth) . "SKIP (exists): {$name} → {$url}";
            $menu_item_id = $existing_item_id;
        } else {
            if ($dry_run || $menu_id <= 0) {
                $fake_id = -abs(crc32($menu_id . '|' . $term_id . '|' . $parent_menu_item_id));
                $term_to_item[$term_id] = $fake_id;
                $created++;
                $log[] = $this->indent($depth) . "DRY CREATE: {$name} → {$url} (parent item {$parent_menu_item_id})";
                $menu_item_id = $fake_id;
            } else {
                $item_data = [
                    'menu-item-title'  => $name,
                    'menu-item-url'    => $url,
                    'menu-item-status' => 'publish',
                ];
                if ($parent_menu_item_id > 0) {
                    $item_data['menu-item-parent-id'] = $parent_menu_item_id;
                }

                $new_item_id = wp_update_nav_menu_item($menu_id, 0, $item_data);

                if (is_wp_error($new_item_id) || !$new_item_id) {
                    $errors++;
                    $msg = is_wp_error($new_item_id) ? $new_item_id->get_error_message() : 'Unknown error';
                    $log[] = $this->indent($depth) . "ERROR: Could not create menu item for {$name}: {$msg}";
                    return;
                }

                $new_item_id = (int)$new_item_id;
                $term_to_item[$term_id] = $new_item_id;
                $existing_url_map[$norm] = $new_item_id;
                $created++;
                $log[] = $this->indent($depth) . "CREATE: {$name} → {$url} (item {$new_item_id})";
                $menu_item_id = $new_item_id;
            }
        }

        // Children
        if (!empty($children[$term_id])) {
            foreach ($children[$term_id] as $child_id) {
                $this->build_branch(
                    (int)$child_id,
                    $depth + 1,
                    $menu_item_id > 0 ? $menu_item_id : 0,
                    $max_depth,
                    $menu_id,
                    $children,
                    $by_id,
                    $term_to_item,
                    $existing_url_map,
                    $skip_existing_by_url,
                    $dry_run,
                    $created,
                    $skipped,
                    $errors,
                    $log
                );
            }
        }
    }

    private function parse_ids(string $raw): array {
        $raw = trim($raw);
        if ($raw === '') return [];
        $parts = preg_split('/\s*,\s*/', $raw);
        $ids = [];
        foreach ($parts as $p) {
            $p = trim($p);
            if ($p === '') continue;
            $n = (int)$p;
            if ($n > 0) $ids[] = $n;
        }
        return array_values(array_unique($ids));
    }

    private function normalize_url(string $url): string {
        $url = trim($url);
        // remove trailing slash
        $url = rtrim($url, '/');
        // normalize scheme-less or relative
        return $url;
    }

    private function indent(int $depth): string {
        return str_repeat('  ', max(0, $depth - 1));
    }

    private function admin_page_url(): string {
        // When embedded in HM Pro Theme, always redirect back to the theme tool page.
        if ( defined( 'HMPRO_SLUG_MENU_BUILDER_EMBEDDED' ) && HMPRO_SLUG_MENU_BUILDER_EMBEDDED ) {
            return admin_url( 'admin.php?page=hmpro-slug-menu-builder' );
        }

        // Plugin mode (fallback)
        $base = admin_url( 'admin.php?page=hm-product-cat-menu-builder' );
        return $base;
    }
}
endif;


if ( ! function_exists( 'hmpro_get_slug_menu_builder' ) ) {
    function hmpro_get_slug_menu_builder() {
        static $instance = null;
        if ( null === $instance && class_exists( 'HM_Product_Cat_Menu_Builder' ) ) {
            $instance = new HM_Product_Cat_Menu_Builder( false );
        }
        return $instance;
    }
}

if ( ! function_exists( 'hmpro_render_slug_menu_builder_page' ) ) {
    function hmpro_render_slug_menu_builder_page() {
        $tool = hmpro_get_slug_menu_builder();
        if ( $tool && method_exists( $tool, 'render_page' ) ) {
            $tool->render_page();
        } else {
            echo '<div class="notice notice-error"><p>Slug Menu Builder could not be initialized.</p></div>';
        }
    }
}
