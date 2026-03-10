(function($){
	'use strict';

	function parseIds(val){
		if (!val) return [];
		return val.split(',').map(function(x){ return parseInt(String(x).trim(), 10); }).filter(function(n){ return n > 0; });
	}
	function setIds($row, ids){
		$row.find('.hmpro-var-gallery-ids').val(ids.join(','));
	}
	function renderThumbs($row, ids){
		var $wrap = $row.find('.hmpro-var-gallery-thumbs');
		$wrap.empty();
		if (!ids.length) return;

		ids.forEach(function(id){
			// Use WP ajax endpoint to get thumb URL via attachment data already in media frame cache when possible.
			// Fallback: show placeholder; admin UX still OK because selections show inside media modal.
			var html = '' +
				'<div class="hmpro-var-thumb" data-id="'+id+'">' +
					'<span class="hmpro-var-thumb-ph">'+id+'</span>' +
					'<button type="button" class="button-link-delete hmpro-var-thumb-remove" aria-label="Remove image">×</button>' +
				'</div>';
			$wrap.append(html);
		});
	}

	function hydrateThumbsFromMedia($row, selection){
		var $wrap = $row.find('.hmpro-var-gallery-thumbs');
		$wrap.empty();
		selection.each(function(att){
			var data = att.toJSON();
			var src = (data.sizes && data.sizes.thumbnail) ? data.sizes.thumbnail.url : data.url;
			var html = '' +
				'<div class="hmpro-var-thumb" data-id="'+data.id+'">' +
					'<img src="'+src+'" alt="" />' +
					'<button type="button" class="button-link-delete hmpro-var-thumb-remove" aria-label="Remove image">×</button>' +
				'</div>';
			$wrap.append(html);
		});
	}

	$(document).on('click', '.hmpro-var-gallery-add', function(e){
		e.preventDefault();
		var $row = $(this).closest('.hmpro-var-gallery-row');
		var ids = parseIds($row.find('.hmpro-var-gallery-ids').val());

		var frame = wp.media({
			title: 'Select variation gallery images',
			button: { text: 'Use these images' },
			library: { type: 'image' },
			multiple: true
		});

		frame.on('open', function(){
			var selection = frame.state().get('selection');
			ids.forEach(function(id){
				var att = wp.media.attachment(id);
				if (att) { att.fetch(); selection.add(att); }
			});
		});

		frame.on('select', function(){
			var selection = frame.state().get('selection');
			var newIds = [];
			selection.each(function(att){
				var data = att.toJSON();
				if (data && data.id) newIds.push(parseInt(data.id, 10));
			});
			newIds = newIds.filter(function(n){ return n > 0; });
			setIds($row, newIds);
			hydrateThumbsFromMedia($row, selection);

			// Mark variations as changed so Woo enables Save.
			$('#variable_product_options').trigger('woocommerce_variations_input_changed');
		});

		frame.open();
	});

	$(document).on('click', '.hmpro-var-gallery-clear', function(e){
		e.preventDefault();
		var $row = $(this).closest('.hmpro-var-gallery-row');
		setIds($row, []);
		$row.find('.hmpro-var-gallery-thumbs').empty();
		$('#variable_product_options').trigger('woocommerce_variations_input_changed');
	});

	$(document).on('click', '.hmpro-var-thumb-remove', function(e){
		e.preventDefault();
		var $thumb = $(this).closest('.hmpro-var-thumb');
		var id = parseInt($thumb.attr('data-id'), 10);
		var $row = $thumb.closest('.hmpro-var-gallery-row');
		var ids = parseIds($row.find('.hmpro-var-gallery-ids').val()).filter(function(x){ return x !== id; });
		setIds($row, ids);
		$thumb.remove();
		$('#variable_product_options').trigger('woocommerce_variations_input_changed');
	});

	// On load: convert placeholder ID boxes into real thumbs if WP media cache can resolve.
	$(function(){
		$('.hmpro-var-gallery-row').each(function(){
			var $row = $(this);
			var ids = parseIds($row.find('.hmpro-var-gallery-ids').val());
			// If server already printed <img> tags, leave as-is.
			if ($row.find('.hmpro-var-gallery-thumbs img').length) return;
			renderThumbs($row, ids);
		});
	});

})(jQuery);
