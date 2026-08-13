<?php

namespace Lkn\WCCieloPaymentGateway\Includes;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use Lkn\WCCieloPaymentGateway\Includes\LknWCGatewayCieloCredit;

final class LknWcCieloCreditBlocks extends AbstractPaymentMethodType
{
    private $gateway;
    protected $name = 'lkn_cielo_credit';

    public function initialize(): void
    {
        $this->settings = get_option('woocommerce_lkn_cielo_credit_settings', array());
        $lknWcGateWayCieloCredit = new LknWCGatewayCieloCredit();
        $this->gateway = $lknWcGateWayCieloCredit;
    }

    public function is_active()
    {
        return $this->gateway->is_available();
    }

    public function get_payment_method_script_handles()
    {
        $pro_plugin_active = function_exists('is_plugin_active') && is_plugin_active('lkn-cielo-api-pro/lkn-cielo-api-pro.php');
        $pro_license_active = get_option('lkn_cielo_pro_license_boolean', false);
        $pro_plugin_version_valid = defined('LKN_CIELO_API_PRO_VERSION') && version_compare(LKN_CIELO_API_PRO_VERSION, '1.20.2', '>=');

        $is_pro_plugin_valid = $pro_plugin_active && $pro_license_active && $pro_plugin_version_valid;

        // Gate exclusivo para carregar os scripts de UI do layout moderno
        $checkout_layout = isset($this->settings['checkout_layout']) ? $this->settings['checkout_layout'] : 'no';
        $use_modern_layout = $is_pro_plugin_valid && $checkout_layout === 'yes';

        // Enqueue base styles
        wp_enqueue_style('lkn-cc-style', plugin_dir_url(__FILE__) . '../resources/css/frontend/lkn-cc-style.css', array(), LKN_WC_CIELO_VERSION, 'all');
        wp_enqueue_style('lkn-mask', plugin_dir_url(__FILE__) . '../resources/css/frontend/lkn-mask.css', array(), LKN_WC_CIELO_VERSION, 'all');
        
        // Enqueue mask and installment scripts
        wp_enqueue_script('lkn-mask-script', plugin_dir_url(__FILE__) . '../resources/js/frontend/formatter.js', array('jquery'), LKN_WC_CIELO_VERSION, false);
        wp_enqueue_script('lkn-mask-script-load', plugin_dir_url(__FILE__) . '../resources/js/frontend/define-mask.js', array('lkn-mask-script', 'jquery'), LKN_WC_CIELO_VERSION, false);
        
        // Setup installment args and scripts
        $installmentArgs = apply_filters('lkn_wc_cielo_js_credit_args', array('installment_min' => '5'));
        $order_id = absint(get_query_var('order-pay'));
        $order = wc_get_order($order_id);

        if ($order) {
            $currency = $order->get_currency();
        } else {
            $currency = get_woocommerce_currency();
        }

        $installmentArgs['currency'] = $currency;

        if (WC()->session) {
            WC()->session->set('lkn_cielo_credit_installment', '1');
        }

        // Recuperar parcela atual da sessão
        $current_installment = WC()->session ? WC()->session->get('lkn_cielo_credit_installment', '1') : '1';

        wp_enqueue_script('lkn-installment-script', plugin_dir_url(__FILE__) . '../resources/js/frontend/lkn-cc-installment.js', array('jquery'), LKN_WC_CIELO_VERSION, false);
        wp_localize_script('lkn-installment-script', 'lknWCCieloCredit', $installmentArgs);
        wp_localize_script('lkn-installment-script', 'lknWCCieloCreditConfig', array(
            'interest_or_discount' => $this->gateway->get_option('interest_or_discount'),
            'installment_discount' => $this->gateway->get_option('installment_discount')
        ));
        wp_localize_script('lkn-installment-script', 'lknWCCieloCreditAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('lkn_payment_fees_nonce'),
            'current_installment' => $current_installment
        ));

        wp_register_script(
            'lkn_cielo_credit-blocks-integration',
            plugin_dir_url(__FILE__) . '../resources/js/creditCard/lknCieloCreditCompiled.js',
            array(
                'wc-blocks-registry',
                'wc-settings',
                'wp-element',
                'wp-html-entities',
                'wp-i18n',
            ),
            '1.0.0',
            true
        );

