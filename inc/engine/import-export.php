<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CSV columns (Demo Engine compatible)
 */
function hmpro_csv_columns() {
	return [ 'name', 'primary', 'dark', 'bg', 'footer', 'link', 'body_font', 'heading_font' ];
}

/**
 * Download CSV template.
 */
function hmpro_download_csv_template() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Forbidden' );
	}

	if ( empty( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'hmpro_csv_template' ) ) {
		wp_die( 'Security check failed' );
	}

	$cols = hmpro_csv_columns();

	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=hmpro-presets-template.csv' );

	$out = fopen( 'php://output', 'w' );
	fputcsv( $out, $cols );
	fputcsv( $out, [ 'Kadın Giyim 1 Soft Rose', '#D97C8A', '#9E4F5E', '#FFF6F7', '#7F3F4A', '#E85D75', 'Inter', 'Poppins' ] );
	fclose( $out );
	exit;
}

/**
 * Import CSV file into presets.
 *
 * @param string $file_path Uploaded temp path.
 * @param string $mode 'update' or 'create'
 * @return array [imported => int, updated => int, created => int, skipped => int]
 */
function hmpro_import_presets_csv( $file_path, $mode = 'update' ) {
	$mode = ( 'create' === $mode ) ? 'create' : 'update';

	$cols = hmpro_csv_columns();
	$fh   = fopen( $file_path, 'r' );
	if ( ! $fh ) {
		return [ 'imported' => 0, 'updated' => 0, 'created' => 0, 'skipped' => 0 ];
	}

	// Read first line raw and auto-detect delimiter (comma/semicolon/tab).
	$first_line = fgets( $fh );
	if ( $first_line === false ) {
		fclose( $fh );
		return [ 'imported' => 0, 'updated' => 0, 'created' => 0, 'skipped' => 0 ];
	}

	$delims = [ ',', ';', "\t" ];
	$best_delim = ',';
	$best_count = -1;
	foreach ( $delims as $d ) {
		$c = substr_count( $first_line, $d );
		if ( $c > $best_count ) {
			$best_count = $c;
			$best_delim = $d;
		}
	}

	$header = str_getcsv( $first_line, $best_delim );
	if ( ! is_array( $header ) ) {
		fclose( $fh );
		return [ 'imported' => 0, 'updated' => 0, 'created' => 0, 'skipped' => 0 ];
	}

	// Normalize header.
	$header = array_map( function( $h ) {
		$h = (string) $h;
		$h = preg_replace( '/^\xEF\xBB\xBF/', '', $h ); // strip UTF-8 BOM
		$h = trim( $h );
		$h = strtolower( $h );
		$h = str_replace( [ ' ', '-' ], '_', $h );
		return sanitize_key( $h );
	}, $header );

	// Build column index map.
	$idx = [];
	foreach ( $cols as $c ) {
		$pos       = array_search( $c, $header, true );
		$idx[ $c ] = ( $pos === false ) ? null : $pos;
	}

	$presets = hmpro_get_presets();

	// Helper: find preset by name (case-insensitive).
	$find_by_name = function( $name ) use ( $presets ) {
		$name_l = mb_strtolower( trim( (string) $name ) );
		foreach ( $presets as $p ) {
			$pname = mb_strtolower( trim( (string) ( $p['name'] ?? '' ) ) );
			if ( $pname && $pname === $name_l ) {
				return $p;
			}
		}
		return null;
	};

	$imported = 0;
	$updated  = 0;
	$created  = 0;
	$skipped  = 0;

	while ( ( $line = fgets( $fh ) ) !== false ) {
		$row = str_getcsv( $line, $best_delim );
		if ( ! is_array( $row ) || empty( $row ) ) {
			continue;
		}

		$name = ( $idx['name'] !== null && isset( $row[ $idx['name'] ] ) ) ? trim( (string) $row[ $idx['name'] ] ) : '';
		if ( $name === '' ) {
			$skipped++;
			continue;
		}

		$data = [
			'name'         => $name,
			'primary'      => ( $idx['primary'] !== null && isset( $row[ $idx['primary'] ] ) ) ? trim( (string) $row[ $idx['primary'] ] ) : '',
			'dark'         => ( $idx['dark'] !== null && isset( $row[ $idx['dark'] ] ) ) ? trim( (string) $row[ $idx['dark'] ] ) : '',
			'bg'           => ( $idx['bg'] !== null && isset( $row[ $idx['bg'] ] ) ) ? trim( (string) $row[ $idx['bg'] ] ) : '',
			'footer'       => ( $idx['footer'] !== null && isset( $row[ $idx['footer'] ] ) ) ? trim( (string) $row[ $idx['footer'] ] ) : '',
			'link'         => ( $idx['link'] !== null && isset( $row[ $idx['link'] ] ) ) ? trim( (string) $row[ $idx['link'] ] ) : '',
			'body_font'    => ( $idx['body_font'] !== null && isset( $row[ $idx['body_font'] ] ) ) ? trim( (string) $row[ $idx['body_font'] ] ) : 'system',
			'heading_font' => ( $idx['heading_font'] !== null && isset( $row[ $idx['heading_font'] ] ) ) ? trim( (string) $row[ $idx['heading_font'] ] ) : 'system',
		];

		$existing = $find_by_name( $name );

		if ( $existing && 'update' === $mode ) {
			$ok = hmpro_update_preset( $existing['id'], $data );
			if ( $ok ) {
				$updated++;
				$imported++;
			} else {
				$skipped++;
			}
			continue;
		}

		// Create new preset.
		$new_id = sanitize_key( $name );
		if ( $new_id === '' ) {
			$new_id = 'preset_' . wp_generate_password( 6, false, false );
		}

		$now = gmdate( 'c' );
		$new = [
			'id'           => $new_id,
			'name'         => $data['name'],
			'primary'      => $data['primary'],
			'dark'         => $data['dark'],
			'bg'           => $data['bg'],
			'footer'       => $data['footer'],
			'link'         => $data['link'],
			'body_font'    => $data['body_font'],
			'heading_font' => $data['heading_font'],
			'created_at'   => $now,
			'updated_at'   => $now,
		];

		$presets[] = $new;
		update_option( hmpro_presets_option_key(), $presets, false );

		$created++;
		$imported++;
	}

	fclose( $fh );

	// Normalize IDs after import (ensures uniqueness and sanitization).
	hmpro_get_presets();

	return [ 'imported' => $imported, 'updated' => $updated, 'created' => $created, 'skipped' => $skipped ];
}
