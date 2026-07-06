<?php
/**
 * Cielo Debit Card Payment Fields - Modern Layout Template
 * 
 * This template provides a modern, responsive layout for debit card payment forms.
 * It includes card brand detection, animated icons, enhanced user experience,
 * and specific features for debit card processing including 3DS authentication.
 * 
 * Features:
 * - Modern responsive design with flexbox layout
 * - Card brand icons with real-time detection
 * - Credit/Debit card type selection
 * - 3DS authentication support
 * - Custom CSS classes for consistent styling
 * - Animated focus effects and transitions
 * - Mobile-first responsive breakpoints
 * 
 * Template Variables:
 * @param array  $card_brands    Available card brands with images and labels
 * @param string $gateway        Gateway instance for accessing configuration
 * @param string $installments   Installments dropdown HTML
 * @param array  $years          Available expiration years
 * @param array  $access_token   3DS authentication token data
 * @param string $nonce          Security nonce
 * @param bool   $placeholder_enabled Whether placeholders are enabled
 * 
 * @package Lkn\WCCieloPaymentGateway
 * @since 1.0.0
 * @author Link Nacional
 * 
 * Required CSS: lkn-cielo-modern-layout.css
 * Required JS:  lkn-cielo-brand-detector.js
 * 
 * @see LknWCGatewayCieloDebit::payment_fields()
 */

