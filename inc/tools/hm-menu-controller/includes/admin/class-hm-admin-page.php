<?php
if ( ! defined( 'ABSPATH' ) ) {
exit;
}

final class HM_MC_Admin_Page {

// Embed under HM Pro Theme menu.
const MENU_SLUG = 'hmpro-menu-controller';

public static function register_menu() : void {
add_submenu_page(
'hmpro-theme',
__( 'Menu Access Control', 'hm-menu-controller' ),
__( 'Menu Access Control', 'hm-menu-controller' ),
'manage_options',
self::MENU_SLUG,
array( __CLASS__, 'render_page' )
);
}

public static function handle_post() : void {
if ( ! current_user_can( 'manage_options' ) ) {
return;
}

if ( empty( $_POST['hm_mc_action'] ) ) {
return;
}

check_admin_referer( 'hm_mc_admin_page' );

$action = sanitize_text_field( wp_unslash( $_POST['hm_mc_action'] ) );

if ( 'add_user' === $action ) {
$email = isset( $_POST['hm_mc_email'] ) ? sanitize_email( wp_unslash( $_POST['hm_mc_email'] ) ) : '';
if ( empty( $email ) ) {
self::redirect_with_notice( 'email_empty' );
}

$user_id = HM_MC_Settings::get_user_id_by_email( $email );
if ( $user_id <= 0 ) {
self::redirect_with_notice( 'user_not_found' );
}

HM_MC_Settings::add_restricted_user_id( (int) $user_id );
self::redirect_with_notice( 'user_added' );
}

if ( 'remove_user' === $action ) {
$user_id = isset( $_POST['hm_mc_user_id'] ) ? absint( wp_unslash( $_POST['hm_mc_user_id'] ) ) : 0;

if ( $user_id > 0 ) {
HM_MC_Settings::remove_restricted_user_id( (int) $user_id );
delete_user_meta( (int) $user_id, 'hm_mc_hidden_menu_slugs' ); // cleanup
self::redirect_with_notice( 'user_removed' );
}

self::redirect_with_notice( 'invalid_request' );
}

if ( 'save_menu_visibility' === $action ) {
$target_user_id = isset( $_POST['hm_mc_target_user_id'] ) ? absint( wp_unslash( $_POST['hm_mc_target_user_id'] ) ) : 0;

if ( $target_user_id <= 0 ) {
self::redirect_with_notice( 'invalid_request' );
}

$tab_slugs = isset( $_POST['hm_mc_tab_slugs'] ) ? (array) wp_unslash( $_POST['hm_mc_tab_slugs'] ) : array();
$tab_slugs = array_map( 'sanitize_text_field', $tab_slugs );
$tab_slugs = array_values( array_unique( array_filter( $tab_slugs ) ) );

$posted_hidden = isset( $_POST['hm_mc_hidden_slugs'] ) ? (array) wp_unslash( $_POST['hm_mc_hidden_slugs'] ) : array();
$posted_hidden = array_map( 'sanitize_text_field', $posted_hidden );
$posted_hidden = array_values( array_unique( array_filter( $posted_hidden ) ) );

// Merge strategy: update only current tab slugs, keep other tabs untouched.
$preset_key = HM_MC_Settings::get_user_preset_key( (int) $target_user_id );
$existing_hidden = '' !== $preset_key
? HM_MC_Settings::get_effective_hidden_menu_slugs( (int) $target_user_id )
: HM_MC_Settings::get_hidden_menu_slugs( (int) $target_user_id );

if ( ! empty( $tab_slugs ) ) {
$existing_hidden = array_values(
array_filter(
$existing_hidden,
static function ( $slug ) use ( $tab_slugs ) {
return ! in_array( (string) $slug, $tab_slugs, true );
}
)
);
}

$new_hidden = array_values( array_unique( array_merge( $existing_hidden, $posted_hidden ) ) );

if ( '' !== $preset_key ) {
// Preset base hidden
$preset        = HM_MC_Settings::get_preset( $preset_key );
$preset_hidden = ! empty( $preset['hidden_slugs'] ) && is_array( $preset['hidden_slugs'] )
? array_values( array_unique( array_filter( $preset['hidden_slugs'] ) ) )
: array();

$desired_hidden = $new_hidden;

// force_hide = desired_hidden - preset_hidden
$force_hide = array_values( array_diff( $desired_hidden, $preset_hidden ) );

// force_show = preset_hidden - desired_hidden
$force_show = array_values( array_diff( $preset_hidden, $desired_hidden ) );

HM_MC_Settings::set_user_force_hide_slugs( (int) $target_user_id, $force_hide );
HM_MC_Settings::set_user_force_show_slugs( (int) $target_user_id, $force_show );

self::redirect_with_notice( 'visibility_saved' );
}

HM_MC_Settings::save_hidden_menu_slugs( (int) $target_user_id, $new_hidden );
HM_MC_Settings::clear_user_overrides( (int) $target_user_id );
self::redirect_with_notice( 'visibility_saved' );
}

if ( 'save_preset' === $action ) {
$preset_key = isset( $_POST['hm_mc_preset_key'] ) ? sanitize_key( wp_unslash( $_POST['hm_mc_preset_key'] ) ) : '';
$name       = isset( $_POST['hm_mc_preset_name'] ) ? sanitize_text_field( wp_unslash( $_POST['hm_mc_preset_name'] ) ) : '';
$raw_slugs  = isset( $_POST['hm_mc_preset_hidden_slugs'] ) ? (string) wp_unslash( $_POST['hm_mc_preset_hidden_slugs'] ) : '';

if ( '' === $preset_key || '' === $name ) {
self::redirect_with_notice( 'preset_invalid' );
}

$lines = preg_split( "/\r\n|\n|\r/", $raw_slugs );
if ( ! is_array( $lines ) ) {
$lines = array();
}

$hidden_slugs = array();
foreach ( $lines as $line ) {
$line = trim( (string) $line );
if ( '' === $line ) {
continue;
}
$hidden_slugs[] = sanitize_text_field( $line );
}

HM_MC_Settings::save_preset( $preset_key, $name, $hidden_slugs );

$url = add_query_arg(
array(
'page'         => self::MENU_SLUG,
'hm_mc_notice' => rawurlencode( 'preset_saved' ),
'hm_mc_preset' => rawurlencode( $preset_key ),
),
admin_url( 'admin.php' )
);

wp_safe_redirect( $url );
exit;
}

if ( 'delete_preset' === $action ) {
$preset_key = isset( $_POST['hm_mc_preset_key'] ) ? sanitize_key( wp_unslash( $_POST['hm_mc_preset_key'] ) ) : '';
if ( '' === $preset_key ) {
self::redirect_with_notice( 'preset_invalid' );
}

HM_MC_Settings::delete_preset( $preset_key );

$url = add_query_arg(
array(
'page'         => self::MENU_SLUG,
'hm_mc_notice' => rawurlencode( 'preset_deleted' ),
),
admin_url( 'admin.php' )
);

wp_safe_redirect( $url );
exit;
}

if ( 'assign_preset' === $action ) {
$target_user_id = isset( $_POST['hm_mc_target_user_id'] ) ? absint( wp_unslash( $_POST['hm_mc_target_user_id'] ) ) : 0;
$preset_key     = isset( $_POST['hm_mc_preset_key'] ) ? sanitize_key( wp_unslash( $_POST['hm_mc_preset_key'] ) ) : '';

if ( $target_user_id <= 0 ) {
self::redirect_with_notice( 'invalid_request' );
}

HM_MC_Settings::set_user_preset_key( (int) $target_user_id, $preset_key );

$url = add_query_arg(
array(
'page'                 => self::MENU_SLUG,
'hm_mc_notice'         => rawurlencode( 'preset_assigned' ),
'hm_mc_target_user_id' => (int) $target_user_id,
),
admin_url( 'admin.php' )
);

wp_safe_redirect( $url );
exit;
}

if ( 'capture_preset_from_user' === $action ) {
$target_user_id = isset( $_POST['hm_mc_target_user_id'] ) ? absint( wp_unslash( $_POST['hm_mc_target_user_id'] ) ) : 0;
$preset_key     = isset( $_POST['hm_mc_preset_key'] ) ? sanitize_key( wp_unslash( $_POST['hm_mc_preset_key'] ) ) : '';
$preset_name    = isset( $_POST['hm_mc_preset_name'] ) ? sanitize_text_field( wp_unslash( $_POST['hm_mc_preset_name'] ) ) : '';

if ( $target_user_id <= 0 || '' === $preset_key || '' === $preset_name ) {
self::redirect_with_notice( 'preset_invalid' );
}

// Pull the EXACT per-user settings you created by ticking checkboxes
$hidden = HM_MC_Settings::get_hidden_menu_slugs( (int) $target_user_id );

HM_MC_Settings::save_preset( $preset_key, $preset_name, $hidden );

$url = add_query_arg(
array(
'page'                 => self::MENU_SLUG,
'hm_mc_notice'         => rawurlencode( 'preset_captured' ),
'hm_mc_preset'         => rawurlencode( $preset_key ),
'hm_mc_target_user_id' => (int) $target_user_id,
),
admin_url( 'admin.php' )
);

wp_safe_redirect( $url );
exit;
}

if ( 'export_presets' === $action ) {
$payload = HM_MC_Settings::export_presets_payload();
$json    = wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );

if ( false === $json ) {
$json = '{}';
}

nocache_headers();
header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );

$filename = 'hm-menu-controller-presets-' . gmdate( 'Y-m-d-His' ) . '.json';
header( 'Content-Disposition: attachment; filename="' . $filename . '"' );

echo $json;
exit;
}

