<?php
/**
 * Plugin Name: HM Basic Çeviri (Inline Only + Shortcode) — FINAL v1.4.3
 * Description: Google Translate Inline. Switcher only via shortcode. URL prefix (/de/, /fr/) can auto-select language. Dropdown is notranslate and uses short codes (TR/EN/DE...). Includes robust auto-translate boot (googtrans cookie + one-time reload fallback).
 * Version: 1.4.3
 * Author: HM
 */

if (!defined('ABSPATH')) exit;

final class HM_Basic_Ceviri_Inline {
  const OPT_KEY = 'hm_basic_ceviri_inline_opts';
  const COOKIE  = 'hm_bc_lang';

  public static function boot() {
    add_action('admin_menu', [__CLASS__, 'admin_menu']);
    add_action('admin_init', [__CLASS__, 'register_settings']);
    add_shortcode('hm_translate_inline', [__CLASS__, 'shortcode']);
    add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
  }

  public static function defaults() {
    return [
      'enabled'          => 1,
      'allowed_langs'    => ['tr','en','de','fr','it','ru','ro','el','bs','mk'],
      'default_lang'     => 'tr',
      'label'            => 'Language',
      'use_theme_colors' => 1,
      'exclude_paths'    => "/checkout/\n/cart/\n/my-account/",
      'shortcode_token'  => '',
      // URL prefix detection (enable/disable)
      'enable_url_detect'=> 1,
    ];
  }

  public static function get_opts() {
    $saved = get_option(self::OPT_KEY, []);
    $opts  = wp_parse_args(is_array($saved) ? $saved : [], self::defaults());

    $opts['enabled']           = (int)!!$opts['enabled'];
    $opts['use_theme_colors']  = (int)!!$opts['use_theme_colors'];
    $opts['enable_url_detect'] = (int)!!($opts['enable_url_detect'] ?? 1);

    $opts['allowed_langs']   = array_values(array_filter(array_map([__CLASS__, 'normalize_lang_code'], (array)($opts['allowed_langs'] ?? []))));
    $opts['default_lang']    = self::normalize_lang_code($opts['default_lang'] ?? 'tr');
    $opts['label']           = sanitize_text_field((string)($opts['label'] ?? 'Language'));
    $opts['exclude_paths']   = (string)($opts['exclude_paths'] ?? '');
    $opts['shortcode_token'] = sanitize_text_field((string)($opts['shortcode_token'] ?? ''));

    if ($opts['shortcode_token'] === '') {
      $opts['shortcode_token'] = wp_generate_password(12, false, false);
      update_option(self::OPT_KEY, array_merge($opts, ['shortcode_token' => $opts['shortcode_token']]));
    }

    if (empty($opts['allowed_langs'])) {
      $opts['allowed_langs'] = self::defaults()['allowed_langs'];
    }

    if (!in_array($opts['default_lang'], $opts['allowed_langs'], true)) {
      $opts['default_lang'] = $opts['allowed_langs'][0];
    }

    return $opts;
  }

  private static function lang_map() {
    return [
      'tr' => 'TR',
      'en' => 'EN',
      'de' => 'DE',
      'fr' => 'FR',
      'es' => 'ES',
      'it' => 'IT',
      'pt' => 'PT',
      'ru' => 'RU',
      'ar' => 'AR',
      'fa' => 'FA',
      'nl' => 'NL',
      'pl' => 'PL',
      'ro' => 'RO',
      'sv' => 'SV',
      'no' => 'NO',
      'da' => 'DA',
      'fi' => 'FI',
      'uk' => 'UK',
      'el' => 'EL',
      'bg' => 'BG',
      'bs' => 'BS',
      'hr' => 'HR',
      'sr' => 'SR',
      'sq' => 'SQ',
      'mk' => 'MK',
      'sl' => 'SL',
      'hu' => 'HU',
      'cs' => 'CS',
      'sk' => 'SK',
      'id' => 'ID',
      'ms' => 'MS',
      'hi' => 'HI',
      'ur' => 'UR',
      'bn' => 'BN',
      'ta' => 'TA',
      'vi' => 'VI',
      'th' => 'TH',
      'ja' => 'JA',
      'ko' => 'KO',
      'zh-CN' => 'ZH',
      'zh-TW' => 'ZH-TW',
    ];
  }

  public static function normalize_lang_code($code) {
    $code = trim((string)$code);
    $code = preg_replace('/[^a-zA-Z\-]/', '', $code);
    if (stripos($code, 'zh-') === 0) {
      $parts = explode('-', $code, 2);
      $tail = strtoupper($parts[1] ?? '');
      return $tail ? 'zh-' . $tail : 'zh';
    }
    return strtolower($code);
  }

