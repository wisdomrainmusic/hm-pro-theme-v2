<?php
/**
 * Render callback for HM Hero Slider block.
 */
// LCP optimization: render first slide media as <img>/<picture> to allow fetchpriority.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'hmpro_hero_sanitize_color' ) ) {
	function hmpro_hero_sanitize_color( $c ) {
		$c = is_string( $c ) ? trim( $c ) : '';
		if ( $c === '' ) {
			return '';
		}
		// #RGB, #RRGGBB, #RRGGBBAA
		if ( preg_match( '/^#([0-9a-f]{3}|[0-9a-f]{6}|[0-9a-f]{8})$/i', $c ) ) {
			return $c;
		}
		// rgb()/rgba()
		if ( preg_match( '/^rgba?\(([^)]+)\)$/i', $c ) ) {
			return $c;
		}
		return '';
	}
}

if ( ! function_exists( 'hmpro_hero_sanitize_css_text' ) ) {
	function hmpro_hero_sanitize_css_text( $v ) {
		$v = trim( (string) $v );
		$v = str_replace( [ ';', '{', '}', '<', '>', '"' ], '', $v );
		if ( strlen( $v ) > 200 ) {
			$v = substr( $v, 0, 200 );
		}
		return trim( $v );
	}
}


if ( ! function_exists( 'hmpro_hero_queue_preload' ) ) {
	/**
	 * Queue a hero preload link for output in <head>.
	 * Outputting preloads in the render body is too late for LCP discovery.
	 */
	function hmpro_hero_queue_preload( $href, $media = '' ) {
		$href  = is_string( $href ) ? trim( $href ) : '';
		$media = is_string( $media ) ? trim( $media ) : '';
		if ( $href === '' ) {
			return;
		}
		if ( ! isset( $GLOBALS['hmpro_hero_preloads'] ) || ! is_array( $GLOBALS['hmpro_hero_preloads'] ) ) {
			$GLOBALS['hmpro_hero_preloads'] = [];
		}
		$key = md5( $href . '|' . $media );
		$GLOBALS['hmpro_hero_preloads'][ $key ] = [ 'href' => $href, 'media' => $media ];

		// Output early in head, once.
		if ( ! has_action( 'wp_head', 'hmpro_hero_output_preloads' ) ) {
			add_action( 'wp_head', 'hmpro_hero_output_preloads', 1 );
		}
	}
}

if ( ! function_exists( 'hmpro_hero_output_preloads' ) ) {
	/**
	 * Output queued preload links.
	 */
	function hmpro_hero_output_preloads() {
		if ( is_admin() ) {
			return;
		}
		$items = $GLOBALS['hmpro_hero_preloads'] ?? [];
		if ( ! is_array( $items ) || empty( $items ) ) {
			return;
		}
		foreach ( $items as $it ) {
			$href  = isset( $it['href'] ) ? (string) $it['href'] : '';
			$media = isset( $it['media'] ) ? (string) $it['media'] : '';
			if ( $href === '' ) {
				continue;
			}
			echo '<link rel="preload" as="image" href="' . esc_url( $href ) . '"' . ( $media !== '' ? ' media="' . esc_attr( $media ) . '"' : '' ) . '>' . "\n";
		}
	}
}
$attrs = isset( $attributes ) && is_array( $attributes ) ? $attributes : [];

$only_homepage = ! empty( $attrs['onlyHomepage'] );
if ( $only_homepage && ! is_front_page() ) {
	return '';
}

$full_width = isset( $attrs['fullWidth'] ) ? (bool) $attrs['fullWidth'] : true;
$max_width  = isset( $attrs['maxWidth'] ) ? absint( $attrs['maxWidth'] ) : 1200;
$height     = isset( $attrs['height'] ) ? absint( $attrs['height'] ) : 520;
$hide_mobile = ! empty( $attrs['hideOnMobile'] );

$mobile_height_mode = isset( $attrs['mobileHeightMode'] ) ? (string) $attrs['mobileHeightMode'] : 'auto';
$mobile_height_mode = in_array( $mobile_height_mode, [ 'auto', 'compact', 'square' ], true ) ? $mobile_height_mode : 'auto';
$mobile_height_vh   = isset( $attrs['mobileHeightVh'] ) ? (int) $attrs['mobileHeightVh'] : 56;
$mobile_height_vh   = max( 40, min( 80, $mobile_height_vh ) );

// Background fit: "cover" (default) or "contain" (show full image).
$image_fit = isset( $attrs['imageFit'] ) ? (string) $attrs['imageFit'] : 'cover';
$image_fit = ( $image_fit === 'contain' ) ? 'contain' : 'cover';

