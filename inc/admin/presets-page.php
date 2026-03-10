<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function hmpro_render_presets_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// Admin notices
	if ( isset( $_GET['hmpro_notice'] ) ) {
		$notice = sanitize_key( wp_unslash( $_GET['hmpro_notice'] ) );
		if ( 'preset_activated' === $notice ) {
			echo '<div class="notice notice-success is-dismissible"><p>Preset activated.</p></div>';
		} elseif ( 'preset_deleted' === $notice ) {
			echo '<div class="notice notice-success is-dismissible"><p>Preset deleted.</p></div>';
		} elseif ( 'preset_delete_failed' === $notice ) {
			echo '<div class="notice notice-error is-dismissible"><p>Preset could not be deleted.</p></div>';
		} elseif ( 'preset_delete_active' === $notice ) {
			echo '<div class="notice notice-error is-dismissible"><p>Active preset cannot be deleted.</p></div>';
		} elseif ( 'seeded' === $notice ) {
			echo '<div class="notice notice-success is-dismissible"><p>Sample presets added.</p></div>';
		} elseif ( 'already_seeded' === $notice ) {
			echo '<div class="notice notice-info is-dismissible"><p>Sample presets already exist.</p></div>';
		} elseif ( 'csv_missing' === $notice ) {
			echo '<div class="notice notice-error is-dismissible"><p>Please choose a CSV file.</p></div>';
		} elseif ( 'csv_imported' === $notice ) {
			$i = isset( $_GET['i'] ) ? (int) $_GET['i'] : 0;
			$u = isset( $_GET['u'] ) ? (int) $_GET['u'] : 0;
			$c = isset( $_GET['c'] ) ? (int) $_GET['c'] : 0;
			$s = isset( $_GET['s'] ) ? (int) $_GET['s'] : 0;
			echo '<div class="notice notice-success is-dismissible"><p>CSV imported. Imported: ' . esc_html( $i ) . ', Updated: ' . esc_html( $u ) . ', Created: ' . esc_html( $c ) . ', Skipped: ' . esc_html( $s ) . '.</p></div>';
		} elseif ( 'typo_applied' === $notice ) {
			echo '<div class="notice notice-success is-dismissible"><p>Typography preset applied to the active preset.</p></div>';
		} elseif ( 'typo_failed' === $notice ) {
			echo '<div class="notice notice-error is-dismissible"><p>Typography preset could not be applied.</p></div>';
		} elseif ( 'typo_invalid' === $notice ) {
			echo '<div class="notice notice-error is-dismissible"><p>Invalid typography preset.</p></div>';
		} elseif ( 'preset_not_found' === $notice ) {
			echo '<div class="notice notice-error is-dismissible"><p>Preset not found.</p></div>';
		} elseif ( 'nonce_failed' === $notice ) {
			echo '<div class="notice notice-error is-dismissible"><p>Security check failed. Please try again.</p></div>';
		}
	}

	$presets   = hmpro_get_presets();
	$active_id = hmpro_get_active_preset_id();
	?>
	<div class="wrap hmpro-admin">
		<h1>HM Pro Theme — Presets</h1>
		<?php
		$seed_url = wp_nonce_url(
			admin_url( 'admin.php?page=hmpro-presets&hmpro_action=seed_presets' ),
			'hmpro_seed_presets'
		);
		?>
		<p>
			<a class="button" href="<?php echo esc_url( $seed_url ); ?>">Add Sample Presets</a>
		</p>

		<p>Manage global color and typography presets for HM Pro Theme.</p>
		<?php
		$template_url = wp_nonce_url(
			admin_url( 'admin.php?page=hmpro-presets&hmpro_action=download_csv_template' ),
			'hmpro_csv_template'
		);
		?>

		<h2>Typography Presets</h2>
		<p>Apply a curated font combo to the currently active preset.</p>

		<div style="display:flex;gap:10px;flex-wrap:wrap;margin:10px 0 20px;">
			<?php
			$typos = function_exists( 'hmpro_typography_presets' ) ? hmpro_typography_presets() : [];
			foreach ( $typos as $key => $t ) :
				$base = add_query_arg(
					[
						'hmpro_action' => 'apply_typography_preset',
						'preset_key'   => $key,
					],
					admin_url( 'admin.php?page=hmpro-presets' )
				);
				$url = wp_nonce_url( $base, 'hmpro_apply_typography' );
				$label = isset( $t['label'] ) ? $t['label'] : $key;
				$body  = isset( $t['body_font'] ) ? $t['body_font'] : 'system';
				$head  = isset( $t['heading_font'] ) ? $t['heading_font'] : 'system';
				$body_stack = hmpro_font_token_to_stack( (string) ( $t['body_font'] ?? 'system' ) );
				$head_stack = hmpro_font_token_to_stack( (string) ( $t['heading_font'] ?? 'system' ) );
				?>
				<a class="button hmpro-typo-btn" href="<?php echo esc_url( $url ); ?>">
					<span class="hmpro-typo-aa" style="font-family: <?php echo esc_attr( $head_stack ); ?>;">Aa</span>
					<span class="hmpro-typo-label"><?php echo esc_html( $label ); ?></span>
					<span class="hmpro-typo-meta">(<?php echo esc_html( $body ); ?> / <?php echo esc_html( $head ); ?>)</span>
				</a>
			<?php endforeach; ?>
		</div>

		<hr />

		<h2>Import Presets from CSV</h2>
		<p>
			<a class="button" href="<?php echo esc_url( $template_url ); ?>">Download CSV Template</a>
		</p>

		<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin.php?page=hmpro-presets' ) ); ?>">
			<?php wp_nonce_field( 'hmpro_import_csv' ); ?>

			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row">CSV File</th>
						<td>
							<input type="file" name="csv_file" accept=".csv" />
							<p class="description">CSV columns: <code>name, primary, dark, bg, footer, link, body_font, heading_font</code></p>
						</td>
					</tr>
					<tr>
						<th scope="row">Import Mode</th>
						<td>
							<label>
								<input type="radio" name="import_mode" value="update" checked>
								Update if name matches (recommended)
							</label><br>
							<label>
								<input type="radio" name="import_mode" value="create">
								Always create new presets
							</label>
						</td>
					</tr>
				</tbody>
			</table>

			<p>
				<button type="submit" class="button button-primary" name="hmpro_import_csv" value="1">Import CSV</button>
			</p>
		</form>

		<hr />

		<table class="widefat fixed striped">
			<thead>
				<tr>
					<th>Name</th>
					<th>Palette</th>
					<th>Primary</th>
					<th>Background</th>
					<th>Fonts</th>
					<th>Actions</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $presets as $preset ) : ?>
					<?php
					$id        = esc_html( $preset['id'] ?? '' );
					$name      = esc_html( $preset['name'] ?? '' );
					$primary   = esc_html( $preset['primary'] ?? '' );
					$bg        = esc_html( $preset['bg'] ?? '' );
					$fonts     = esc_html( ( $preset['body_font'] ?? 'system' ) . ' / ' . ( $preset['heading_font'] ?? 'system' ) );
					$is_active = ( ! empty( $preset['id'] ) && $preset['id'] === $active_id );
					?>
					<tr>
						<td><strong><?php echo $name; ?></strong></td>
						<td>
							<div class="hmpro-palette">
								<span style="background: <?php echo esc_attr( $preset['primary'] ?? '#000' ); ?>"></span>
								<span style="background: <?php echo esc_attr( $preset['dark'] ?? '#333' ); ?>"></span>
								<span style="background: <?php echo esc_attr( $preset['bg'] ?? '#fff' ); ?>"></span>
								<span style="background: <?php echo esc_attr( $preset['footer'] ?? '#111' ); ?>"></span>
								<span style="background: <?php echo esc_attr( $preset['link'] ?? '#0073aa' ); ?>"></span>
							</div>
						</td>
						<td><?php echo $primary; ?></td>
						<td><?php echo $bg; ?></td>
						<td><?php echo $fonts; ?></td>
						<td>
							<?php
							$preset_key  = sanitize_key( (string) ( $preset['id'] ?? '' ) );
							$edit_url    = admin_url( 'admin.php?page=hmpro-preset-edit&preset=' . rawurlencode( $preset_key ) );
							$activate_url = wp_nonce_url(
								admin_url( 'admin.php?page=hmpro-presets&hmpro_action=set_active&preset=' . rawurlencode( $preset_key ) ),
								'hmpro_set_active_preset'
							);
							$delete_url  = wp_nonce_url(
								admin_url( 'admin.php?page=hmpro-presets&hmpro_action=delete_preset&preset=' . rawurlencode( $preset_key ) ),
								'hmpro_delete_preset'
							);
							?>
							<a class="button" href="<?php echo esc_url( $edit_url ); ?>">Edit</a>
							<?php if ( $active_id === ( $preset['id'] ?? '' ) ) : ?>
								<span class="hmpro-badge-active">Active</span>
							<?php else : ?>
								<a class="button" href="<?php echo esc_url( $activate_url ); ?>">Set Active</a>
							<?php endif; ?>
							<?php if ( $active_id !== ( $preset['id'] ?? '' ) ) : ?>
								<a class="button button-danger hmpro-delete-preset"
								   href="<?php echo esc_url( $delete_url ); ?>"
								   data-name="<?php echo esc_attr( $preset['name'] ); ?>">
								   Delete
								</a>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php
}
