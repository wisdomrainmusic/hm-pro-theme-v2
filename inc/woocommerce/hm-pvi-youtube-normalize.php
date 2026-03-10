<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Extract YouTube video ID from common URL formats.
 *
 * @param string $url YouTube URL.
 * @return string Empty string if no ID is found.
 */
function hmpro_pvi_extract_youtube_id( $url ) {
	$url = trim( (string) $url );
	if ( '' === $url ) {
		return '';
	}

	$parts = wp_parse_url( $url );
	if ( ! is_array( $parts ) ) {
		return '';
	}

	$host = isset( $parts['host'] ) ? strtolower( (string) $parts['host'] ) : '';
	$host = preg_replace( '/^www\./', '', $host );
	$path = isset( $parts['path'] ) ? trim( (string) $parts['path'], '/' ) : '';

	$id = '';

	if ( 'youtube.com' === $host || 'm.youtube.com' === $host ) {
		if ( isset( $parts['query'] ) ) {
			parse_str( (string) $parts['query'], $query_args );
			if ( ! empty( $query_args['v'] ) && is_string( $query_args['v'] ) ) {
				$id = $query_args['v'];
			}
		}

		if ( '' === $id && '' !== $path ) {
			$segments = array_values( array_filter( explode( '/', $path ) ) );
			if ( isset( $segments[0], $segments[1] ) && in_array( $segments[0], [ 'shorts', 'embed' ], true ) ) {
				$id = (string) $segments[1];
			} elseif ( ! empty( $segments ) ) {
				$id = (string) end( $segments );
			}
		}
	} elseif ( 'youtu.be' === $host && '' !== $path ) {
		$segments = array_values( array_filter( explode( '/', $path ) ) );
		if ( ! empty( $segments[0] ) ) {
			$id = (string) $segments[0];
		}
	}

	if ( '' === $id && '' !== $path ) {
		$segments = array_values( array_filter( explode( '/', $path ) ) );
		if ( ! empty( $segments ) ) {
			$id = (string) end( $segments );
		}
	}

	$id = preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $id );
	if ( ! is_string( $id ) ) {
		$id = '';
	}

	// YouTube IDs are commonly 11 chars; allow a safe 6-15 range to stay additive.
	if ( '' === $id || ! preg_match( '/^[A-Za-z0-9_-]{6,15}$/', $id ) ) {
		return '';
	}

	return $id;
}

/**
 * Normalize YouTube URLs to watch format used by the lightbox integration.
 *
 * @param string $url Raw URL.
 * @return string Normalized URL or original when no ID is detected.
 */
function hmpro_pvi_normalize_youtube_url( $url ) {
	$video_id = hmpro_pvi_extract_youtube_id( $url );
	if ( '' === $video_id ) {
		return (string) $url;
	}

	return 'https://www.youtube.com/watch?v=' . $video_id;
}

add_filter( 'sanitize_post_meta__hm_pvi_youtube_url', function ( $meta_value ) {
	return hmpro_pvi_normalize_youtube_url( $meta_value );
} );

add_action( 'wp', function () {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}

	$product_id = get_queried_object_id();
	if ( ! $product_id ) {
		return;
	}

	$current_url = (string) get_post_meta( $product_id, '_hm_pvi_youtube_url', true );
	if ( '' === $current_url ) {
		return;
	}

	$normalized_url = hmpro_pvi_normalize_youtube_url( $current_url );
	if ( $normalized_url !== $current_url ) {
		update_post_meta( $product_id, '_hm_pvi_youtube_url', $normalized_url );
	}
} );
