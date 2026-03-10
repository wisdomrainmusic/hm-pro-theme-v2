<?php
// phpcs:ignoreFile
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_enqueue_scripts', function () {
	if ( is_admin() ) {
		return;
	}

	$css = 'html, body { overflow-x: clip !important; }' . "\n"
		. '@supports not (overflow-x: clip) { html, body { overflow-x: hidden !important; } }' . "\n"
		. '#page, #content, main, .site, .site-content { overflow-x: clip !important; }' . "\n"
		. '[class*="is-fullwidth"], [class*="fullwidth"] { overflow-x: clip !important; }' . "\n"
		. 'img, video, iframe { max-width: 100%; height: auto; }' . "\n";

	// Prefer attaching to an existing global front-end stylesheet.
	$handle = 'hmpro-base';
	if ( ! wp_style_is( $handle, 'enqueued' ) && ! wp_style_is( $handle, 'registered' ) ) {
		$handle = 'hmpro-mn-fix';
		wp_register_style( $handle, false, array(), HMPRO_VERSION );
		wp_enqueue_style( $handle );
	}

	wp_add_inline_style( $handle, $css );
}, 50 );

add_action( 'wp_enqueue_scripts', function () {
	if ( is_admin() || ! is_front_page() ) {
		return;
	}

	$js = "(function(){\n" .
		"  function fix(){\n" .
		"    var docEl=document.documentElement;\n" .
		"    var body=document.body;\n" .
		"    docEl.style.overflowX='clip';\n" .
		"    body.style.overflowX='clip';\n" .
		"    var footer=document.querySelector('footer, #colophon, .site-footer');\n" .
		"    if(!footer) return;\n" .
		"    var footerBottom=footer.getBoundingClientRect().bottom + window.scrollY;\n" .
		"    var pageHeight=Math.max(body.scrollHeight, docEl.scrollHeight);\n" .
		"    if(pageHeight - footerBottom > 200){\n" .
		"      body.style.minHeight='auto';\n" .
		"      body.style.height='auto';\n" .
		"      body.style.paddingBottom='0';\n" .
		"      body.style.marginBottom='0';\n" .
		"      var candidates=document.querySelectorAll('#page, .site, .site-content, main, #content');\n" .
		"      candidates.forEach(function(el){\n" .
		"        var cs=window.getComputedStyle(el);\n" .
		"        if(cs && cs.minHeight && cs.minHeight.indexOf('px')>-1){\n" .
		"          var v=parseFloat(cs.minHeight);\n" .
		"          if(!isNaN(v) && v > (window.innerHeight*2)){ el.style.minHeight='auto'; }\n" .
		"        }\n" .
		"      });\n" .
		"    }\n" .
		"  }\n" .
		"  window.addEventListener('load', fix, {passive:true});\n" .
		"  window.addEventListener('resize', function(){ setTimeout(fix, 50); }, {passive:true});\n" .
		"  setTimeout(fix, 300);\n" .
		"  setTimeout(fix, 1200);\n" .
		"})();";

	$handle = 'hmpro-mn-fix-footer-gap';
	wp_register_script( $handle, '', array(), HMPRO_VERSION, true );
	wp_enqueue_script( $handle );
	wp_add_inline_script( $handle, $js );
}, 50 );
