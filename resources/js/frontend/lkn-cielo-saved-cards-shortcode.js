/**
 * Cielo Saved Cards — Shortcode Checkout Interaction
 *
 * Handles the saved-cards button list in the classic (shortcode) checkout.
 * Mirrors the Gutenberg block behaviour:
 *  - Click a saved card   → hide new-card form + animated card, highlight button
 *  - Click "+ Add Card"   → show new-card form + animated card
 *  - Only sets lkn_selected_saved_card_index; never touches input values
 *
 * Survives WooCommerce AJAX fragment updates via `updated_checkout` event.
 *
 * Dependencies: jQuery (enqueued by LknWCGatewayCieloDebit::payment_fields)
 *
 * @package Lkn\WCCieloPaymentGateway
 * @since 1.0.0
 */

(function ($, config) {
    'use strict';

    if (!config || !config.cards || !config.cards.length) {
        return;
    }

    function initSavedCards() {
        var $fieldset = $('#wc-' + config.gateway_id + '-cc-form');
        if (!$fieldset.length) return;
        if ($fieldset.data('lkn-saved-cards-initialized')) return;
        $fieldset.data('lkn-saved-cards-initialized', true);

        var $newCardFields = $fieldset.find('#lkn-debit-new-card-fields');
        var $selectedIndexInput = $fieldset.find('#lkn_selected_saved_card_index');

        function highlightButton($btn) {
            $fieldset.find('.lkn-cielo-saved-card-btn')
                .removeClass('selected')
                .css('outline', 'none');
            $btn.addClass('selected').css('outline', '2px solid #2563eb');
        }

        // Click a saved card: hide form, set index, highlight
        $fieldset.on('click', '.lkn-cielo-saved-card-btn:not(.lkn-cielo-add-card-btn)', function () {
            var $btn = $(this);
            highlightButton($btn);
            if ($newCardFields.length) $newCardFields.hide();
            if ($selectedIndexInput.length) $selectedIndexInput.val($btn.data('card-index'));
        });

        // Click "+ Add Card": show form, clear index, highlight
        $fieldset.on('click', '.lkn-cielo-add-card-btn', function () {
            highlightButton($(this));
            if ($newCardFields.length) $newCardFields.show();
            if ($selectedIndexInput.length) $selectedIndexInput.val('');
        });

        // Initial state: if a card is pre-selected, hide the form
        if ($fieldset.find('.lkn-cielo-saved-card-btn.selected').first().length) {
            if ($newCardFields.length) $newCardFields.hide();
        }
    }

    $(initSavedCards);
    $(document.body).on('updated_checkout', initSavedCards);
})(jQuery, window.lknCieloSavedCardsShortcode);