  private static function is_excluded_path() {
    $opts = self::get_opts();
    $paths = array_filter(array_map('trim', preg_split("/\r\n|\n|\r/", (string)$opts['exclude_paths'])));
    if (empty($paths)) return false;

    $req = isset($_SERVER['REQUEST_URI']) ? (string)$_SERVER['REQUEST_URI'] : '';
    foreach ($paths as $p) {
      if ($p === '') continue;
      if (strpos($req, $p) !== false) return true;
    }
    return false;
  }

  private static function builder_layout_has_shortcode( $layout, $shortcode ) {
    if ( empty( $layout['regions'] ) || ! is_array( $layout['regions'] ) ) {
      return false;
    }

    foreach ( $layout['regions'] as $rows ) {
      if ( empty( $rows ) || ! is_array( $rows ) ) {
        continue;
      }
      foreach ( $rows as $row ) {
        if ( empty( $row['columns'] ) || ! is_array( $row['columns'] ) ) {
          continue;
        }
        foreach ( $row['columns'] as $column ) {
          if ( empty( $column['components'] ) || ! is_array( $column['components'] ) ) {
            continue;
          }
          foreach ( $column['components'] as $component ) {
            if ( empty( $component['settings'] ) || ! is_array( $component['settings'] ) ) {
              continue;
            }
            $content = isset( $component['settings']['content'] ) ? (string) $component['settings']['content'] : '';
            if ( '' !== $content && has_shortcode( $content, $shortcode ) ) {
              return true;
            }
          }
        }
      }
    }

    return false;
  }

  private static function should_enqueue_lazy_loader() {
    $post = get_post();
    if ( $post && has_shortcode( (string) $post->post_content, 'hm_translate_inline' ) ) {
      return true;
    }

    if ( function_exists( 'hmpro_builder_get_layout' ) ) {
      $layout = hmpro_builder_get_layout( 'header' );
      if ( self::builder_layout_has_shortcode( $layout, 'hm_translate_inline' ) ) {
        return true;
      }
    }

    return false;
  }

  private static function theme_css_vars($useTheme) {
    if (!$useTheme) {
      return [
        'bg' => '#ffffff',
        'text' => '#111111',
        'border' => 'rgba(0,0,0,.15)',
        'accent' => '#2271b1',
      ];
    }
    return [
      'bg'     => 'var(--ast-global-color-5, #ffffff)',
      'text'   => 'var(--ast-global-color-3, #111111)',
      'border' => 'rgba(0,0,0,.15)',
      'accent' => 'var(--ast-global-color-0, #2271b1)',
    ];
  }

