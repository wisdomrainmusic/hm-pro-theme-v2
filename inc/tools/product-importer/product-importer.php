<?php
/**
 * Plugin Name: HM Master Importer
 * Description: CSV importer + smart exporter for WooCommerce (Textile + Shoes + Underwear + Cosmetic). Uses model_kodu as parent SKU and stok_kodu as unique variation SKU. Dedupes images by stable keys (local uploads path or canonical URL). Supports tags + HM PVI product video meta. Supports Rank Math SEO fields + slug. Adds smart image SEO (ALT/TITLE/SLUG + SEO filename) via image_alt/focus_keyword/urun_adi. Shared-hosting safe: BATCH QUEUE mode (no cron/defer). CSV upload builds a queue; Admin button processes next N parent products (all variations) per click (N selectable).
 * Version: 1.2.3
 */

if (!defined('ABSPATH')) exit;

// Safety: avoid re-declaring the class, BUT do not return from file
// because helper functions / callbacks below must remain available.
if ( ! class_exists( 'HM_Master_Importer', false ) ) :

/**
 * Emergency-safe importer logger (works even when WP debug logs are disabled).
 * Writes to: /wp-content/hm-importer.log
 */
if (!function_exists('hm_import_log')) {
  function hm_import_log($msg) {
    $file = WP_CONTENT_DIR . '/hm-importer.log';
    @file_put_contents($file, '[' . date('c') . '] ' . $msg . PHP_EOL, FILE_APPEND);
  }

  register_shutdown_function(function () {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
      hm_import_log('FATAL: ' . $e['message'] . ' in ' . $e['file'] . ':' . $e['line']);
    }
  });
}

class HM_Master_Importer {

  /**
   * Return admin page URL depending on embedded/theme mode.
   * $which: 'import' or 'export'
   */
  private static function admin_page_url( string $which = 'import' ): string {
    $embedded = ( defined('HMPRO_PRODUCT_IMPORTER_EMBEDDED') && HMPRO_PRODUCT_IMPORTER_EMBEDDED );
    if ( $embedded ) {
      if ( $which === 'export' ) {
        return admin_url( 'admin.php?page=hmpro-product-exporter' );
      }
      return admin_url( 'admin.php?page=hmpro-product-importer' );
    }

    // Plugin mode (Tools submenu)
    if ( $which === 'export' ) {
      return admin_url( 'admin.php?page=hmpro-product-exporter' );
    }
    return admin_url( 'admin.php?page=hmpro-product-importer' );
  }


  /**
   * Dedupe key meta:
   * - local:uploads:<relative>  (stable even if domain changes)
   * - url:<canonical_url_without_query>
   */
  const META_SOURCE_KEY      = '_hm_source_key';

  // Queue job option (single active job)
  const OPTION_QUEUE_JOB     = 'hm_master_importer_queue_job';

  // Term recount option (kept)
  const OPTION_RECOUNT_TERMS = 'hm_master_importer_recount_terms';

  // Default batch size (per click) — can be overridden from UI (10..100 step 10)
  const DEFAULT_BATCH_SIZE = 30;

  // FINAL COLUMNS (ASCII) — exporter uses the SAME header order for round-trip.
  private static $columns = [
    'urun_kodu','model_kodu','stok_kodu','urun_adi',
    'ana_kategori','alt_kategori','alt_kategori_2','alt_kategori_3','alt_kategori_4',

    'product_description',
    'short_description',

    // Rank Math + Slug (parent level)
    'seo_title',
    'meta_description',
    'focus_keyword',
    'image_alt',   // optional
    'url_slug',

    // tags + HM PVI video
    'etiketler',
    'youtube_url',
    'video_poster_url',
    'video_placement',
    'video_lightbox',
    'video_button_label',

    // FILTER columns (variation-ready)
    'beden','olcu_miktari','ayakkabi_numara_eu','ic_giyim_beden','renk',

    // pricing & stock
    'fiyat','indirimli_fiyat','stok_adedi','kdv_orani','desi',

    // images
    'gorsel_1','gorsel_2','gorsel_3','gorsel_4','gorsel_5','gorsel_6','gorsel_7','gorsel_8',
    'varyasyon_gorsel',

    // textile extra info
    'materyal','kumas_tipi','kalip','desen','sezon','yaka_tipi','kol_tipi','kol_boyu','boy','cinsiyet','yas_grubu',
    'ek_ozellik','ek_ozellik_2','ek_ozellik_3',

    // shoe extra info
    'topuk_boyu','topuk_sekli','burun_tipi','taban_tipi','baglama_sekli','ic_astar','dis_materyal','taban_materyal','su_gecirmez',

    // underwear extra info
    'kap_tipi','dolgu_durumu','askili','toparlayici','set_icerik'
  ];

  // FILTER attributes (global pa_*) - used for variation + product filters
  private static $filter_tax_attrs = [
    'renk'               => 'pa_renk',
    'beden'              => 'pa_beden',
    'olcu_miktari'       => 'pa_olcu-miktari',
    'ayakkabi_numara_eu' => 'pa_ayakkabi-numara-eu',
    'ic_giyim_beden'     => 'pa_ic-giyim-beden',
  ];

  // Optional "extra info" fields (shown under Additional information)
  private static $extra_info_fields = [
    // Tekstil
    'materyal','kumas_tipi','kalip','desen','sezon','yaka_tipi','kol_tipi','kol_boyu','boy','cinsiyet','yas_grubu',
    'ek_ozellik','ek_ozellik_2','ek_ozellik_3',

    // Ayakkabı
    'topuk_boyu','topuk_sekli','burun_tipi','taban_tipi','baglama_sekli','ic_astar','dis_materyal','taban_materyal','su_gecirmez',

    // İç giyim
    'kap_tipi','dolgu_durumu','askili','toparlayici','set_icerik',
  ];

  public static function init() {
    if ( ! ( defined('HMPRO_PRODUCT_IMPORTER_EMBEDDED') && HMPRO_PRODUCT_IMPORTER_EMBEDDED ) ) {
      add_action('admin_menu', [__CLASS__, 'menu']);
    }

    // Upload CSV -> Build queue
    add_action('admin_post_hm_master_import', [__CLASS__, 'handle_import']);

    // Process next batch
    add_action('admin_post_hm_master_import_process_batch', [__CLASS__, 'handle_process_batch']);

    // Reset queue
    add_action('admin_post_hm_master_import_reset_queue', [__CLASS__, 'handle_reset_queue']);

    // Export
    add_action('admin_post_hm_master_export', [__CLASS__, 'handle_export']);
  }

  /**
   * Disable intermediate image sizes during import to prevent memory/time spikes.
   */
  public static function disable_intermediate_sizes($sizes) {
    return [];
  }

  public static function menu() {
    if ( defined('HMPRO_PRODUCT_IMPORTER_EMBEDDED') && HMPRO_PRODUCT_IMPORTER_EMBEDDED ) { return; }

    add_submenu_page(
      'tools.php',
      'HM Master Importer',
      'HM Master Importer',
      'manage_woocommerce',
      'hm-master-importer',
      [__CLASS__, 'page']
    );

    add_submenu_page(
      'tools.php',
      'HM Master Exporter',
      'HM Master Exporter',
      'manage_woocommerce',
      'hm-master-exporter',
      [__CLASS__, 'export_page']
    );
  }

  // NEW
  private static function get_recount_terms_from_request_or_option(): bool {
    if (isset($_POST['recount_terms'])) {
      $val = sanitize_text_field(wp_unslash($_POST['recount_terms']));
      return in_array(strtolower((string)$val), ['1','yes','true','on'], true);
    }
    $saved = (string) get_option(self::OPTION_RECOUNT_TERMS, 'yes');
    return ($saved === 'yes');
  }

  private static function get_queue_job(): array {
    $job = get_option(self::OPTION_QUEUE_JOB, []);
    return is_array($job) ? $job : [];
  }

  private static function set_queue_job(array $job): void {
    update_option(self::OPTION_QUEUE_JOB, $job, false);
  }

  private static function clear_queue_job(): void {
    delete_option(self::OPTION_QUEUE_JOB);
  }

  private static function queue_dir_abs(): string {
    $up = wp_upload_dir();
    $basedir = isset($up['basedir']) ? (string)$up['basedir'] : '';
    if ($basedir === '') return WP_CONTENT_DIR . '/uploads';
    return rtrim($basedir, '/\\') . '/hm-importer-queue';
  }

  private static function ensure_queue_dir(): bool {
    $dir = self::queue_dir_abs();
    if (!file_exists($dir)) {
      return (bool) wp_mkdir_p($dir);
    }
    return is_dir($dir) && is_writable($dir);
  }

  // NEW: batch size sanitization (10..100 step 10)
  private static function sanitize_batch_size($n): int {
    $n = (int)$n;
    if ($n <= 0) return (int)self::DEFAULT_BATCH_SIZE;

    if ($n < 10) $n = 10;
    if ($n > 100) $n = 100;

    // force step 10
    $n = (int) (round($n / 10) * 10);
    if ($n < 10) $n = 10;
    if ($n > 100) $n = 100;

    return $n;
  }

  private static function calc_batch_info(int $cursor, int $total, int $batch_size): array {
    $cursor = max(0, (int)$cursor);
    $total  = max(0, (int)$total);
    $batch_size = max(1, (int)$batch_size);

    $done = min($cursor, $total);
    $left = max($total - $done, 0);

    $start = $done;
    $end   = min($start + $batch_size, $total);
    $will  = max($end - $start, 0);

    $batch_no = (int) floor($start / $batch_size) + 1;
    $batch_total = (int) ceil($total / $batch_size);

    // Human-friendly ranges (1-indexed)
    $human_from = ($will > 0) ? ($start + 1) : 0;
    $human_to   = ($will > 0) ? $end : 0;

    return [
      'done'        => $done,
      'left'        => $left,
      'start'       => $start,
      'end'         => $end,
      'will'        => $will,
      'batch_no'    => $batch_no,
      'batch_total' => $batch_total,
      'human_from'  => $human_from,
      'human_to'    => $human_to,
    ];
  }

