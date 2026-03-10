<?php
/**
 * Header Background Banner (Top + Main)
 * - Renders inside the Header Builder wrapper (not the Hero section)
 * - Uses the same typography + mobile scaling logic as the banner system
 */
function hmpro_header_bg_banner_is_enabled() {
	if ( ! (int) get_theme_mod( 'hmpro_hb_enable', 0 ) ) {
		return false;
	}
	if ( (int) get_theme_mod( 'hmpro_hb_home_only', 0 ) === 1 && ! is_front_page() ) {
		return false;
	}
	return true;
}

function hmpro_render_header_bg_banner() {
	if ( ! hmpro_header_bg_banner_is_enabled() ) {
		return;
	}

	$show_title  = (int) get_theme_mod( 'hmpro_hb_show_title', 1 ) === 1;
	$show_text   = (int) get_theme_mod( 'hmpro_hb_show_text', 1 ) === 1;
	$show_button = (int) get_theme_mod( 'hmpro_hb_show_button', 1 ) === 1;

	$after_gap = absint( get_theme_mod( 'hmpro_hb_after_gap', 0 ) );
	if ( $after_gap > 1600 ) {
		$after_gap = 1600;
	}

	$slider_enabled = (int) get_theme_mod( 'hmpro_hb_slider_enable', 0 ) === 1;
	$slider_delay   = absint( get_theme_mod( 'hmpro_hb_slider_delay', 4500 ) );
	if ( $slider_delay < 1500 ) {
		$slider_delay = 1500;
	}
	if ( $slider_delay > 20000 ) {
		$slider_delay = 20000;
	}

	$img_raw = get_theme_mod( 'hmpro_hb_image', 0 );
	$image_id      = is_numeric( $img_raw ) ? absint( $img_raw ) : 0;
	$image_url_raw = ( ! $image_id && is_string( $img_raw ) ) ? trim( $img_raw ) : '';

	$use_video = (int) get_theme_mod( 'hmpro_hb_use_video', 0 ) === 1;
	$vid_raw   = get_theme_mod( 'hmpro_hb_video', 0 );
	$video_id      = is_numeric( $vid_raw ) ? absint( $vid_raw ) : 0;
	$video_url_raw = ( ! $video_id && is_string( $vid_raw ) ) ? trim( $vid_raw ) : '';

	$height        = absint( get_theme_mod( 'hmpro_hb_height', 320 ) );
	$hide_mobile   = (int) get_theme_mod( 'hmpro_hb_hide_mobile', 0 ) === 1;
	$height_mobile = absint( get_theme_mod( 'hmpro_hb_height_mobile', 0 ) );
	$overlay       = absint( get_theme_mod( 'hmpro_hb_overlay', 30 ) );

	$title = trim( (string) get_theme_mod( 'hmpro_hb_title', '' ) );
	$text  = trim( (string) get_theme_mod( 'hmpro_hb_text', '' ) );

	$btn_text = trim( (string) get_theme_mod( 'hmpro_hb_btn_text', '' ) );
	$btn_url  = trim( (string) get_theme_mod( 'hmpro_hb_btn_url', '' ) );
	$btn_new  = (int) get_theme_mod( 'hmpro_hb_btn_newtab', 0 ) === 1;

	// Typography / style.
	$title_color = trim( (string) get_theme_mod( 'hmpro_hb_title_color', '#ffffff' ) );
	$text_color  = trim( (string) get_theme_mod( 'hmpro_hb_text_color', '#ffffff' ) );
	$btn_bg      = trim( (string) get_theme_mod( 'hmpro_hb_btn_bg', '#d4af37' ) );
	$btn_color   = trim( (string) get_theme_mod( 'hmpro_hb_btn_color', '#111111' ) );
	$font_family = trim( (string) get_theme_mod( 'hmpro_hb_font_family', 'inherit' ) );

	$title_size = absint( get_theme_mod( 'hmpro_hb_title_size', 34 ) );
	$text_size  = absint( get_theme_mod( 'hmpro_hb_text_size', 16 ) );

	// Group transform (scale + move).
	$scale = (float) get_theme_mod( 'hmpro_hb_group_scale', 1 );
	if ( $scale < 0.5 ) {
		$scale = 0.5;
	}
	if ( $scale > 2.0 ) {
		$scale = 2.0;
	}
	$offset_x = (int) get_theme_mod( 'hmpro_hb_group_x', 0 );
	$offset_y = (int) get_theme_mod( 'hmpro_hb_group_y', 0 );

	$scale_mobile = (float) get_theme_mod( 'hmpro_hb_group_scale_mobile', 0 );
	if ( $scale_mobile < 0 ) {
		$scale_mobile = 0;
	}
	if ( $scale_mobile > 0 ) {
		if ( $scale_mobile < 0.5 ) {
			$scale_mobile = 0.5;
		}
		if ( $scale_mobile > 2.0 ) {
			$scale_mobile = 2.0;
		}
	}

	$top_mobile = (int) get_theme_mod( 'hmpro_hb_group_top_mobile', 0 );
	if ( $top_mobile < -200 ) {
		$top_mobile = -200;
	}
	if ( $top_mobile > 200 ) {
		$top_mobile = 200;
	}

	$img_url = $image_id ? wp_get_attachment_image_url( $image_id, 'full' ) : '';
	// Some environments may return an empty string for wp_get_attachment_image_url().
	// Fallback to the raw attachment URL so the banner background reliably renders.
	if ( ! $img_url && $image_id ) {
		$img_url = wp_get_attachment_url( $image_id );
	}
	if ( ! $img_url && $image_url_raw !== '' ) {
		$img_url = esc_url_raw( $image_url_raw );
	}

	$vid_url = ( $use_video && $video_id ) ? wp_get_attachment_url( $video_id ) : '';
	if ( ! $vid_url && $use_video && $video_url_raw !== '' ) {
		$vid_url = esc_url_raw( $video_url_raw );
	}

	// If video is active, always disable slider.
	if ( $vid_url ) {
		$slider_enabled = false;
	}

	$slider_urls = [];
	if ( $slider_enabled ) {
		for ( $i = 1; $i <= 5; $i++ ) {
			$raw = get_theme_mod( 'hmpro_hb_slider_img_' . $i, '' );
			$img_id = is_numeric( $raw ) ? absint( $raw ) : 0;
			$raw_url = ( ! $img_id && is_string( $raw ) ) ? trim( $raw ) : '';
			$url = $img_id ? wp_get_attachment_image_url( $img_id, 'full' ) : '';
			if ( ! $url && $img_id ) {
				$url = wp_get_attachment_url( $img_id );
			}
			if ( ! $url && $raw_url !== '' ) {
				$url = esc_url_raw( $raw_url );
			}
			if ( $url ) {
				$slider_urls[] = $url;
			}
		}
		// If no slider images, fallback to the single image.
		if ( empty( $slider_urls ) ) {
			$slider_enabled = false;
		}
	}

	// Require at least one media source.
	if ( ! $vid_url && ! $img_url && empty( $slider_urls ) ) {
		return;
	}

	$banner_title_font = 'var(--hm-heading-font)';
	$banner_text_font  = 'var(--hm-body-font)';
	if ( $font_family !== '' && $font_family !== 'inherit' ) {
		$banner_title_font = $font_family;
		$banner_text_font  = $font_family;
	}

	$style  = '--hmpro-hb-h:' . $height . 'px;';
	if ( $height_mobile > 0 ) {
		$style .= ' --hmpro-hb-h-m:' . $height_mobile . 'px;';
	}
	$style .= ' --hmpro-hb-after-gap:' . $after_gap . 'px;';
	$style .= ' --hmpro-hb-overlay:' . ( $overlay / 100 ) . ';';
	$style .= ' --hmpro-hb-title-color:' . $title_color . ';';
	$style .= ' --hmpro-hb-text-color:' . $text_color . ';';
	$style .= ' --hmpro-hb-btn-bg:' . $btn_bg . ';';
	$style .= ' --hmpro-hb-btn-color:' . $btn_color . ';';
	$style .= ' --hmpro-hb-title-font:' . $banner_title_font . ';';
	$style .= ' --hmpro-hb-text-font:' . $banner_text_font . ';';
	$style .= ' --hmpro-hb-title-size:' . $title_size . 'px;';
	$style .= ' --hmpro-hb-text-size:' . $text_size . 'px;';
	$style .= ' --hmpro-hb-group-scale:' . $scale . ';';
	if ( $scale_mobile > 0 ) {
		$style .= ' --hmpro-hb-group-scale-m:' . $scale_mobile . ';';
	}
	if ( $top_mobile !== 0 ) {
		$style .= ' --hmpro-hb-group-top-m:' . $top_mobile . 'px;';
	}
	$style .= ' --hmpro-hb-group-x:' . $offset_x . 'px;';
	$style .= ' --hmpro-hb-group-y:' . $offset_y . 'px;';
	if ( ! $vid_url && ! $slider_enabled && $img_url ) {
		$style .= ' background-image:url(' . esc_url( $img_url ) . ');';
	}

	$classes = 'hmpro-hb-banner';
	if ( $hide_mobile ) {
		$classes .= ' hmpro-hb-banner--hide-mobile';
	}
	if ( $slider_enabled ) {
		$classes .= ' hmpro-hb-banner--slider';
	}

	echo '<div class="' . esc_attr( $classes ) . '" style="' . esc_attr( $style ) . '">';
	if ( $slider_enabled && ! empty( $slider_urls ) ) {
		echo '<div class="hmpro-hb-slides" data-delay="' . esc_attr( (string) $slider_delay ) . '">';
		foreach ( $slider_urls as $idx => $u ) {
			$active = ( 0 === $idx ) ? ' is-active' : '';
			echo '<div class="hmpro-hb-slide' . esc_attr( $active ) . '" style="background-image:url(' . esc_url( $u ) . ')"></div>';
		}
		echo '</div>';
	}
	if ( $vid_url ) {
		echo '<video class="hmpro-hb-banner__video" autoplay muted loop playsinline>';
		echo '<source src="' . esc_url( $vid_url ) . '">';
		echo '</video>';
	}
	echo '<div class="hmpro-hb-banner__overlay" aria-hidden="true"></div>';
	echo '<div class="hmpro-hb-banner__inner">';
	echo '<div class="hmpro-hb-banner__content">';
	if ( $show_title && $title !== '' ) {
		echo '<div class="hmpro-hb-banner__title">' . esc_html( $title ) . '</div>';
	}
	if ( $show_text && $text !== '' ) {
		echo '<div class="hmpro-hb-banner__text">' . esc_html( $text ) . '</div>';
	}
	if ( $show_button && $btn_text !== '' && $btn_url !== '' ) {
		$target = $btn_new ? ' target="_blank" rel="noopener noreferrer"' : '';
		echo '<a class="hmpro-hb-banner__btn" href="' . esc_url( $btn_url ) . '"' . $target . '>' . esc_html( $btn_text ) . '</a>';
	}
	echo '</div>';
	echo '</div>';
	echo '</div>';
}