if ( 'import_presets' === $action ) {
$raw = isset( $_POST['hm_mc_import_presets_json'] ) ? (string) wp_unslash( $_POST['hm_mc_import_presets_json'] ) : '';
$raw = trim( $raw );

if ( '' === $raw ) {
self::redirect_with_notice( 'import_empty' );
}

$data = json_decode( $raw, true );
if ( ! is_array( $data ) ) {
self::redirect_with_notice( 'import_invalid_json' );
}

$result = HM_MC_Settings::import_presets_payload( $data );

$url = add_query_arg(
array(
'page'           => self::MENU_SLUG,
'hm_mc_notice'   => rawurlencode( 'presets_imported' ),
'hm_mc_imported' => (int) ( $result['imported'] ?? 0 ),
),
admin_url( 'admin.php' )
);

wp_safe_redirect( $url );
exit;
}
}

private static function redirect_with_notice( string $notice ) : void {
$url = add_query_arg(
array(
'page'         => self::MENU_SLUG,
'hm_mc_notice' => rawurlencode( $notice ),
),
admin_url( 'admin.php' )
);

wp_safe_redirect( $url );
exit;
}

public static function render_page() : void {
if ( ! current_user_can( 'manage_options' ) ) {
wp_die( esc_html__( 'You do not have permission to access this page.', 'hm-menu-controller' ) );
}

$restricted_ids = HM_MC_Settings::get_restricted_user_ids();

$notice = isset( $_GET['hm_mc_notice'] ) ? sanitize_text_field( wp_unslash( $_GET['hm_mc_notice'] ) ) : '';

$target_user_id = isset( $_GET['hm_mc_target_user_id'] ) ? absint( wp_unslash( $_GET['hm_mc_target_user_id'] ) ) : 0;
if ( 0 === $target_user_id && ! empty( $restricted_ids ) ) {
$target_user_id = (int) $restricted_ids[0];
}

$menu_tree = HM_MC_Menu_Snapshot::get_tree();

$hidden_slugs = ( $target_user_id > 0 ) ? HM_MC_Settings::get_effective_hidden_menu_slugs( (int) $target_user_id ) : array();

?>
<div class="wrap">
<h1><?php echo esc_html__( 'HM Menu Controller', 'hm-menu-controller' ); ?></h1>

<?php self::render_notice( $notice ); ?>

<h2><?php echo esc_html__( 'Restricted Users (UI-only)', 'hm-menu-controller' ); ?></h2>
<p><?php echo esc_html__( 'Add admin users here to apply menu visibility settings. This plugin does not restrict access; it only changes what appears in the admin UI.', 'hm-menu-controller' ); ?></p>

<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top: 12px;">
<?php wp_nonce_field( 'hm_mc_admin_page' ); ?>
<input type="hidden" name="action" value="hm_mc_admin_page" />
<input type="hidden" name="hm_mc_action" value="add_user" />

<table class="form-table" role="presentation">
<tr>
<th scope="row">
<label for="hm_mc_email"><?php echo esc_html__( 'User email', 'hm-menu-controller' ); ?></label>
</th>
<td>
<input type="email" id="hm_mc_email" name="hm_mc_email" class="regular-text" placeholder="musteri@gmail.com" required />
<p class="description"><?php echo esc_html__( 'We store user IDs, not emails, for stability.', 'hm-menu-controller' ); ?></p>
</td>
</tr>
</table>

<?php submit_button( __( 'Add restricted user', 'hm-menu-controller' ) ); ?>
</form>

<hr />

<h2><?php echo esc_html__( 'Current restricted users', 'hm-menu-controller' ); ?></h2>

<?php if ( empty( $restricted_ids ) ) : ?>
<p><?php echo esc_html__( 'No restricted users yet.', 'hm-menu-controller' ); ?></p>
<?php else : ?>
<table class="widefat striped" style="max-width: 980px;">
<thead>
<tr>
<th><?php echo esc_html__( 'User', 'hm-menu-controller' ); ?></th>
<th><?php echo esc_html__( 'Email', 'hm-menu-controller' ); ?></th>
<th><?php echo esc_html__( 'Role', 'hm-menu-controller' ); ?></th>
<th style="width: 140px;">&nbsp;</th>
</tr>
</thead>
<tbody>
<?php foreach ( $restricted_ids as $user_id ) : ?>
<?php
$user = get_user_by( 'id', (int) $user_id );
if ( ! $user ) {
continue;
}
$roles = ! empty( $user->roles ) ? implode( ', ', array_map( 'sanitize_text_field', $user->roles ) ) : '';
?>
<tr>
<td>
<?php echo esc_html( $user->display_name ); ?>
<?php echo ' '; ?>
<code>#<?php echo esc_html( (string) $user->ID ); ?></code>
</td>
<td><?php echo esc_html( $user->user_email ); ?></td>
<td><?php echo esc_html( $roles ); ?></td>
<td>
<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
<?php wp_nonce_field( 'hm_mc_admin_page' ); ?>
<input type="hidden" name="action" value="hm_mc_admin_page" />
<input type="hidden" name="hm_mc_action" value="remove_user" />
<input type="hidden" name="hm_mc_user_id" value="<?php echo esc_attr( (string) $user->ID ); ?>" />
<?php submit_button( __( 'Remove', 'hm-menu-controller' ), 'delete', 'submit', false ); ?>
</form>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>

<hr />

<h2><?php echo esc_html__( 'Menu Visibility (Per User)', 'hm-menu-controller' ); ?></h2>
<p><?php echo esc_html__( 'Select a restricted user, then check items you want to hide from their admin sidebar. This does not block direct URL access.', 'hm-menu-controller' ); ?></p>

<?php self::render_target_user_picker( $restricted_ids, $target_user_id ); ?>

<?php if ( $target_user_id > 0 ) : ?>
<div style="margin: 14px 0 18px; padding: 12px; background: #fff; border: 1px solid #dcdcde; max-width: 980px;">
<h3 style="margin-top:0;"><?php echo esc_html__( 'Capture preset from this user', 'hm-menu-controller' ); ?></h3>
<p class="description" style="margin-top:0;">
<?php echo esc_html__( 'Creates/overwrites a preset using the current saved menu visibility of the selected user (your checkbox configuration).', 'hm-menu-controller' ); ?>
</p>

<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
<?php wp_nonce_field( 'hm_mc_admin_page' ); ?>
<input type="hidden" name="action" value="hm_mc_admin_page" />
<input type="hidden" name="hm_mc_action" value="capture_preset_from_user" />
<input type="hidden" name="hm_mc_target_user_id" value="<?php echo esc_attr( (string) $target_user_id ); ?>" />

<table class="form-table" role="presentation" style="margin:0;">
<tr>
<th scope="row" style="width:180px;">
<label for="hm_mc_capture_preset_key"><?php echo esc_html__( 'Preset key', 'hm-menu-controller' ); ?></label>
</th>
<td>
<input id="hm_mc_capture_preset_key" name="hm_mc_preset_key" type="text" class="regular-text" placeholder="client" required />
</td>
</tr>
<tr>
<th scope="row">
<label for="hm_mc_capture_preset_name"><?php echo esc_html__( 'Preset name', 'hm-menu-controller' ); ?></label>
</th>
<td>
<input id="hm_mc_capture_preset_name" name="hm_mc_preset_name" type="text" class="regular-text" placeholder="Client (captured)" required />
</td>
</tr>
</table>

<p style="margin: 10px 0 0;">
<button type="submit" class="button button-primary"><?php echo esc_html__( 'Capture preset', 'hm-menu-controller' ); ?></button>
</p>
</form>
</div>
<?php
$presets        = HM_MC_Settings::get_presets();
$current_preset = HM_MC_Settings::get_user_preset_key( (int) $target_user_id );
?>
<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin: 12px 0 20px;">
<?php wp_nonce_field( 'hm_mc_admin_page' ); ?>
<input type="hidden" name="action" value="hm_mc_admin_page" />
<input type="hidden" name="hm_mc_action" value="assign_preset" />
<input type="hidden" name="hm_mc_target_user_id" value="<?php echo esc_attr( (string) $target_user_id ); ?>" />

<label for="hm_mc_preset_key" style="margin-right:10px;">
<strong><?php echo esc_html__( 'Assigned preset:', 'hm-menu-controller' ); ?></strong>
</label>

<select name="hm_mc_preset_key" id="hm_mc_preset_key">
<option value=""><?php echo esc_html__( '— No preset (use user settings) —', 'hm-menu-controller' ); ?></option>
<?php foreach ( $presets as $key => $preset ) : ?>
<option value="<?php echo esc_attr( (string) $key ); ?>" <?php selected( $current_preset, (string) $key ); ?>>
<?php echo esc_html( $preset['name'] ?? $key ); ?>
</option>
<?php endforeach; ?>
</select>

<?php submit_button( __( 'Assign preset', 'hm-menu-controller' ), 'secondary', 'submit', false ); ?>
</form>

<?php if ( $target_user_id > 0 ) :
$pk = HM_MC_Settings::get_user_preset_key( (int) $target_user_id );
if ( '' !== $pk ) : ?>
<p class="description" style="margin-top:6px;">
<?php echo esc_html__( 'Note: A preset is assigned. The checklist below reflects the preset. Saving per-user visibility will not override the preset unless you remove the preset assignment.', 'hm-menu-controller' ); ?>
</p>
<?php endif;
endif; ?>

<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
<?php wp_nonce_field( 'hm_mc_admin_page' ); ?>
<input type="hidden" name="action" value="hm_mc_admin_page" />
<input type="hidden" name="hm_mc_action" value="save_menu_visibility" />
<input type="hidden" name="hm_mc_target_user_id" value="<?php echo esc_attr( (string) $target_user_id ); ?>" />

<?php self::render_menu_tabs_editor( $menu_tree, $hidden_slugs ); ?>

<?php submit_button( __( 'Save menu visibility', 'hm-menu-controller' ) ); ?>
</form>
<?php endif; ?>

<hr />

<h2><?php echo esc_html__( 'Presets', 'hm-menu-controller' ); ?></h2>
<p><?php echo esc_html__( 'Create reusable menu visibility presets. In the next step you will be able to assign a preset to any restricted user.', 'hm-menu-controller' ); ?></p>

<?php self::render_presets_ui(); ?>

<h3><?php echo esc_html__( 'Preset Export / Import', 'hm-menu-controller' ); ?></h3>

<div style="display:flex; gap:24px; flex-wrap:wrap; max-width: 980px;">

<div style="flex:1; min-width: 320px;">
<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
<?php wp_nonce_field( 'hm_mc_admin_page' ); ?>
<input type="hidden" name="action" value="hm_mc_admin_page" />
<input type="hidden" name="hm_mc_action" value="export_presets" />
<p>
<button type="submit" class="button button-secondary"><?php echo esc_html__( 'Download presets JSON', 'hm-menu-controller' ); ?></button>
</p>
</form>
</div>

<div style="flex:2; min-width: 420px;">
<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
<?php wp_nonce_field( 'hm_mc_admin_page' ); ?>
<input type="hidden" name="action" value="hm_mc_admin_page" />
<input type="hidden" name="hm_mc_action" value="import_presets" />

<textarea name="hm_mc_import_presets_json" rows="10" class="large-text code" placeholder="{...}"></textarea>

<p style="margin-top:10px;">
<button type="submit" class="button button-primary"><?php echo esc_html__( 'Import presets JSON', 'hm-menu-controller' ); ?></button>
</p>
</form>
</div>

</div>

</div>
<?php
}