if ( $max_width < 720 ) {
	$max_width = 720;
}
if ( $max_width > 1800 ) {
	$max_width = 1800;
}

if ( $height < 200 ) {
	$height = 200;
}
if ( $height > 1200 ) {
	$height = 1200;
}

$overlay = isset( $attrs['overlayOpacity'] ) ? (float) $attrs['overlayOpacity'] : 0.35;
if ( $overlay < 0 ) {
	$overlay = 0;
}
if ( $overlay > 0.8 ) {
	$overlay = 0.8;
}

$autoplay   = isset( $attrs['autoplay'] ) ? (bool) $attrs['autoplay'] : true;
$interval   = isset( $attrs['interval'] ) ? absint( $attrs['interval'] ) : 4500;
$show_arrows = isset( $attrs['showArrows'] ) ? (bool) $attrs['showArrows'] : true;
$show_dots   = isset( $attrs['showDots'] ) ? (bool) $attrs['showDots'] : true;

if ( $interval < 1500 ) {
	$interval = 1500;
}
if ( $interval > 20000 ) {
	$interval = 20000;
}

$group_x = isset( $attrs['groupX'] ) ? (int) $attrs['groupX'] : 0;
$group_y = isset( $attrs['groupY'] ) ? (int) $attrs['groupY'] : 0;
$m_group_x = isset( $attrs['mobileGroupX'] ) ? (int) $attrs['mobileGroupX'] : 0;
$m_group_y = isset( $attrs['mobileGroupY'] ) ? (int) $attrs['mobileGroupY'] : 0;
$m_title_scale = isset( $attrs['mobileTitleScale'] ) ? (float) $attrs['mobileTitleScale'] : 0.88; // backward-compat.
$content_scale = isset( $attrs['contentScale'] ) ? (float) $attrs['contentScale'] : 1.0;
$m_content_scale = isset( $attrs['mobileContentScale'] ) ? (float) $attrs['mobileContentScale'] : 0.92;

$group_x = max( -300, min( 300, $group_x ) );
$group_y = max( -300, min( 300, $group_y ) );
$m_group_x = max( -300, min( 300, $m_group_x ) );
$m_group_y = max( -300, min( 300, $m_group_y ) );
if ( $m_title_scale < 0.6 ) {
	$m_title_scale = 0.6;
}
if ( $m_title_scale > 1.2 ) {
	$m_title_scale = 1.2;
}

// Clamp scales.
if ( $content_scale < 0.7 ) {
	$content_scale = 0.7;
}
if ( $content_scale > 1.3 ) {
	$content_scale = 1.3;
}
if ( $m_content_scale < 0.6 ) {
	$m_content_scale = 0.6;
}
if ( $m_content_scale > 1.3 ) {
	$m_content_scale = 1.3;
}

// If new mobile content scale wasn't set (still default), fall back to old title scale for backwards compatibility.
// This keeps existing pages looking similar after update.
if ( ! isset( $attrs['mobileContentScale'] ) && isset( $attrs['mobileTitleScale'] ) ) {
	$m_content_scale = $m_title_scale;
}

$slides = [];
if ( ! empty( $attrs['slides'] ) && is_array( $attrs['slides'] ) ) {
	$slides = $attrs['slides'];
}

// Normalize slides to 1..12.
$slides = array_values( array_filter( $slides, 'is_array' ) );
while ( count( $slides ) < 1 ) {
	$slides[] = [];
}
if ( count( $slides ) > 12 ) {
	$slides = array_slice( $slides, 0, 12 );
}

$classes = [
	'hmpro-block',
	'hmpro-hero-slider',
	$full_width ? 'is-fullwidth' : 'is-boxed',
	$hide_mobile ? 'is-hide-mobile' : '',
	( $mobile_height_mode === 'square' ) ? 'is-mobile-square' : '',
	( $mobile_height_mode === 'compact' ) ? 'is-mobile-compact' : '',
];

$title_ff = hmpro_hero_sanitize_css_text( $attrs['titleFontFamily'] ?? '' );
$title_fw = hmpro_hero_sanitize_css_text( $attrs['titleFontWeight'] ?? '' );
$subtitle_ff = hmpro_hero_sanitize_css_text( $attrs['subtitleFontFamily'] ?? '' );
$subtitle_fw = hmpro_hero_sanitize_css_text( $attrs['subtitleFontWeight'] ?? '' );
$button_ff = hmpro_hero_sanitize_css_text( $attrs['buttonFontFamily'] ?? '' );
$button_fw = hmpro_hero_sanitize_css_text( $attrs['buttonFontWeight'] ?? '' );

