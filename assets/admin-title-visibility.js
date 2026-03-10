(function($){
	'use strict';

	function getCheckbox(){
		return $('input[name="hmpro_hide_title"]');
	}

	function isHidden(){
		return getCheckbox().is(':checked');
	}

	function renderButton(){
		var $wrap = $('#titlewrap');
		if(!$wrap.length) return;
		if($wrap.find('.hmpro-hide-title-eye').length) return;

		var svg = '' +
			'<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">' +
			'<path d="M12 5c-5 0-9.27 3.11-11 7 1.73 3.89 6 7 11 7s9.27-3.11 11-7c-1.73-3.89-6-7-11-7zm0 12c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8a3 3 0 100 6 3 3 0 000-6z"/>' +
			'</svg>';

		var $btn = $('<button type="button" class="hmpro-hide-title-eye" aria-label="Toggle title visibility">'+svg+'</button>');
		$wrap.prepend($btn);

		function sync(){
			$btn.toggleClass('is-hidden', isHidden());
			$btn.attr('title', isHidden() ? 'Title hidden on frontend' : 'Title visible on frontend');
		}
		sync();

		$btn.on('click', function(){
			var $cb = getCheckbox();
			if(!$cb.length) return;
			$cb.prop('checked', !$cb.is(':checked')).trigger('change');
			sync();
		});
	}

	$(document).ready(function(){
		// Gutenberg has its own sidebar toggle; don't add the classic button there.
		if($('body').hasClass('block-editor-page')) return;
		renderButton();
	});
})(jQuery);