private static function render_target_user_picker( array $restricted_ids, int $target_user_id ) : void {
if ( empty( $restricted_ids ) ) {
echo '<p>' . esc_html__( 'Add at least one restricted user to configure menu visibility.', 'hm-menu-controller' ) . '</p>';
return;
}

$base_url = admin_url( 'admin.php?page=' . self::MENU_SLUG );

// Safe JS base for onchange redirect
$js_base = $base_url . '&hm_mc_target_user_id=';

echo '<div style="margin: 10px 0 16px;">';
echo '<label for="hm_mc_target_user_id" style="margin-right:10px;"><strong>' . esc_html__( 'Target user:', 'hm-menu-controller' ) . '</strong></label>';

printf(
'<select id="hm_mc_target_user_id" onchange="if(this.value){window.location=%s + encodeURIComponent(this.value);}">',
esc_attr( wp_json_encode( $js_base ) )
);

foreach ( $restricted_ids as $uid ) {
$user = get_user_by( 'id', (int) $uid );
if ( ! $user ) {
continue;
}

printf(
'<option value="%1$d"%2$s>%3$s (%4$s)</option>',
(int) $user->ID,
selected( (int) $user->ID, $target_user_id, false ),
esc_html( $user->display_name ),
esc_html( $user->user_email )
);
}

echo '</select>';
echo '</div>';
}