        wp_localize_script('lkn_cielo_credit-blocks-integration', 'lknCieloCreditConfig', array(
            'isProPluginValid' => $is_pro_plugin_valid,
            'ajax_url' => admin_url('admin-ajax.php'),
            'fees_nonce' => wp_create_nonce('lkn_payment_fees_nonce'),
            'currency' => array(
                'code' => get_woocommerce_currency(),
                'symbol' => get_woocommerce_currency_symbol(),
                'decimals' => wc_get_price_decimals(),
                'decimal_separator' => wc_get_price_decimal_separator(),
                'thousand_separator' => wc_get_price_thousand_separator(),
                'position' => get_option('woocommerce_currency_pos', 'left')
            )
        ));

        if (function_exists('wp_set_script_translations')) {
            wp_set_script_translations('lkn_cielo_credit-blocks-integration');
        }

        if ($use_modern_layout) {
            wp_enqueue_script('lkn-wc-gateway-credit-checkout-layout', plugin_dir_url(__FILE__) . '../resources/js/creditCard/lkn-wc-gateway-checkout-layout.js', array(), LKN_WC_CIELO_VERSION, false);
            wp_localize_script('lkn-wc-gateway-credit-checkout-layout', 'lknCieloRestSettings', array(
                'rest_url' => esc_url_raw(rest_url()),
                'nonce'    => wp_create_nonce('wp_rest'),
            ));

            // Verificar se os ícones de marca do cartão devem ser exibidos
            $show_card_brand_icons = isset($this->settings['show_card_brand_icons']) ? $this->settings['show_card_brand_icons'] : 'yes';
            
            wp_localize_script('lkn-wc-gateway-credit-checkout-layout', 'lknCieloCreditCardIcons', array(
                'visa'       => plugin_dir_url(__FILE__) . '../resources/img/visa-icon.svg',
                'mastercard' => plugin_dir_url(__FILE__) . '../resources/img/mastercard-icon.svg',
                'amex'       => plugin_dir_url(__FILE__) . '../resources/img/amex-icon.svg',
                'elo'        => plugin_dir_url(__FILE__) . '../resources/img/elo-icon.svg',
                'other_card'        => plugin_dir_url(__FILE__) . '../resources/img/other-card.svg',
                'other_card_alt'    => __('other card', 'lkn-wc-gateway-cielo'),
                'show_card_brand_icons' => $show_card_brand_icons
            ));
            wp_localize_script('lkn-wc-gateway-credit-checkout-layout', 'lknCieloInputIcons', array(
                'calendar'       => plugin_dir_url(__FILE__) . '../resources/img/calendar.svg',
                'key' => plugin_dir_url(__FILE__) . '../resources/img/key.svg',
                'lock'       => plugin_dir_url(__FILE__) . '../resources/img/lock.svg'
            ));
            wp_enqueue_style('lkn-wc-gateway-credit-checkout-layout', plugin_dir_url(__FILE__) . '../resources/css/frontend/lkn-wc-gateway-credit-card-checkout-layout.css', array(), LKN_WC_CIELO_VERSION, 'all');
        }

        // Checkout installment select script
        if (function_exists('WC') && WC()->session) {
            WC()->session->set('lkn_cielo_credit_installment', '1');
            WC()->session->set('lkn_cielo_debit_installment', '1');
            // Não forçar 'Credit' cegamente: respeita o card_type_mode do gateway
            // de débito para não aplicar taxa como crédito quando está em 'only_debit'.
            $debit_settings = get_option('woocommerce_lkn_cielo_debit_settings', array());
            $debit_card_type_mode = isset($debit_settings['card_type_mode']) ? $debit_settings['card_type_mode'] : 'both';
            $debit_default_card_type = 'only_debit' === $debit_card_type_mode ? 'Debit' : 'Credit';
            WC()->session->set('lkn_cielo_debit_card_type', $debit_default_card_type);
        }