  public static function enqueue_assets() {
    $opts = self::get_opts();
    if (!$opts['enabled']) return;
    if (self::is_excluded_path()) return;

    $vars = self::theme_css_vars((int)$opts['use_theme_colors']);

    $css = "
    :root{
      --hm-bc-bg: {$vars['bg']};
      --hm-bc-text: {$vars['text']};
      --hm-bc-border: {$vars['border']};
      --hm-bc-accent: {$vars['accent']};
    }
    .hm-bc-wrap{display:inline-flex;gap:8px;align-items:center;font:inherit}
    .hm-bc-label{font:inherit;opacity:.9;color:var(--hm-bc-text)}
    .hm-bc-select{
      font:inherit;
      padding:6px 10px;
      border:1px solid var(--hm-bc-border);
      border-radius:10px;
      background:var(--hm-bc-bg);
      color:var(--hm-bc-text);
      max-width:240px;
      outline:none;
      cursor:pointer;
    }
    .hm-bc-select:focus{border-color:var(--hm-bc-accent); box-shadow:0 0 0 3px rgba(0,0,0,.06)}
    .notranslate, .notranslate *{ translate: no !important; }
    ";

    wp_register_style('hm-bc-inline-style', false);
    wp_enqueue_style('hm-bc-inline-style');
    wp_add_inline_style('hm-bc-inline-style', $css);

    $data = [
      'defaultLang'     => $opts['default_lang'],
      'cookieName'      => self::COOKIE,
      'allowedLangs'    => $opts['allowed_langs'],
      'pageLanguage'    => $opts['default_lang'], // treat site base language as default_lang (usually tr)
      'enableUrlDetect' => (int)$opts['enable_url_detect'],
    ];

    wp_register_script('hm-bc-inline-script', false, [], '1.4.3', true);
    wp_enqueue_script('hm-bc-inline-script');
    wp_add_inline_script('hm-bc-inline-script', 'window.HMBC_INLINE=' . wp_json_encode($data) . ';');

    if ( ! is_admin() && self::should_enqueue_lazy_loader() ) {
      wp_enqueue_script(
        'hm-translate-lazy',
        get_template_directory_uri() . '/assets/js/hm-translate-lazy.js',
        [],
        defined( 'HMPRO_VERSION' ) ? HMPRO_VERSION : '1.0.0',
        true
      );
    }

    $js = <<<JS
(function(){
  function setCookie(name,value,days){
    var d=new Date(); d.setTime(d.getTime()+days*24*60*60*1000);
    document.cookie=name+"="+encodeURIComponent(value)+"; expires="+d.toUTCString()+"; path=/; SameSite=Lax";
  }
  function getCookie(name){
    var m=document.cookie.match(new RegExp('(?:^|; )'+name.replace(/([.$?*|{}()\\[\\]\\\\\\/\\+^])/g,'\\\\$1')+'=([^;]*)'));
    return m?decodeURIComponent(m[1]):null;
  }

  function detectLangFromPath(){
    try{
      if(!window.HMBC_INLINE.enableUrlDetect) return null;
      var path = window.location.pathname || "/";
      // /de/ , /fr , /ru/...
      var m = path.match(/^\\/([a-zA-Z]{2}(?:-[A-Z]{2})?)(?:\\/|$)/);
      if(!m) return null;
      var raw = m[1];
      // normalize like plugin does
      var code = raw;
      if(/^zh-/i.test(code)){
        var parts = code.split('-',2);
        code = 'zh-' + (parts[1]||'').toUpperCase();
      } else {
        code = code.toLowerCase();
      }
      if(Array.isArray(window.HMBC_INLINE.allowedLangs) && window.HMBC_INLINE.allowedLangs.indexOf(code) !== -1){
        return code;
      }
      return null;
    }catch(e){ return null; }
  }

  function setGoogTrans(lang){
    // google translate cookie: /<source>/<target>
    setCookie('googtrans', '/'+(window.HMBC_INLINE.pageLanguage||'auto')+'/' + lang, 1);
  }

  function ensureInlineTarget(){
    if(document.getElementById("hm-bc-inline-target")) return;
    var div=document.createElement("div");
    div.id="hm-bc-inline-target";
    div.style.position="fixed";
    div.style.left="-9999px";
    div.style.top="-9999px";
    document.body.appendChild(div);
  }

  function ensureGoogleTranslateInline(){
    if (window.google && window.google.translate && window.google.translate.TranslateElement) return true;
    if (document.getElementById("google-translate-inline-script")) return true;
    if (typeof window.HMBCInlineLoadGoogle === "function") {
      window.HMBCInlineLoadGoogle();
      return true;
    }
    var s=document.createElement("script");
    s.id="google-translate-inline-script";
    s.src="https://translate.google.com/translate_a/element.js?cb=HMBCInlineInit";
    s.async=true;
    document.head.appendChild(s);
    return true;
  }

  window.HMBCInlineInit = function(){
    try{
      var included = (Array.isArray(window.HMBC_INLINE.allowedLangs) ? window.HMBC_INLINE.allowedLangs : []).join(',');
      new google.translate.TranslateElement({
        pageLanguage: window.HMBC_INLINE.pageLanguage || "tr",
        includedLanguages: included || undefined,
        autoDisplay: false
      }, "hm-bc-inline-target");
    }catch(e){}
  };

  function applyInlineLang(lang){
    var tries=0;
    var t=setInterval(function(){
      tries++;
      var sel=document.querySelector("select.goog-te-combo");
      if(sel){
        sel.value = lang;
        sel.dispatchEvent(new Event("change"));
        clearInterval(t);
      }
      if(tries>60) clearInterval(t);
    }, 250);
  }

  function oneTimeReloadIfNeeded(lang){
    // some environments only apply translation if googtrans exists before google script init.
    // We'll enforce it once per lang with a query flag.
    try{
      var p = new URLSearchParams(window.location.search);
      var key = "hm_bc_boot";
      if(p.get(key) === lang) return;
      // If translation not active (no banner, no html class) after a short delay, reload once
      setTimeout(function(){
        var html = document.documentElement;
        var hasTrans = html.className && html.className.indexOf('translated-') !== -1;
        // also check if body has goog-te-banner-frame or iframe exists
        var frame = document.querySelector('iframe.goog-te-banner-frame') || document.getElementById(':1.container');
        if(!hasTrans && !frame){
          p.set(key, lang);
          var url = window.location.pathname + "?" + p.toString() + window.location.hash;
          window.location.replace(url);
        }
      }, 1800);
    }catch(e){}
  }

  function bindSelects(){
    var selects=document.querySelectorAll(".hm-bc-select");
    if(!selects.length) return;

    var urlLang = detectLangFromPath();
    var cookieLang = getCookie(window.HMBC_INLINE.cookieName);
    var saved = urlLang || cookieLang || window.HMBC_INLINE.defaultLang;
    var pendingLang = saved;

    if(saved){
      // persist our cookie as well so subsequent pages keep language even if prefix missing
      setCookie(window.HMBC_INLINE.cookieName, saved, 60);
      setGoogTrans(saved);
    }

    selects.forEach(function(sel){
      if(saved) sel.value = saved;
      sel.addEventListener("change", function(e){
        var lang = e.target.value;
        pendingLang = lang;
        setCookie(window.HMBC_INLINE.cookieName, lang, 60);
        setGoogTrans(lang);
        ensureInlineTarget();
        ensureGoogleTranslateInline();
        applyInlineLang(lang);
      });
    });

    function activateSaved(){
      if(!pendingLang) return;
      ensureInlineTarget();
      ensureGoogleTranslateInline();
      applyInlineLang(pendingLang);
      oneTimeReloadIfNeeded(pendingLang);
    }

    window.addEventListener("hmpro:translate:inline:activate", activateSaved);
  }

  function init(){ bindSelects(); }

  if(document.readyState==="loading"){
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
JS;

    wp_add_inline_script('hm-bc-inline-script', $js);
  }

  public static function shortcode($atts) {
    $opts = self::get_opts();
    if (!$opts['enabled']) return '';
    if (self::is_excluded_path()) return '';

    $atts = shortcode_atts([
      'label' => '',
      'class' => '',
      'token' => '',
      'hide_label' => '1',
    ], (array)$atts, 'hm_translate_inline');

    $token = sanitize_text_field((string)$atts['token']);
    if ($token !== '' && !hash_equals($opts['shortcode_token'], $token)) {
      return '';
    }

    $map = self::lang_map();

    $allowed = $opts['allowed_langs'];
    if (empty($allowed)) $allowed = [$opts['default_lang']];
    $allowed = array_values(array_unique(array_map([__CLASS__,'normalize_lang_code'], $allowed)));

    $label = $atts['label'] !== '' ? sanitize_text_field((string)$atts['label']) : $opts['label'];
    $hideLabel = ((string)$atts['hide_label'] === '1');
    $wrap_class = trim((string)$atts['class']);

    ob_start();
    ?>
    <div class="hm-bc-wrap <?php echo esc_attr($wrap_class); ?>" data-hm-translate-inline="1">
      <?php if (!$hideLabel && !empty($label)) : ?>
        <span class="hm-bc-label notranslate" translate="no"><?php echo esc_html($label); ?></span>
      <?php endif; ?>
      <select class="hm-bc-select notranslate" translate="no" aria-label="<?php echo esc_attr($label ?: 'Language'); ?>">
        <?php foreach ($allowed as $code) :
          $name = isset($map[$code]) ? $map[$code] : strtoupper($code);
        ?>
          <option class="notranslate" translate="no" value="<?php echo esc_attr($code); ?>"><?php echo esc_html($name); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php
    return (string)ob_get_clean();
  }

  public static function admin_menu() {
    add_options_page(
      'HM Basic Çeviri (Inline)',
      'HM Basic Çeviri',
      'manage_options',
      'hm-basic-ceviri-inline',
      [__CLASS__, 'settings_page']
    );
  }

  public static function register_settings() {
    register_setting('hm_basic_ceviri_inline_group', self::OPT_KEY, [__CLASS__, 'sanitize_opts']);
  }

  public static function sanitize_opts($input) {
    $d = self::defaults();
    $out = [];

    $out['enabled'] = !empty($input['enabled']) ? 1 : 0;
    $out['use_theme_colors'] = !empty($input['use_theme_colors']) ? 1 : 0;
    $out['enable_url_detect'] = !empty($input['enable_url_detect']) ? 1 : 0;

    $allowed = isset($input['allowed_langs']) ? (array)$input['allowed_langs'] : $d['allowed_langs'];
    $allowed = array_values(array_unique(array_filter(array_map([__CLASS__,'normalize_lang_code'], $allowed))));
    $out['allowed_langs'] = $allowed ?: $d['allowed_langs'];

    $out['default_lang'] = isset($input['default_lang']) ? self::normalize_lang_code($input['default_lang']) : $d['default_lang'];
    if (!in_array($out['default_lang'], $out['allowed_langs'], true)) {
      $out['default_lang'] = $out['allowed_langs'][0];
    }

    $out['label'] = isset($input['label']) ? sanitize_text_field($input['label']) : $d['label'];
    $out['exclude_paths'] = isset($input['exclude_paths']) ? (string)$input['exclude_paths'] : $d['exclude_paths'];

    $tok = isset($input['shortcode_token']) ? sanitize_text_field((string)$input['shortcode_token']) : '';
    $out['shortcode_token'] = $tok !== '' ? $tok : wp_generate_password(12, false, false);

    return $out;
  }

  public static function settings_page() {
    if (!current_user_can('manage_options')) return;

    $opts = self::get_opts();
    $map  = self::lang_map();

    $shortcode = '[hm_translate_inline token="'.$opts['shortcode_token'].'" hide_label="1"]';
    ?>
    <div class="wrap">
      <h1>HM Basic Çeviri (Inline Only)</h1>

      <div style="padding:12px 14px;border:1px solid #dcdcde;border-radius:10px;background:#fff;margin:14px 0;">
        <h2 style="margin:0 0 10px;">Shortcode (kopyala)</h2>
        <input type="text" class="large-text code" readonly value="<?php echo esc_attr($shortcode); ?>" onclick="this.select();" />
      </div>

      <form method="post" action="options.php">
        <?php settings_fields('hm_basic_ceviri_inline_group'); ?>

        <table class="form-table" role="presentation">
          <tr>
            <th scope="row">Enable</th>
            <td>
              <label><input type="checkbox" name="<?php echo esc_attr(self::OPT_KEY); ?>[enabled]" value="1" <?php checked($opts['enabled'], 1); ?> /> Aktif</label>
            </td>
          </tr>

          <tr>
            <th scope="row">URL Detect</th>
            <td>
              <label><input type="checkbox" name="<?php echo esc_attr(self::OPT_KEY); ?>[enable_url_detect]" value="1" <?php checked($opts['enable_url_detect'], 1); ?> /> /de/ /fr/ gibi prefix ile dili otomatik seç</label>
            </td>
          </tr>

          <tr>
            <th scope="row">Use Theme Colors</th>
            <td>
              <label><input type="checkbox" name="<?php echo esc_attr(self::OPT_KEY); ?>[use_theme_colors]" value="1" <?php checked($opts['use_theme_colors'], 1); ?> /> Tema renklerini kullan</label>
            </td>
          </tr>

          <tr>
            <th scope="row">Allowed Languages</th>
            <td>
              <fieldset>
                <?php foreach ($map as $code => $short) : ?>
                  <label style="display:inline-block;margin:0 16px 8px 0;">
                    <input type="checkbox" name="<?php echo esc_attr(self::OPT_KEY); ?>[allowed_langs][]" value="<?php echo esc_attr($code); ?>" <?php checked(in_array($code, $opts['allowed_langs'], true)); ?> />
                    <?php echo esc_html($short . " ($code)"); ?>
                  </label>
                <?php endforeach; ?>
              </fieldset>
              <p class="description">Not: Auto URL detect sadece burada işaretli dilleri yakalar.</p>
            </td>
          </tr>

          <tr>
            <th scope="row">Default Language</th>
            <td>
              <select name="<?php echo esc_attr(self::OPT_KEY); ?>[default_lang]">
                <?php foreach ($opts['allowed_langs'] as $code) :
                  $code = self::normalize_lang_code($code);
                  $name = isset($map[$code]) ? $map[$code] : strtoupper($code);
                ?>
                  <option value="<?php echo esc_attr($code); ?>" <?php selected($opts['default_lang'], $code); ?>>
                    <?php echo esc_html($name . " ($code)"); ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <p class="description">Sayfanın ana dili. Genelde tr bırak.</p>
            </td>
          </tr>

          <tr>
            <th scope="row">Exclude Paths</th>
            <td>
              <textarea class="large-text" rows="5" name="<?php echo esc_attr(self::OPT_KEY); ?>[exclude_paths]"><?php echo esc_textarea($opts['exclude_paths']); ?></textarea>
            </td>
          </tr>

          <tr>
            <th scope="row">Shortcode Token</th>
            <td>
              <input type="text" class="regular-text code" name="<?php echo esc_attr(self::OPT_KEY); ?>[shortcode_token]" value="<?php echo esc_attr($opts['shortcode_token']); ?>" />
            </td>
          </tr>
        </table>

        <?php submit_button(); ?>
      </form>
    </div>
    <?php
  }
}

HM_Basic_Ceviri_Inline::boot();
