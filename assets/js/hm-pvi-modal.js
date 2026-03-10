(function () {
	'use strict';

	var data = window.hmPviModalData || {};
	if (!data || data.enabled !== 'yes') {
		return;
	}

	var modal;
	var modalContent;
	var closeButton;
	var body = document.body;

	var triggerSelector = [
		'.hm-pvi-play',
		'.hm-pvi-cta',
		'.hm-pvi-block',
		'a[href*="youtube.com"]',
		'a[href*="youtu.be"]',
		'a.mfp-iframe'
	].join(',');

	function extractYouTubeId(url) {
		if (!url) {
			return '';
		}

		var value = String(url).trim();
		if (!value) {
			return '';
		}

		var patterns = [
			/[?&]v=([A-Za-z0-9_-]{6,15})/,
			/youtu\.be\/([A-Za-z0-9_-]{6,15})/,
			/youtube\.com\/shorts\/([A-Za-z0-9_-]{6,15})/,
			/youtube\.com\/embed\/([A-Za-z0-9_-]{6,15})/
		];

		for (var i = 0; i < patterns.length; i += 1) {
			var match = value.match(patterns[i]);
			if (match && match[1]) {
				return match[1];
			}
		}

		return '';
	}

	function isPortraitVideo(url) {
		var value = String(url || '').toLowerCase();
		if (value.indexOf('/shorts/') !== -1) {
			return true;
		}

		return window.matchMedia && window.matchMedia('(max-width: 767px)').matches;
	}

	function ensureModal() {
		if (modal) {
			return;
		}

		modal = document.createElement('div');
		modal.className = 'hm-pvi-modal';
		modal.setAttribute('aria-hidden', 'true');
		modal.innerHTML = '' +
			'<div class="hm-pvi-modal__backdrop" data-hm-pvi-close="1"></div>' +
			'<div class="hm-pvi-modal__dialog" role="dialog" aria-modal="true">' +
				'<button type="button" class="hm-pvi-modal__close" aria-label="' + (data.i18nClose || 'Close') + '" data-hm-pvi-close="1">×</button>' +
				'<div class="hm-pvi-modal__content"><div class="hm-pvi-modal__video"></div></div>' +
			'</div>';

		document.body.appendChild(modal);
		modalContent = modal.querySelector('.hm-pvi-modal__video');
		closeButton = modal.querySelector('.hm-pvi-modal__close');

		modal.addEventListener('click', function (event) {
			if (event.target && event.target.getAttribute('data-hm-pvi-close') === '1') {
				closeModal();
			}
		});
	}

	function openModal() {
		ensureModal();

		var type = (data.type || '').toLowerCase();
		var youtubeUrl = data.youtube_url || '';
		var mp4Url = data.mp4_url || '';
		var html = '';

		if (type === 'mp4' && mp4Url) {
			html = '<video controls autoplay playsinline preload="metadata" src="' + mp4Url + '"></video>';
			modal.classList.remove('hm-pvi-modal--portrait');
		} else {
			var videoId = extractYouTubeId(youtubeUrl);
			if (!videoId) {
				return;
			}

			html = '<iframe allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen referrerpolicy="strict-origin-when-cross-origin" src="https://www.youtube.com/embed/' + videoId + '?autoplay=1&playsinline=1&rel=0&modestbranding=1"></iframe>';
			modal.classList.toggle('hm-pvi-modal--portrait', isPortraitVideo(youtubeUrl));
		}

		modalContent.innerHTML = html;
		modal.classList.add('is-open');
		modal.setAttribute('aria-hidden', 'false');
		body.classList.add('hm-pvi-modal-open');

		if (closeButton) {
			closeButton.focus();
		}
	}

	function closeModal() {
		if (!modal) {
			return;
		}

		modal.classList.remove('is-open', 'hm-pvi-modal--portrait');
		modal.setAttribute('aria-hidden', 'true');
		body.classList.remove('hm-pvi-modal-open');
		if (modalContent) {
			modalContent.innerHTML = '';
		}
	}

	document.addEventListener('keydown', function (event) {
		if (event.key === 'Escape') {
			closeModal();
		}
	});

	document.addEventListener('click', function (event) {
		var target = event.target;
		if (!target || !target.closest) {
			return;
		}

		var trigger = target.closest(triggerSelector);
		if (!trigger) {
			return;
		}

		var inPviBlock = target.closest('.hm-pvi-block');
		var isDirectPvi = target.closest('.hm-pvi-play, .hm-pvi-cta');
		if (!inPviBlock && !isDirectPvi) {
			return;
		}

		event.preventDefault();
		event.stopPropagation();
		if (typeof event.stopImmediatePropagation === 'function') {
			event.stopImmediatePropagation();
		}

		openModal();
	}, true);
})();