        if (has_block('woocommerce/checkout') && !wp_script_is('lkn-installment-label', 'enqueued') && !wp_script_is('lkn-installment-label', 'done')) {
            wp_enqueue_script('lkn-installment-label', plugin_dir_url(__FILE__) . '../resources/js/frontend/lkn-installment-label.js', array(), LKN_WC_CIELO_VERSION, true);
            
            // Configuração de tradução para o script de label de parcelamento
            wp_localize_script('lkn-installment-label', 'lknInstallmentLabelTranslations', array(
                'payment' => __('Payment', 'lkn-wc-gateway-cielo'),
                'installment' => __('Installment', 'lkn-wc-gateway-cielo'),
                'loading' => __('Loading...', 'lkn-wc-gateway-cielo'),
                'calculatingInstallments' => __('Calculating installments...', 'lkn-wc-gateway-cielo'),
                'cashPayment' => __('Cash payment', 'lkn-wc-gateway-cielo'),
                'noInterest' => __('no interest', 'lkn-wc-gateway-cielo'),
                'noDiscount' => __('no discount', 'lkn-wc-gateway-cielo'),
                'withDiscount' => __('% discount', 'lkn-wc-gateway-cielo'),
                'withInterest' => __('% interest', 'lkn-wc-gateway-cielo'),
                'fallbackInstallment' => __('Cash payment', 'lkn-wc-gateway-cielo'),
                'loadingPrice' => __('Loading price...', 'lkn-wc-gateway-cielo')
            ));
        }

        if (is_checkout() && !has_block('woocommerce/checkout') && !wp_script_is('lkn-payment-method-shortcode', 'enqueued') && !wp_script_is('lkn-payment-method-shortcode', 'done')) {
            wp_enqueue_script('lkn-payment-method-shortcode', plugin_dir_url(__FILE__) . '../resources/js/frontend/lkn-payment-method-shortcode.js', array('jquery'), LKN_WC_CIELO_VERSION, true);
        }

        do_action('lkn_wc_cielo_remove_cardholder_name', $this->gateway);
        return array('lkn_cielo_credit-blocks-integration');
    }

    public function get_payment_method_data()
    {
        $installmentLimit = $this->gateway->get_option('installment_limit', 12);
        $installments = array();

        $installmentMinAmount = apply_filters('lkn_wc_cielo_set_installment_min_amount', '5,00', $this->gateway);
        $installmentMinAmount = preg_replace('/,/', '.', $installmentMinAmount);

        $installmentLimit = apply_filters('lkn_wc_cielo_set_installment_limit', $installmentLimit, $this->gateway);

        for ($i = 1; $i <= 12; $i++) {
            $installments[] = array(
                'index' => (string) $i,
                'label' => "teste $i"
            );
        }
        /**
         * @param $installments array
         * @param $gateway LknWCGatewayCieloCredit - Payment Gateway instance
         */
        $installments = apply_filters('lkn_wc_cielo_set_installments', $installments, $this->gateway);

        return array(
            'title' => $this->gateway->title,
            'description' => $this->gateway->description,
            'supports' => $this->gateway->supports,
            'activeInstallment' => $this->gateway->get_option('installment_payment'),
            'activeDiscount' => $this->gateway->get_option('installment_discount', 'no'),
            'interestOrDiscount' => $this->gateway->get_option('interest_or_discount', 'no'),
            'installmentLimit' => $installmentLimit,
            'installments' => $installments,
            'installmentMinAmount' => $installmentMinAmount,
            'totalCart' => $this->gateway->lknGetCartTotal(),
            'showCard' => $this->gateway->get_option('show_card_animation'),
            'nonceCieloCredit' => wp_create_nonce('nonce_lkn_cielo_credit'),
            'translations' => array(
                'cardNumber' => __('Card Number', 'lkn-wc-gateway-cielo'),
                'cardExpiryDate' => __('Expiry Date', 'lkn-wc-gateway-cielo'),
                'securityCode' => __('Security Code', 'lkn-wc-gateway-cielo'),
                'installments' => __('Installments', 'lkn-wc-gateway-cielo'),
                'cardHolder' => __('Card Holder Name', 'lkn-wc-gateway-cielo'),
                // translators: %1$d is the number of installments, %2$s is the installment amount
                'installmentText' => __('%1$dx of %2$s', 'lkn-wc-gateway-cielo'),
                'cashPayment' => __('(cash payment)', 'lkn-wc-gateway-cielo'),
                'noInterest' => __('no interest', 'lkn-wc-gateway-cielo'),
                'noDiscount' => __('no discount', 'lkn-wc-gateway-cielo'),
                'withDiscount' => __('% discount', 'lkn-wc-gateway-cielo'),
                'withInterest' => __('% interest', 'lkn-wc-gateway-cielo'),
                'calculatingInstallments' => __('Calculating installments...', 'lkn-wc-gateway-cielo'),
            )
        );
    }
}