$title_fs = isset( $attrs['titleFontSize'] ) ? (float) $attrs['titleFontSize'] : 0;
$title_fs_m = isset( $attrs['titleFontSizeMobile'] ) ? (float) $attrs['titleFontSizeMobile'] : 0;
$subtitle_fs = isset( $attrs['subtitleFontSize'] ) ? (float) $attrs['subtitleFontSize'] : 0;
$subtitle_fs_m = isset( $attrs['subtitleFontSizeMobile'] ) ? (float) $attrs['subtitleFontSizeMobile'] : 0;
$button_fs = isset( $attrs['buttonFontSize'] ) ? (float) $attrs['buttonFontSize'] : 0;
$button_fs_m = isset( $attrs['buttonFontSizeMobile'] ) ? (float) $attrs['buttonFontSizeMobile'] : 0;

$style = sprintf(
	'--hmpro-hero-h:%dpx;--hmpro-hero-h-m:%dvh;--hmpro-hero-bg-fit:%s;--hmpro-hero-overlay:%s;--hmpro-hero-maxw:%dpx;--hmpro-hero-group-x:%dpx;--hmpro-hero-group-y:%dpx;--hmpro-hero-group-x-m:%dpx;--hmpro-hero-group-y-m:%dpx;--hmpro-hero-scale:%s;--hmpro-hero-scale-m:%s;--hmpro-hero-title-scale-m:%s;',
	$height,
	$mobile_height_vh,
	esc_attr( $image_fit ),
	rtrim( rtrim( (string) $overlay, '0' ), '.' ),
	$max_width,
	$group_x,
	$group_y,
	$m_group_x,
	$m_group_y,
	rtrim( rtrim( (string) $content_scale, '0' ), '.' ),
	rtrim( rtrim( (string) $m_content_scale, '0' ), '.' ),
	rtrim( rtrim( (string) $m_title_scale, '0' ), '.' )
);

$typo_vars = [];
if ( $title_ff !== '' ) {
	$typo_vars[] = '--hmpro-hero-title-ff:' . $title_ff;
}
if ( $title_fw !== '' ) {
	$typo_vars[] = '--hmpro-hero-title-fw:' . $title_fw;
}
if ( $title_fs > 0 ) {
	$title_fs = max( 12, min( 120, $title_fs ) );
	$typo_vars[] = '--hmpro-hero-title-fs:' . rtrim( rtrim( (string) $title_fs, '0' ), '.' ) . 'px';
}
if ( $title_fs_m > 0 ) {
	$title_fs_m = max( 12, min( 96, $title_fs_m ) );
	$typo_vars[] = '--hmpro-hero-title-fs-m:' . rtrim( rtrim( (string) $title_fs_m, '0' ), '.' ) . 'px';
}
if ( $subtitle_ff !== '' ) {
	$typo_vars[] = '--hmpro-hero-subtitle-ff:' . $subtitle_ff;
}
if ( $subtitle_fw !== '' ) {
	$typo_vars[] = '--hmpro-hero-subtitle-fw:' . $subtitle_fw;
}
if ( $subtitle_fs > 0 ) {
	$subtitle_fs = max( 10, min( 60, $subtitle_fs ) );
	$typo_vars[] = '--hmpro-hero-subtitle-fs:' . rtrim( rtrim( (string) $subtitle_fs, '0' ), '.' ) . 'px';
}
if ( $subtitle_fs_m > 0 ) {
	$subtitle_fs_m = max( 10, min( 54, $subtitle_fs_m ) );
	$typo_vars[] = '--hmpro-hero-subtitle-fs-m:' . rtrim( rtrim( (string) $subtitle_fs_m, '0' ), '.' ) . 'px';
}
if ( $button_ff !== '' ) {
	$typo_vars[] = '--hmpro-hero-btn-ff:' . $button_ff;
}
if ( $button_fw !== '' ) {
	$typo_vars[] = '--hmpro-hero-btn-fw:' . $button_fw;
}
if ( $button_fs > 0 ) {
	$button_fs = max( 10, min( 42, $button_fs ) );
	$typo_vars[] = '--hmpro-hero-btn-fs:' . rtrim( rtrim( (string) $button_fs, '0' ), '.' ) . 'px';
}
if ( $button_fs_m > 0 ) {
	$button_fs_m = max( 10, min( 38, $button_fs_m ) );
	$typo_vars[] = '--hmpro-hero-btn-fs-m:' . rtrim( rtrim( (string) $button_fs_m, '0' ), '.' ) . 'px';
}

if ( $typo_vars ) {
	$style .= implode( ';', $typo_vars ) . ';';
}

