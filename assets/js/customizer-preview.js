/* global wp */
(function () {
	if (typeof wp === 'undefined' || !wp.customize) return;

	function getHeaderBanners() {
		return document.querySelectorAll('.hmpro-hb-banner');
	}

	function setHBVar(varName, value) {
		getHeaderBanners().forEach(function (el) {
			el.style.setProperty(varName, value);
		});
	}

	function bindHBPx(settingId, cssVar) {
		wp.customize(settingId, function (setting) {
			setting.bind(function (val) {
				var n = parseInt(val, 10);
				if (isNaN(n)) n = 0;
				setHBVar(cssVar, n + 'px');
			});
		});
	}

	// Header banner offsets.
	bindHBPx('hmpro_hb_group_x', '--hmpro-hb-group-x');
	bindHBPx('hmpro_hb_group_y', '--hmpro-hb-group-y');
})();
