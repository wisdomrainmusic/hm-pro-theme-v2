<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sanitize media control values.
 *
 * WP_Customize_Media_Control usually stores an attachment ID, but in some edge cases
 * (migrations, older exports, or manual input) a URL may be passed.
 *
 * We accept either:
 * - a positive integer attachment ID
 * - a safe URL string (will be normalized + stored as URL)
 */
function hmpro_sanitize_media_id_or_url( $value ) {
	if ( is_numeric( $value ) ) {
		return absint( $value );
	}

	$value = trim( (string) $value );
	if ( $value === '' ) {
		return '';
	}

	// Try to map URL back to an attachment ID when possible.
	$maybe_id = attachment_url_to_postid( $value );
	if ( $maybe_id ) {
		return absint( $maybe_id );
	}

	return esc_url_raw( $value );
}

add_action( 'customize_register', function ( $wp_customize ) {
	// NOTE: UI-only settings; frontend wiring happens via body classes + CSS/JS.

	// --------------------------------------------------
	// Header/Top Bar + Footer color controls
	// --------------------------------------------------
	$hmpro_header_section = 'hmpro_header_settings';
	if ( ! $wp_customize->get_section( $hmpro_header_section ) ) {
		$wp_customize->add_section( $hmpro_header_section, [
			'title'    => __( 'Header & Navigation', 'hm-pro-theme' ),
			'priority' => 30,
		] );
	}

	$wp_customize->add_setting( 'hmpro_topbar_bg_color', [
		'default'           => '',
		'sanitize_callback' => 'sanitize_hex_color',
		'transport'         => 'refresh',
	] );
	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'hmpro_topbar_bg_color', [
		'label'       => __( 'Top Bar Background Color', 'hm-pro-theme' ),
		'description' => __( 'Applies to Header Builder: Top region (header_top). Leave empty to use default styling.', 'hm-pro-theme' ),
		'section'     => $hmpro_header_section,
	] ) );

	$wp_customize->add_setting( 'hmpro_topbar_text_color', [
		'default'           => '',
		'sanitize_callback' => 'sanitize_hex_color',
		'transport'         => 'refresh',
	] );
	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'hmpro_topbar_text_color', [
		'label'       => __( 'Top Bar Text/Link Color', 'hm-pro-theme' ),
		'description' => __( 'Applies to text + links inside header_top. Leave empty to inherit.', 'hm-pro-theme' ),
		'section'     => $hmpro_header_section,
	] ) );

	// Top Bar Search field colors (improves readability on dark top bars).
	$wp_customize->add_setting( 'hmpro_topbar_search_text_color', [
		'default'           => '',
		'sanitize_callback' => 'sanitize_hex_color',
		'transport'         => 'refresh',
	] );
	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'hmpro_topbar_search_text_color', [
		'label'       => __( 'Search Input Text Color', 'hm-pro-theme' ),
		'description' => __( 'Applies to the search input text inside header_top. Leave empty to inherit.', 'hm-pro-theme' ),
		'section'     => $hmpro_header_section,
	] ) );

	$wp_customize->add_setting( 'hmpro_topbar_search_placeholder_color', [
		'default'           => '',
		'sanitize_callback' => 'sanitize_hex_color',
		'transport'         => 'refresh',
	] );
	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'hmpro_topbar_search_placeholder_color', [
		'label'       => __( 'Search Placeholder Color', 'hm-pro-theme' ),
		'description' => __( 'Applies to the search placeholder text inside header_top (e.g., “Ara…”). Leave empty to inherit.', 'hm-pro-theme' ),
		'section'     => $hmpro_header_section,
	] ) );

	// --------------------------------------------------
	// Top Bar height (Header Builder: Top region).
	$wp_customize->add_setting( 'hmpro_topbar_height', [
		'default'           => 0,
		'sanitize_callback' => function ( $v ) {
			$v = absint( $v );
			if ( $v && $v < 28 ) {
				$v = 28;
			}
			if ( $v > 240 ) {
				$v = 240;
			}
			return $v;
		},
		'transport'         => 'refresh',
	] );
	$wp_customize->add_control( 'hmpro_topbar_height', [
		'label'       => __( 'Top Bar Height (px)', 'hm-pro-theme' ),
		'description' => __( 'Sets a fixed minimum height for Header Builder: Top region (header_top). Use 0 for auto height.', 'hm-pro-theme' ),
		'section'     => $hmpro_header_section,
		'type'        => 'number',
		'input_attrs' => [ 'min' => 0, 'max' => 240, 'step' => 1 ],
	] );

	// --------------------------------------------------
	// Primary Menu color controls (Header Builder: Main region)
	// --------------------------------------------------
	$wp_customize->add_setting( 'hmpro_menu_text_color', [
		'default'           => '',
		'sanitize_callback' => 'sanitize_hex_color',
		'transport'         => 'refresh',
	] );
	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'hmpro_menu_text_color', [
		'label'       => __( 'Primary Menu Text/Link Color', 'hm-pro-theme' ),
		'description' => __( 'Applies to the main header menu links. Leave empty to inherit.', 'hm-pro-theme' ),
		'section'     => $hmpro_header_section,
	] ) );

	// Primary Menu color override for Header Banner overlay state.
	// Use this when the header sits on top of a dark banner/hero image and needs higher contrast.
	$wp_customize->add_setting( 'hmpro_menu_text_color_overlay', [
		'default'           => '',
		'sanitize_callback' => 'sanitize_hex_color',
		'transport'         => 'refresh',
	] );
	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'hmpro_menu_text_color_overlay', [
		'label'       => __( 'Primary Menu Text Color (Banner Overlay)', 'hm-pro-theme' ),
		'description' => __( 'Applies only when the Header Background Banner is active (e.g., homepage hero overlay). Leave empty to use the normal menu color.', 'hm-pro-theme' ),
		'section'     => $hmpro_header_section,
	] ) );

	$wp_customize->add_setting( 'hmpro_menu_hover_color', [
		'default'           => '',
		'sanitize_callback' => 'sanitize_hex_color',
		'transport'         => 'refresh',
	] );
	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'hmpro_menu_hover_color', [
		'label'       => __( 'Primary Menu Hover Color', 'hm-pro-theme' ),
		'description' => __( 'Applies on hover/focus for main header menu links. Leave empty to inherit.', 'hm-pro-theme' ),
		'section'     => $hmpro_header_section,
	] ) );

	$wp_customize->add_setting( 'hmpro_menu_active_color', [
		'default'           => '',
		'sanitize_callback' => 'sanitize_hex_color',
		'transport'         => 'refresh',
	] );
	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'hmpro_menu_active_color', [
		'label'       => __( 'Primary Menu Active Color', 'hm-pro-theme' ),
		'description' => __( 'Applies to current/active menu items in the main header menu. Leave empty to inherit.', 'hm-pro-theme' ),
		'section'     => $hmpro_header_section,
	] ) );

	
	// --------------------------------------------------
	// Mega Menu color controls (Mega Menu v2 Canvas)
	// --------------------------------------------------
	$wp_customize->add_setting( 'hmpro_mega_menu_bg_color', [
		'default'           => '',
		'sanitize_callback' => 'sanitize_hex_color',
		'transport'         => 'refresh',
	] );
	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'hmpro_mega_menu_bg_color', [
		'label'       => __( 'Mega Menu Background Color', 'hm-pro-theme' ),
		'description' => __( 'Applies to the Mega Menu v2 dropdown panel background. Leave empty to use default styling.', 'hm-pro-theme' ),
		'section'     => $hmpro_header_section,
	] ) );

	$wp_customize->add_setting( 'hmpro_mega_menu_link_color', [
		'default'           => '',
		'sanitize_callback' => 'sanitize_hex_color',
		'transport'         => 'refresh',
	] );
	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'hmpro_mega_menu_link_color', [
		'label'       => __( 'Mega Menu Link Color', 'hm-pro-theme' ),
		'description' => __( 'Applies to links inside the Mega Menu v2 dropdown panel. Leave empty to inherit.', 'hm-pro-theme' ),
		'section'     => $hmpro_header_section,
	] ) );