$wrapper_attrs = get_block_wrapper_attributes( [
	'class' => implode( ' ', array_filter( $classes ) ),
	'style' => $style,
] );

$has_multiple = count( $slides ) > 1;

// v2 layout (scratch rebuild):
// - Remove the extra outer bleed wrapper that relied on global overflow hacks.
// - Keep the existing public class hooks so editor + JS (view.js) remain compatible.
// - Use an internal "bleed" layer that is absolutely positioned and does not affect layout width.

echo '<div ' . $wrapper_attrs . '>';
echo '<div class="hmpro-hero__frame">';
echo '<div class="hmpro-hero__bleed">';

echo '<div class="hmpro-hero__slides" data-autoplay="' . esc_attr( $autoplay ? '1' : '0' ) . '" data-interval="' . esc_attr( (string) $interval ) . '">';

foreach ( $slides as $i => $s ) {
	$s = is_array( $s ) ? $s : [];

	// One-time preload for the first slide (media-query gated).
	static $hmpro_hero_did_preload = false;

	$media_id   = isset( $s['mediaId'] ) ? absint( $s['mediaId'] ) : 0;
	$media_url  = isset( $s['mediaUrl'] ) ? esc_url_raw( (string) $s['mediaUrl'] ) : '';

	$media_id_t  = isset( $s['mediaIdTablet'] ) ? absint( $s['mediaIdTablet'] ) : 0;
	$media_url_t = isset( $s['mediaUrlTablet'] ) ? esc_url_raw( (string) $s['mediaUrlTablet'] ) : '';

	$media_id_m  = isset( $s['mediaIdMobile'] ) ? absint( $s['mediaIdMobile'] ) : 0;
	$media_url_m = isset( $s['mediaUrlMobile'] ) ? esc_url_raw( (string) $s['mediaUrlMobile'] ) : '';
	
	$title      = isset( $s['title'] ) ? sanitize_text_field( (string) $s['title'] ) : '';
	$subtitle   = isset( $s['subtitle'] ) ? sanitize_text_field( (string) $s['subtitle'] ) : '';
	$btn_text   = isset( $s['buttonText'] ) ? sanitize_text_field( (string) $s['buttonText'] ) : '';
	$btn_url    = isset( $s['buttonUrl'] ) ? esc_url_raw( (string) $s['buttonUrl'] ) : '';

	$t_col = hmpro_hero_sanitize_color( $s['titleColor'] ?? '' );
	$st_col = hmpro_hero_sanitize_color( $s['subtitleColor'] ?? '' );
	$b_col = hmpro_hero_sanitize_color( $s['buttonTextColor'] ?? '' );
	$bbg_col = hmpro_hero_sanitize_color( $s['buttonBgColor'] ?? '' );

	$resolved_url = '';
	if ( $media_id ) {
		$resolved_url = wp_get_attachment_image_url( $media_id, 'full' );
		if ( ! $resolved_url ) {
			$resolved_url = wp_get_attachment_url( $media_id );
		}
	}
	if ( ! $resolved_url && $media_url ) {
		$resolved_url = $media_url;
	}

	$resolved_t = '';
	if ( $media_id_t ) {
		$resolved_t = wp_get_attachment_image_url( $media_id_t, 'full' );
		if ( ! $resolved_t ) {
			$resolved_t = wp_get_attachment_url( $media_id_t );
		}
	}
	if ( ! $resolved_t && $media_url_t ) {
		$resolved_t = $media_url_t;
	}

	$resolved_m = '';
	if ( $media_id_m ) {
		$resolved_m = wp_get_attachment_image_url( $media_id_m, 'full' );
		if ( ! $resolved_m ) {
			$resolved_m = wp_get_attachment_url( $media_id_m );
		}
	}
	if ( ! $resolved_m && $media_url_m ) {
		$resolved_m = $media_url_m;
	}

	$is_active = ( $i === 0 );
	$slide_classes = 'hmpro-hero-slide' . ( $is_active ? ' is-active' : '' );

	$style_bits = [];
	if ( $resolved_url ) {
		$style_bits[] = '--hmpro-hero-bg-d:url(' . esc_url( $resolved_url ) . ')';
	}
	if ( $resolved_t ) {
		$style_bits[] = '--hmpro-hero-bg-t:url(' . esc_url( $resolved_t ) . ')';
	}
	if ( $resolved_m ) {
		$style_bits[] = '--hmpro-hero-bg-m:url(' . esc_url( $resolved_m ) . ')';
	}
	if ( $t_col ) {
		$style_bits[] = '--hmpro-hero-title-color:' . $t_col;
	}
	if ( $st_col ) {
		$style_bits[] = '--hmpro-hero-subtitle-color:' . $st_col;
	}
	if ( $b_col ) {
		$style_bits[] = '--hmpro-hero-btn-color:' . $b_col;
	}
	if ( $bbg_col ) {
		$style_bits[] = '--hmpro-hero-btn-bg:' . $bbg_col;
	}
	$slide_style = $style_bits ? ' style="' . esc_attr( implode( ';', $style_bits ) ) . ';"' : '';

	// Preload first-slide image(s) early in <head> (media-query gated) to improve LCP discovery.
	if ( $is_active && ! $hmpro_hero_did_preload ) {
		if ( $resolved_m ) {
			hmpro_hero_queue_preload( $resolved_m, '(max-width: 767px)' );
		}
		if ( $resolved_t ) {
			hmpro_hero_queue_preload( $resolved_t, '(min-width: 768px) and (max-width: 1024px)' );
		}
		if ( $resolved_url ) {
			hmpro_hero_queue_preload( $resolved_url, '(min-width: 1025px)' );
		}
		$hmpro_hero_did_preload = true;
	}

	
echo '<div class="' . esc_attr( $slide_classes ) . '" data-index="' . esc_attr( (string) $i ) . '" aria-hidden="' . esc_attr( $is_active ? 'false' : 'true' ) . '"' . $slide_style . '>';
	echo '<div class="hmpro-hero-slide__media">';

	// LCP: render first slide as real image so we can set fetchpriority/loading.
	if ( $is_active && ( $resolved_url || $resolved_t || $resolved_m ) ) {
		// Pick a safe fallback src (prefer mobile if exists, else desktop).
		$fallback_src = $resolved_m ? $resolved_m : ( $resolved_url ? $resolved_url : $resolved_t );

		echo '<picture class="hmpro-hero-slide__lcp-picture">';
		if ( $resolved_m ) {
			echo '<source media="(max-width: 767px)" srcset="' . esc_url( $resolved_m ) . '">';
		}
		if ( $resolved_t ) {
			echo '<source media="(min-width: 768px) and (max-width: 1024px)" srcset="' . esc_url( $resolved_t ) . '">';
		}
		if ( $resolved_url ) {
			echo '<source media="(min-width: 1025px)" srcset="' . esc_url( $resolved_url ) . '">';
		}
		echo '<img class="hmpro-hero-slide__lcp-img" src="' . esc_url( $fallback_src ) . '" alt="" fetchpriority="high" loading="eager" decoding="sync">';
		echo '</picture>';
	}

	echo '<div class="hmpro-hero-slide__bg"></div>';

	echo '</div>'; // media

	echo '<div class="hmpro-hero__overlay" aria-hidden="true"></div>';

	echo '<div class="hmpro-hero__inner">';
	echo '<div class="hmpro-hero__content">';

	if ( $title !== '' ) {
		echo '<div class="hmpro-hero__title">' . esc_html( $title ) . '</div>';
	}
	if ( $subtitle !== '' ) {
		echo '<div class="hmpro-hero__subtitle">' . esc_html( $subtitle ) . '</div>';
	}
	if ( $btn_text !== '' && $btn_url !== '' ) {
		echo '<a class="hmpro-hero__btn" href="' . esc_url( $btn_url ) . '">' . esc_html( $btn_text ) . '</a>';
	}

	echo '</div>'; // content
	echo '</div>'; // inner

	echo '</div>'; // slide
}

echo '</div>'; // slides

if ( $has_multiple && $show_arrows ) {
	echo '<button class="hmpro-hero__arrow hmpro-hero__arrow--prev" type="button" aria-label="Previous slide">‹</button>';
	echo '<button class="hmpro-hero__arrow hmpro-hero__arrow--next" type="button" aria-label="Next slide">›</button>';
}

if ( $has_multiple && $show_dots ) {
	echo '<div class="hmpro-hero__dots" role="tablist" aria-label="Slides">';
	for ( $i = 0; $i < count( $slides ); $i++ ) {
		$active = ( $i === 0 );
		echo '<button class="hmpro-hero__dot' . ( $active ? ' is-active' : '' ) . '" type="button" data-index="' . esc_attr( (string) $i ) . '" role="tab" aria-selected="' . ( $active ? 'true' : 'false' ) . '" tabindex="' . ( $active ? '0' : '-1' ) . '" aria-label="Go to slide ' . esc_attr( (string) ( $i + 1 ) ) . '"></button>';
	}
	echo '</div>';
}

echo '</div>'; // bleed
echo '</div>'; // frame
echo '</div>'; // wrapper