private static function render_menu_tabs_editor( array $menu_tree, array $hidden_slugs ) : void {
if ( empty( $menu_tree ) ) {
echo '<p>' . esc_html__( 'Menu tree is empty.', 'hm-menu-controller' ) . '</p>';
return;
}

$active = isset( $_GET['hm_mc_tab'] ) ? sanitize_text_field( wp_unslash( $_GET['hm_mc_tab'] ) ) : '';
if ( '' === $active ) {
$active = (string) ( $menu_tree[0]['parent_slug'] ?? '' );
}

echo '<h2 class="nav-tab-wrapper" style="margin-top:12px;">';
foreach ( $menu_tree as $node ) {
$slug  = (string) ( $node['parent_slug'] ?? '' );
$label = (string) ( $node['label'] ?? $slug );
if ( '' === $slug ) {
continue;
}

$url = add_query_arg(
array(
'page'      => self::MENU_SLUG,
'hm_mc_tab' => rawurlencode( $slug ),
'hm_mc_target_user_id' => isset( $_GET['hm_mc_target_user_id'] ) ? absint( wp_unslash( $_GET['hm_mc_target_user_id'] ) ) : 0,
),
admin_url( 'admin.php' )
);

$is_active = ( $slug === $active ) ? ' nav-tab-active' : '';
printf(
'<a class="nav-tab%1$s" href="%2$s">%3$s</a>',
esc_attr( $is_active ),
esc_url( $url ),
esc_html( $label )
);
}
echo '</h2>';

foreach ( $menu_tree as $node ) {
$slug = (string) ( $node['parent_slug'] ?? '' );
if ( $slug !== $active ) {
continue;
}

$children = isset( $node['children'] ) && is_array( $node['children'] ) ? $node['children'] : array();

echo '<table class="widefat striped" style="max-width: 980px;">';
echo '<thead><tr>';
echo '<th style="width:80px;">' . esc_html__( 'Show', 'hm-menu-controller' ) . '</th>';
echo '<th>' . esc_html__( 'Menu Item', 'hm-menu-controller' ) . '</th>';
echo '<th style="width:420px;">' . esc_html__( 'Slug', 'hm-menu-controller' ) . '</th>';
echo '</tr></thead>';
echo '<tbody>';

foreach ( $children as $child ) {
$child_label = (string) ( $child['label'] ?? '' );
$child_slug  = (string) ( $child['slug'] ?? '' );
if ( '' === $child_slug ) {
continue;
}

$is_hidden = in_array( $child_slug, $hidden_slugs, true );

echo '<tr>';
// IMPORTANT: mark this slug as part of current tab scope
echo '<input type="hidden" name="hm_mc_tab_slugs[]" value="' . esc_attr( $child_slug ) . '" />';
echo '<td><input type="checkbox" name="hm_mc_hidden_slugs[]" value="' . esc_attr( $child_slug ) . '" ' . checked( $is_hidden, true, false ) . ' /></td>';
echo '<td>' . esc_html( $child_label ) . '</td>';
echo '<td><code>' . esc_html( $child_slug ) . '</code></td>';
echo '</tr>';
}

echo '</tbody>';
echo '</table>';

break;
}
}

