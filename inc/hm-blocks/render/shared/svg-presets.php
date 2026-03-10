<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SVG preset library (Spectra-alternative style).
 * Expanded for e-commerce + social + contact.
 */
function hmpro_svg_presets() {
	return array(
		'check' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" role="img"><path d="M20 6L9 17l-5-5"/></svg>',
		'star'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" role="img"><path d="M12 2l3.1 6.3 7 .9-5.1 5 1.2 7-6.2-3.3-6.2 3.3 1.2-7-5.1-5 7-.9z"/></svg>',
		'shield'=> '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" role="img"><path d="M12 2l8 4v6c0 5-3.5 9.4-8 10-4.5-.6-8-5-8-10V6l8-4z"/></svg>',
		'bolt'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" role="img"><path d="M13 2L3 14h7l-1 8 12-14h-7z"/></svg>',
		'heart' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" role="img"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8z"/></svg>',

		// E-commerce / utility.
		'truck'   => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" role="img"><path d="M3 7h11v10H3z"/><path d="M14 10h4l3 3v4h-7z"/><circle cx="7" cy="19" r="1.5"/><circle cx="18" cy="19" r="1.5"/></svg>',
		'box'     => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" role="img"><path d="M21 8l-9-5-9 5 9 5 9-5z"/><path d="M3 8v10l9 5 9-5V8"/><path d="M12 13v10"/></svg>',
		'return'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" role="img"><path d="M9 14l-4-4 4-4"/><path d="M5 10h9a5 5 0 0 1 0 10H7"/></svg>',
		'recycle' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" role="img"><path d="M7 19l-2-3 3-2"/><path d="M5 16a7 7 0 0 0 12 1"/><path d="M17 5l2 3-3 2"/><path d="M19 8a7 7 0 0 0-12-1"/><path d="M10 5h4l2 3"/></svg>',
		'cash'    => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" role="img"><rect x="3" y="7" width="18" height="10" rx="2"/><path d="M7 12h0"/><path d="M17 12h0"/><circle cx="12" cy="12" r="2.5"/></svg>',
		'mail'    => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" role="img"><path d="M4 6h16v12H4z"/><path d="M4 7l8 6 8-6"/></svg>',
		'phone'   => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" role="img"><path d="M22 16.5v3a2 2 0 0 1-2.2 2 19 19 0 0 1-8.3-3 19 19 0 0 1-6-6A19 19 0 0 1 2.5 4.2 2 2 0 0 1 4.5 2h3a2 2 0 0 1 2 1.7c.1.8.3 1.6.6 2.4a2 2 0 0 1-.5 2.1L8.5 9.3a16 16 0 0 0 6.2 6.2l1.1-1.1a2 2 0 0 1 2.1-.5c.8.3 1.6.5 2.4.6A2 2 0 0 1 22 16.5z"/></svg>',
		'chat'    => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" role="img"><path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"/></svg>',

		// Social.
		'whatsapp'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" role="img"><path d="M20 11.5A8.5 8.5 0 0 1 7.3 19.2L4 20l.9-3.2A8.5 8.5 0 1 1 20 11.5z"/><path d="M9.2 9.1c.3 2 3 4.7 5 5 .6.1 1.2-.2 1.6-.6l.7-.7-1.6-1-.6.5c-.2.2-.4.2-.7.1-1-.4-2.2-1.6-2.6-2.6-.1-.3 0-.5.1-.7l.5-.6-1-1.6-.7.7c-.4.4-.7 1-.6 1.5z"/></svg>',
		'facebook'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" role="img"><path d="M14 8h3V5h-3a4 4 0 0 0-4 4v3H7v3h3v7h3v-7h3l1-3h-4V9a1 1 0 0 1 1-1z"/></svg>',
		'instagram' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" role="img"><rect x="4" y="4" width="16" height="16" rx="4"/><circle cx="12" cy="12" r="3.5"/><path d="M16.5 7.5h0"/></svg>',
		'youtube'   => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" role="img"><path d="M22 12s0-4-1-5-4-1-9-1-8 0-9 1-1 5-1 5 0 4 1 5 4 1 9 1 8 0 9-1 1-5 1-5z"/><path d="M10 9l6 3-6 3z"/></svg>',
	);
}

function hmpro_get_svg_preset( $key ) {
	$presets = hmpro_svg_presets();
	if ( ! is_string( $key ) || ! isset( $presets[ $key ] ) ) {
		return '';
	}
	return $presets[ $key ];
}
