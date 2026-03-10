<?php
/**
 * Plugin Name: HM Pro Category Importer
 * Description: Imports hierarchical WooCommerce Product Categories (product_cat) from CSV with level_1..level_6 + descriptions.
 * Version: 1.0.0
 * Author: HM
 */

if (!defined('ABSPATH')) exit;

// Safety: avoid re-declaring the class, BUT do not return from file
// because helper functions below must always be available for admin callbacks.
if ( ! class_exists( 'HM_Pro_Category_Importer', false ) ) :
final class HM_Pro_Category_Importer {
    const NONCE_ACTION = 'hm_pro_cat_importer_upload';
    const OPTION_LAST_LOG = 'hm_pro_cat_importer_last_log';

    public function __construct( $register_menu = true ) {
        if ( $register_menu ) {
            add_action( 'admin_menu', [ $this, 'admin_menu' ] );
        }
        add_action('admin_post_hm_pro_cat_import', [$this, 'handle_import']);
    }

    public function admin_menu() {
        // WooCommerce varsa Products altında, yoksa Tools altında göster
        if ($this->is_woocommerce_active()) {
            add_submenu_page(
                'edit.php?post_type=product',
                'Category Importer',
                'Category Importer',
                'manage_woocommerce',
                'hmpro-category-importer',
                [$this, 'render_page']
            );
        } else {
            add_management_page(
                'Category Importer',
                'Category Importer',
                'manage_options',
                'hmpro-category-importer',
                [$this, 'render_page']
            );
        }
    }

    private function is_woocommerce_active() {
        return taxonomy_exists('product_cat');
    }

    private function taxonomy() {
        // WooCommerce varsa product_cat, yoksa normal category
        return $this->is_woocommerce_active() ? 'product_cat' : 'category';
    }

    public function render_page() {
        if (!current_user_can($this->is_woocommerce_active() ? 'manage_woocommerce' : 'manage_options')) {
            wp_die('You do not have permission to access this page.');
        }

        $last_log = get_option(self::OPTION_LAST_LOG, '');
        $tax = esc_html($this->taxonomy());
        ?>
        <div class="wrap">
            <h1>HM Pro Category Importer</h1>

            <p><strong>CSV format:</strong> level_1, level_1_desc, level_2, level_2_desc, ... level_6, level_6_desc</p>
            <p><strong>Target taxonomy:</strong> <?php echo $tax; ?></p>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
                <input type="hidden" name="action" value="hm_pro_cat_import">
                <?php wp_nonce_field(self::NONCE_ACTION, '_wpnonce'); ?>

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="hm_csv">CSV File</label></th>
                        <td><input type="file" name="hm_csv" id="hm_csv" accept=".csv,text/csv" required></td>
                    </tr>
                    <tr>
                        <th scope="row">Options</th>
                        <td>
                            <label>
                                <input type="checkbox" name="dry_run" value="1" checked>
                                Dry run (no changes, just log)
                            </label>
                            <br>
                            <label>
                                <input type="checkbox" name="update_desc" value="1" checked>
                                Update descriptions when term exists
                            </label>
                            <br>
                            <label>
                                <input type="checkbox" name="skip_empty_rows" value="1" checked>
                                Skip empty rows
                            </label>
                        </td>
                    </tr>
                </table>

                <?php submit_button('Import Categories'); ?>
            </form>

            <?php if (!empty($last_log)) : ?>
                <hr>
                <h2>Last Import Log</h2>
                <pre style="background:#fff;border:1px solid #ccd0d4;padding:12px;max-height:420px;overflow:auto;"><?php echo esc_html($last_log); ?></pre>
            <?php endif; ?>
        </div>
        <?php
    }