private static function render_presets_ui() : void {
$presets = HM_MC_Settings::get_presets();

$selected_key = isset( $_GET['hm_mc_preset'] ) ? sanitize_key( wp_unslash( $_GET['hm_mc_preset'] ) ) : '';

$editing = array(
'key'   => '',
'name'  => '',
'slugs' => '',
);

if ( '' !== $selected_key ) {
$preset = HM_MC_Settings::get_preset( $selected_key );
if ( ! empty( $preset ) ) {
$editing['key']  = $selected_key;
$editing['name'] = isset( $preset['name'] ) ? (string) $preset['name'] : '';
$slugs           = isset( $preset['hidden_slugs'] ) && is_array( $preset['hidden_slugs'] ) ? $preset['hidden_slugs'] : array();
$editing['slugs'] = implode( "\n", $slugs );
}
}

echo '<div style="display:flex; gap:24px; flex-wrap:wrap; max-width: 980px;">';

// Left: list
echo '<div style="flex:1; min-width: 320px;">';
echo '<h3>' . esc_html__( 'Existing presets', 'hm-menu-controller' ) . '</h3>';

if ( empty( $presets ) ) {
echo '<p>' . esc_html__( 'No presets yet.', 'hm-menu-controller' ) . '</p>';
} else {
echo '<table class="widefat striped">';
echo '<thead><tr>';
echo '<th>' . esc_html__( 'Name', 'hm-menu-controller' ) . '</th>';
echo '<th style="width:180px;">' . esc_html__( 'Key', 'hm-menu-controller' ) . '</th>';
echo '<th style="width:120px;">' . esc_html__( 'Edit', 'hm-menu-controller' ) . '</th>';
echo '</tr></thead><tbody>';

foreach ( $presets as $key => $preset ) {
$name = isset( $preset['name'] ) ? (string) $preset['name'] : $key;

$edit_url = add_query_arg(
array(
'page'        => self::MENU_SLUG,
'hm_mc_preset'=> rawurlencode( (string) $key ),
),
admin_url( 'admin.php' )
);

echo '<tr>';
echo '<td>' . esc_html( $name ) . '</td>';
echo '<td><code>' . esc_html( (string) $key ) . '</code></td>';
echo '<td><a class="button button-small" href="' . esc_url( $edit_url ) . '">' . esc_html__( 'Edit', 'hm-menu-controller' ) . '</a></td>';
echo '</tr>';
}

echo '</tbody></table>';
}

echo '</div>';

// Right: editor
echo '<div style="flex:2; min-width: 420px;">';
echo '<h3>' . esc_html__( 'Create / Edit preset', 'hm-menu-controller' ) . '</h3>';

echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
wp_nonce_field( 'hm_mc_admin_page' );
echo '<input type="hidden" name="action" value="hm_mc_admin_page" />';
echo '<input type="hidden" name="hm_mc_action" value="save_preset" />';

echo '<table class="form-table" role="presentation">';

echo '<tr>';
echo '<th scope="row"><label for="hm_mc_preset_key">' . esc_html__( 'Preset key', 'hm-menu-controller' ) . '</label></th>';
echo '<td>';
echo '<input type="text" id="hm_mc_preset_key" name="hm_mc_preset_key" class="regular-text" value="' . esc_attr( $editing['key'] ) . '" placeholder="client" required />';
echo '<p class="description">' . esc_html__( 'Lowercase key (letters/numbers/underscores). Used for export/import and assignment.', 'hm-menu-controller' ) . '</p>';
echo '</td>';
echo '</tr>';

echo '<tr>';
echo '<th scope="row"><label for="hm_mc_preset_name">' . esc_html__( 'Preset name', 'hm-menu-controller' ) . '</label></th>';
echo '<td>';
echo '<input type="text" id="hm_mc_preset_name" name="hm_mc_preset_name" class="regular-text" value="' . esc_attr( $editing['name'] ) . '" placeholder="Client Preset" required />';
echo '</td>';
echo '</tr>';

echo '<tr>';
echo '<th scope="row"><label for="hm_mc_preset_hidden_slugs">' . esc_html__( 'Hidden menu slugs', 'hm-menu-controller' ) . '</label></th>';
echo '<td>';
echo '<textarea id="hm_mc_preset_hidden_slugs" name="hm_mc_preset_hidden_slugs" rows="10" class="large-text code" placeholder="plugins.php&#10;themes.php">' . esc_textarea( $editing['slugs'] ) . '</textarea>';
echo '<p class="description">' . esc_html__( 'One slug per line. These items will be hidden in the admin sidebar when this preset is assigned.', 'hm-menu-controller' ) . '</p>';
echo '</td>';
echo '</tr>';

echo '</table>';

submit_button( __( 'Save preset', 'hm-menu-controller' ), 'primary' );

echo '</form>';

// Delete (only when editing)
if ( '' !== $editing['key'] ) {
echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="margin-top:10px;">';
wp_nonce_field( 'hm_mc_admin_page' );
echo '<input type="hidden" name="action" value="hm_mc_admin_page" />';
echo '<input type="hidden" name="hm_mc_action" value="delete_preset" />';
echo '<input type="hidden" name="hm_mc_preset_key" value="' . esc_attr( $editing['key'] ) . '" />';
submit_button( __( 'Delete preset', 'hm-menu-controller' ), 'delete', 'submit', false );
echo '</form>';
}

echo '</div>';

echo '</div>';
}

