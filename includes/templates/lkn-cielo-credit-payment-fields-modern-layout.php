<?php
/**
 * Template for Cielo Credit Card Payment Fields - Modern Layout
 * 
 * This template provides a modern, clean layout for credit card payment forms.
 * 
 * @package Lkn\WCCieloPaymentGateway
 * @since 1.0.0
 * 
 * Dependencies:
 * - CSS: /resources/css/frontend/lkn-cielo-modern-layout.css
 * - JS:  /resources/js/creditCard/lkn-cielo-brand-detector.js
 * 
 * Enqueued by:
 * - LknWCGatewayCieloCredit::payment_fields() (shortcode checkout)
 * - LknWcCieloCreditBlocks::init() (block checkout)
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Variables available in this template:
// $gateway_id, $description, $show_card_animation, $active_installment, $nonce
// $placeholder_enabled, $total_cart, $no_login_checkout, $installment_limit
// $installment_min, $installments, $fees_total, $taxes_total, $discounts_total
?>

<fieldset
    id="wc-<?php echo esc_attr($gateway_id); ?>-cc-form"
    class="wc-credit-card-form wc-payment-form lkn-modern-layout"
    style="background:transparent;">

    <div class="cielo-credit-fields-wrapper modern-layout">
        <?php if ('yes' === $show_card_animation) { ?>
        <div class="lkn-cielo-animated-card-container">
            <div id="cielo-credit-card-animation" class="card-wrapper card-animation modern-card"></div>
        </div>
        <?php } ?>

        <!-- Card Brand Icons -->
        <?php if ($show_card_brand_icons === 'yes') { ?>
        <div class="cielo-card-brands-container" id="cielo-credit-card-brands">
            <div class="cielo-card-brands">
                <?php 
                $card_brands = array(
                    'visa'       => __('Visa', 'lkn-wc-gateway-cielo'),
                    'mastercard' => __('Mastercard', 'lkn-wc-gateway-cielo'), 
                    'elo'        => __('Elo', 'lkn-wc-gateway-cielo'),
                    'amex'       => __('Amex', 'lkn-wc-gateway-cielo'),
                    'other_card' => __('Other Card', 'lkn-wc-gateway-cielo')
                );
                
                foreach ($card_brands as $brand => $title) {
                    $image_url = plugin_dir_url(__FILE__) . '../../resources/img/' . $brand . '-icon.svg';
                    if ($brand === 'other_card') {
                        $image_url = plugin_dir_url(__FILE__) . '../../resources/img/other-card.svg';
                    }
                    ?>
                    <img 
                        src="<?php echo esc_url($image_url); ?>" 
                        alt="<?php echo esc_attr($title . ' logo'); ?>" 
                        title="<?php echo esc_attr($title); ?>" 
                        data-brand="<?php echo esc_attr($brand); ?>" 
                        class="card-brand-icon" 
                        style="width: 40px; height: auto;">
                    <?php
                }
                ?>
            </div>
        </div>
        <?php } ?>
        
        <div class="wc-payment-cielo-form-fields modern-form-fields">

        <?php do_action('woocommerce_credit_card_form_start', $gateway_id); ?>
        
        <input
            type="hidden"
            name="nonce_lkn_cielo_credit"
            class="nonce_lkn_cielo_credit"
            value="<?php echo esc_attr($nonce); ?>" />

        <?php if ($gateway_instance->get_option('show_cardholder_name', 'no') !== 'yes') : ?>
        <!-- Card Holder Name Field -->
        <div class="modern-field">
            <label for="lkn_cc_cardholder_name" class="field-label">
                <?php esc_html_e('Name on Card', 'lkn-wc-gateway-cielo'); ?>
                <span class="required">*</span>
            </label>
            <div class="field-wrapper">
                <input
                    id="lkn_cc_cardholder_name"
                    name="lkn_cc_cardholder_name"
                    type="text"
                    autocomplete="cc-name"
                    required
                    placeholder="<?php echo $placeholder_enabled ? esc_attr('John Doe') : ''; ?>"
                    class="field-input">
            </div>
        </div>
        <?php else : ?>
        <!-- Campo virtual para concatenação first_name + last_name -->
        <input type="hidden" id="complete_name_lkn_cielo_credit" name="lkn_virtual_name_credit" />
        <?php endif; ?>

        <!-- Card Number Field -->
        <div class="modern-field">
            <label for="lkn_ccno" class="field-label">
                <?php esc_html_e('Card Number', 'lkn-wc-gateway-cielo'); ?>
                <span class="required">*</span>
            </label>
            <div class="field-wrapper">
                <input
                    id="lkn_ccno"
                    name="lkn_ccno"
                    type="tel"
                    inputmode="numeric"
                    class="field-input lkn-card-num"
                    maxlength="24"
                    required
                    placeholder="<?php echo $placeholder_enabled ? esc_attr('0000 0000 0000 0000') : ''; ?>">
                <div class="field-icon">
                    <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . '../../resources/img/lock.svg'); ?>" alt="Security" />
                </div>
            </div>
        </div>

        <!-- Expiry Date and CVV Fields -->
        <div class="field-group">
            <div class="modern-field field-half">
                <label for="lkn_cc_expdate" class="field-label">
                    <?php esc_html_e('Expiry Date', 'lkn-wc-gateway-cielo'); ?>
                    <span class="required">*</span>
                </label>
                <div class="field-wrapper">
                    <input
                        id="lkn_cc_expdate"
                        name="lkn_cc_expdate"
                        type="tel"
                        inputmode="numeric"
                        class="field-input lkn-card-exp"
                        maxlength="7"
                        required
                        placeholder="<?php echo $placeholder_enabled ? esc_attr('MM/AA') : ''; ?>">
                    <div class="field-icon">
                        <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . '../../resources/img/calendar.svg'); ?>" alt="Calendar" />
                    </div>
                </div>
            </div>

            <div class="modern-field field-half">
                <label for="lkn_cc_cvc" class="field-label">
                    <?php esc_html_e('Security Code', 'lkn-wc-gateway-cielo'); ?>
                    <span class="required">*</span>
                </label>
                <div class="field-wrapper">
                    <input
                        id="lkn_cc_cvc"
                        name="lkn_cc_cvc"
                        type="tel"
                        inputmode="numeric"
                        class="field-input lkn-cvv"
                        maxlength="8"
                        required
                        placeholder="<?php echo $placeholder_enabled ? esc_attr('CVC') : ''; ?>">
                    <div class="field-icon">
                        <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . '../../resources/img/key.svg'); ?>" alt="Security Code" />
                    </div>
                </div>
            </div>
        </div>

        <?php
        if ('yes' === $active_installment) {
        ?>
            <!-- Hidden fields for installment calculation -->
            <input
                id="lkn_cc_installment_total"
                type="hidden"
                value="<?php echo esc_attr($total_cart); ?>">
            <input
                id="lkn_cc_no_login_checkout"
                type="hidden"
                value="<?php echo esc_attr($no_login_checkout); ?>">
            <input
                id="lkn_cc_installment_limit"
                type="hidden"
                value="<?php echo esc_attr($installment_limit); ?>">
            <input
                id="lkn_cc_installment_min"
                type="hidden"
                value="<?php echo esc_attr($installment_min); ?>">
            <input
                id="lkn_cc_installment_interest"
                type="hidden"
                value="<?php echo esc_attr(wp_json_encode($installments)); ?>">
            <input
                id="lkn_cc_fees_total"
                type="hidden"
                value="<?php echo esc_attr($fees_total); ?>">
            <input
                id="lkn_cc_taxes_total"
                type="hidden"
                value="<?php echo esc_attr($taxes_total); ?>">
            <input
                id="lkn_cc_discounts_total"
                type="hidden"
                value="<?php echo esc_attr($discounts_total); ?>">

            <!-- Installments Selection -->
            <div id="lkn-cc-installment-row" class="modern-field" style="display: none;">
                <label for="lkn_cc_installments" class="field-label">
                    <?php esc_html_e('Installments', 'lkn-wc-gateway-cielo'); ?>
                    <span class="required">*</span>
                </label>
                <select
                    id="lkn_cc_installments"
                    name="lkn_cc_installments"
                    class="field-select">
                    <option value="1" selected="1">1 x R$0,00 sem juros</option>
                </select>
            </div>
        <?php
        } ?>

        <!-- Submit Button -->
        <div class="payment-submit-section">
            <button type="button" id="cielo-credit-submit-btn" class="cielo-submit-button credit-submit">
                <?php esc_html_e('Place order', 'lkn-wc-gateway-cielo'); ?>
            </button>
            <p class="submit-description">
                <?php echo esc_html($description); ?>
            </p>
        </div>
        
        <div class="clear"></div>

        <?php do_action('woocommerce_credit_card_form_end', $gateway_id); ?>

        <div class="clear"></div>

        </div>
    </div>

</fieldset>

<?php
do_action('lkn_wc_cielo_remove_cardholder_name', $gateway_instance);
?>