  private static function queue_status_html(): string {
    $job = self::get_queue_job();
    if (empty($job['job_id']) || empty($job['total_parents'])) return '';

    $cursor = isset($job['cursor']) ? (int)$job['cursor'] : 0;
    $total  = (int)$job['total_parents'];
    $rows_total = isset($job['total_rows']) ? (int)$job['total_rows'] : 0;

    $batch_size = isset($job['batch_size']) ? self::sanitize_batch_size($job['batch_size']) : (int)self::DEFAULT_BATCH_SIZE;

    $info = self::calc_batch_info($cursor, $total, $batch_size);

    $created = !empty($job['created_at']) ? esc_html((string)$job['created_at']) : '';
    $jid     = esc_html((string)$job['job_id']);

    $next_line = '';
    if ($info['will'] > 0) {
      $next_line = ' | Batch Boyutu: <strong>'.$batch_size.'</strong> | Sıradaki Parti: <strong>'.$info['batch_no'].'/'.$info['batch_total'].'</strong> (Ürün '.$info['human_from'].'–'.$info['human_to'].') | Bu tıkta işlenecek: <strong>'.$info['will'].'</strong>';
    } else {
      $next_line = ' | Kuyruk tamamlandı görünüyor.';
    }

    $rows_line = ($rows_total > 0) ? (' | Toplam varyasyon satırı: <strong>'.(int)$rows_total.'</strong>') : '';

    return '<div class="notice notice-info" style="padding:10px 12px;">
      <p style="margin:0;">
        <strong>Aktif Kuyruk:</strong> İş <code>'.$jid.'</code>
        | Toplam Parent Ürün: <strong>'.$total.'</strong>
        | Tamamlanan: <strong>'.$info['done'].'</strong>
        | Kalan: <strong>'.$info['left'].'</strong>'
        .$rows_line
        .$next_line
        .($created ? ' | Oluşturma: '.$created : '').
      '</p>
    </div>';
  }

  public static function page() {
    if (!class_exists('WooCommerce')) {
      echo '<div class="notice notice-error"><p>WooCommerce gerekli.</p></div>';
      return;
    }

    $saved_recount = (string) get_option(self::OPTION_RECOUNT_TERMS, 'yes');
    if (!in_array($saved_recount, ['yes','no'], true)) $saved_recount = 'yes';

    $job = self::get_queue_job();
    $has_queue = !empty($job['job_id']) && !empty($job['index_path']) && file_exists((string)$job['index_path']);

    $cursor = isset($job['cursor']) ? (int)$job['cursor'] : 0;
    $total  = isset($job['total_parents']) ? (int)$job['total_parents'] : 0;

    $batch_size = isset($job['batch_size']) ? self::sanitize_batch_size($job['batch_size']) : (int)self::DEFAULT_BATCH_SIZE;
    $info   = self::calc_batch_info($cursor, $total, $batch_size);

    ?>
    <div class="wrap">
      <h1>HM Master Importer — Parti (Kuyruk) Modu</h1>
      <p><strong>model_kodu</strong> = parent SKU, <strong>stok_kodu</strong> = unique variation SKU.</p>

      <?php
      if (!empty($_GET['hm_msg'])) {
        echo '<div class="notice notice-success"><p>'.esc_html(rawurldecode((string)$_GET['hm_msg'])).'</p></div>';
      }
      if (!empty($_GET['hm_err'])) {
        $err = trim((string)rawurldecode((string)$_GET['hm_err']));
        if ($err !== '') {
          echo '<div class="notice notice-warning"><pre style="white-space:pre-wrap;margin:0;padding:10px 12px;">'.esc_html($err).'</pre></div>';
        }
      }

      echo self::queue_status_html();
      ?>

      <h2 style="margin-top:18px;">1) CSV Yükle (Kuyruk Oluştur)</h2>
      <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
        <?php wp_nonce_field('hm_master_import'); ?>
        <input type="hidden" name="action" value="hm_master_import">
        <table class="form-table">
          <tr>
            <th scope="row">CSV Dosyası</th>
            <td><input type="file" name="csv" accept=".csv,text/csv" required></td>
          </tr>
          <tr>
            <th scope="row">Ayraç (Delimiter)</th>
            <td>
              <select name="delimiter">
                <option value="auto" selected>Otomatik</option>
                <option value=",">Virgül (,)</option>
                <option value=";">Noktalı Virgül (;)</option>
                <option value="\t">Tab</option>
              </select>
              <p class="description">CSV bir kere okunur ve <code>model_kodu</code>’ya göre parent bazında kuyruk hazırlanır.</p>
            </td>
          </tr>

          <tr>
            <th scope="row">Kategori/Etiket Sayaç Düzeltme</th>
            <td>
              <label>
                <input type="checkbox" name="recount_terms" value="yes" <?php checked($saved_recount, 'yes'); ?> />
                Kuyruk tamamen bitince kategori/etiket sayacı düzelt (önerilir)
              </label>
              <p class="description">Bu işlem sadece kuyruk %100 bittiğinde çalışır.</p>
            </td>
          </tr>

        </table>
        <?php submit_button('Kuyruğu Oluştur'); ?>
      </form>

      <hr style="margin:22px 0;" />