// Header Logo visibility (Header Builder: Logo component)
	$wp_customize->add_setting( 'hmpro_show_header_logo', [
		'default'           => 1,
		'sanitize_callback' => function ( $v ) {
			return (int) ( $v ? 1 : 0 );
		},
		'transport'         => 'refresh',
	] );
	$wp_customize->add_control( 'hmpro_show_header_logo', [
		'label'       => __( 'Show Header Logo', 'hm-pro-theme' ),
		'description' => __( 'Toggles the Logo component in the Header Builder (main region).', 'hm-pro-theme' ),
		'section'     => $hmpro_header_section,
		'type'        => 'checkbox',
	] );

	// --------------------------------------------------
	// Header Backdrop Panel (Top + Main)
	// --------------------------------------------------
	$wp_customize->add_setting( 'hmpro_header_backdrop_enable', [
		'default'           => 0,
		'sanitize_callback' => function ( $v ) {
			return (int) ( $v ? 1 : 0 );
		},
		'transport'         => 'refresh',
	] );
	$wp_customize->add_control( 'hmpro_header_backdrop_enable', [
		'label'       => __( 'Enable Header Backdrop Panel', 'hm-pro-theme' ),
		'description' => __( 'Adds a customizable opaque background behind Top + Main header regions (useful with Header Background / Hero overlays).', 'hm-pro-theme' ),
		'section'     => $hmpro_header_section,
		'type'        => 'checkbox',
	] );

	$wp_customize->add_setting( 'hmpro_header_backdrop_color', [
		'default'           => '',
		'sanitize_callback' => 'sanitize_hex_color',
		'transport'         => 'refresh',
	] );
	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'hmpro_header_backdrop_color', [
		'label'       => __( 'Header Backdrop Color', 'hm-pro-theme' ),
		'description' => __( 'Backdrop color behind header. Leave empty to use the preset/topbar color as base.', 'hm-pro-theme' ),
		'section'     => $hmpro_header_section,
	] ) );

	$wp_customize->add_setting( 'hmpro_header_backdrop_opacity', [
		'default'           => 85,
		'sanitize_callback' => 'absint',
		'transport'         => 'refresh',
	] );
	$wp_customize->add_control( 'hmpro_header_backdrop_opacity', [
		'label'       => __( 'Header Backdrop Opacity (%)', 'hm-pro-theme' ),
		'description' => __( 'Controls how opaque the header background panel is.', 'hm-pro-theme' ),
		'section'     => $hmpro_header_section,
		'type'        => 'range',
		'input_attrs' => [ 'min' => 0, 'max' => 100, 'step' => 1 ],
	] );

	$wp_customize->add_setting( 'hmpro_footer_bg_color', [
		'default'           => '',
		'sanitize_callback' => 'sanitize_hex_color',
		'transport'         => 'refresh',
	] );
	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'hmpro_footer_bg_color', [
		'label'       => __( 'Footer Background Color', 'hm-pro-theme' ),
		'description' => __( 'Applies to the Footer Builder wrapper (#site-footer). Leave empty to use default styling.', 'hm-pro-theme' ),
		'section'     => $hmpro_header_section,
	] ) );

	$wp_customize->add_setting( 'hmpro_footer_text_color', [
		'default'           => '',
		'sanitize_callback' => 'sanitize_hex_color',
		'transport'         => 'refresh',
	] );
	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'hmpro_footer_text_color', [
		'label'       => __( 'Footer Text/Link Color', 'hm-pro-theme' ),
		'description' => __( 'Applies to text + links inside footer builder. Leave empty to inherit.', 'hm-pro-theme' ),
		'section'     => $hmpro_header_section,
	] ) );

	// --------------------------------------------------
	// Social Icon Styles (Header Builder)
	// --------------------------------------------------
	$wp_customize->add_setting( 'hmpro_social_icon_color', [
		'default'           => '',
		'sanitize_callback' => 'sanitize_hex_color',
		'transport'         => 'refresh',
	] );
	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'hmpro_social_icon_color', [
		'label'       => __( 'Social Icon Color', 'hm-pro-theme' ),
		'description' => __( 'Applies to Social Icon Button icons (header/footer). Leave empty to inherit preset defaults.', 'hm-pro-theme' ),
		'section'     => $hmpro_header_section,
	] ) );

	$wp_customize->add_setting( 'hmpro_social_icon_bg', [
		'default'           => '',
		'sanitize_callback' => 'sanitize_hex_color',
		'transport'         => 'refresh',
	] );
	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'hmpro_social_icon_bg', [
		'label'       => __( 'Social Icon Background', 'hm-pro-theme' ),
		'description' => __( 'Background color for the social icon circle. Leave empty to inherit.', 'hm-pro-theme' ),
		'section'     => $hmpro_header_section,
	] ) );

	$wp_customize->add_setting( 'hmpro_social_icon_border', [
		'default'           => '',
		'sanitize_callback' => 'sanitize_hex_color',
		'transport'         => 'refresh',
	] );
	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'hmpro_social_icon_border', [
		'label'       => __( 'Social Icon Border', 'hm-pro-theme' ),
		'description' => __( 'Border color for the social icon circle. Leave empty to inherit.', 'hm-pro-theme' ),
		'section'     => $hmpro_header_section,
	] ) );

	$wp_customize->add_setting( 'hmpro_social_icon_hover_color', [
		'default'           => '',
		'sanitize_callback' => 'sanitize_hex_color',
		'transport'         => 'refresh',
	] );
	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'hmpro_social_icon_hover_color', [
		'label'       => __( 'Social Icon Hover Color', 'hm-pro-theme' ),
		'description' => __( 'Icon color on hover/focus. Leave empty to inherit.', 'hm-pro-theme' ),
		'section'     => $hmpro_header_section,
	] ) );

	$wp_customize->add_setting( 'hmpro_social_icon_hover_bg', [
		'default'           => '',
		'sanitize_callback' => 'sanitize_hex_color',
		'transport'         => 'refresh',
	] );
	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'hmpro_social_icon_hover_bg', [
		'label'       => __( 'Social Icon Hover Background', 'hm-pro-theme' ),
		'description' => __( 'Background color on hover/focus. Leave empty to inherit.', 'hm-pro-theme' ),
		'section'     => $hmpro_header_section,
	] ) );

	$wp_customize->add_setting( 'hmpro_social_icon_hover_border', [
		'default'           => '',
		'sanitize_callback' => 'sanitize_hex_color',
		'transport'         => 'refresh',
	] );
	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'hmpro_social_icon_hover_border', [
		'label'       => __( 'Social Icon Hover Border', 'hm-pro-theme' ),
		'description' => __( 'Border color on hover/focus. Leave empty to inherit.', 'hm-pro-theme' ),
		'section'     => $hmpro_header_section,
	] ) );

	$wp_customize->add_setting( 'hmpro_social_icon_contrast', [
		'default'           => '',
		'sanitize_callback' => 'sanitize_hex_color',
		'transport'         => 'refresh',
	] );
	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'hmpro_social_icon_contrast', [
		'label'       => __( 'Social Icon Contrast Fill', 'hm-pro-theme' ),
		'description' => __( 'Used for inner cut-outs (e.g., YouTube play triangle). Leave empty to inherit.', 'hm-pro-theme' ),
		'section'     => $hmpro_header_section,
	] ) );

	$wp_customize->add_setting( 'hmpro_social_icon_size', [
		'default'           => 34,
		'sanitize_callback' => 'absint',
		'transport'         => 'refresh',
	] );
	$wp_customize->add_control( 'hmpro_social_icon_size', [
		'label'       => __( 'Social Icon Size (px)', 'hm-pro-theme' ),
		'description' => __( 'Controls the circle size for Social Icon Button.', 'hm-pro-theme' ),
		'section'     => $hmpro_header_section,
		'type'        => 'range',
		'input_attrs' => [ 'min' => 20, 'max' => 64, 'step' => 1 ],
	] );

	$wp_customize->add_setting( 'hmpro_social_icon_radius', [
		'default'           => 999,
		'sanitize_callback' => 'absint',
		'transport'         => 'refresh',
	] );
	$wp_customize->add_control( 'hmpro_social_icon_radius', [
		'label'       => __( 'Social Icon Radius (px)', 'hm-pro-theme' ),
		'description' => __( '999 = full circle, smaller values = rounded square.', 'hm-pro-theme' ),
		'section'     => $hmpro_header_section,
		'type'        => 'range',
		'input_attrs' => [ 'min' => 0, 'max' => 999, 'step' => 1 ],
	] );

	$wp_customize->add_setting( 'hmpro_social_icon_svg_size', [
		'default'           => 18,
		'sanitize_callback' => 'absint',
		'transport'         => 'refresh',
	] );
	$wp_customize->add_control( 'hmpro_social_icon_svg_size', [
		'label'       => __( 'Social Icon SVG Size (px)', 'hm-pro-theme' ),
		'description' => __( 'Controls the inner SVG size.', 'hm-pro-theme' ),
		'section'     => $hmpro_header_section,
		'type'        => 'range',
		'input_attrs' => [ 'min' => 12, 'max' => 40, 'step' => 1 ],
	] );

	// Reset button (Header Top + Footer colors)
	if ( class_exists( 'WP_Customize_Control' ) && ! class_exists( 'HMPRO_Reset_Colors_Control' ) ) {
		class HMPRO_Reset_Colors_Control extends WP_Customize_Control {
			public $type = 'hmpro_reset_colors';
			public function render_content() {
				$nonce = wp_create_nonce( 'hmpro_reset_header_footer_colors' );
				?>
				<span class="customize-control-title"><?php esc_html_e( 'Reset Header UI Colors', 'hm-pro-theme' ); ?></span>
				<p class="description"><?php esc_html_e( 'Clears the Top Bar, Primary Menu, and Footer color overrides and returns to theme preset defaults.', 'hm-pro-theme' ); ?></p>
				<button type="button" class="button" id="hmpro-reset-hf-colors" data-nonce="<?php echo esc_attr( $nonce ); ?>">
					<?php esc_html_e( 'Reset to Defaults', 'hm-pro-theme' ); ?>
				</button>
				<?php
			}
		}
	}

	$wp_customize->add_setting( 'hmpro_reset_hf_colors_dummy', [
		'default'           => '',
		'sanitize_callback' => 'sanitize_text_field',
		'transport'         => 'refresh',
	] );
	$wp_customize->add_control( new HMPRO_Reset_Colors_Control( $wp_customize, 'hmpro_reset_hf_colors_dummy', [
		'section' => $hmpro_header_section,
	] ) );
	$wp_customize->add_setting( 'hmpro_logo_max_height', [
		'default'           => 56,
		'sanitize_callback' => 'absint',
		'transport'         => 'refresh',
	] );

	$wp_customize->add_control( 'hmpro_logo_max_height', [
		'label'       => __( 'Logo Max Height (px)', 'hm-pro-theme' ),
		'section'     => 'title_tagline',
		'type'        => 'range',
		'input_attrs' => [
			'min'  => 24,
			'max'  => 160,
			'step' => 1,
		],
	] );

	// Mobile logo sizing (separate control for mobile header)
	$wp_customize->add_setting( 'hmpro_mobile_logo_max_height', [
		'default'           => 64,
		'sanitize_callback' => 'absint',
		'transport'         => 'refresh',
	] );

	$wp_customize->add_control( 'hmpro_mobile_logo_max_height', [
		'label'       => __( 'Mobil Logo Max Height (px)', 'hm-pro-theme' ),
		'section'     => 'title_tagline',
		'type'        => 'range',
		'input_attrs' => [
			'min'  => 28,
			'max'  => 140,
			'step' => 1,
		],
	] );


	// Footer logo sizing (separate control for footer builder logo).
	$wp_customize->add_setting( 'hmpro_footer_logo_max_height', [
		'default'           => 96,
		'sanitize_callback' => 'absint',
		'transport'         => 'refresh',
	] );

	$wp_customize->add_control( 'hmpro_footer_logo_max_height', [
		'label'       => __( 'Footer Logo Max Height (px)', 'hm-pro-theme' ),
		'section'     => 'title_tagline',
		'type'        => 'range',
		'input_attrs' => [
			'min'  => 24,
			'max'  => 220,
			'step' => 1,
		],
	] );

	// Mega Menu v2 kill switch (canvas frontend).
	$wp_customize->add_setting( 'hmpro_enable_mega_menu_v2', [
		'default'           => 0,
		'sanitize_callback' => 'absint',
		'transport'         => 'refresh',
	] );

	$wp_customize->add_control( 'hmpro_enable_mega_menu_v2', [
		'label'       => __( 'Mega Menü v2 (Canvas Düzen) Etkinleştir', 'hm-pro-theme' ),
		'description' => __( 'Deneyseldir. Sadece Mega Menü v2 (Canvas) ön yüz çıktısını açar/kapatır. Header Builder yerleşimini veya mevcut menü yapınızı değiştirmez. Sorun yaşarsanız kapatıp kaydedin.', 'hm-pro-theme' ),
		'section' => 'title_tagline',
		'type'    => 'checkbox',
	] );

	// --------------------------------------------------
	// Header Background Banner (Top + Main)
	// Elementor kullanmadan menü arkasına arka plan + metin/CTA
	// --------------------------------------------------
	$wp_customize->add_section( 'hmpro_header_bg_banner', [
		'title'       => __( 'Header Arka Plan Banner', 'hm-pro-theme' ),
		'description' => __( 'Header Top + Main alanına arka plan görseli/video ve opsiyonel metin/CTA ekler.', 'hm-pro-theme' ),
		'priority'    => 34,
	] );

	$wp_customize->add_setting( 'hmpro_hb_enable', [
		'default'           => 0,
		'sanitize_callback' => 'absint',
		'transport'         => 'refresh',
	] );
	$wp_customize->add_control( 'hmpro_hb_enable', [
		'label'       => __( 'Header Banner aktif', 'hm-pro-theme' ),
		'description' => __( 'Aktif olunca header alanı genişler ve arka plan + içerik gösterilir.', 'hm-pro-theme' ),
		'section'     => 'hmpro_header_bg_banner',
		'type'        => 'checkbox',
	] );

	$wp_customize->add_setting( 'hmpro_hb_home_only', [
		'default'           => 0,
		'sanitize_callback' => 'absint',
		'transport'         => 'refresh',
	] );
	$wp_customize->add_control( 'hmpro_hb_home_only', [
		'label'       => __( 'Sadece ana sayfada göster', 'hm-pro-theme' ),
		'description' => __( 'Açık olursa header banner sadece ana sayfada görünür.', 'hm-pro-theme' ),
		'section'     => 'hmpro_header_bg_banner',
		'type'        => 'checkbox',
	] );

	$wp_customize->add_setting( 'hmpro_hb_image', [
		'default'           => '',
		'sanitize_callback' => 'hmpro_sanitize_media_id_or_url',
		'transport'         => 'refresh',
	] );
	$wp_customize->add_control( new WP_Customize_Media_Control( $wp_customize, 'hmpro_hb_image', [
		'label'       => __( 'Header Banner görseli', 'hm-pro-theme' ),
		'description' => __( 'Header arka plan görseli. Video seçmezseniz bu görsel kullanılır.', 'hm-pro-theme' ),
		'section'     => 'hmpro_header_bg_banner',
		'mime_type'   => 'image',
	] ) );

	$wp_customize->add_setting( 'hmpro_hb_use_video', [
		'default'           => 0,
		'sanitize_callback' => 'absint',
		'transport'         => 'refresh',
	] );
	$wp_customize->add_control( 'hmpro_hb_use_video', [
		'label'       => __( 'Header Banner video kullan (opsiyonel)', 'hm-pro-theme' ),
		'description' => __( 'MP4/WebM seçerseniz arkaplanda otomatik döngü oynar (muted). Video yoksa görsele düşer.', 'hm-pro-theme' ),
		'section'     => 'hmpro_header_bg_banner',
		'type'        => 'checkbox',
	] );

	$wp_customize->add_setting( 'hmpro_hb_video', [
		'default'           => '',
		'sanitize_callback' => 'hmpro_sanitize_media_id_or_url',
		'transport'         => 'refresh',
	] );
	$wp_customize->add_control( new WP_Customize_Media_Control( $wp_customize, 'hmpro_hb_video', [
		'label'       => __( 'Header Banner video dosyası (mp4/webm)', 'hm-pro-theme' ),
		'description' => __( 'Örnek: kısa ve optimize video önerilir.', 'hm-pro-theme' ),
		'section'     => 'hmpro_header_bg_banner',
		'mime_type'   => 'video',
	] ) );

	$wp_customize->add_setting( 'hmpro_hb_height', [
		'default'           => 320,
		'sanitize_callback' => function ( $v ) {
			$v = absint( $v );
			if ( $v < 120 ) {
				$v = 120;
			}
			if ( $v > 1200 ) {
				$v = 1200;
			}
			return $v;
		},
		'transport'         => 'refresh',
	] );
	$wp_customize->add_control( 'hmpro_hb_height', [
		'label'       => __( 'Header Banner yüksekliği (px)', 'hm-pro-theme' ),
		'description' => __( 'Öneri: 260–420 arası. Daha büyük başlık kullanıyorsanız artırabilirsiniz.', 'hm-pro-theme' ),
		'section'     => 'hmpro_header_bg_banner',
		'type'        => 'number',
		'input_attrs' => [ 'min' => 120, 'max' => 1200, 'step' => 10 ],
	] );

	$wp_customize->add_setting( 'hmpro_hb_hide_mobile', [
		'default'           => 0,
		'sanitize_callback' => function ( $v ) {
			return (int) ( $v ? 1 : 0 );
		},
		'transport'         => 'refresh',
	] );
	$wp_customize->add_control( 'hmpro_hb_hide_mobile', [
		'label'       => __( 'Mobilde Header Banner gizle', 'hm-pro-theme' ),
		'description' => __( 'Mobilde header banner alanını kapatır (daha hızlı ve daha az kaydırma).', 'hm-pro-theme' ),
		'section'     => 'hmpro_header_bg_banner',
		'type'        => 'checkbox',
	] );

	$wp_customize->add_setting( 'hmpro_hb_height_mobile', [
		'default'           => 0,
		'sanitize_callback' => function ( $v ) {
			$v = absint( $v );
			if ( $v > 1200 ) {
				$v = 1200;
			}
			return $v;
		},
		'transport'         => 'refresh',
	] );
	$wp_customize->add_control( 'hmpro_hb_height_mobile', [
		'label'       => __( 'Mobil Header Banner yüksekliği (px)', 'hm-pro-theme' ),
		'description' => __( '0 bırakırsanız masaüstü yüksekliğini baz alır. Öneri: 220–360.', 'hm-pro-theme' ),
		'section'     => 'hmpro_header_bg_banner',
		'type'        => 'number',
		'input_attrs' => [ 'min' => 0, 'max' => 1200, 'step' => 10 ],
	] );

	$wp_customize->add_setting( 'hmpro_hb_overlay', [
		'default'           => 30,
		'sanitize_callback' => function ( $v ) {
			$v = absint( $v );
			if ( $v < 0 ) {
				$v = 0;
			}
			if ( $v > 80 ) {
				$v = 80;
			}
			return $v;
		},
		'transport'         => 'refresh',
	] );
	$wp_customize->add_control( 'hmpro_hb_overlay', [
		'label'       => __( 'Overlay karartma (%)', 'hm-pro-theme' ),
		'description' => __( 'Metin okunurluğu için arka plana karartma ekler.', 'hm-pro-theme' ),
		'section'     => 'hmpro_header_bg_banner',
		'type'        => 'number',
		'input_attrs' => [ 'min' => 0, 'max' => 80, 'step' => 5 ],
	] );

	$wp_customize->add_setting( 'hmpro_hb_title', [
		'default'           => '',
		'sanitize_callback' => 'sanitize_text_field',
		'transport'         => 'refresh',
	] );
	$wp_customize->add_control( 'hmpro_hb_title', [
		'label'   => __( 'Header başlık (opsiyonel)', 'hm-pro-theme' ),
		'section' => 'hmpro_header_bg_banner',
		'type'    => 'text',
	] );

	$wp_customize->add_setting( 'hmpro_hb_text', [
		'default'           => '',
		'sanitize_callback' => 'sanitize_textarea_field',
		'transport'         => 'refresh',
	] );
	$wp_customize->add_control( 'hmpro_hb_text', [
		'label'   => __( 'Header açıklama (opsiyonel)', 'hm-pro-theme' ),
		'section' => 'hmpro_header_bg_banner',
		'type'    => 'textarea',
	] );

	$wp_customize->add_setting( 'hmpro_hb_btn_text', [
		'default'           => '',
		'sanitize_callback' => 'sanitize_text_field',
		'transport'         => 'refresh',
	] );
	$wp_customize->add_control( 'hmpro_hb_btn_text', [
		'label'   => __( 'Header buton metni (opsiyonel)', 'hm-pro-theme' ),
		'section' => 'hmpro_header_bg_banner',
		'type'    => 'text',
	] );

	$wp_customize->add_setting( 'hmpro_hb_btn_url', [
		'default'           => '',
		'sanitize_callback' => 'esc_url_raw',
		'transport'         => 'refresh',
	] );
	$wp_customize->add_control( 'hmpro_hb_btn_url', [
		'label'   => __( 'Header buton linki (URL)', 'hm-pro-theme' ),
		'section' => 'hmpro_header_bg_banner',
		'type'    => 'url',
	] );

	$wp_customize->add_setting( 'hmpro_hb_btn_newtab', [
		'default'           => 0,
		'sanitize_callback' => 'absint',
		'transport'         => 'refresh',
	] );
	$wp_customize->add_control( 'hmpro_hb_btn_newtab', [
		'label'   => __( 'Butonu yeni sekmede aç', 'hm-pro-theme' ),
		'section' => 'hmpro_header_bg_banner',
		'type'    => 'checkbox',
	] );

	// Professional toggles: allow hiding title/text/button without using placeholders.
	$wp_customize->add_setting( 'hmpro_hb_show_title', [
		'default'           => 1,
		'sanitize_callback' => 'absint',
		'transport'         => 'refresh',
	] );
	$wp_customize->add_control( 'hmpro_hb_show_title', [
		'label'       => __( 'Başlığı göster', 'hm-pro-theme' ),
		'description' => __( 'Kapalıysa başlık alanı hiç render edilmez.', 'hm-pro-theme' ),
		'section'     => 'hmpro_header_bg_banner',
		'type'        => 'checkbox',
	] );

	$wp_customize->add_setting( 'hmpro_hb_show_text', [
		'default'           => 1,
		'sanitize_callback' => 'absint',
		'transport'         => 'refresh',
	] );
	$wp_customize->add_control( 'hmpro_hb_show_text', [
		'label'       => __( 'Açıklamayı göster', 'hm-pro-theme' ),
		'description' => __( 'Kapalıysa açıklama alanı hiç render edilmez.', 'hm-pro-theme' ),
		'section'     => 'hmpro_header_bg_banner',
		'type'        => 'checkbox',
	] );

	$wp_customize->add_setting( 'hmpro_hb_show_button', [
		'default'           => 1,
		'sanitize_callback' => 'absint',
		'transport'         => 'refresh',
	] );
	$wp_customize->add_control( 'hmpro_hb_show_button', [
		'label'       => __( 'Butonu göster', 'hm-pro-theme' ),
		'description' => __( 'Kapalıysa buton alanı hiç render edilmez.', 'hm-pro-theme' ),
		'section'     => 'hmpro_header_bg_banner',
		'type'        => 'checkbox',
	] );

	// Spacing control: keep the first section balanced.
	$wp_customize->add_setting( 'hmpro_hb_after_gap', [
		'default'           => 0,
		'sanitize_callback' => function ( $v ) {
			$v = (int) $v;
			if ( $v < 0 ) {
				$v = 0;
			}
			if ( $v > 1600 ) {
				$v = 1600;
			}
			return $v;
		},
		'transport'         => 'refresh',
	] );
	$wp_customize->add_control( 'hmpro_hb_after_gap', [
		'label'       => __( 'Banner sonrası boşluk (px)', 'hm-pro-theme' ),
		'description' => __( 'İlk içerik bloğunun bannerın hemen altından başlaması için 0 önerilir.', 'hm-pro-theme' ),
		'section'     => 'hmpro_header_bg_banner',
		'type'        => 'number',
		'input_attrs' => [ 'min' => 0, 'max' => 1600, 'step' => 1 ],
	] );

	// Slider / Gallery (playful premium upgrade).
	$wp_customize->add_setting( 'hmpro_hb_slider_enable', [
		'default'           => 0,
		'sanitize_callback' => 'absint',
		'transport'         => 'refresh',
	] );
	$wp_customize->add_control( 'hmpro_hb_slider_enable', [
		'label'       => __( 'Header Banner Slider (Galeri) aktif', 'hm-pro-theme' ),
		'description' => __( 'Seçtiğiniz görseller banner arkasında otomatik geçiş yapar. Video seçiliyse slider devre dışı kalır.', 'hm-pro-theme' ),
		'section'     => 'hmpro_header_bg_banner',
		'type'        => 'checkbox',
	] );

	$wp_customize->add_setting( 'hmpro_hb_slider_delay', [
		'default'           => 4500,
		'sanitize_callback' => function ( $v ) {
			$v = absint( $v );
			if ( $v < 1500 ) {
				$v = 1500;
			}
			if ( $v > 20000 ) {
				$v = 20000;
			}
			return $v;
		},
		'transport'         => 'refresh',
	] );
	$wp_customize->add_control( 'hmpro_hb_slider_delay', [
		'label'       => __( 'Slider geçiş süresi (ms)', 'hm-pro-theme' ),
		'description' => __( 'Öneri: 3500–6000.', 'hm-pro-theme' ),
		'section'     => 'hmpro_header_bg_banner',
		'type'        => 'number',
		'input_attrs' => [ 'min' => 1500, 'max' => 20000, 'step' => 250 ],
	] );

	for ( $i = 1; $i <= 5; $i++ ) {
		$setting_id = 'hmpro_hb_slider_img_' . $i;
		$wp_customize->add_setting( $setting_id, [
			'default'           => '',
			'sanitize_callback' => 'hmpro_sanitize_media_id_or_url',
			'transport'         => 'refresh',
		] );
		$wp_customize->add_control( new WP_Customize_Media_Control( $wp_customize, $setting_id, [
			'label'       => sprintf( __( 'Slider görseli %d', 'hm-pro-theme' ), $i ),
			'section'     => 'hmpro_header_bg_banner',
			'mime_type'   => 'image',
		] ) );
	}

	$wp_customize->add_setting( 'hmpro_hb_title_color', [
		'default'           => '#ffffff',
		'sanitize_callback' => 'sanitize_hex_color',
		'transport'         => 'refresh',
	] );
	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'hmpro_hb_title_color', [
		'label'   => __( 'Header başlık rengi', 'hm-pro-theme' ),
		'section' => 'hmpro_header_bg_banner',
	] ) );

	$wp_customize->add_setting( 'hmpro_hb_text_color', [
		'default'           => '#ffffff',
		'sanitize_callback' => 'sanitize_hex_color',
		'transport'         => 'refresh',
	] );
	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'hmpro_hb_text_color', [
		'label'   => __( 'Header açıklama rengi', 'hm-pro-theme' ),
		'section' => 'hmpro_header_bg_banner',
	] ) );

	$wp_customize->add_setting( 'hmpro_hb_btn_bg', [
		'default'           => '#d4af37',
		'sanitize_callback' => 'sanitize_hex_color',
		'transport'         => 'refresh',
	] );
	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'hmpro_hb_btn_bg', [
		'label'   => __( 'Header buton arka plan rengi', 'hm-pro-theme' ),
		'section' => 'hmpro_header_bg_banner',
	] ) );

	$wp_customize->add_setting( 'hmpro_hb_btn_color', [
		'default'           => '#111111',
		'sanitize_callback' => 'sanitize_hex_color',
		'transport'         => 'refresh',
	] );
	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'hmpro_hb_btn_color', [
		'label'   => __( 'Header buton yazı rengi', 'hm-pro-theme' ),
		'section' => 'hmpro_header_bg_banner',
	] ) );

	$wp_customize->add_setting( 'hmpro_hb_title_size', [
		'default'           => 36,
		'sanitize_callback' => function ( $v ) {
			$v = absint( $v );
			if ( $v < 16 ) {
				$v = 16;
			}
			if ( $v > 96 ) {
				$v = 96;
			}
			return $v;
		},
		'transport'         => 'refresh',
	] );
	$wp_customize->add_control( 'hmpro_hb_title_size', [
		'label'       => __( 'Header başlık font boyutu (px)', 'hm-pro-theme' ),
		'section'     => 'hmpro_header_bg_banner',
		'type'        => 'number',
		'input_attrs' => [ 'min' => 16, 'max' => 96, 'step' => 1 ],
	] );

	$wp_customize->add_setting( 'hmpro_hb_text_size', [
		'default'           => 16,
		'sanitize_callback' => function ( $v ) {
			$v = absint( $v );
			if ( $v < 12 ) {
				$v = 12;
			}
			if ( $v > 40 ) {
				$v = 40;
			}
			return $v;
		},
		'transport'         => 'refresh',
	] );
	$wp_customize->add_control( 'hmpro_hb_text_size', [
		'label'       => __( 'Header açıklama font boyutu (px)', 'hm-pro-theme' ),
		'section'     => 'hmpro_header_bg_banner',
		'type'        => 'number',
		'input_attrs' => [ 'min' => 12, 'max' => 40, 'step' => 1 ],
	] );

	$wp_customize->add_setting( 'hmpro_hb_font_family', [
		'default'           => 'inherit',
		'sanitize_callback' => 'sanitize_text_field',
		'transport'         => 'refresh',
	] );
	$wp_customize->add_control( 'hmpro_hb_font_family', [
		'label'       => __( 'Header yazı tipi (Font Family)', 'hm-pro-theme' ),
		'description' => __( 'Hazır seçeneklerden seçebilirsiniz. Özel bir font-family yazmak isterseniz “Tema varsayılanı” seçip aşağıdaki alana manuel girebilirsiniz.', 'hm-pro-theme' ),
		'section'     => 'hmpro_header_bg_banner',
		'type'        => 'select',
		'choices'     => [
			'inherit'                      => __( 'Tema varsayılanı (inherit)', 'hm-pro-theme' ),
			'Inter, sans-serif'            => __( 'Inter', 'hm-pro-theme' ),
			'Poppins, sans-serif'          => __( 'Poppins', 'hm-pro-theme' ),
			'Lato, sans-serif'             => __( 'Lato', 'hm-pro-theme' ),
			'"Playfair Display", serif'   => __( 'Playfair Display', 'hm-pro-theme' ),
			'"Dancing Script", cursive'   => __( 'Dancing Script', 'hm-pro-theme' ),
			'serif'                        => __( 'Serif (genel)', 'hm-pro-theme' ),
			'sans-serif'                   => __( 'Sans-serif (genel)', 'hm-pro-theme' ),
		],
	] );

	$wp_customize->add_setting( 'hmpro_hb_group_scale', [
		'default'           => 1,
		'sanitize_callback' => function ( $v ) {
			$v = (float) $v;
			if ( $v < 0.5 ) {
				$v = 0.5;
			}
			if ( $v > 2.0 ) {
				$v = 2.0;
			}
			return $v;
		},
		'transport'         => 'refresh',
	] );
	$wp_customize->add_control( 'hmpro_hb_group_scale', [
		'label'       => __( 'Header içerik grubu ölçek (büyüt/küçült)', 'hm-pro-theme' ),
		'description' => __( 'Başlık + açıklama + buton birlikte büyür/küçülür.', 'hm-pro-theme' ),
		'section'     => 'hmpro_header_bg_banner',
		'type'        => 'number',
		'input_attrs' => [ 'min' => 0.5, 'max' => 2.0, 'step' => 0.05 ],
	] );

	$wp_customize->add_setting( 'hmpro_hb_group_scale_mobile', [
		'default'           => 0,
		'sanitize_callback' => function ( $v ) {
			if ( $v === '' || $v === null ) {
				return 0;
			}
			$v = (float) $v;
			if ( $v <= 0 ) {
				return 0;
			}
			if ( $v < 0.5 ) {
				$v = 0.5;
			}
			if ( $v > 2.0 ) {
				$v = 2.0;
			}
			return $v;
		},
		'transport'         => 'refresh',
	] );
	$wp_customize->add_control( 'hmpro_hb_group_scale_mobile', [
		'label'       => __( 'Mobil header içerik grubu ölçek (opsiyonel)', 'hm-pro-theme' ),
		'description' => __( 'Sadece mobilde içerik grubunu büyütür/küçültür. 0 bırakırsanız masaüstü ölçeğini baz alır.', 'hm-pro-theme' ),
		'section'     => 'hmpro_header_bg_banner',
		'type'        => 'number',
		'input_attrs' => [ 'min' => 0, 'max' => 2.0, 'step' => 0.05 ],
	] );

	$wp_customize->add_setting( 'hmpro_hb_group_top_mobile', [
		'default'           => 0,
		'sanitize_callback' => function ( $v ) {
			if ( is_array( $v ) || is_object( $v ) ) {
				return 0;
			}
			$v = (string) $v;
			$v = trim( $v );
			if ( $v === '' ) {
				return 0;
			}
			$v = str_replace( ',', '.', $v );
			$int = (int) round( (float) $v );
			if ( $int < -200 ) {
				$int = -200;
			}
			if ( $int > 200 ) {
				$int = 200;
			}
			return $int;
		},
		'transport'         => 'refresh',
	] );
	$wp_customize->add_control( 'hmpro_hb_group_top_mobile', [
		'label'       => __( 'Mobil/Tablet Header içerik üst ofset (px)', 'hm-pro-theme' ),
		'description' => __( 'Sadece mobil/tablet görünümde içerik grubunu yukarı (-) / aşağı (+) kaydırır.', 'hm-pro-theme' ),
		'section'     => 'hmpro_header_bg_banner',
		'type'        => 'number',
		'input_attrs' => [ 'min' => -200, 'max' => 200, 'step' => 2 ],
	] );

	$wp_customize->add_setting( 'hmpro_hb_group_x', [
		'default'           => 0,
		'sanitize_callback' => function ( $v ) {
			if ( is_array( $v ) || is_object( $v ) ) {
				return 0;
			}
			$v = (string) $v;
			$v = trim( $v );
			if ( $v === '' ) {
				return 0;
			}
			$v = str_replace( ',', '.', $v );
			$int = (int) round( (float) $v );
			if ( $int < -1200 ) {
				$int = -1200;
			}
			if ( $int > 1200 ) {
				$int = 1200;
			}
			return $int;
		},
		'transport'         => 'postMessage',
	] );
	$wp_customize->add_control( 'hmpro_hb_group_x', [
		'label'       => __( 'Header içerik X kaydırma (px)', 'hm-pro-theme' ),
		'description' => __( 'Sadece masaüstünde (desktop) sağa (+) / sola (-) kaydırır. Mobil/tablet görünümde sabitlenir.', 'hm-pro-theme' ),
		'section'     => 'hmpro_header_bg_banner',
		'type'        => 'number',
		'input_attrs' => [ 'min' => -1200, 'max' => 1200, 'step' => 5 ],
	] );

	$wp_customize->add_setting( 'hmpro_hb_group_y', [
		'default'           => 0,
		'sanitize_callback' => function ( $v ) {
			if ( is_array( $v ) || is_object( $v ) ) {
				return 0;
			}
			$v = (string) $v;
			$v = trim( $v );
			if ( $v === '' ) {
				return 0;
			}
			$v = str_replace( ',', '.', $v );
			$int = (int) round( (float) $v );
			if ( $int < -1200 ) {
				$int = -1200;
			}
			if ( $int > 1200 ) {
				$int = 1200;
			}
			return $int;
		},
		'transport'         => 'postMessage',
	] );
	$wp_customize->add_control( 'hmpro_hb_group_y', [
		'label'       => __( 'Header içerik Y kaydırma (px)', 'hm-pro-theme' ),
		'description' => __( 'Sadece masaüstünde (desktop) aşağı (+) / yukarı (-) kaydırır. Mobil/tablet görünümde sabitlenir.', 'hm-pro-theme' ),
		'section'     => 'hmpro_header_bg_banner',
		'type'        => 'number',
		'input_attrs' => [ 'min' => -1200, 'max' => 1200, 'step' => 5 ],
	] );

} );