    public function handle_import() {
        $cap = $this->is_woocommerce_active() ? 'manage_woocommerce' : 'manage_options';
        if (!current_user_can($cap)) {
            wp_die('You do not have permission to perform this action.');
        }

        check_admin_referer(self::NONCE_ACTION);

        if (empty($_FILES['hm_csv']) || empty($_FILES['hm_csv']['tmp_name'])) {
            wp_safe_redirect(add_query_arg(['page' => 'hmpro-category-importer', 'hm_err' => 'no_file'], admin_url( ( defined('HMPRO_CATEGORY_IMPORTER_EMBEDDED') && HMPRO_CATEGORY_IMPORTER_EMBEDDED ) ? 'admin.php?page=hmpro-category-importer' : ( $this->is_woocommerce_active() ? 'edit.php?post_type=product' : 'tools.php' ) )));
            exit;
        }

        $dry_run = !empty($_POST['dry_run']);
        $update_desc = !empty($_POST['update_desc']);
        $skip_empty_rows = !empty($_POST['skip_empty_rows']);

        $file = $_FILES['hm_csv']['tmp_name'];
        $tax = $this->taxonomy();

        $log = [];
        $log[] = '=== HM Pro Category Importer ===';
        $log[] = 'Taxonomy: ' . $tax;
        $log[] = 'Dry run: ' . ($dry_run ? 'YES' : 'NO');
        $log[] = 'Update descriptions: ' . ($update_desc ? 'YES' : 'NO');
        $log[] = 'Skip empty rows: ' . ($skip_empty_rows ? 'YES' : 'NO');
        $log[] = 'File: ' . sanitize_text_field($_FILES['hm_csv']['name']);
        $log[] = '--------------------------------';

        $handle = fopen($file, 'r');
        if (!$handle) {
            wp_die('Could not open uploaded file.');
        }

        // Auto-detect delimiter from first line (comma/semicolon/tab)
        $firstLine = fgets($handle);
        if ($firstLine === false) {
            fclose($handle);
            wp_die('CSV seems empty.');
        }

        $delimiter = $this->detect_delimiter($firstLine);
        rewind($handle);

        $log[] = 'Detected delimiter: ' . ($delimiter === "\t" ? 'TAB' : $delimiter);

        $rowIndex = 0;
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors  = 0;

        // Expect at least 12 columns: level_1, level_1_desc, ... level_6, level_6_desc
        // If CSV has more columns, we ignore extras.
        $header = fgetcsv($handle, 0, $delimiter);
        $rowIndex++;

        if (!$header || count($header) < 2) {
            fclose($handle);
            wp_die('CSV header is invalid.');
        }

        // Normalize header keys
        $header_norm = array_map(function($h){
            $h = (string)$h;
            $h = preg_replace('/^\xEF\xBB\xBF/', '', $h);
            $h = strtolower(trim($h));
            $h = preg_replace('/\xEF\xBB\xBF/', '', $h); // BOM safety
            return $h;
        }, $header);

        // Build index map for required fields
        $idx = [];
        for ($i=1; $i<=6; $i++) {
            $name_key = "level_{$i}";
            $desc_key = "level_{$i}_desc";
            $idx[$name_key] = array_search($name_key, $header_norm, true);
            $idx[$desc_key] = array_search($desc_key, $header_norm, true);
        }

        // If the export had weird extra header values (like "..."), we still proceed using the indexes we found.
        
        // Fallback: if header matching fails (many missing indices), map by column position (A..L).
        $missing = 0;
        foreach ($idx as $k => $v) {
            if ($v === false || $v === null) $missing++;
        }
        if ($missing >= 6 || $idx['level_1'] === false) {
            $idx = [];
            for ($i=1; $i<=6; $i++) {
                $idx["level_{$i}"] = ($i-1)*2;
                $idx["level_{$i}_desc"] = ($i-1)*2 + 1;
            }
            $log[] = 'Header fallback mapping applied (positional A..L).';
        }

$log[] = 'Header parsed. Indices: ' . json_encode($idx);

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rowIndex++;

            // If header row is accidentally included as a data row, skip it.
            $first_cell = strtolower(trim($this->get_col($row, $idx['level_1'])));
            if ($first_cell === 'level_1') {
                $skipped++;
                continue;
            }

            $levels = [];
            for ($i=1; $i<=6; $i++) {
                $nKey = "level_{$i}";
                $dKey = "level_{$i}_desc";

                $name = $this->get_col($row, $idx[$nKey]);
                $desc = $this->get_col($row, $idx[$dKey]);

                $levels[$i] = [
                    'name' => $this->clean_cell($name),
                    'desc' => $this->clean_cell($desc),
                ];
            }

            // Determine if row is empty
            $has_any = false;
            foreach ($levels as $lv) {
                if ($lv['name'] !== '') { $has_any = true; break; }
            }
            if (!$has_any) {
                if ($skip_empty_rows) {
                    $skipped++;
                    continue;
                }
            }

            $parent_term_id = 0;
            $path = [];

            for ($i=1; $i<=6; $i++) {
                $name = $levels[$i]['name'];
                $desc = $levels[$i]['desc'];

                if ($name === '') {
                    // If a level is empty, stop processing deeper levels for this row
                    break;
                }

                $path[] = $name;

                $result = $this->ensure_term($tax, $name, $parent_term_id, $desc, $dry_run, $update_desc);

                if (is_wp_error($result)) {
                    $errors++;
                    $log[] = "[Row {$rowIndex}] ERROR at level {$i} (" . implode(' > ', $path) . "): " . $result->get_error_message();
                    // Stop deeper processing for this row
                    break;
                }

                // $result = ['term_id' => int, 'status' => 'created|exists|updated']
                $parent_term_id = (int)$result['term_id'];

                if ($result['status'] === 'created') $created++;
                if ($result['status'] === 'updated') $updated++;
            }
        }

        fclose($handle);

        $log[] = '--------------------------------';
        $log[] = "Done. Rows processed: " . max(0, ($rowIndex - 1));
        $log[] = "Created: {$created}";
        $log[] = "Updated: {$updated}";
        $log[] = "Skipped: {$skipped}";
        $log[] = "Errors: {$errors}";
        $log[] = '=== END ===';

        update_option(self::OPTION_LAST_LOG, implode("\n", $log));