      <h2>2) Kuyruğu İşle (Parti Parti)</h2>
      <?php if ($has_queue): ?>
        <p style="margin-top:6px;color:#555">
          Her tıkta seçtiğin batch boyutu kadar parent ürün (tüm varyasyonlarıyla) işlenir.
          <?php if ($info['will'] > 0): ?>
            Şu an sırada: <strong><?php echo (int)$info['batch_no']; ?>/<?php echo (int)$info['batch_total']; ?></strong> parti (Ürün <?php echo (int)$info['human_from']; ?>–<?php echo (int)$info['human_to']; ?>).
            Bu tıkta işlenecek parent sayısı: <strong><?php echo (int)$info['will']; ?></strong>.
          <?php else: ?>
            Kuyruk tamamlanmış görünüyor.
          <?php endif; ?>
        </p>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline-block;margin-right:12px;">
          <?php wp_nonce_field('hm_master_import_process_batch'); ?>
          <input type="hidden" name="action" value="hm_master_import_process_batch" />

          <label style="margin-right:10px;vertical-align:middle;">
            <strong>Batch Boyutu:</strong>
            <select name="batch_size" style="min-width:110px;">
              <?php
                $opts = [10,20,30,40,50,60,70,80,90,100];
                foreach ($opts as $opt) {
                  echo '<option value="'.(int)$opt.'" '.selected($batch_size, (int)$opt, false).'>'.(int)$opt.'</option>';
                }
              ?>
            </select>
          </label>

          <?php
            $btnWill = (int)$info['will'];
            $btnMax  = (int)$batch_size;
            submit_button('Sıradaki Partiyi İşle ('.$btnWill.' / '.$btnMax.')', 'primary', 'submit', false);
          ?>
        </form>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline-block;">
          <?php wp_nonce_field('hm_master_import_reset_queue'); ?>
          <input type="hidden" name="action" value="hm_master_import_reset_queue" />
          <?php submit_button('Kuyruğu Sıfırla (İşi Sil)', 'secondary', 'submit', false); ?>
        </form>

      <?php else: ?>
        <p style="margin-top:6px;color:#666">Aktif kuyruk yok. Yeni bir kuyruk oluşturmak için CSV yükleyin.</p>
      <?php endif; ?>

      <p style="margin-top:16px;color:#666">Log: <code>/wp-content/hm-importer.log</code></p>
    </div>
    <?php
  }

  /**
   * SMART Export page (filters + round-trip CSV).
   */
  public static function export_page() {
    if (!class_exists('WooCommerce')) {
      echo '<div class="notice notice-error"><p>WooCommerce gerekli.</p></div>';
      return;
    }

    $cats = get_terms([
      'taxonomy'   => 'product_cat',
      'hide_empty' => false,
    ]);
    $cats = is_wp_error($cats) ? [] : $cats;

    $selected_cat = isset($_GET['cat_id']) ? (int) $_GET['cat_id'] : 0;
    $sku_like     = isset($_GET['sku_like']) ? sanitize_text_field(wp_unslash($_GET['sku_like'])) : '';
    $updated_from = isset($_GET['updated_from']) ? sanitize_text_field(wp_unslash($_GET['updated_from'])) : '';
    $stock_filter = isset($_GET['stock_filter']) ? sanitize_text_field(wp_unslash($_GET['stock_filter'])) : 'all';
    $delimiter    = isset($_GET['delimiter']) ? sanitize_text_field(wp_unslash($_GET['delimiter'])) : ';';
    ?>
    <div class="wrap">
      <h1>HM Master Exporter (Akıllı CSV)</h1>
      <p style="color:#666;margin-top:6px">Mevcut kataloğu, HM Master Importer ile aynı CSV formatında dışa aktarır (round-trip uyumlu).</p>

      <form method="get" action="<?php echo esc_url(admin_url( ( defined('HMPRO_PRODUCT_IMPORTER_EMBEDDED') && HMPRO_PRODUCT_IMPORTER_EMBEDDED ) ? 'admin.php' : 'tools.php' )); ?>">
        <input type="hidden" name="page" value="<?php echo esc_attr( ( defined('HMPRO_PRODUCT_IMPORTER_EMBEDDED') && HMPRO_PRODUCT_IMPORTER_EMBEDDED ) ? 'hmpro-product-exporter' : 'hm-master-exporter' ); ?>" />
        <table class="form-table">
          <tr>
            <th scope="row">Kategori (opsiyonel)</th>
            <td>
              <select name="cat_id">
                <option value="0">Tüm kategoriler</option>
                <?php foreach ($cats as $c): ?>
                  <option value="<?php echo (int)$c->term_id; ?>" <?php selected($selected_cat, (int)$c->term_id); ?>>
                    <?php echo esc_html($c->name); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </td>
          </tr>

          <tr>
            <th scope="row">SKU içerir (opsiyonel)</th>
            <td>
              <input type="text" name="sku_like" value="<?php echo esc_attr($sku_like); ?>" placeholder="örn. MOD- veya 123" class="regular-text" />
              <p class="description">Parent SKU (model_kodu / _sku) alanında LIKE ile arar.</p>
            </td>
          </tr>

          <tr>
            <th scope="row">Şu tarihten sonra güncellenenler (opsiyonel)</th>
            <td>
              <input type="date" name="updated_from" value="<?php echo esc_attr($updated_from); ?>" />
              <p class="description">Bu tarihten sonra güncellenen ürünleri filtreler (site saat dilimi).</p>
            </td>
          </tr>

          <tr>
            <th scope="row">Stok filtresi (opsiyonel)</th>
            <td>
              <select name="stock_filter">
                <option value="all" <?php selected($stock_filter, 'all'); ?>>Hepsi</option>
                <option value="instock" <?php selected($stock_filter, 'instock'); ?>>Sadece stokta olan varyasyonlar</option>
                <option value="outofstock" <?php selected($stock_filter, 'outofstock'); ?>>Sadece stokta olmayan varyasyonlar</option>
              </select>
              <p class="description">Varyasyon seviyesinde uygulanır. En az 1 varyasyon eşleşirse ürün export edilir.</p>
            </td>
          </tr>

          <tr>
            <th scope="row">Ayraç (Delimiter)</th>
            <td>
              <select name="delimiter">
                <option value=";" <?php selected($delimiter, ';'); ?>>Noktalı Virgül (;)</option>
                <option value="," <?php selected($delimiter, ','); ?>>Virgül (,)</option>
                <option value="\t" <?php selected($delimiter, "\t"); ?>>Tab</option>
              </select>
              <p class="description">TR Excel için genelde noktalı virgül en rahatı.</p>
            </td>
          </tr>
        </table>

        <?php submit_button('Filtreleri Uygula', 'primary', 'hm_export_preview'); ?>
      </form>

      <hr style="margin:20px 0;" />

      <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <?php wp_nonce_field('hm_master_export'); ?>
        <input type="hidden" name="action" value="hm_master_export" />
        <input type="hidden" name="cat_id" value="<?php echo (int)$selected_cat; ?>" />
        <input type="hidden" name="sku_like" value="<?php echo esc_attr($sku_like); ?>" />
        <input type="hidden" name="updated_from" value="<?php echo esc_attr($updated_from); ?>" />
        <input type="hidden" name="stock_filter" value="<?php echo esc_attr($stock_filter); ?>" />
        <input type="hidden" name="delimiter" value="<?php echo esc_attr($delimiter); ?>" />

        <p style="margin:0 0 12px 0;color:#666">
          Aşağıdan, seçtiğiniz filtrelerle CSV’yi indirebilirsiniz.
        </p>

        <?php submit_button('CSV İndir', 'secondary'); ?>
      </form>

      <p style="margin-top:14px;color:#666">
        Export formatı Importer başlıklarıyla birebir aynıdır; Excel’de düzenleyip tekrar import edebilirsiniz (Rank Math SEO alanları dahil).
      </p>
    </div>
    <?php
  }

  /**
   * EXPORT handler — streams CSV.
   */
  public static function handle_export() {
    if (!current_user_can('manage_woocommerce')) wp_die('Yetki yok');
    check_admin_referer('hm_master_export');

    if (!class_exists('WooCommerce')) wp_die('WooCommerce gerekli');

    $cat_id       = isset($_POST['cat_id']) ? (int) $_POST['cat_id'] : 0;
    $sku_like     = isset($_POST['sku_like']) ? sanitize_text_field(wp_unslash($_POST['sku_like'])) : '';
    $updated_from = isset($_POST['updated_from']) ? sanitize_text_field(wp_unslash($_POST['updated_from'])) : '';
    $stock_filter = isset($_POST['stock_filter']) ? sanitize_text_field(wp_unslash($_POST['stock_filter'])) : 'all';
    $del          = isset($_POST['delimiter']) ? (string) $_POST['delimiter'] : ';';
    if ($del === '\\t') $del = "\t";

    $filename = 'hm-master-export-' . date('Y-m-d-His') . '.csv';

    nocache_headers();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);
    header('X-Content-Type-Options: nosniff');

    $out = fopen('php://output', 'w');
    if (!$out) wp_die('Çıktı açılamadı');

    echo "\xEF\xBB\xBF";
    fputcsv($out, self::$columns, $del);

    $paged = 1;
    $per_page = 100;

    while (true) {
      $args = [
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'fields'         => 'ids',
        'posts_per_page' => $per_page,
        'paged'          => $paged,
        'orderby'        => 'ID',
        'order'          => 'ASC',
      ];

      $tax_query = [];
      if ($cat_id > 0) {
        $tax_query[] = [
          'taxonomy' => 'product_cat',
          'field'    => 'term_id',
          'terms'    => [$cat_id],
          'operator' => 'IN',
        ];
      }
      if ($tax_query) $args['tax_query'] = $tax_query;

      $meta_query = [];
      if ($sku_like !== '') {
        $meta_query[] = [
          'key'     => '_sku',
          'value'   => $sku_like,
          'compare' => 'LIKE',
        ];
      }
      if ($meta_query) $args['meta_query'] = $meta_query;

      if ($updated_from !== '') {
        $args['date_query'] = [
          [
            'column' => 'post_modified',
            'after'  => $updated_from . ' 00:00:00',
            'inclusive' => true,
          ]
        ];
      }

      $q = new WP_Query($args);
      if (!$q->have_posts()) break;

      foreach ($q->posts as $parent_id) {
        $parent = wc_get_product($parent_id);
        if (!$parent || !($parent instanceof WC_Product_Variable)) continue;

        $parent_sku  = (string) $parent->get_sku();
        if ($parent_sku === '') continue;

        $name        = (string) $parent->get_name();
        $desc        = (string) $parent->get_description();
        $short_desc  = (string) $parent->get_short_description();

        $cat_path = self::get_category_path_levels($parent_id);
        $tags     = self::get_product_tags_csv($parent_id);

        $pvi      = self::get_hm_pvi_meta_export($parent_id);
        $imgs     = self::get_parent_images_urls($parent);

        $urun_kodu  = (string) get_post_meta($parent_id, '_hm_urun_kodu', true);
        $kdv_orani  = (string) get_post_meta($parent_id, '_hm_kdv_orani', true);
        $desi       = (string) get_post_meta($parent_id, '_hm_desi', true);

        $extra = [];
        foreach (self::$extra_info_fields as $k) {
          $extra[$k] = (string) get_post_meta($parent_id, '_hm_' . $k, true);
        }

        $seo_title        = (string) get_post_meta($parent_id, 'rank_math_title', true);
        $meta_description = (string) get_post_meta($parent_id, 'rank_math_description', true);
        $focus_keyword    = (string) get_post_meta($parent_id, 'rank_math_focus_keyword', true);
        $url_slug         = (string) get_post_field('post_name', $parent_id);

        $image_alt = '';
        $featured_id = (int) $parent->get_image_id();
        if ($featured_id) {
          $image_alt = (string) get_post_meta($featured_id, '_wp_attachment_image_alt', true);
        }
        if ($image_alt === '') {
          $image_alt = $focus_keyword !== '' ? $focus_keyword : $name;
        }

        $variation_ids = $parent->get_children();
        if (!$variation_ids) continue;

        foreach ($variation_ids as $vid) {
          $v = wc_get_product($vid);
          if (!$v || !($v instanceof WC_Product_Variation)) continue;

          $v_stock_status = $v->get_stock_status();
          if ($stock_filter === 'instock' && $v_stock_status !== 'instock') continue;
          if ($stock_filter === 'outofstock' && $v_stock_status !== 'outofstock') continue;

          $row = array_fill_keys(self::$columns, '');

          $row['urun_kodu']   = $urun_kodu;
          $row['model_kodu']  = $parent_sku;
          $row['stok_kodu']   = (string) $v->get_sku();
          $row['urun_adi']    = $name;

          $row['ana_kategori']     = $cat_path[0];
          $row['alt_kategori']     = $cat_path[1];
          $row['alt_kategori_2']   = $cat_path[2];
          $row['alt_kategori_3']   = $cat_path[3];
          $row['alt_kategori_4']   = $cat_path[4];

          $row['product_description'] = $desc;
          $row['short_description']   = $short_desc;

          $row['seo_title']        = $seo_title;
          $row['meta_description'] = $meta_description;
          $row['focus_keyword']    = $focus_keyword;
          $row['image_alt']        = $image_alt;
          $row['url_slug']         = $url_slug;

          $row['etiketler']          = $tags;
          $row['youtube_url']        = $pvi['youtube_url'];
          $row['video_poster_url']   = $pvi['video_poster_url'];
          $row['video_placement']    = $pvi['video_placement'];
          $row['video_lightbox']     = $pvi['video_lightbox'];
          $row['video_button_label'] = $pvi['video_button_label'];

          $attrs = $v->get_attributes();
          foreach (self::$filter_tax_attrs as $src => $tax) {
            $row[$src] = self::variation_attr_to_value($tax, $attrs);
          }

          $row['fiyat']           = (string) $v->get_regular_price();
          $row['indirimli_fiyat'] = (string) $v->get_sale_price();
          $row['stok_adedi']      = (string) (int) $v->get_stock_quantity();

          $row['kdv_orani'] = $kdv_orani;
          $row['desi']      = $desi;

          for ($i=1; $i<=8; $i++) {
            $row['gorsel_'.$i] = $imgs[$i-1] ?? '';
          }

          $row['varyasyon_gorsel'] = self::get_variation_image_url($v);

          foreach ($extra as $k => $val) {
            $row[$k] = $val;
          }

          $ordered = [];
          foreach (self::$columns as $col) {
            $ordered[] = isset($row[$col]) ? (string)$row[$col] : '';
          }
          fputcsv($out, $ordered, $del);
        }
      }

      wp_reset_postdata();

      if ($q->max_num_pages <= $paged) break;
      $paged++;
    }

    fclose($out);
    exit;
  }

  /**
   * IMPORT handler (UPLOAD -> BUILD QUEUE)
   */
  public static function handle_import() {
    if (!current_user_can('manage_woocommerce')) wp_die('Yetki yok');
    check_admin_referer('hm_master_import');

    if (!class_exists('WooCommerce')) wp_die('WooCommerce gerekli');

    if (empty($_FILES['csv']['tmp_name'])) wp_die('CSV bulunamadı');

    if (!self::ensure_queue_dir()) {
      wp_die('Kuyruk klasörü yazılabilir değil: ' . esc_html(self::queue_dir_abs()));
    }

    $do_recount = self::get_recount_terms_from_request_or_option();
    update_option(self::OPTION_RECOUNT_TERMS, $do_recount ? 'yes' : 'no', false);

    @set_time_limit(300);

    wp_defer_term_counting(true);
    wp_defer_comment_counting(true);
    wp_suspend_cache_addition(true);

    add_filter('intermediate_image_sizes_advanced', [__CLASS__, 'disable_intermediate_sizes'], 9999);
    add_filter('big_image_size_threshold', '__return_false', 9999);

    $tmp_path = (string) $_FILES['csv']['tmp_name'];
    $del = isset($_POST['delimiter']) ? (string) $_POST['delimiter'] : 'auto';
    if ($del === '\\t') $del = "\t";

    $fh = fopen($tmp_path, 'r');
    if (!$fh) wp_die('Dosya okunamadı');

    $firstLine = fgets($fh);
    rewind($fh);

    if ($del === 'auto') {
      $del = self::guess_delimiter($firstLine ?: '');
    }

    $header = fgetcsv($fh, 0, $del);
    if (!$header) wp_die('Header bulunamadı');

    $header = array_map([__CLASS__, 'norm_header'], $header);
    $header = array_map([__CLASS__, 'alias_header'], $header);
    $colIndex = array_flip($header);

    foreach (['model_kodu','stok_kodu','urun_adi'] as $req) {
      if (!isset($colIndex[$req])) wp_die('Eksik kolon: '.$req);
    }

    // Ensure global attributes exist (filters)
    foreach (self::$filter_tax_attrs as $src => $tax) {
      self::ensure_global_attribute_taxonomy($tax, $src);
    }

    $job_id = 'job_' . date('Ymd_His') . '_' . wp_generate_password(6, false, false);
    $dir = self::queue_dir_abs();

    $csv_dest   = $dir . '/' . $job_id . '.csv';
    $index_dest = $dir . '/' . $job_id . '.index.json';

    // Move the uploaded file into uploads queue dir
    $moved = @move_uploaded_file($tmp_path, $csv_dest);
    if (!$moved) {
      // fallback copy
      $moved = @copy($tmp_path, $csv_dest);
    }
    if (!$moved || !file_exists($csv_dest)) {
      fclose($fh);
      wp_die('CSV kuyruk klasörüne kopyalanamadı.');
    }

    hm_import_log('QUEUE BUILD START v1.2.3 job_id=' . $job_id . ' delimiter=' . (string)$del . ' recount_terms=' . ($do_recount ? 'yes' : 'no'));

    // Build grouped rows by parent SKU (model_kodu)
    $parents_order = [];
    $rows_by_parent = [];

    $rowNum = 1;
    $skipped = 0;
    $total_rows = 0;

    while (($row = fgetcsv($fh, 0, $del)) !== false) {
      $rowNum++;
      $data = self::row_to_assoc($row, $colIndex);

      $parentSku = trim((string)($data['model_kodu'] ?? ''));
      $varSku    = trim((string)($data['stok_kodu'] ?? ''));
      $name      = trim((string)($data['urun_adi'] ?? ''));

      if ($parentSku === '' && $varSku === '' && $name === '') { $skipped++; continue; }
      if ($parentSku === '' || $varSku === '') { $skipped++; continue; }

      if (!isset($rows_by_parent[$parentSku])) {
        $rows_by_parent[$parentSku] = [];
        $parents_order[] = $parentSku;
      }
      $rows_by_parent[$parentSku][] = $data;
      $total_rows++;

      if ($rowNum % 250 === 0) {
        wp_cache_flush();
        if (function_exists('gc_collect_cycles')) { gc_collect_cycles(); }
      }
    }

    fclose($fh);

    // Write index JSON
    $index_payload = [
      'version'        => '1.2.3',
      'job_id'         => $job_id,
      'delimiter'      => $del,
      'created_at'     => date('c'),
      'parents_order'  => $parents_order,
      'rows_by_parent' => $rows_by_parent,
      'total_rows'     => (int)$total_rows,
      'batch_size'     => (int)self::DEFAULT_BATCH_SIZE,
    ];

    $json = wp_json_encode($index_payload);
    if (!$json) {
      @unlink($csv_dest);
      wp_die('Kuyruk index JSON üretilemedi.');
    }
    $ok = @file_put_contents($index_dest, $json);
    if (!$ok) {
      @unlink($csv_dest);
      wp_die('Kuyruk index dosyası yazılamadı.');
    }

    // Save job state in option
    $job = [
      'version'       => '1.2.3',
      'job_id'        => $job_id,
      'csv_path'      => $csv_dest,
      'index_path'    => $index_dest,
      'delimiter'     => $del,
      'cursor'        => 0,
      'total_parents' => count($parents_order),
      'total_rows'    => (int)$total_rows,
      'created_at'    => date('Y-m-d H:i:s'),
      'recount_terms' => $do_recount ? 'yes' : 'no',
      'batch_size'    => (int)self::DEFAULT_BATCH_SIZE,
    ];
    self::set_queue_job($job);

    remove_filter('intermediate_image_sizes_advanced', [__CLASS__, 'disable_intermediate_sizes'], 9999);
    remove_filter('big_image_size_threshold', '__return_false', 9999);

    wp_suspend_cache_addition(false);
    wp_defer_term_counting(false);
    wp_defer_comment_counting(false);

    hm_import_log('QUEUE BUILD END job_id=' . $job_id . ' parents=' . count($parents_order) . ' rows=' . (int)$total_rows . ' skipped=' . (int)$skipped);

    $msg = 'Kuyruk oluşturuldu. Parent: ' . count($parents_order) . ' | Varyasyon satırı: ' . (int)$total_rows . ' | Atlanan satır: ' . (int)$skipped;

    $redirect = add_query_arg([
      'hm_msg' => rawurlencode($msg),
    ], self::admin_page_url('import'));

    wp_safe_redirect($redirect);
    exit;
  }

  /**
   * PROCESS NEXT N PARENTS (N selectable)
   */
  public static function handle_process_batch() {
    if (!current_user_can('manage_woocommerce')) wp_die('Yetki yok');
    check_admin_referer('hm_master_import_process_batch');

    if (!class_exists('WooCommerce')) wp_die('WooCommerce gerekli');

    $job = self::get_queue_job();
    if (empty($job['job_id']) || empty($job['index_path']) || !file_exists((string)$job['index_path'])) {
      $redirect = add_query_arg([
        'hm_err' => rawurlencode('Aktif kuyruk bulunamadı. Önce CSV yükleyip kuyruk oluşturun.'),
      ], self::admin_page_url('import'));
      wp_safe_redirect($redirect);
      exit;
    }

    // allow UI override per click (and persist into job)
    if (isset($_POST['batch_size'])) {
      $picked = self::sanitize_batch_size(wp_unslash($_POST['batch_size']));
      $job['batch_size'] = $picked;
      self::set_queue_job($job);
    }

    $cursor = isset($job['cursor']) ? (int)$job['cursor'] : 0;

    $batch_size = isset($job['batch_size']) ? self::sanitize_batch_size($job['batch_size']) : (int)self::DEFAULT_BATCH_SIZE;

    @set_time_limit(300);
    if (function_exists('wp_raise_memory_limit')) { @wp_raise_memory_limit('image'); }

    wp_defer_term_counting(true);
    wp_defer_comment_counting(true);
    wp_suspend_cache_addition(true);

    add_filter('intermediate_image_sizes_advanced', [__CLASS__, 'disable_intermediate_sizes'], 9999);
    add_filter('big_image_size_threshold', '__return_false', 9999);

    $raw = @file_get_contents((string)$job['index_path']);
    $idx = $raw ? json_decode($raw, true) : null;
    if (!is_array($idx) || empty($idx['parents_order']) || empty($idx['rows_by_parent'])) {
      $redirect = add_query_arg([
        'hm_err' => rawurlencode('Kuyruk index bozuk veya boş. Kuyruğu sıfırlayıp CSV’yi tekrar yükleyin.'),
      ], self::admin_page_url('import'));
      wp_safe_redirect($redirect);
      exit;
    }

    $parents_order  = (array) $idx['parents_order'];
    $rows_by_parent = (array) $idx['rows_by_parent'];

    $start = max($cursor, 0);
    $end   = min($start + $batch_size, count($parents_order));

    $info = self::calc_batch_info($start, (int)count($parents_order), $batch_size);

    if ($start >= $end) {
      $msg = 'Kuyruk zaten bitmiş görünüyor.';
      $redirect = add_query_arg([
        'hm_msg' => rawurlencode($msg),
      ], self::admin_page_url('import'));
      wp_safe_redirect($redirect);
      exit;
    }

    $imported_parents = 0;
    $imported_rows    = 0;
    $errors = [];

    hm_import_log('BATCH START job_id=' . (string)$job['job_id'] . ' batch_size=' . (int)$batch_size . ' batch=' . (int)$info['batch_no'] . '/' . (int)$info['batch_total'] . ' range=' . (int)$info['human_from'] . '-' . (int)$info['human_to'] . ' total=' . (int)count($parents_order));

    for ($i = $start; $i < $end; $i++) {
      $parentSku = (string) $parents_order[$i];
      if ($parentSku === '' || empty($rows_by_parent[$parentSku]) || !is_array($rows_by_parent[$parentSku])) {
        continue;
      }

      $rows = (array) $rows_by_parent[$parentSku];

      try {
        hm_import_log('PARENT START model_kodu=' . $parentSku . ' rows=' . count($rows));

        foreach ($rows as $ridx => $data) {
          if (!is_array($data)) continue;
          $imported_rows++;
          self::import_row($data, 'now'); // FORCE NOW (no defer/cron)
        }

        $imported_parents++;

        wp_cache_flush();
        if (function_exists('gc_collect_cycles')) { gc_collect_cycles(); }

        hm_import_log('PARENT OK model_kodu=' . $parentSku);
      } catch (\Throwable $e) {
        $errors[] = 'Parent ' . $parentSku . ': ' . $e->getMessage();
        hm_import_log('PARENT ERROR model_kodu=' . $parentSku . ' | ' . $e->getMessage());
      }
    }

    // Update cursor
    $new_cursor = $end;
    $job['cursor'] = $new_cursor;
    self::set_queue_job($job);

    $finished = ($new_cursor >= count($parents_order));

    if ($finished) {
      $do_recount = (isset($job['recount_terms']) && (string)$job['recount_terms'] === 'yes');

      if ($do_recount) {
        self::recount_all_terms_notice_safe();
      }

      hm_import_log('BATCH FINISH job_id=' . (string)$job['job_id'] . ' imported_parents=' . (int)$imported_parents . ' imported_rows=' . (int)$imported_rows . ' errors=' . (int)count($errors));

      self::clear_queue_job();

      $msg = 'Kuyruk tamamlandı. Parti: ' . (int)$info['batch_no'] . '/' . (int)$info['batch_total']
        . ' (Ürün ' . (int)$info['human_from'] . '–' . (int)$info['human_to'] . ')'
        . ' | Batch Boyutu: ' . (int)$batch_size
        . ' | İşlenen parent: ' . (int)$imported_parents
        . ' | Satır: ' . (int)$imported_rows
        . ($do_recount ? ' | Kategori/etiket sayacı düzeltildi' : '');
    } else {
      $nextInfo = self::calc_batch_info($new_cursor, (int)count($parents_order), $batch_size);

      $msg = 'Parti tamamlandı: ' . (int)$info['batch_no'] . '/' . (int)$info['batch_total']
        . ' (Ürün ' . (int)$info['human_from'] . '–' . (int)$info['human_to'] . ')'
        . ' | Batch Boyutu: ' . (int)$batch_size
        . ' | İşlenen parent: ' . (int)$imported_parents
        . ' | Satır: ' . (int)$imported_rows
        . ' | Kalan parent: ' . (int)$nextInfo['left']
        . ' | Sıradaki: Parti ' . (int)$nextInfo['batch_no'] . '/' . (int)$nextInfo['batch_total']
        . ' (Ürün ' . (int)$nextInfo['human_from'] . '–' . (int)$nextInfo['human_to'] . ')';

      hm_import_log('BATCH END job_id=' . (string)$job['job_id'] . ' imported_parents=' . (int)$imported_parents . ' imported_rows=' . (int)$imported_rows . ' errors=' . (int)count($errors) . ' cursor=' . (int)$new_cursor);
    }

    remove_filter('intermediate_image_sizes_advanced', [__CLASS__, 'disable_intermediate_sizes'], 9999);
    remove_filter('big_image_size_threshold', '__return_false', 9999);

    wp_suspend_cache_addition(false);
    wp_defer_term_counting(false);
    wp_defer_comment_counting(false);

    $redirect = add_query_arg([
      'hm_msg' => rawurlencode($msg),
      'hm_err' => rawurlencode(implode("\n", array_slice($errors, 0, 30))),
    ], self::admin_page_url('import'));

    wp_safe_redirect($redirect);
    exit;
  }

  /**
   * RESET QUEUE (delete job option; also deletes stored job files if present)
   */
  public static function handle_reset_queue() {
    if (!current_user_can('manage_woocommerce')) wp_die('Yetki yok');
    check_admin_referer('hm_master_import_reset_queue');

    $job = self::get_queue_job();
    $jid = !empty($job['job_id']) ? (string)$job['job_id'] : '';

    if (!empty($job['csv_path']) && is_string($job['csv_path']) && file_exists($job['csv_path'])) {
      @unlink($job['csv_path']);
    }
    if (!empty($job['index_path']) && is_string($job['index_path']) && file_exists($job['index_path'])) {
      @unlink($job['index_path']);
    }

    self::clear_queue_job();

    $msg = $jid ? ('Kuyruk sıfırlandı. Silinen iş: ' . $jid) : 'Kuyruk sıfırlandı.';
    $redirect = add_query_arg([
      'hm_msg' => rawurlencode($msg),
    ], self::admin_page_url('import'));

    wp_safe_redirect($redirect);
    exit;
  }

  // NEW (safe recount, once at finish)
  private static function recount_all_terms_notice_safe(): void {
    $cats = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false, 'fields' => 'ids']);
    if (!is_wp_error($cats) && !empty($cats)) {
      wp_update_term_count_now(array_map('intval', (array)$cats), 'product_cat');
      hm_import_log('TERM RECOUNT product_cat all count=' . count($cats));
      if (function_exists('clean_term_cache')) clean_term_cache((array)$cats, 'product_cat');
    }

    $tags = get_terms(['taxonomy' => 'product_tag', 'hide_empty' => false, 'fields' => 'ids']);
    if (!is_wp_error($tags) && !empty($tags)) {
      wp_update_term_count_now(array_map('intval', (array)$tags), 'product_tag');
      hm_import_log('TERM RECOUNT product_tag all count=' . count($tags));
      if (function_exists('clean_term_cache')) clean_term_cache((array)$tags, 'product_tag');
    }
  }

  private static function import_row(array $d, string $image_mode) {
    $parentSku    = trim((string)($d['model_kodu'] ?? ''));
    $variationSku = trim((string)($d['stok_kodu'] ?? ''));

    if ($parentSku === '' || $variationSku === '') {
      throw new \Exception('model_kodu / stok_kodu boş olamaz');
    }

    // Parent
    $parentId = wc_get_product_id_by_sku($parentSku);
    $parent   = $parentId ? wc_get_product($parentId) : null;

    if (!$parent || !($parent instanceof WC_Product_Variable)) {
      if (!$parentId) {
        $parent = new WC_Product_Variable();
        $parent->set_name(self::clean($d['urun_adi'] ?? ''));
        $parent->set_sku($parentSku);
        $parent->set_status('publish');
        $parentId = $parent->save();
      } else {
        wp_set_object_terms($parentId, 'variable', 'product_type');
        $parent = new WC_Product_Variable($parentId);
      }
    }

    $parent->set_name(self::clean($d['urun_adi'] ?? ''));

    if (!empty($d['product_description'])) {
      $parent->set_description(self::clean($d['product_description']));
    }
    if (!empty($d['short_description'])) {
      $parent->set_short_description(self::clean($d['short_description']));
    }

    $parent->save();

    self::apply_categories($parentId, $d);
    self::apply_tags($parentId, $d);
    self::apply_hm_pvi_product_video($parentId, $d);
    self::apply_rank_math_seo($parentId, $d);

    foreach (self::$filter_tax_attrs as $src => $tax) {
      $val = isset($d[$src]) ? trim((string)$d[$src]) : '';
      if ($val === '') continue;

      $term = self::ensure_term_and_get($tax, $val);
      if (!$term) continue;

      wp_set_object_terms($parentId, (int)$term->term_id, $tax, true);
    }

    self::apply_extra_info_attributes($parentId, $d);
    self::set_parent_attributes_object($parentId);

    self::set_meta_if_present($parentId, 'urun_kodu', $d);
    self::set_meta_if_present($parentId, 'kdv_orani', $d);
    self::set_meta_if_present($parentId, 'desi', $d);
    self::set_meta_if_present($parentId, 'olcu_miktari', $d);

    self::apply_parent_images_if_empty($parentId, $d);

    // Variation
    $varId     = wc_get_product_id_by_sku($variationSku);
    $variation = $varId ? wc_get_product($varId) : null;

    if (!$variation || !($variation instanceof WC_Product_Variation)) {
      $variation = new WC_Product_Variation();
      $variation->set_parent_id($parentId);
      $variation->set_sku($variationSku);
    } else {
      if ((int)$variation->get_parent_id() !== (int)$parentId) {
        $variation->set_parent_id($parentId);
      }
    }

    $regular = self::to_price($d['fiyat'] ?? '');
    $sale    = self::to_price($d['indirimli_fiyat'] ?? '');
    if ($regular !== null) $variation->set_regular_price($regular);

    if ($sale !== null && $sale !== '' && (float)$sale > 0) {
      $variation->set_sale_price($sale);
    } else {
      $variation->set_sale_price('');
    }

    $stockQty = isset($d['stok_adedi']) ? (int)$d['stok_adedi'] : 0;
    $variation->set_manage_stock(true);
    $variation->set_stock_quantity($stockQty);
    $variation->set_stock_status($stockQty > 0 ? 'instock' : 'outofstock');

    $varAttrs = [];
    foreach (self::$filter_tax_attrs as $src => $tax) {
      $val = isset($d[$src]) ? trim((string)$d[$src]) : '';
      if ($val === '') continue;

      $term = self::ensure_term_and_get($tax, $val);
      if (!$term) continue;

      $varAttrs['attribute_'.$tax] = $term->slug;
    }
    $variation->set_attributes($varAttrs);

    $variation_image = '';
    if (!empty($d['varyasyon_gorsel'])) {
      $variation_image = self::normalize_csv_url_value($d['varyasyon_gorsel']);
    }
    if ($variation_image !== '') {
      $baseText = self::build_image_base_text($d);
      $fileBase = $baseText !== '' ? $baseText : (string)($d['urun_adi'] ?? 'image');

      $attId = self::get_or_import_attachment_from_url($variation_image, $fileBase);
      if ($attId) {
        $variation->set_image_id($attId);

        $alt = $baseText !== '' ? ($baseText . ' - varyasyon') : 'varyasyon';
        self::apply_attachment_seo($attId, $alt, $alt, '');
      }
    }

    $variation->save();
  }

  private static function apply_tags(int $productId, array $d) {
    $raw = '';
    if (isset($d['etiketler'])) $raw = (string)$d['etiketler'];
    if ($raw === '' && isset($d['tags'])) $raw = (string)$d['tags'];

    $raw = trim($raw);
    if ($raw === '') return;

    $raw = str_replace(';', ',', $raw);
    $parts = array_filter(array_map('trim', explode(',', $raw)));
    if (!$parts) return;

    wp_set_object_terms($productId, $parts, 'product_tag', false);
  }

  private static function apply_rank_math_seo(int $productId, array $d): void {
    $seo_title = isset($d['seo_title']) ? trim((string)$d['seo_title']) : '';
    $meta_desc = isset($d['meta_description']) ? trim((string)$d['meta_description']) : '';
    $focus_kw  = isset($d['focus_keyword']) ? trim((string)$d['focus_keyword']) : '';
    $slug_in   = isset($d['url_slug']) ? trim((string)$d['url_slug']) : '';

    if ($seo_title !== '') {
      update_post_meta($productId, 'rank_math_title', sanitize_text_field($seo_title));
    }
    if ($meta_desc !== '') {
      update_post_meta($productId, 'rank_math_description', sanitize_text_field($meta_desc));
    }
    if ($focus_kw !== '') {
      update_post_meta($productId, 'rank_math_focus_keyword', sanitize_text_field($focus_kw));
    }

    if ($slug_in !== '') {
      $san = sanitize_title($slug_in);
      if ($san !== '') {
        $post = get_post($productId);
        $current = $post ? (string)$post->post_name : '';
        if ($san !== $current) {
          $unique = wp_unique_post_slug(
            $san,
            $productId,
            $post ? $post->post_status : 'publish',
            'product',
            $post ? (int)$post->post_parent : 0
          );
          wp_update_post([
            'ID'        => $productId,
            'post_name' => $unique,
          ]);
        }
      }
    }
  }

  private static function apply_hm_pvi_product_video(int $productId, array $d) {
    $youtube = '';
    if (!empty($d['youtube_url'])) $youtube = trim((string)$d['youtube_url']);
    if ($youtube === '' && !empty($d['video_url'])) $youtube = trim((string)$d['video_url']);

    $poster_url = '';
    if (!empty($d['video_poster_url'])) $poster_url = self::normalize_csv_url_value($d['video_poster_url']);

    $placement = '';
    if (!empty($d['video_placement'])) $placement = trim((string)$d['video_placement']);

    $lightbox = '';
    if (isset($d['video_lightbox']) && (string)$d['video_lightbox'] !== '') $lightbox = trim((string)$d['video_lightbox']);

    $btn_label = '';
    if (!empty($d['video_button_label'])) $btn_label = trim((string)$d['video_button_label']);

    if ($youtube === '' && $poster_url === '' && $placement === '' && $lightbox === '' && $btn_label === '') {
      return;
    }

    if ($youtube !== '') {
      $youtube = self::normalize_youtube_url($youtube);

      update_post_meta($productId, '_hm_pvi_enabled', 'yes');
      update_post_meta($productId, '_hm_pvi_type', 'youtube');
      update_post_meta($productId, '_hm_pvi_youtube_url', esc_url_raw($youtube));
    }

    if ($poster_url !== '') {
      update_post_meta($productId, '_hm_pvi_poster_url', esc_url_raw($poster_url));

      $existing_id = (int) get_post_meta($productId, '_hm_pvi_poster_id', true);
      if ($existing_id <= 0) {
        $baseText = self::build_image_base_text($d);
        $fileBase = $baseText !== '' ? $baseText : 'video-poster';

        $attId = self::get_or_import_attachment_from_url($poster_url, $fileBase);
        if ($attId) {
          update_post_meta($productId, '_hm_pvi_poster_id', (int)$attId);

          $alt = $baseText !== '' ? ($baseText . ' - video poster') : 'video poster';
          self::apply_attachment_seo($attId, $alt, $alt, '');
        }
      }
    }

    $p = $placement !== '' ? strtolower(str_replace([' ', '-'], '_', $placement)) : 'below_gallery';
    update_post_meta($productId, '_hm_pvi_placement', $p);

    if ($lightbox !== '') {
      $val = strtolower($lightbox);
      $yes = in_array($val, ['1','true','yes','on','open'], true) ? 'yes' : '';
      update_post_meta($productId, '_hm_pvi_lightbox', $yes);
    }

    if ($btn_label !== '') {
      update_post_meta($productId, '_hm_pvi_button_label', sanitize_text_field($btn_label));
    }
  }

  private static function normalize_youtube_url(string $url): string {
    $url = trim($url);
    if ($url === '') return $url;

    if (preg_match('#youtube\.com/shorts/([A-Za-z0-9_-]{6,})#i', $url, $m)) {
      return 'https://www.youtube.com/watch?v=' . $m[1];
    }
    if (preg_match('#youtu\.be/([A-Za-z0-9_-]{6,})#i', $url, $m)) {
      return 'https://www.youtube.com/watch?v=' . $m[1];
    }
    return $url;
  }

  private static function get_base_category_parent_id(): int {
    $magaza = get_terms([
      'taxonomy'   => 'product_cat',
      'slug'       => 'magaza',
      'parent'     => 0,
      'hide_empty' => false,
      'number'     => 1,
    ]);
    if (is_wp_error($magaza) || !$magaza) {
      $magaza = get_term_by('slug', 'magaza', 'product_cat');
      $magaza = ($magaza && !is_wp_error($magaza)) ? [$magaza] : [];
    }

    if (!$magaza) return 0;

    $magaza_id = (int) $magaza[0]->term_id;
    if (!$magaza_id) return 0;

    $kategoriler = get_terms([
      'taxonomy'   => 'product_cat',
      'slug'       => 'kategoriler',
      'parent'     => $magaza_id,
      'hide_empty' => false,
      'number'     => 1,
    ]);
    if (is_wp_error($kategoriler) || !$kategoriler) return 0;

    return (int) $kategoriler[0]->term_id;
  }

  private static function strip_leading_base_categories(array $path): array {
    $base_slugs = ['magaza', 'kategoriler'];
    while ($path) {
      $first = sanitize_title((string) $path[0]);
      if (!in_array($first, $base_slugs, true)) break;
      array_shift($path);
    }
    return $path;
  }

  private static function apply_categories(int $productId, array $d) {
    $levels = ['ana_kategori','alt_kategori','alt_kategori_2','alt_kategori_3','alt_kategori_4'];
    $path = [];
    foreach ($levels as $k) {
      $v = isset($d[$k]) ? trim((string)$d[$k]) : '';
      if ($v !== '') $path[] = $v;
    }
    if (!$path) return;

    $base_parent = self::get_base_category_parent_id();
    $path = self::strip_leading_base_categories($path);

    $parent = $base_parent ?: 0;
    $lastTermId = $parent;

    if (!$path && !$lastTermId) return;

    foreach ($path as $name) {
      $term = term_exists($name, 'product_cat', $parent);
      if (!$term) {
        $term = wp_insert_term($name, 'product_cat', ['parent' => $parent]);
      }
      if (is_wp_error($term)) break;

      $termId = (int)($term['term_id'] ?? $term);
      $parent = $termId;
      $lastTermId = $termId;
    }

    if ($lastTermId) {
      wp_set_object_terms($productId, [$lastTermId], 'product_cat', false);
    }
  }

  // -------------------------
  // IMAGES (NOW MODE)
  // -------------------------

  private static function apply_parent_images_if_empty(int $productId, array $d) {
    $product = wc_get_product($productId);
    if (!$product) return;

    $existingFeatured = (int)$product->get_image_id();
    $existingGallery  = $product->get_gallery_image_ids();

    $urls = [];
    for ($i=1; $i<=8; $i++) {
      $k = 'gorsel_'.$i;
      if (!empty($d[$k])) {
        $u = self::normalize_csv_url_value($d[$k]);
        if ($u !== '') $urls[] = $u;
      }
    }
    $urls = array_values(array_unique(array_filter($urls)));
    if (!$urls) return;

    // only if empty (same as your current behavior)
    if ($existingFeatured || !empty($existingGallery)) return;

    $baseText = self::build_image_base_text($d);
    $fileBase = $baseText !== '' ? $baseText : (string)($d['urun_adi'] ?? 'image');

    $attachmentIds = [];
    $idx = 0;
    foreach ($urls as $u) {
      $attId = self::get_or_import_attachment_from_url($u, $fileBase);
      if ($attId) {
        $attachmentIds[] = $attId;

        $alt   = self::make_image_text_variant($baseText, $idx);
        $title = $alt !== '' ? $alt : $fileBase;

        self::apply_attachment_seo($attId, $alt, $title, '');
        $idx++;
      }
      if (count($attachmentIds) >= 8) break;
    }
    $attachmentIds = array_values(array_unique(array_filter($attachmentIds)));

    if (!$attachmentIds) return;

    $product->set_image_id($attachmentIds[0]);
    if (count($attachmentIds) > 1) {
      $product->set_gallery_image_ids(array_slice($attachmentIds, 1));
    }
    $product->save();
  }

  // -------------------------
  // ATTACHMENT SEO HELPERS
  // -------------------------

  private static function build_image_base_text(array $d): string {
    $base = '';
    if (!empty($d['image_alt'])) {
      $base = trim((string)$d['image_alt']);
    }
    if ($base === '' && !empty($d['focus_keyword'])) {
      $base = trim((string)$d['focus_keyword']);
    }
    if ($base === '' && !empty($d['urun_adi'])) {
      $base = trim((string)$d['urun_adi']);
    }
    return $base;
  }

  private static function make_image_text_variant(string $base, int $index): string {
    $base = trim($base);
    if ($base === '') return '';
    if ($index <= 0) return $base;
    return $base . ' - ' . ($index + 1);
  }

  private static function sanitize_filename_base(string $text): string {
    $text = trim($text);
    if ($text === '') return 'image';
    $san = sanitize_title($text);
    return $san !== '' ? $san : 'image';
  }

  private static function apply_attachment_seo(int $attId, string $alt, string $title, string $caption = ''): void {
    if (!$attId) return;

    $alt = trim($alt);
    $title = trim($title);

    if ($alt !== '') {
      update_post_meta($attId, '_wp_attachment_image_alt', sanitize_text_field($alt));
    }

    $post = get_post($attId);
    if ($post && $post->post_type === 'attachment') {
      $update = ['ID' => $attId];

      if ($title !== '') {
        $update['post_title'] = wp_strip_all_tags($title);
      }

      if ($caption !== '') {
        $update['post_excerpt'] = wp_kses_post($caption);
      }

      $slug_base = sanitize_title($title !== '' ? $title : $alt);
      if ($slug_base !== '') {
        $unique = wp_unique_post_slug($slug_base, $attId, $post->post_status, 'attachment', (int)$post->post_parent);
        $update['post_name'] = $unique;
      }

      wp_update_post($update);
    }
  }

  private static function guess_ext_from_url_or_mime(string $url, string $fallback = 'jpg'): string {
    $path = (string) parse_url($url, PHP_URL_PATH);
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    if (in_array($ext, ['jpg','jpeg','png','gif','webp'], true)) return $ext === 'jpeg' ? 'jpg' : $ext;
    return $fallback;
  }

  // -------------------------
  // URL -> LOCAL UPLOADS RESOLVE (DOMAIN-INDEPENDENT)
  // -------------------------

  private static function try_map_any_uploads_url_to_local(string $url): ?array {
    $url = trim($url);
    if ($url === '') return null;

    $up = wp_upload_dir();
    $basedir = isset($up['basedir']) ? (string)$up['basedir'] : '';
    $baseurl = isset($up['baseurl']) ? (string)$up['baseurl'] : '';
    if ($basedir === '' || $baseurl === '') return null;

    $path = (string) parse_url($url, PHP_URL_PATH);
    if ($path === '') return null;

    $needle = '/wp-content/uploads/';
    $pos = strpos($path, $needle);
    if ($pos === false) return null;

    $rel = substr($path, $pos + strlen($needle));
    if ($rel === false || $rel === '') return null;

    $rel = ltrim($rel, '/');

    $ext_candidates = ['jpg', 'jpeg', 'png', 'webp'];
    $rel_candidates = [$rel];
    $filename = pathinfo($rel, PATHINFO_FILENAME);
    $dirname = pathinfo($rel, PATHINFO_DIRNAME);
    $ext = strtolower(pathinfo($rel, PATHINFO_EXTENSION));
    $dir_prefix = ($dirname && $dirname !== '.') ? $dirname . '/' : '';

    if ($filename !== '') {
      foreach ($ext_candidates as $candidate_ext) {
        if ($candidate_ext === $ext) continue;
        $candidate_rel = $dir_prefix . $filename . '.' . $candidate_ext;
        if (!in_array($candidate_rel, $rel_candidates, true)) {
          $rel_candidates[] = $candidate_rel;
        }
      }
    }

    $real_base = realpath($basedir);
    if (!$real_base) return null;

    foreach ($rel_candidates as $candidate_rel) {
      $abs = rtrim($basedir, '/\\') . '/' . $candidate_rel;
      if (!file_exists($abs) || !is_file($abs) || !is_readable($abs)) {
        continue;
      }

      $real_abs = realpath($abs);
      if (!$real_abs) continue;
      if (strpos($real_abs, $real_base) !== 0) continue;

      $served_url = rtrim($baseurl, '/') . '/' . str_replace('\\', '/', $candidate_rel);
      $key = 'local:uploads:' . str_replace('\\', '/', $candidate_rel);

      return [
        'abs' => $real_abs,
        'rel' => str_replace('\\', '/', $candidate_rel),
        'url' => $served_url,
        'key' => $key,
      ];
    }

    return null;
  }

  private static function canonical_url_no_query(string $url): string {
    $url = trim($url);
    if ($url === '') return $url;

    $parts = wp_parse_url($url);
    if (!$parts || empty($parts['scheme']) || empty($parts['host'])) return $url;

    $canon = $parts['scheme'] . '://' . $parts['host'] . ($parts['path'] ?? '');
    return $canon;
  }

  private static function build_source_key_from_url(string $url): string {
    $canon = self::canonical_url_no_query($url);
    $mapped = self::try_map_any_uploads_url_to_local($canon);
    if (!$mapped) $mapped = self::try_map_any_uploads_url_to_local($url);
    if ($mapped && !empty($mapped['key'])) return (string)$mapped['key'];
    return 'url:' . $canon;
  }

  /**
   * v1.2.3 FIX:
   * - If META_SOURCE_KEY points to a wrong attachment (stale / poisoned key),
   *   ignore it by validating _wp_attached_file for local keys.
   */
  private static function find_attachment_by_source_key(string $source_key): int {
    $source_key = trim($source_key);
    if ($source_key === '') return 0;

    $q = new WP_Query([
      'post_type'      => 'attachment',
      'post_status'    => 'inherit',
      'posts_per_page' => 1,
      'fields'         => 'ids',
      'meta_query'     => [[
        'key'     => self::META_SOURCE_KEY,
        'value'   => $source_key,
        'compare' => '='
      ]]
    ]);
    if (empty($q->posts[0])) return 0;

    $aid = (int)$q->posts[0];

    // Validate local keys against attached file path
    if (strpos($source_key, 'local:uploads:') === 0) {
      $expected_rel = substr($source_key, strlen('local:uploads:'));
      $actual_rel   = (string)get_post_meta($aid, '_wp_attached_file', true);

      $expected_rel = str_replace('\\', '/', ltrim((string)$expected_rel, '/'));
      $actual_rel   = str_replace('\\', '/', ltrim((string)$actual_rel, '/'));

      if ($expected_rel !== '' && $actual_rel !== '' && $expected_rel !== $actual_rel) {
        hm_import_log('IMG KEY MISMATCH IGNORE aid='.$aid.' key='.$source_key.' expected='.$expected_rel.' actual='.$actual_rel);
        return 0;
      }
    }

    return $aid;
  }

  /**
   * v1.2.3 FIX:
   * - Remove unsafe attachment_url_to_postid() shortcut that could "poison" META_SOURCE_KEY
   *   by writing it onto a wrong attachment.
   */
  private static function get_or_create_attachment_from_local(array $mapped, string $source_key): int {
    $abs_path   = (string)($mapped['abs'] ?? '');
    $rel_path   = (string)($mapped['rel'] ?? '');
    $served_url = (string)($mapped['url'] ?? '');

    if ($abs_path === '' || $rel_path === '' || !file_exists($abs_path)) return 0;

    // NOTE: Intentionally NOT using attachment_url_to_postid($served_url) here (unsafe on some sites)

    $q = new WP_Query([
      'post_type'      => 'attachment',
      'post_status'    => 'inherit',
      'posts_per_page' => 1,
      'fields'         => 'ids',
      'meta_query'     => [[
        'key'     => '_wp_attached_file',
        'value'   => $rel_path,
        'compare' => '=',
      ]]
    ]);
    if (!empty($q->posts[0])) {
      $aid = (int)$q->posts[0];
      update_post_meta($aid, self::META_SOURCE_KEY, $source_key);
      return $aid;
    }

    require_once ABSPATH . 'wp-admin/includes/image.php';

    $filetype = wp_check_filetype(basename($abs_path), null);
    $mime = isset($filetype['type']) ? (string)$filetype['type'] : '';
    if ($mime === '') $mime = 'image/webp';

    $attachment = [
      'post_mime_type' => $mime,
      'post_title'     => sanitize_text_field(pathinfo($abs_path, PATHINFO_FILENAME)),
      'post_content'   => '',
      'post_status'    => 'inherit',
      'guid'           => $served_url !== '' ? esc_url_raw($served_url) : '',
    ];

    $attId = wp_insert_attachment($attachment, $abs_path, 0);
    if (is_wp_error($attId) || !$attId) {
      hm_import_log('IMG LOCAL ATTACH FAIL file=' . $abs_path . ' | ' . (is_wp_error($attId) ? $attId->get_error_message() : 'unknown'));
      return 0;
    }

    update_post_meta((int)$attId, '_wp_attached_file', $rel_path);

    $meta = wp_generate_attachment_metadata((int)$attId, $abs_path);
    if (is_array($meta)) {
      wp_update_attachment_metadata((int)$attId, $meta);
    }

    update_post_meta((int)$attId, self::META_SOURCE_KEY, $source_key);

    hm_import_log('IMG LOCAL OK attId=' . (int)$attId . ' rel=' . $rel_path);
    return (int)$attId;
  }

  private static function get_or_import_attachment_from_url(string $url, string $desired_base_filename = ''): int {
    $url = self::normalize_csv_url_value($url);
    if ($url === '') return 0;

    // CRITICAL FIX:
    // If this exact URL already exists in Media Library, use it immediately.
    // This is the original (working) behavior that was lost.
    $canon = self::canonical_url_no_query($url);
    $existing = attachment_url_to_postid($canon);
    if ($existing) {
      return (int)$existing;
    }

    $source_key = self::build_source_key_from_url($canon);

    $found = self::find_attachment_by_source_key($source_key);
    if ($found) return $found;

    $mapped = self::try_map_any_uploads_url_to_local($canon);
    if (!$mapped && $canon !== $url) {
      $mapped = self::try_map_any_uploads_url_to_local($url);
    }
    if ($mapped) {
      $attLocal = self::get_or_create_attachment_from_local($mapped, $source_key);
      if ($attLocal) return $attLocal;
    }

    // Keep this, but do NOT write source_key unless it truly matches our local key
    $maybe = attachment_url_to_postid($canon);
    if ($maybe) {
      // If we have a local key, validate _wp_attached_file before writing the key
      if (strpos($source_key, 'local:uploads:') === 0) {
        $expected_rel = substr($source_key, strlen('local:uploads:'));
        $actual_rel   = (string)get_post_meta((int)$maybe, '_wp_attached_file', true);

        $expected_rel = str_replace('\\', '/', ltrim((string)$expected_rel, '/'));
        $actual_rel   = str_replace('\\', '/', ltrim((string)$actual_rel, '/'));

        if ($expected_rel !== '' && $actual_rel !== '' && $expected_rel === $actual_rel) {
          update_post_meta((int)$maybe, self::META_SOURCE_KEY, $source_key);
          return (int)$maybe;
        }

        hm_import_log('IMG URL->ID mismatch (skip meta write) maybe='.$maybe.' key='.$source_key.' expected='.$expected_rel.' actual='.$actual_rel);
      } else {
        // url: keys are less stable, but still ok to tag
        update_post_meta((int)$maybe, self::META_SOURCE_KEY, $source_key);
        return (int)$maybe;
      }
    }

    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    hm_import_log('IMG START url=' . $url . ' canon=' . $canon . ' key=' . $source_key);

    $max_tries = 3;
    $timeout   = 35;

    $tmp = null;
    for ($try = 1; $try <= $max_tries; $try++) {
      hm_import_log("IMG TRY {$try}/{$max_tries} timeout={$timeout} url={$url}");
      $tmp = download_url($url, $timeout);

      if (!is_wp_error($tmp)) break;

      hm_import_log('IMG TRY FAIL url=' . $url . ' | ' . $tmp->get_error_message());

      if (stripos($tmp->get_error_message(), 'forbidden') !== false) {
        $mapped2 = self::try_map_any_uploads_url_to_local($url);
        if ($mapped2) {
          hm_import_log('IMG LOCAL FALLBACK due to Forbidden url=' . $url);
          $attLocal2 = self::get_or_create_attachment_from_local($mapped2, $source_key);
          if ($attLocal2) return $attLocal2;
        }
      }

      sleep($try * 2);
    }

    if (is_wp_error($tmp)) {
      hm_import_log('IMG GIVE UP url=' . $url);
      return 0;
    }

    $filename = wp_basename((string)parse_url($canon, PHP_URL_PATH));
    if (!$filename) $filename = 'image.jpg';

    if ($desired_base_filename !== '') {
      $ext  = self::guess_ext_from_url_or_mime($canon, 'jpg');
      $base = self::sanitize_filename_base($desired_base_filename);
      $filename = $base . '.' . $ext;
    }

    $file = [
      'name'     => $filename,
      'type'     => function_exists('mime_content_type') ? (mime_content_type($tmp) ?: 'image/jpeg') : 'image/jpeg',
      'tmp_name' => $tmp,
      'error'    => 0,
      'size'     => @filesize($tmp) ?: 0,
    ];

    $attId = media_handle_sideload($file, 0);

    if (is_wp_error($attId)) {
      hm_import_log('IMG media_handle_sideload WP_Error url=' . $url . ' | ' . $attId->get_error_message());
      if (is_string($tmp) && file_exists($tmp)) { @unlink($tmp); }
      return 0;
    }

    if (is_string($tmp) && file_exists($tmp)) { @unlink($tmp); }

    update_post_meta((int)$attId, self::META_SOURCE_KEY, $source_key);
    hm_import_log('IMG OK attId=' . (int)$attId . ' key=' . $source_key);

    return (int)$attId;
  }

  // -------------------------
  // EXTRA INFO / ATTRS
  // -------------------------

  private static function apply_extra_info_attributes(int $productId, array $d) {
    foreach (self::$extra_info_fields as $k) {
      if (!isset($d[$k])) continue;
      $v = trim((string)$d[$k]);
      if ($v === '') continue;
      update_post_meta($productId, '_hm_'.$k, $v);
    }
  }

  private static function set_parent_attributes_object(int $productId) {
    $product = wc_get_product($productId);
    if (!$product) return;

    $attrs = [];

    foreach (self::$filter_tax_attrs as $src => $tax) {
      if (!taxonomy_exists($tax)) continue;

      $terms = wp_get_post_terms($productId, $tax, ['fields' => 'ids']);
      if (!$terms) continue;

      $a = new WC_Product_Attribute();
      $a->set_id(wc_attribute_taxonomy_id_by_name($tax));
      $a->set_name($tax);
      $a->set_options($terms);
      $a->set_visible(true);
      $a->set_variation(true);
      $attrs[] = $a;
    }

    foreach (self::$extra_info_fields as $k) {
      $v = trim((string)get_post_meta($productId, '_hm_'.$k, true));
      if ($v === '') continue;

      $label = ucwords(str_replace('_',' ', $k));
      $a = new WC_Product_Attribute();
      $a->set_name($label);
      $a->set_options([$v]);
      $a->set_visible(true);
      $a->set_variation(false);
      $attrs[] = $a;
    }

    $product->set_attributes($attrs);
    $product->save();
  }

  private static function ensure_global_attribute_taxonomy(string $tax, string $srcName) {
    if (taxonomy_exists($tax)) return;

    $attrName = str_replace('pa_', '', $tax);
    $label = ucwords(str_replace(['-','_'], ' ', $srcName));

    $existing = wc_get_attribute_taxonomies();
    foreach ($existing as $a) {
      if ($a->attribute_name === $attrName) {
        register_taxonomy($tax, ['product'], ['hierarchical' => false, 'label' => $label, 'show_ui' => false]);
        return;
      }
    }

    $id = wc_create_attribute([
      'name'         => $label,
      'slug'         => $attrName,
      'type'         => 'select',
      'order_by'     => 'menu_order',
      'has_archives' => false,
    ]);
    if (is_wp_error($id)) return;

    register_taxonomy($tax, ['product'], ['hierarchical' => false, 'label' => $label, 'show_ui' => false]);
    delete_transient('wc_attribute_taxonomies');
  }

  private static function ensure_term_and_get(string $tax, string $name) {
    if (!taxonomy_exists($tax)) return null;

    $term = get_term_by('name', $name, $tax);
    if ($term && !is_wp_error($term)) return $term;

    $exists = term_exists($name, $tax);
    if (!$exists) {
      $res = wp_insert_term($name, $tax);
      if (is_wp_error($res)) return null;
      $termId = (int)$res['term_id'];
      return get_term($termId, $tax);
    }

    $termId = is_array($exists) ? (int)$exists['term_id'] : (int)$exists;
    return get_term($termId, $tax);
  }

  private static function set_meta_if_present(int $productId, string $key, array $d) {
    if (!isset($d[$key])) return;
    $v = trim((string)$d[$key]);
    if ($v === '') return;
    update_post_meta($productId, '_hm_'.$key, $v);
  }

  // -------------------------
  // EXPORT HELPERS
  // -------------------------

  private static function get_hm_pvi_meta_export(int $productId): array {
    $enabled      = (string) get_post_meta($productId, '_hm_pvi_enabled', true);
    $type         = (string) get_post_meta($productId, '_hm_pvi_type', true);
    $youtube_url  = (string) get_post_meta($productId, '_hm_pvi_youtube_url', true);
    $poster_id    = (int) get_post_meta($productId, '_hm_pvi_poster_id', true);
    $poster_url   = (string) get_post_meta($productId, '_hm_pvi_poster_url', true);
    $placement    = (string) get_post_meta($productId, '_hm_pvi_placement', true);
    $lightbox     = (string) get_post_meta($productId, '_hm_pvi_lightbox', true);
    $btn_label    = (string) get_post_meta($productId, '_hm_pvi_button_label', true);

    $yt = '';
    if ($enabled === 'yes' && ($type === '' || $type === 'youtube')) {
      $yt = $youtube_url;
    }

    $poster = $poster_url;
    if ($poster === '' && $poster_id) {
      $u = wp_get_attachment_url($poster_id);
      if ($u) $poster = $u;
    }

    if ($placement === '') $placement = 'below_gallery';
    $lb = ($lightbox === 'yes') ? 'yes' : '';

    return [
      'youtube_url'        => $yt,
      'video_poster_url'   => $poster,
      'video_placement'    => $placement,
      'video_lightbox'     => $lb,
      'video_button_label' => $btn_label,
    ];
  }

  private static function get_variation_image_url(WC_Product_Variation $v): string {
    $id = (int) $v->get_image_id();
    if ($id) {
      $u = wp_get_attachment_url($id);
      return $u ? (string)$u : '';
    }
    return '';
  }

  private static function get_parent_images_urls(WC_Product $p): array {
    $urls = [];
    $fid = (int) $p->get_image_id();
    if ($fid) {
      $u = wp_get_attachment_url($fid);
      if ($u) $urls[] = (string)$u;
    }
    $g = $p->get_gallery_image_ids();
    if ($g) {
      foreach ($g as $gid) {
        $u = wp_get_attachment_url((int)$gid);
        if ($u) $urls[] = (string)$u;
        if (count($urls) >= 8) break;
      }
    }
    $urls = array_values(array_unique(array_filter($urls)));
    return array_slice($urls, 0, 8);
  }

  private static function get_category_path_levels(int $productId): array {
    $levels = ['', '', '', '', ''];

    $terms = wp_get_post_terms($productId, 'product_cat', ['fields' => 'all']);
    if (is_wp_error($terms) || !$terms) return $levels;

    $deepest = null;
    $deepest_depth = -1;

    foreach ($terms as $t) {
      $anc = get_ancestors((int)$t->term_id, 'product_cat');
      $depth = is_array($anc) ? count($anc) : 0;
      if ($depth > $deepest_depth) {
        $deepest_depth = $depth;
        $deepest = $t;
      }
    }
    if (!$deepest) return $levels;

    $path = [];
    $ancestors = get_ancestors((int)$deepest->term_id, 'product_cat');
    $ancestors = is_array($ancestors) ? array_reverse($ancestors) : [];
    foreach ($ancestors as $aid) {
      $at = get_term((int)$aid, 'product_cat');
      if ($at && !is_wp_error($at)) $path[] = (string)$at->name;
    }
    $path[] = (string)$deepest->name;

    for ($i=0; $i<5; $i++) {
      $levels[$i] = $path[$i] ?? '';
    }
    return $levels;
  }

  private static function get_product_tags_csv(int $productId): string {
    $terms = wp_get_post_terms($productId, 'product_tag', ['fields' => 'names']);
    if (is_wp_error($terms) || !$terms) return '';
    $terms = array_values(array_unique(array_filter(array_map('trim', $terms))));
    return implode(',', $terms);
  }

  private static function variation_attr_to_value(string $tax, array $attrs): string {
    if (!array_key_exists($tax, $attrs)) return '';

    $val = (string) $attrs[$tax];
    if ($val === '') return '';

    if (taxonomy_exists($tax)) {
      $term = get_term_by('slug', $val, $tax);
      if ($term && !is_wp_error($term)) return (string)$term->name;

      $term2 = get_term_by('name', $val, $tax);
      if ($term2 && !is_wp_error($term2)) return (string)$term2->name;
    }

    return $val;
  }

  // -------------------------
  // CSV HELPERS
  // -------------------------

  private static function row_to_assoc(array $row, array $colIndex): array {
    $out = [];
    foreach ($colIndex as $name => $idx) {
      $out[$name] = isset($row[$idx]) ? (string)$row[$idx] : '';
    }
    return $out;
  }

  private static function guess_delimiter(string $line): string {
    $c = substr_count($line, ',');
    $s = substr_count($line, ';');
    $t = substr_count($line, "\t");
    if ($s >= $c && $s >= $t) return ';';
    if ($t >= $c && $t >= $s) return "\t";
    return ',';
  }

  private static function normalize_csv_url_value($value): string {
    $value = trim((string)$value);
    if ($value === '') return '';

    $value = preg_replace('/^\s*=\s*/', '', $value);
    $value = trim((string)$value);

    $pairs = [
      '"' => '"',
      "'" => "'",
      '“' => '”',
      '‘' => '’',
    ];
    $len_fn = function_exists('mb_strlen') ? 'mb_strlen' : 'strlen';
    $sub_fn = function_exists('mb_substr') ? 'mb_substr' : 'substr';
    $changed = true;
    while ($changed && $value !== '') {
      $changed = false;
      $first = $sub_fn($value, 0, 1);
      $last = $sub_fn($value, -1, 1);
      if (isset($pairs[$first]) && $pairs[$first] === $last) {
        $length = $len_fn($value);
        if ($length <= 1) break;
        $value = $sub_fn($value, 1, $length - 2);
        $value = trim($value);
        $changed = true;
      }
    }

    $value = preg_replace('/\s+/', '', (string)$value);
    return (string)$value;
  }

  private static function norm_header(string $h): string {
    $h = trim($h);

    $map = [
      'Ç'=>'C','Ö'=>'O','Ş'=>'S','İ'=>'I','I'=>'I','Ü'=>'U','Ğ'=>'G',
      'ç'=>'c','ö'=>'o','ş'=>'s','ı'=>'i','i'=>'i','ü'=>'u','ğ'=>'g',
      '’'=>"'",'“'=>'"','”'=>'"'
    ];
    $h = strtr($h, $map);

    if (function_exists('iconv')) {
      $tmp = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $h);
      if ($tmp !== false && $tmp !== '') $h = $tmp;
    }

    $h = strtolower($h);
    $h = preg_replace('/[^\w\s-]/', ' ', $h);
    $h = preg_replace('/\s+/', '_', $h);
    $h = preg_replace('/_+/', '_', $h);
    $h = trim($h, '_');

    return $h;
  }

  private static function alias_header(string $h): string {
    $aliases = [
      'urun_aciklama'         => 'product_description',
      'urun_aciklamasi'       => 'product_description',
      'aciklama'              => 'product_description',
      'kisa_aciklama'         => 'short_description',

      'product_description'   => 'product_description',
      'short_description'     => 'short_description',

      'seo_title'             => 'seo_title',
      'seo_basligi'           => 'seo_title',
      'rank_math_title'       => 'seo_title',

      'meta_description'      => 'meta_description',
      'meta_aciklama'         => 'meta_description',
      'rank_math_description' => 'meta_description',

      'focus_keyword'           => 'focus_keyword',
      'odak_anahtar_kelime'     => 'focus_keyword',
      'rank_math_focus_keyword' => 'focus_keyword',

      'image_alt'         => 'image_alt',
      'gorsel_alt'        => 'image_alt',
      'alt_metin'         => 'image_alt',
      'alt_text'          => 'image_alt',
      'image_alt_text'    => 'image_alt',

      'url_slug'              => 'url_slug',
      'slug'                  => 'url_slug',
      'permalink'             => 'url_slug',

      'etiket'                => 'etiketler',
      'etiketler'             => 'etiketler',
      'tags'                  => 'etiketler',
      'tag'                   => 'etiketler',

      'youtube'               => 'youtube_url',
      'youtube_url'           => 'youtube_url',
      'video'                 => 'youtube_url',
      'video_url'             => 'youtube_url',
      'video_link'            => 'youtube_url',
      'video_poster'          => 'video_poster_url',
      'video_poster_url'      => 'video_poster_url',
      'poster_url'            => 'video_poster_url',

      'video_placement'       => 'video_placement',
      'video_lightbox'        => 'video_lightbox',
      'video_button_label'    => 'video_button_label',

      'ana_kategori'          => 'ana_kategori',
      'alt_kategori'          => 'alt_kategori',
      'alt_kategori2'         => 'alt_kategori_2',
      'alt_kategori_2'        => 'alt_kategori_2',
      'alt_kategori3'         => 'alt_kategori_3',
      'alt_kategori_3'        => 'alt_kategori_3',
      'alt_kategori4'         => 'alt_kategori_4',
      'alt_kategori_4'        => 'alt_kategori_4',

      'olcu_miktari_listesi'  => 'olcu_miktari',
    ];
    return $aliases[$h] ?? $h;
  }

  private static function clean($v): string {
    return wp_kses_post((string)$v);
  }

  private static function to_price($v) {
    $v = trim((string)$v);
    if ($v === '') return null;
    $v = str_replace([' ', ','], ['', '.'], $v);
    return $v;
  }
}
endif;

HM_Master_Importer::init();


if ( ! function_exists( 'hmpro_render_product_importer_page' ) ) {
  function hmpro_render_product_importer_page() {
    if ( class_exists( 'HM_Master_Importer' ) && method_exists( 'HM_Master_Importer', 'page' ) ) {
      HM_Master_Importer::page();
    } else {
      echo '<div class="notice notice-error"><p>Product Importer could not be initialized.</p></div>';
    }
  }
}

if ( ! function_exists( 'hmpro_render_product_exporter_page' ) ) {
  function hmpro_render_product_exporter_page() {
    if ( class_exists( 'HM_Master_Importer' ) && method_exists( 'HM_Master_Importer', 'export_page' ) ) {
      HM_Master_Importer::export_page();
    } else {
      echo '<div class="notice notice-error"><p>Product Exporter could not be initialized.</p></div>';
    }
  }
}
