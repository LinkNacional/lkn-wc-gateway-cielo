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
 * Persists the user's card selection across AJAX re-renders.
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

    // Persistent selection across AJAX re-renders (PHP always resets to default_card)
    var userSelectedIndex = null; // null = not set yet, '' = add card, '0'-'N' = card index

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
            userSelectedIndex = String($btn.data('card-index'));
            highlightButton($btn);
            if ($newCardFields.length) $newCardFields.hide();
            if ($selectedIndexInput.length) $selectedIndexInput.val(userSelectedIndex);
        });

        // Click "+ Add Card": show form, clear index, highlight
        $fieldset.on('click', '.lkn-cielo-add-card-btn', function () {
            userSelectedIndex = '';
            highlightButton($(this));
            if ($newCardFields.length) $newCardFields.show();
            if ($selectedIndexInput.length) $selectedIndexInput.val('');
        });

        // --- Restore state ---
        // On first load, respect the PHP-rendered default.
        // On AJAX re-renders (updated_checkout), userSelectedIndex already holds the user's choice.
        var $target;
        if (userSelectedIndex === null) {
            // First load: use PHP default
            $target = $fieldset.find('.lkn-cielo-saved-card-btn.selected').first();
        } else if (userSelectedIndex === '') {
            // User chose "+ Add Card" before AJAX refresh — restore that
            $target = $fieldset.find('.lkn-cielo-add-card-btn');
        } else {
            // User chose a specific card
            $target = $fieldset.find('.lkn-cielo-saved-card-btn[data-card-index="' + userSelectedIndex + '"]').first();
            if (!$target.length) {
                // Card may have been removed — fallback to PHP default
                userSelectedIndex = null;
                $target = $fieldset.find('.lkn-cielo-saved-card-btn.selected').first();
            }
        }

        if ($target.length && $target.hasClass('lkn-cielo-add-card-btn')) {
            highlightButton($target);
            if ($newCardFields.length) $newCardFields.show();
            if ($selectedIndexInput.length) $selectedIndexInput.val('');
        } else if ($target.length) {
            highlightButton($target);
            if ($newCardFields.length) $newCardFields.hide();
            if ($selectedIndexInput.length) $selectedIndexInput.val($target.data('card-index'));
        }
    }

    $(initSavedCards);
    $(document.body).on('updated_checkout', initSavedCards);
})(jQuery, window.lknCieloSavedCardsShortcode);