// Ensure this file is not accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>
<fieldset
    id="wc-<?php echo esc_attr($gateway_id); ?>-cc-form"
    class="wc-credit-card-form wc-payment-form lkn-debit-modern-layout"
    style="background:transparent;">

    <div class="cielo-debit-fields-wrapper modern-layout">

        <!-- Saved Cards List (PRO feature - shortcode) -->
        <?php if (isset($show_saved_cards) && $show_saved_cards && ! empty($cards_array)) : ?>
        <div class="lkn-cielo-saved-cards-list lkn-cielo-saved-cards-flex" style="display: flex; flex-wrap: wrap; margin-bottom: 10px; gap: 10px; width: 100%; justify-content: center;">
            <?php foreach ($cards_array as $idx => $card) : 
                $brand = isset($card['brand']) ? $card['brand'] : '';
                $brand_lower = strtolower($brand);
                $icon_key = in_array($brand_lower, array('visa', 'mastercard', 'master', 'amex', 'elo')) ? $brand_lower : 'other_card';
                if ($icon_key === 'master') $icon_key = 'mastercard';
                $icon_url = isset($card_brand_icons[$icon_key]) ? $card_brand_icons[$icon_key] : $card_brand_icons['other_card'];
                $card_digits = isset($card['cardDigits']) ? $card['cardDigits'] : '';
                $last_four = preg_replace('/.*(\d{4})$/', '•••• $1', $card_digits);
                $description = isset($card['description']) ? $card['description'] : '';
                $exp_date = isset($card['expirationDate']) ? $card['expirationDate'] : '';
                $is_default = (string) $idx === (string) $default_card;
            ?>
            <button type="button"
                class="lkn-cielo-saved-card-btn<?php echo $is_default ? ' selected' : ''; ?>"
                data-card-index="<?php echo esc_attr($idx); ?>"
                data-card-brand="<?php echo esc_attr($brand); ?>"
                data-card-digits="<?php echo esc_attr($card_digits); ?>"
                data-card-description="<?php echo esc_attr($description); ?>"
                data-card-expiration="<?php echo esc_attr($exp_date); ?>"
                style="color: #2563eb; font-weight: 500; font-size: 16px; cursor: pointer; padding: 10px 18px; display: flex; align-items: center; gap: 10px; width: 225px; border: none; transition: all 0.2s; outline: <?php echo $is_default ? '2px solid #2563eb' : 'none'; ?>;">
                <img src="<?php echo esc_url($icon_url); ?>" alt="<?php echo esc_attr($brand); ?>" style="height: 40px; margin-right: 8px;">
                <span style="font-weight: 600;"><?php echo esc_html($brand); ?></span>
                <span style="margin-left: 6px;"><?php echo esc_html($last_four); ?></span>
            </button>
            <?php endforeach; ?>
        </div>
        <div style="display: flex; justify-content: center; margin-bottom: 20px;">
            <button type="button"
                class="lkn-cielo-saved-card-btn lkn-cielo-add-card-btn<?php echo empty($default_card) ? ' selected' : ''; ?>"
                style="font-weight: 500; color: #2563eb; font-size: 16px; cursor: pointer; padding: 10px 18px; display: flex; align-items: center; gap: 10px; border: none; width: 225px; justify-content: center; outline: <?php echo empty($default_card) ? '2px solid #2563eb' : 'none'; ?>;">
                <span style="font-size: 22px; margin-right: 8px;">＋</span> <?php echo esc_html__('Add Card', 'lkn-wc-gateway-cielo'); ?>
            </button>
        </div>
        <input type="hidden" id="lkn_selected_saved_card_index" name="lkn_selected_saved_card_index" value="<?php echo esc_attr($default_card !== '' ? $default_card : ''); ?>">
        <?php endif; ?>

        <!-- Card Brand Icons -->
        <?php if ($show_card_brand_icons === 'yes') { ?>
        <div class="cielo-card-brands-container" id="cielo-debit-card-brands">
            <div class="cielo-card-brands">
                <?php 
                $card_brands = array(
                    'visa'       => __('Visa', 'lkn-wc-gateway-cielo'),
                    'mastercard' => __('Mastercard', 'lkn-wc-gateway-cielo'), 
                    'amex'       => __('American Express', 'lkn-wc-gateway-cielo'),
                    'elo'        => __('Elo', 'lkn-wc-gateway-cielo'),
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
                        class="card-brand-icon debit-brand" 
                        style="width: 40px; height: auto;">
                    <?php
                }
                ?>
            </div>
        </div>
        <?php } ?>
        
        <div class="wc-payment-cielo-form-fields modern-form-fields">

        <?php do_action('woocommerce_credit_card_form_start', $gateway_id); ?>
        
        <!-- Hidden fields for 3DS authentication -->
        <input type="hidden" id="lkn_cielo_3ds_installment_show" value="no" />
        <input type="hidden" name="nonce_lkn_cielo_debit" class="nonce_lkn_cielo_debit" value="<?php echo esc_attr($nonce); ?>" />
        <input type="hidden" name="lkn_auth_enabled" class="bpmpi_auth" value="true" />
        <input type="hidden" name="lkn_auth_enabled_notifyonly" class="bpmpi_auth_notifyonly" value="true" />
        <input type="hidden" name="lkn_auth_suppresschallenge" className="bpmpi_auth_suppresschallenge" value="false" />
        <input type="hidden" name="lkn_access_token" class="bpmpi_accesstoken" value="<?php echo esc_attr($access_token['access_token']); ?>" />
        <input type="hidden" name="lkn_expires_in" id="expires_in" value="<?php echo esc_attr($access_token['expires_in']); ?>" />
        <input type="hidden" size="50" name="lkn_order_number" class="bpmpi_ordernumber" value="<?php echo esc_attr(uniqid()); ?>" />
        <input type="hidden" name="lkn_currency" class="bpmpi_currency" value="BRL" />
        <input type="hidden" size="50" id="lkn_cielo_3ds_value" name="lkn_amount" class="bpmpi_totalamount" value="<?php echo esc_attr($total_cart_3ds); ?>" />
        <input type="hidden" size="2" name="lkn_installments" class="bpmpi_installments" value="1" />
        <input type="hidden" name="lkn_payment_method" class="bpmpi_paymentmethod" value="Debit" />
        <input type="hidden" id="lkn_bpmpi_cardnumber" class="bpmpi_cardnumber" />
        <input type="hidden" id="lkn_bpmpi_expmonth" maxlength="2" name="lkn_card_expiry_month" class="bpmpi_cardexpirationmonth" />
        <input type="hidden" id="lkn_bpmpi_expyear" maxlength="4" name="lkn_card_expiry_year" class="bpmpi_cardexpirationyear" />
        <input type="hidden" id="lkn_bpmpi_default_card" name="lkn_default_card" className="bpmpi_default_card" value="false" />
        <input type="hidden" id="lkn_bpmpi_order_recurrence" name="lkn_order_recurrence" className="bpmpi_order_recurrence" value="false" />
        <input type="hidden" size="50" class="bpmpi_order_productcode" value="PHY" />
        <input type="hidden" size="50" className="bpmpi_transaction_mode" value="S" />
        <input type="hidden" size="50" class="bpmpi_merchant_url" value="<?php echo esc_attr($url); ?>" />
        <input type="hidden" size="14" id="lkn_bpmpi_billto_customerid" name="lkn_card_customerid" className="bpmpi_billto_customerid" value="<?php echo esc_attr($billing_document); ?>" />
        <input type="hidden" size="120" id="lkn_bpmpi_billto_contactname" name="lkn_card_contactname" className="bpmpi_billto_contactname" value="<?php echo esc_attr($name); ?>" />
        <input type="hidden" size="15" id="lkn_bpmpi_billto_phonenumber" name="lkn_card_phonenumber" className="bpmpi_billto_phonenumber" value="<?php echo esc_attr($billing_phone); ?>" />
        <input type="hidden" size="255" id="lkn_bpmpi_billto_email" name="lkn_card_email" className="bpmpi_billto_email" value="<?php echo esc_attr($email); ?>" />
        <input type="hidden" size="60" id="lkn_bpmpi_billto_street1" name="lkn_card_billto_street1" className="bpmpi_billto_street1" value="<?php echo esc_attr($billing_address_1); ?>" />
        <input type="hidden" size="60" id="lkn_bpmpi_billto_street2" name="lkn_card_billto_street2" className="bpmpi_billto_street2" value="<?php echo esc_attr($billing_address_2); ?>" />
        <input type="hidden" size="50" id="lkn_bpmpi_billto_city" name="lkn_card_billto_city" className="bpmpi_billto_city" value="<?php echo esc_attr($billing_city); ?>" />
        <input type="hidden" size="2" id="lkn_bpmpi_billto_state" name="lkn_card_billto_state" className="bpmpi_billto_state" value="<?php echo esc_attr($billing_state); ?>" />
        <input type="hidden" size="8" id="lkn_bpmpi_billto_zipcode" name="lkn_card_billto_zipcode" className="bpmpi_billto_zipcode" value="<?php echo esc_attr($billing_postcode); ?>" />
        <input type="hidden" size="2" id="lkn_bpmpi_billto_country" name="lkn_card_billto_country" className="bpmpi_billto_country" value="<?php echo esc_attr($billing_country); ?>" />
        <input type="hidden" id="lkn_bpmpi_shipto_sameasbillto" name="lkn_card_shipto_sameasbillto" className="bpmpi_shipto_sameasbillto" value="true" />
        <input type="hidden" id="lkn_bpmpi_useraccount_guest" name="lkn_card_useraccount_guest" className="bpmpi_useraccount_guest" value="<?php echo esc_attr($user_guest); ?>" />
        <input type="hidden" id="lkn_bpmpi_useraccount_authenticationmethod" name="lkn_card_useraccount_authenticationmethod" className="bpmpi_useraccount_authenticationmethod" value="<?php echo esc_attr($authentication_method); ?>" />
        <input type="hidden" size="45" id="lkn_bpmpi_device_ipaddress" name="lkn_card_device_ipaddress" className="bpmpi_device_ipaddress" value="<?php echo esc_attr($client_ip); ?>" />
        <input type="hidden" size="7" id="lkn_bpmpi_device_channel" name="lkn_card_device_channel" className="bpmpi_device_channel" value="Browser" />
        <input type="hidden" size="10" id="lkn_bpmpi_brand_establishment_code" name="lkn_card_brand_establishment_code" className="bpmpi_brand_establishment_code" value="<?php echo esc_attr($bec); ?>" />
        <!-- Browser info fields for 3DS ELO compliance -->
        <input type="hidden" id="lkn_bpmpi_device_useragent" name="lkn_card_device_useragent" className="bpmpi_device_useragent" />
        <input type="hidden" id="lkn_bpmpi_device_screenwidth" name="lkn_card_device_screenwidth" className="bpmpi_device_screenwidth" />
        <input type="hidden" id="lkn_bpmpi_device_screenheight" name="lkn_card_device_screenheight" className="bpmpi_device_screenheight" />
        <input type="hidden" id="lkn_bpmpi_device_colordepth" name="lkn_card_device_colordepth" className="bpmpi_device_colordepth" />
        <input type="hidden" id="lkn_bpmpi_device_timezone" name="lkn_card_device_timezone" className="bpmpi_device_timezone" />
        <input type="hidden" id="lkn_bpmpi_device_javaenabled" name="lkn_card_device_javaenabled" className="bpmpi_device_javaenabled" />
        <input type="hidden" id="lkn_cavv" name="lkn_cielo_3ds_cavv" value="true" />
        <input type="hidden" id="lkn_eci" name="lkn_cielo_3ds_eci" value="true" />
        <input type="hidden" id="lkn_ref_id" name="lkn_cielo_3ds_ref_id" value="true" />
        <input type="hidden" id="lkn_version" name="lkn_cielo_3ds_version" value="true" />
        <input type="hidden" id="lkn_xid" name="lkn_cielo_3ds_xid" value="true" />

        <!-- Wrapper for new-card form fields (hidden when saved card selected) -->
        <div id="lkn-debit-new-card-fields">

        <?php if ('yes' === $show_card_animation) { ?>
        <div class="lkn-cielo-animated-card-container">
            <div id="cielo-debit-card-animation" class="card-wrapper card-animation modern-card"></div>
        </div>
        <?php } ?>

        <?php if ($this->get_option('show_cardholder_name', 'no') !== 'yes') : ?>
        <!-- Card Holder Name Field -->
        <div class="modern-field">
            <label for="lkn_dc_cardholder_name" class="field-label">
                <?php esc_html_e('Card Holder Name', 'lkn-wc-gateway-cielo'); ?>
                <span class="required">*</span>
            </label>
            <div class="field-wrapper">
                <input
                    id="lkn_dc_cardholder_name"
                    name="lkn_dc_cardholder_name"
                    type="text"
                    autocomplete="cc-name"
                    required
                    placeholder="<?php echo $placeholder_enabled ? esc_attr('John Doe') : ''; ?>"
                    class="field-input">
            </div>
        </div>
        <?php else : ?>
        <!-- Campo virtual para concatenação first_name + last_name -->
        <input type="hidden" id="complete_name_lkn_cielo_debit" name="lkn_virtual_name_debit" />
        <?php endif; ?>

        <!-- Card Number and Card Type Fields -->
        <div class="field-group">
            <div class="modern-field field-half">
                <label for="lkn_dcno" class="field-label">
                    <?php esc_html_e('Card Number', 'lkn-wc-gateway-cielo'); ?>
                    <span class="required">*</span>
                </label>
                <div class="field-wrapper">
                    <input
                        id="lkn_dcno"
                        name="lkn_dcno"
                        type="tel"
                        inputmode="numeric"
                        class="field-input lkn-card-num"
                        maxlength="24"
                        required
                        placeholder="<?php echo $placeholder_enabled ? esc_attr('XXXX XXXX XXXX XXXX') : ''; ?>">
                    <div class="field-icon">
                        <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . '../../resources/img/lock.svg'); ?>" alt="Security" />
                    </div>
                </div>
            </div>

            <div class="modern-field field-half">
                <label for="lkn_cc_type" class="field-label">
                    <?php esc_html_e('Card Type', 'lkn-wc-gateway-cielo'); ?>
                    <span class="required">*</span>
                </label>
                <select id="lkn_cc_type" name="lkn_cc_type" class="field-select">
                    <option value="Credit"><?php esc_html_e('Credit card', 'lkn-wc-gateway-cielo'); ?></option>
                    <option value="Debit"><?php esc_html_e('Debit card', 'lkn-wc-gateway-cielo'); ?></option>
                </select>
            </div>
        </div>

        <!-- Expiry Date and CVV Fields -->
        <div class="field-group">
            <div class="modern-field field-half">
                <label for="lkn_dc_expdate" class="field-label">
                    <?php esc_html_e('Expiry Date', 'lkn-wc-gateway-cielo'); ?>
                    <span class="required">*</span>
                </label>
                <div class="field-wrapper">
                    <input
                        id="lkn_dc_expdate"
                        name="lkn_dc_expdate"
                        type="tel"
                        inputmode="numeric"
                        class="field-input lkn-card-exp"
                        maxlength="7"
                        required
                        placeholder="<?php echo $placeholder_enabled ? esc_attr('MM/YY') : ''; ?>">
                    <div class="field-icon">
                        <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . '../../resources/img/calendar.svg'); ?>" alt="Calendar" />
                    </div>
                </div>
            </div>

            <div class="modern-field field-half">
                <label for="lkn_dc_cvc" class="field-label">
                    <?php esc_html_e('Security Code', 'lkn-wc-gateway-cielo'); ?>
                    <span class="required">*</span>
                </label>
                <div class="field-wrapper">
                    <input
                        id="lkn_dc_cvc"
                        name="lkn_dc_cvc"
                        type="tel"
                        inputmode="numeric"
                        autocomplete="off"
                        class="field-input lkn-cvv"
                        maxlength="4"
                        required
                        placeholder="<?php echo $placeholder_enabled ? esc_attr('CVV') : ''; ?>">
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
            <input id="lkn_cc_dc_installment_total" type="hidden" value="<?php echo esc_attr($total_cart); ?>">
            <input id="lkn_cc_dc_no_login_checkout" type="hidden" value="<?php echo esc_attr($no_login_checkout); ?>">
            <input id="lkn_cc_dc_installment_limit" type="hidden" value="<?php echo esc_attr($installment_limit); ?>">
            <input id="lkn_cc_dc_installment_min" type="hidden" value="<?php echo esc_attr($installment_min); ?>">
            <input id="lkn_cc_dc_installment_interest" type="hidden" value="<?php echo esc_attr(wp_json_encode($installments)); ?>">
            <input id="lkn_cc_dc_fees_total" type="hidden" value="<?php echo esc_attr($fees_total); ?>">
            <input id="lkn_cc_dc_taxes_total" type="hidden" value="<?php echo esc_attr($taxes_total); ?>">
            <input id="lkn_cc_dc_discounts_total" type="hidden" value="<?php echo esc_attr($discounts_total); ?>">

            <!-- Installments Selection -->
            <div id="lkn-cc-dc-installment-row" class="modern-field" style="display: none;">
                <label for="lkn_cc_dc_installments" class="field-label">
                    <?php esc_html_e('Installments', 'lkn-wc-gateway-cielo'); ?>
                    <span class="required">*</span>
                </label>
                <select id="lkn_cc_dc_installments" name="lkn_cc_dc_installments" class="field-select">
                    <option value="1" selected="1">1 x R$0,00 sem juros</option>
                </select>
            </div>
        <?php
        } ?>

        <?php if ($save_card_token === 'optional') : ?>
        <!-- Save Card Checkbox - Only visible when Credit card is selected -->
        <div id="lkn-save-debit-credit-card-row" class="modern-field" style="display: none;">
            <label class="field-label lkn-save-card-label">
                <input
                    id="lkn_save_debit_credit_card"
                    name="lkn_save_debit_credit_card"
                    type="checkbox"
                    value="1"
                    class="lkn-save-card-checkbox field-checkbox">
                <span style="text-transform: none;"><?php esc_html_e('Save card for safe and fast purchase', 'lkn-wc-gateway-cielo'); ?></span>
            </label>
        </div>
        <?php endif; ?>

        </div><!-- end #lkn-debit-new-card-fields -->

        <!-- Submit Button -->
        <div class="payment-submit-section">
            <button type="button" id="cielo-debit-submit-btn" class="cielo-submit-button debit-submit">
                <?php esc_html_e('Confirm Payment', 'lkn-wc-gateway-cielo'); ?>
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
do_action('lkn_wc_cielo_remove_cardholder_name_3ds', $this);
?>
