(function ($) {
  function initShippingAccordion() {
    var $form = $('form.checkout');
    if (!$form.length) return;

    var $toggle = $('#ship-to-different-address input[type="checkbox"]');
    var $panel = $('.shipping_address');

    if (!$toggle.length || !$panel.length) return;

    // Ensure accordion class exists for styling.
    $('#ship-to-different-address').addClass('hmpro-ship-accordion');
    $panel.addClass('hmpro-ship-panel');

    function setState(open) {
      if (open) {
        $panel.stop(true, true).slideDown(180).attr('aria-hidden', 'false');
        $('#ship-to-different-address').addClass('is-open');
      } else {
        $panel.stop(true, true).slideUp(180).attr('aria-hidden', 'true');
        $('#ship-to-different-address').removeClass('is-open');
      }
    }

    // On init: respect checkbox value, but prefer closed unless checked.
    setState($toggle.is(':checked'));

    $toggle.off('change.hmproShip').on('change.hmproShip', function () {
      setState($(this).is(':checked'));
    });
  }

  // Initial load.
  $(document).ready(initShippingAccordion);

  // Woo updates checkout via AJAX; re-init.
  $(document.body).on('updated_checkout', initShippingAccordion);
})(jQuery);