private static function render_notice( string $notice ) : void {
if ( empty( $notice ) ) {
return;
}

$map = array(
'email_empty'     => array( 'error', __( 'Please enter an email.', 'hm-menu-controller' ) ),
'user_not_found'  => array( 'error', __( 'No user found for that email.', 'hm-menu-controller' ) ),
'user_added'      => array( 'success', __( 'User added to restricted list.', 'hm-menu-controller' ) ),
'user_removed'    => array( 'success', __( 'User removed from restricted list.', 'hm-menu-controller' ) ),
'menu_saved'      => array( 'success', __( 'Menu visibility saved for this user.', 'hm-menu-controller' ) ),
'visibility_saved'      => array( 'success', __( 'Menu visibility saved for this user.', 'hm-menu-controller' ) ),
'preset_saved'   => array( 'success', __( 'Preset saved.', 'hm-menu-controller' ) ),
'preset_captured' => array( 'success', __( 'Preset captured from user settings.', 'hm-menu-controller' ) ),
'preset_deleted' => array( 'success', __( 'Preset deleted.', 'hm-menu-controller' ) ),
'preset_invalid' => array( 'error', __( 'Preset key/name is invalid.', 'hm-menu-controller' ) ),
'preset_assigned' => array( 'success', __( 'Preset assigned to user.', 'hm-menu-controller' ) ),
'import_empty'        => array( 'error', __( 'Import JSON is empty.', 'hm-menu-controller' ) ),
'import_invalid_json' => array( 'error', __( 'Import JSON is not valid.', 'hm-menu-controller' ) ),
'invalid_request' => array( 'error', __( 'Invalid request.', 'hm-menu-controller' ) ),
);

if ( 'presets_imported' === $notice ) {
$imported = isset( $_GET['hm_mc_imported'] ) ? absint( wp_unslash( $_GET['hm_mc_imported'] ) ) : 0;

$type    = 'success';
$message = sprintf(
/* translators: %d imported presets */
__( 'Presets imported: %d', 'hm-menu-controller' ),
$imported
);

printf(
'<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
esc_attr( $type ),
esc_html( $message )
);
return;
}

if ( ! isset( $map[ $notice ] ) ) {
return;
}

list( $type, $message ) = $map[ $notice ];

printf(
'<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
esc_attr( $type ),
esc_html( $message )
);
}
}
