(function($){
  var $descPanel = null;
  var originalDescHtml = null;

  function findDescPanel(){
    // Default WooCommerce description tab panel
    var $p = $('#tab-description');
    // Fallbacks
    if(!$p.length) $p = $('.woocommerce-Tabs-panel--description');
    if(!$p.length) $p = $('[id*="tab-description"]');
    return $p;
  }

  function ensureOriginal(){
    if(originalDescHtml !== null) return true;
    $descPanel = findDescPanel();
    if(!$descPanel.length) return false;
    originalDescHtml = $descPanel.html();
    return true;
  }

  $(document).on('found_variation', 'form.variations_form', function(e, variation){
    if(!ensureOriginal()) return;
    var html = (variation && variation.wr_var_long_desc) ? variation.wr_var_long_desc : '';
    if(html && String(html).trim().length){
      $descPanel.html(html);
    } else {
      $descPanel.html(originalDescHtml);
    }
  });

  $(document).on('reset_data', 'form.variations_form', function(){
    if(originalDescHtml === null) return;
    $descPanel = findDescPanel();
    if($descPanel.length){
      $descPanel.html(originalDescHtml);
    }
  });
})(jQuery);