        $redirect_base = ( defined('HMPRO_CATEGORY_IMPORTER_EMBEDDED') && HMPRO_CATEGORY_IMPORTER_EMBEDDED )
            ? admin_url('admin.php')
            : admin_url($this->is_woocommerce_active() ? 'edit.php?post_type=product' : 'tools.php');
        wp_safe_redirect(add_query_arg(['page' => 'hmpro-category-importer', 'hm_ok' => 1], $redirect_base));
        exit;
    }

    private function detect_delimiter($line) {
        $delims = [',', ';', "\t", '|'];
        $best = ',';
        $bestCount = 0;
        foreach ($delims as $d) {
            $count = substr_count($line, $d);
            if ($count > $bestCount) {
                $bestCount = $count;
                $best = $d;
            }
        }
        return $best;
    }

    private function get_col(array $row, $index) {
        if ($index === false || $index === null) return '';
        return isset($row[$index]) ? (string)$row[$index] : '';
    }

    private function clean_cell($v) {
        $v = (string)$v;
        $v = str_replace(["\r\n", "\r", "\n"], ' ', $v);
        $v = trim($v);
        // Excel bazen çift tırnak sarar
        $v = preg_replace('/^"(.*)"$/', '$1', $v);
        // Çoklu boşlukları sadeleştir
        $v = preg_replace('/\s{2,}/', ' ', $v);
        return $v;
    }

    /**
     * Ensure term exists under given parent. If exists, optionally update description.
     * Returns array(term_id, status) or WP_Error
     */
    private function ensure_term($taxonomy, $name, $parent_term_id, $desc, $dry_run, $update_desc) {
        // Aynı isim farklı parent altında olabilir. Bu yüzden parent bazlı arıyoruz.
        $existing_id = $this->find_term_in_parent($taxonomy, $name, $parent_term_id);

        if ($existing_id) {
            if ($update_desc && $desc !== '') {
                if ($dry_run) {
                    return ['term_id' => $existing_id, 'status' => 'updated'];
                }
                $upd = wp_update_term($existing_id, $taxonomy, [
                    'description' => $desc,
                ]);
                if (is_wp_error($upd)) return $upd;
                return ['term_id' => (int)$existing_id, 'status' => 'updated'];
            }
            return ['term_id' => (int)$existing_id, 'status' => 'exists'];
        }

        if ($dry_run) {
            // Fake term_id in dry-run, but must return something consistent for parent chaining.
            // We can’t know the real ID; to keep hierarchy chaining correct in dry-run,
            // we return a deterministic negative hash. (Only used inside the same request.)
            $fake = -abs(crc32($taxonomy . '|' . $parent_term_id . '|' . $name));
            return ['term_id' => $fake, 'status' => 'created'];
        }

        $ins = wp_insert_term($name, $taxonomy, [
            'parent' => (int)$parent_term_id > 0 ? (int)$parent_term_id : 0,
            'description' => $desc,
        ]);

        if (is_wp_error($ins)) {
            // Eğer aynı isim-parent kombinasyonu çakıştıysa tekrar bulmayı dene
            $retry_id = $this->find_term_in_parent($taxonomy, $name, $parent_term_id);
            if ($retry_id) {
                return ['term_id' => (int)$retry_id, 'status' => 'exists'];
            }
            return $ins;
        }

        return ['term_id' => (int)$ins['term_id'], 'status' => 'created'];
    }

    /**
     * Finds a term by name strictly within a parent.
     */
    private function find_term_in_parent($taxonomy, $name, $parent_term_id) {
        $parent_term_id = (int)$parent_term_id;

        // Quick approach: get_terms with parent filter
        $terms = get_terms([
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
            'parent' => $parent_term_id,
            'name' => $name,
            'fields' => 'ids',
            'number' => 5,
        ]);

        if (!is_wp_error($terms) && !empty($terms)) {
            return (int)$terms[0];
        }

        // Fallback: term_exists then validate parent
        $maybe = term_exists($name, $taxonomy);
        if (is_array($maybe) && !empty($maybe['term_id'])) {
            $tid = (int)$maybe['term_id'];
            $term = get_term($tid, $taxonomy);
            if ($term && !is_wp_error($term) && (int)$term->parent === $parent_term_id) {
                return $tid;
            }
        }

        return 0;
    }
}
endif;



// Theme integration helpers (do not auto-register admin menus here).
if ( ! function_exists( 'hmpro_get_category_importer' ) ) {
    function hmpro_get_category_importer() {
        static $instance = null;
        if ( null === $instance && class_exists( 'HM_Pro_Category_Importer' ) ) {
            $instance = new HM_Pro_Category_Importer( false );
        }
        return $instance;
    }
}

if ( ! function_exists( 'hmpro_render_category_importer_page' ) ) {
    function hmpro_render_category_importer_page() {
        $tool = hmpro_get_category_importer();
        if ( $tool && method_exists( $tool, 'render_page' ) ) {
            $tool->render_page();
        } else {
            echo '<div class="notice notice-error"><p>Category Importer could not be initialized.</p></div>';
        }
    }
}
