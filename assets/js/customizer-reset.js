/* global wp, jQuery, HMPROCustomizerReset */
(function ($) {
	'use strict';

	function doReset(nonce) {
		return $.post(HMPROCustomizerReset.ajaxUrl, {
			action: HMPROCustomizerReset.action,
			nonce: nonce
		});
	}

	$(document).on('click', '#hmpro-reset-hf-colors', function (e) {
		e.preventDefault();

		if (!window.confirm('Reset Header UI colors (Top Bar, Primary Menu, Social Icons, Footer) to defaults?')) {
			return;
		}

		var $btn = $(this);
		var nonce = $btn.data('nonce') || '';
		$btn.prop('disabled', true);

		doReset(nonce)
			.done(function () {
				// Clear Customizer settings in UI.
				if (wp && wp.customize) {
					[
						'hmpro_topbar_bg_color',
						'hmpro_topbar_text_color',
						'hmpro_topbar_search_text_color',
						'hmpro_topbar_search_placeholder_color',
						'hmpro_menu_text_color',
						'hmpro_menu_hover_color',
						'hmpro_menu_active_color',
						'hmpro_mega_menu_bg_color',
						'hmpro_mega_menu_link_color',
						'hmpro_header_backdrop_enable',
						'hmpro_header_backdrop_color',
						'hmpro_header_backdrop_opacity',
						'hmpro_social_icon_color',
						'hmpro_social_icon_bg',
						'hmpro_social_icon_border',
						'hmpro_social_icon_hover_color',
						'hmpro_social_icon_hover_bg',
						'hmpro_social_icon_hover_border',
						'hmpro_social_icon_contrast',
						'hmpro_social_icon_size',
						'hmpro_social_icon_radius',
						'hmpro_social_icon_svg_size',
						'hmpro_footer_bg_color',
						'hmpro_footer_text_color'
					].forEach(function (key) {
						if (wp.customize.has(key)) {
							wp.customize(key).set('');
						}
					});
				}
				// Force a refresh so seeded preset defaults are applied immediately.
				if (wp && wp.customize && wp.customize.previewer) {
					wp.customize.previewer.refresh();
				}
			})
			.always(function () {
				$btn.prop('disabled', false);
			});
	});
})(jQuery);