// Live preview for header banner group X/Y offsets (postMessage).
add_action( 'customize_preview_init', function () {
	$src = get_template_directory_uri() . '/assets/js/customizer-preview.js';
	$path = get_template_directory() . '/assets/js/customizer-preview.js';
	$ver = file_exists( $path ) ? (string) filemtime( $path ) : null;
	wp_enqueue_script( 'hmpro-customizer-preview', $src, [ 'customize-preview' ], $ver, true );
} );

// Customizer controls UI script (reset button).
add_action( 'customize_controls_enqueue_scripts', function () {
	$src  = get_template_directory_uri() . '/assets/js/customizer-reset.js';
	$path = get_template_directory() . '/assets/js/customizer-reset.js';
	$ver  = file_exists( $path ) ? (string) filemtime( $path ) : null;

	wp_enqueue_script( 'hmpro-customizer-reset', $src, [ 'customize-controls', 'jquery', 'wp-util' ], $ver, true );
	wp_localize_script( 'hmpro-customizer-reset', 'HMPROCustomizerReset', [
		'ajaxUrl' => admin_url( 'admin-ajax.php' ),
		'action'  => 'hmpro_reset_header_footer_colors',
	] );
} );

// AJAX: reset Top Bar + Footer color overrides.
add_action( 'wp_ajax_hmpro_reset_header_footer_colors', function () {
	$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'hmpro_reset_header_footer_colors' ) ) {
		wp_send_json_error( [ 'message' => 'Invalid nonce' ], 403 );
	}

	remove_theme_mod( 'hmpro_topbar_bg_color' );
	remove_theme_mod( 'hmpro_topbar_text_color' );
	remove_theme_mod( 'hmpro_topbar_search_text_color' );
	remove_theme_mod( 'hmpro_topbar_search_placeholder_color' );
	remove_theme_mod( 'hmpro_menu_text_color' );
	remove_theme_mod( 'hmpro_menu_text_color_overlay' );
	remove_theme_mod( 'hmpro_menu_hover_color' );
	remove_theme_mod( 'hmpro_menu_active_color' );
	remove_theme_mod( 'hmpro_mega_menu_bg_color' );
	remove_theme_mod( 'hmpro_mega_menu_link_color' );
	remove_theme_mod( 'hmpro_show_header_logo' );
	remove_theme_mod( 'hmpro_header_backdrop_enable' );
	remove_theme_mod( 'hmpro_header_backdrop_color' );
	remove_theme_mod( 'hmpro_header_backdrop_opacity' );
	remove_theme_mod( 'hmpro_social_icon_color' );
	remove_theme_mod( 'hmpro_social_icon_bg' );
	remove_theme_mod( 'hmpro_social_icon_border' );
	remove_theme_mod( 'hmpro_social_icon_hover_color' );
	remove_theme_mod( 'hmpro_social_icon_hover_bg' );
	remove_theme_mod( 'hmpro_social_icon_hover_border' );
	remove_theme_mod( 'hmpro_social_icon_contrast' );
	remove_theme_mod( 'hmpro_social_icon_size' );
	remove_theme_mod( 'hmpro_social_icon_radius' );
	remove_theme_mod( 'hmpro_social_icon_svg_size' );
	remove_theme_mod( 'hmpro_footer_bg_color' );
	remove_theme_mod( 'hmpro_footer_text_color' );

	// Allow defaults to be re-seeded from the active preset.
	delete_option( 'hmpro_builder_colors_initialized' );

	wp_send_json_success( [ 'ok' => true ] );
} );
