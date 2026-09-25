<?php
/**
 * Template for Cielo Credit Card Payment Fields
 *
 * @package Lkn\WCCieloPaymentGateway
 * @since 1.0.0
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
    class="wc-credit-card-form wc-payment-form"
    style="background:transparent;">

    <div class="cielo-credit-fields-wrapper">
        <?php if ('yes' === $show_card_animation) { ?>
        <div class="lkn-cielo-animated-card-container">
            <div
                id="cielo-credit-card-animation"
                class="card-wrapper card-animation"></div>
        </div>
        <?php } ?>
        <div class="wc-payment-cielo-form-fields">

        <?php do_action('woocommerce_credit_card_form_start', $gateway_id); ?>
        <input
            type="hidden"
            name="nonce_lkn_cielo_credit"
            class="nonce_lkn_cielo_credit"
            value="<?php echo esc_attr($nonce); ?>" />

        <?php if ($gateway_instance->get_option('show_cardholder_name', 'no') !== 'yes') : ?>
        <div class="form-row form-row-wide">
            <label
                for="lkn_cc_cardholder_name"><?php esc_html_e('Name on Card', 'lkn-wc-gateway-cielo'); ?>
                <span class="required">*</span></label>
            <input
                id="lkn_cc_cardholder_name"
                name="lkn_cc_cardholder_name"
                type="text"
                autocomplete="cc-name"
                required
                placeholder="<?php echo $placeholder_enabled ? esc_attr('John Doe') : ''; ?>"
                data-placeholder="<?php echo $placeholder_enabled ? esc_attr('John Doe') : ''; ?>"
                class="lkn-wc-gateway-cielo-input">
        </div>
        <?php else : ?>
        <!-- Campo virtual para concatenação first_name + last_name -->
        <input type="hidden" id="complete_name_lkn_cielo_credit" name="lkn_virtual_name_credit" />
        <?php endif; ?>

        <div class="form-row form-row-wide">
            <label
                for="lkn_ccno"><?php esc_html_e('Card Number', 'lkn-wc-gateway-cielo'); ?>
                <span class="required">*</span></label>
            <input
                id="lkn_ccno"
                name="lkn_ccno"
                type="tel"
                inputmode="numeric"
                class="lkn-card-num lkn-wc-gateway-cielo-input wc-credit-card-form-card-number"
                maxlength="24"
                required
                placeholder="<?php echo $placeholder_enabled ? esc_attr('0000 0000 0000 0000') : ''; ?>"
                data-placeholder="<?php echo $placeholder_enabled ? esc_attr('0000 0000 0000 0000') : ''; ?>">
        </div>
        <div class="form-row form-row-wide">
            <label
                for="lkn_cc_expdate"><?php esc_html_e('Expiry Date', 'lkn-wc-gateway-cielo'); ?>
                <span class="required">*</span></label>
            <input
                id="lkn_cc_expdate"
                name="lkn_cc_expdate"
                type="tel"
                inputmode="numeric"
                class="lkn-card-exp lkn-wc-gateway-cielo-input wc-credit-card-form-card-expiry"
                maxlength="7"
                required
                placeholder="<?php echo $placeholder_enabled ? esc_attr('MM/AA') : ''; ?>"
                data-placeholder="<?php echo $placeholder_enabled ? esc_attr('MM/AA') : ''; ?>">
        </div>
        <div class="form-row form-row-wide">
            <label
                for="lkn_cc_cvc"><?php esc_html_e('Security Code', 'lkn-wc-gateway-cielo'); ?>
                <span class="required">*</span></label>
            <input
                id="lkn_cc_cvc"
                name="lkn_cc_cvc"
                type="tel"
                inputmode="numeric"
                class="lkn-cvv lkn-wc-gateway-cielo-input wc-credit-card-form-card-cvc"
                maxlength="8"
                required
                placeholder="<?php echo $placeholder_enabled ? esc_attr('CVC') : ''; ?>"
                data-placeholder="<?php echo $placeholder_enabled ? esc_attr('CVC') : ''; ?>">
        </div>

        <?php
        if ('yes' === $active_installment) {
        ?>
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

            <div id="lkn-cc-installment-row" class="form-row form-row-wide" style="display: none;">
                <label
                    for="lkn_cc_installments"><?php esc_html_e('Installments', 'lkn-wc-gateway-cielo'); ?>
                    <span class="required">*</span>
                </label>
                <select
                    id="lkn_cc_installments"
                    name="lkn_cc_installments"
                    class="input-select wc-credit-card-form-card-cvc">
                    <option
                        value="1"
                        selected="1">1 x R$0,00 sem juros</option>
                </select>
            </div>
        <?php
        } ?>
        <div id="lkn-cc-notice"></div>
        <div class="clear"></div>

        <?php do_action('woocommerce_credit_card_form_end', $gateway_id); ?>

        <div class="clear"></div>
            </div>
    </div>

    <!-- Descrição do gateway: no rodapé, abaixo dos campos. -->
    <p class="credit-card-description">
        <?php echo esc_html($description); ?>
    </p>

</fieldset>

<?php
do_action('lkn_wc_cielo_remove_cardholder_name', $gateway_instance);
?>