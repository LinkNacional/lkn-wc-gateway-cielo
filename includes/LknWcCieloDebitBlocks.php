<?php

namespace Lkn\WCCieloPaymentGateway\Includes;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use Lkn\WCCieloPaymentGateway\Includes\LknWCGatewayCieloDebit;

final class LknWcCieloDebitBlocks extends AbstractPaymentMethodType
{
    private $gateway;
    protected $name = 'lkn_cielo_debit';

    public function initialize(): void
    {
        $this->settings = get_option('woocommerce_lkn_cielo_debit_settings', array());
        $lknWcGateWayCieloDebit = new LknWCGatewayCieloDebit();
        $this->gateway = $lknWcGateWayCieloDebit;
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
        wp_enqueue_style('lkn-dc-style', plugin_dir_url(__FILE__) . '../resources/css/frontend/lkn-dc-style.css', array(), LKN_WC_CIELO_VERSION, 'all');
        wp_enqueue_style('lkn-mask', plugin_dir_url(__FILE__) . '../resources/css/frontend/lkn-mask.css', array(), LKN_WC_CIELO_VERSION, 'all');
        
        // Setup environment and scripts
        $env = $this->gateway->get_option('env');
        $installmentArgs = apply_filters('lkn_wc_cielo_js_3ds_args', array('installment_min' => '5'));

        if (WC()->session) {
            WC()->session->set('lkn_cielo_debit_card_type', 'Credit');
        }

        // Recuperar parcela atual da sessão
        $current_installment = WC()->session ? WC()->session->get('lkn_cielo_debit_installment', '1') : '1';

        // Enqueue environment-specific scripts
        if ('production' === $env) {
            wp_enqueue_script('lkn-dc-script', plugin_dir_url(__FILE__) . '../resources/js/frontend/lkn-dc-script-prd.js', array('wp-i18n', 'jquery'), LKN_WC_CIELO_VERSION, false);
            wp_set_script_translations('lkn-dc-script', 'lkn-wc-gateway-cielo', LKN_WC_CIELO_TRANSLATION_PATH);
        } else {
            wp_enqueue_script('lkn-dc-script', plugin_dir_url(__FILE__) . '../resources/js/frontend/lkn-dc-script-sdb.js', array('wp-i18n', 'jquery'), LKN_WC_CIELO_VERSION, false);
            wp_set_script_translations('lkn-dc-script', 'lkn-wc-gateway-cielo', LKN_WC_CIELO_TRANSLATION_PATH);
        }
        
        // Setup 3DS and other scripts
        wp_localize_script('lkn-dc-script', 'lknDCDirScript3DSCieloShortCode', array('url' => LKN_WC_GATEWAY_CIELO_URL . 'resources/js/debitCard/BP.Mpi.3ds20.min.js'));
        wp_localize_script('lkn-dc-script', 'lknDCScriptAllowCardIneligible', array('allow' => $this->gateway->get_option('allow_card_ineligible', 'no')));
        wp_localize_script('lkn-dc-script', 'lknCieloRestSettings', array(
            'rest_url'  => esc_url_raw(rest_url()),
            'nonce' => wp_create_nonce('wp_rest'),
        ));
        
        // Enqueue mask and token scripts
        wp_enqueue_script('lkn-mask-script', plugin_dir_url(__FILE__) . '../resources/js/frontend/formatter.js', array('jquery'), LKN_WC_CIELO_VERSION, false);
        wp_enqueue_script('lkn-mask-script-load', plugin_dir_url(__FILE__) . '../resources/js/frontend/define-mask.js', array('lkn-mask-script', 'jquery'), LKN_WC_CIELO_VERSION, false);
        wp_enqueue_script('lkn-fix-token-script', plugin_dir_url(__FILE__) . '../resources/js/frontend/lkn-fix-token-script.js', array('jquery'), LKN_WC_CIELO_VERSION, false);
        wp_localize_script('lkn-fix-token-script', 'lknCieloRestSettings', array(
            'rest_url'  => esc_url_raw(rest_url()),
            'nonce' => wp_create_nonce('wp_rest'),
        ));

        // Setup installment script
        wp_enqueue_script('lkn-cc-dc-installment-script', plugin_dir_url(__FILE__) . '../resources/js/frontend/lkn-cc-dc-installment.js', array('jquery'), LKN_WC_CIELO_VERSION, false);
        wp_localize_script('lkn-cc-dc-installment-script', 'lknWCCielo3ds', $installmentArgs);
        wp_localize_script('lkn-cc-dc-installment-script', 'lknWCCielo3dsConfig', array(
            'interest_or_discount' => $this->gateway->get_option('interest_or_discount'),
            'installment_discount' => $this->gateway->get_option('installment_discount')
        ));
        wp_localize_script('lkn-cc-dc-installment-script', 'lknWCCielo3dsAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('lkn_payment_fees_nonce'),
            'current_installment' => $current_installment,
            'current_card_type' => WC()->session ? WC()->session->get('lkn_cielo_debit_card_type', 'Credit') : 'Credit'
        ));

        wp_register_script(
            'lkn_cielo_debit-blocks-integration',
            plugin_dir_url(__FILE__) . '../resources/js/debitCard/lknCieloDebitCompiled.js',
            array(
                'wc-blocks-registry',
                'wc-settings',
                'wp-element',
                'wp-html-entities',
                'wp-i18n',
                'wp-api'
            ),
            '1.0.0',
            true
        );

        // Recupera informações de cartões salvos do usuário

        $user_id = get_current_user_id();
        $cardsArray = $user_id ? get_user_meta($user_id, 'card_array', true) : array();
        $defaultCard = $user_id ? get_user_meta($user_id, 'default_card', true) : '';

        // Remover o campo 'cardToken' de cada cartão antes de enviar ao frontend
        if (is_array($cardsArray)) {
            foreach ($cardsArray as $key => $card) {
                // Remover cartões expirados
                if (is_array($card) && isset($card['expirationDate'])) {
                    $exp = $card['expirationDate'];
                    // Formato esperado: MM/YYYY
                    if (preg_match('/^(0[1-9]|1[0-2])\/(\d{4})$/', $exp, $matches)) {
                        $expMonth = (int)$matches[1];
                        $expYear = (int)$matches[2];
                        $now = new \DateTime();
                        $expDate = \DateTime::createFromFormat('Y-m', $expYear . '-' . str_pad($expMonth, 2, '0', STR_PAD_LEFT));
                        $expDate->modify('last day of this month');
                        if ($now > $expDate) {
                            unset($cardsArray[$key]);
                            continue;
                        }
                    }
                }
                // Remover o campo 'cardToken'
                if (is_array($card) && isset($card['cardToken'])) {
                    unset($cardsArray[$key]['cardToken']);
                }
            }
            // Reindexar o array para evitar buracos nas chaves
            $cardsArray = array_values($cardsArray);
        }

        wp_localize_script('lkn_cielo_debit-blocks-integration', 'lknCieloDebitConfig', array(
            'isProPluginValid' => $is_pro_plugin_valid,
            'ajax_url' => admin_url('admin-ajax.php'),
            'fees_nonce' => wp_create_nonce('lkn_payment_fees_nonce'),
            'rest_url' => esc_url_raw(rest_url()),
            'nonce'    => wp_create_nonce('wp_rest'),
            'currency' => array(
                'code' => get_woocommerce_currency(),
                'symbol' => get_woocommerce_currency_symbol(),
                'decimals' => wc_get_price_decimals(),
                'decimal_separator' => wc_get_price_decimal_separator(),
                'thousand_separator' => wc_get_price_thousand_separator(),
                'position' => get_option('woocommerce_currency_pos', 'left')
            ),
            'card_array' => $cardsArray,
            'default_card' => $defaultCard
        ));

        // Adicionar os mesmos localize scripts da versão clássica
        wp_localize_script('lkn_cielo_debit-blocks-integration', 'lknDCDirScript3DSCieloShortCode', array('url' => LKN_WC_GATEWAY_CIELO_URL . 'resources/js/debitCard/BP.Mpi.3ds20.min.js'));
        wp_localize_script('lkn_cielo_debit-blocks-integration', 'lknDCScriptAllowCardIneligible', array('allow' => $this->gateway->get_option('allow_card_ineligible', 'no')));

        if (function_exists('wp_set_script_translations')) {
            wp_set_script_translations('lkn_cielo_debit-blocks-integration');
        }

        if ($use_modern_layout) {
            wp_enqueue_script('lkn-wc-gateway-debit-checkout-layout', plugin_dir_url(__FILE__) . '../resources/js/debitCard/lkn-wc-gateway-checkout-layout.js', array(), LKN_WC_CIELO_VERSION, false);
            wp_localize_script('lkn-wc-gateway-debit-checkout-layout', 'lknCieloRestSettings', array(
                'rest_url' => esc_url_raw(rest_url()),
                'nonce'    => wp_create_nonce('wp_rest'),
            ));

            // Verificar se os ícones de marca do cartão devem ser exibidos
            $show_card_brand_icons = isset($this->settings['show_card_brand_icons']) ? $this->settings['show_card_brand_icons'] : 'yes'; 

            wp_localize_script('lkn-wc-gateway-debit-checkout-layout', 'lknCieloDebitCardIcons', array(
                'visa'       => plugin_dir_url(__FILE__) . '../resources/img/visa-icon.svg',
                'mastercard' => plugin_dir_url(__FILE__) . '../resources/img/mastercard-icon.svg',
                'amex'       => plugin_dir_url(__FILE__) . '../resources/img/amex-icon.svg',
                'elo'        => plugin_dir_url(__FILE__) . '../resources/img/elo-icon.svg',
                'other_card'        => plugin_dir_url(__FILE__) . '../resources/img/other-card.svg',
                'other_card_alt'    => __('other card', 'lkn-wc-gateway-cielo'),
                'show_card_brand_icons' => $show_card_brand_icons
            ));
            wp_localize_script('lkn-wc-gateway-debit-checkout-layout', 'lknCieloInputIcons', array(
                'calendar'       => plugin_dir_url(__FILE__) . '../resources/img/calendar.svg',
                'key' => plugin_dir_url(__FILE__) . '../resources/img/key.svg',
                'lock'       => plugin_dir_url(__FILE__) . '../resources/img/lock.svg'
            ));
            wp_enqueue_style('lkn-wc-gateway-debit-checkout-layout', plugin_dir_url(__FILE__) . '../resources/css/frontend/lkn-wc-gateway-debit-card-checkout-layout.css', array(), LKN_WC_CIELO_VERSION, 'all');

            // Checkout installment select script
            if (function_exists('WC') && WC()->session) {
                WC()->session->set('lkn_cielo_credit_installment', '1');
                WC()->session->set('lkn_cielo_debit_installment', '1');
                // Força sempre Credit na inicialização para ambos gateways
                WC()->session->set('lkn_cielo_debit_card_type', 'Credit');
            }
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

        do_action('lkn_wc_cielo_remove_cardholder_name_3ds', $this->gateway);
        return array('lkn_cielo_debit-blocks-integration');
    }

    public function get_payment_method_data()
    {
        if ($this->gateway->get_option('env') == 'sandbox') {
            $dirScriptConfig3DS = LKN_WC_GATEWAY_CIELO_URL . 'resources/js/debitCard/lkn-dc-script-sdb.js';
        } else {
            $dirScriptConfig3DS = LKN_WC_GATEWAY_CIELO_URL . 'resources/js/debitCard/lkn-dc-script-prd.js';
        }

        $installmentMinAmount = apply_filters('lkn_wc_cielo_set_installment_min_amount', '5,00', $this->gateway);
        $installmentMinAmount = preg_replace('/,/', '.', $installmentMinAmount);

        $installmentLimit = $this->gateway->get_option('installment_limit', 12);
        $installments = array();

        $installmentLimit = apply_filters('lkn_wc_cielo_set_installment_limit', $installmentLimit, $this->gateway);

        $installments = apply_filters('lkn_wc_cielo_set_installments', $installments, $this->gateway);
        $user = wp_get_current_user();

        $billingDocument = get_user_meta($user->ID, 'billing_cpf', true);

        if (empty($billingDocument) || false === $billingDocument) {
            $billingDocument = get_user_meta($user->ID, 'billing_cnpj', true);

            if (empty($billingDocument) || false === $billingDocument) {
                $billingDocument = '';
            }
        }
        $acessToken = $this->gateway->generate_debit_auth_token();

        return array(
            'title' => $this->gateway->title,
            'description' => $this->gateway->description,
            'supports' => $this->gateway->supports,
            'accessToken' => isset($acessToken['access_token']) ? $acessToken['access_token'] : '',
            'accessTokenExpiration' => isset($acessToken['expires_in']) ? $acessToken['expires_in'] : '',
            'url' => get_page_link(),
            'orderNumber' => uniqid(),
            'activeInstallment' => $this->gateway->get_option('installment_payment'),
            'activeDiscount' => $this->gateway->get_option('installment_discount', 'no'),
            'interestOrDiscount' => $this->gateway->get_option('interest_or_discount', 'no'),
            'dirScript3DS' => LKN_WC_GATEWAY_CIELO_URL . 'resources/js/debitCard/BP.Mpi.3ds20.min.js',
            'dirScriptConfig3DS' => $dirScriptConfig3DS,
            'totalCart' => $this->gateway->lknGetCartTotal(),
            'nonceCieloDebit' => wp_create_nonce('nonce_lkn_cielo_debit'),
            'installmentLimit' => $installmentLimit,
            'installments' => $installments,
            'installmentMinAmount' => $installmentMinAmount,
            'bec' => $this->gateway->get_option('establishment_code'),
            'client_ip' => LknWcCieloHelper::getClientIp(),
            'user_guest' => ! is_user_logged_in(),
            'authentication_method' => is_user_logged_in() ? '02' : '01',
            'showCard' => $this->gateway->get_option('show_card_animation'),
            'showSaveCardToken' => $this->gateway->get_option('save_card_token'),
            'client' => array(
                'name' => $user->display_name,
                'email' => $user->user_email,
                // Fallback: billing → shipping → custom → vazio, para garantir dados ELO obrigatórios
                'billing_phone' => $this->get_user_meta_fallback($user->ID, 'billing_phone', 'shipping_phone', 'custom-phone'),
                'billing_address_1' => $this->get_user_meta_fallback($user->ID, 'billing_address_1', 'shipping_address_1'),
                'billing_address_2' => $this->get_user_meta_fallback($user->ID, 'billing_address_2', 'shipping_address_2'),
                'billing_city' => $this->get_user_meta_fallback($user->ID, 'billing_city', 'shipping_city'),
                'billing_state' => $this->get_user_meta_fallback($user->ID, 'billing_state', 'shipping_state'),
                'billing_postcode' => $this->get_user_meta_fallback($user->ID, 'billing_postcode', 'shipping_postcode'),
                'billing_country' => $this->get_user_meta_fallback($user->ID, 'billing_country', 'shipping_country'),
                'billing_document' => $billingDocument
            ),
            'translations' => array(
                'cardNumber' => __('Card Number', 'lkn-wc-gateway-cielo'),
                'cardExpiryDate' => __('Expiry Date', 'lkn-wc-gateway-cielo'),
                'securityCode' => __('Security Code', 'lkn-wc-gateway-cielo'),
                'installments' => __('Installments', 'lkn-wc-gateway-cielo'),
                'cardHolder' => __('Card Holder Name', 'lkn-wc-gateway-cielo'),
                'creditCard' => __('Credit card', 'lkn-wc-gateway-cielo'),
                'debitCard' => __('Debit card', 'lkn-wc-gateway-cielo'),
                'cardType' => __('Card type', 'lkn-wc-gateway-cielo'),
                'saveCard' => __('Save card for safe and fast purchase.', 'lkn-wc-gateway-cielo'),
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

    /**
     * Helper method: retorna o valor do primeiro meta key que existir e não for vazio.
     * Suporta fallback em múltiplas chaves (ex: billing_phone → shipping_phone → phone).
     *
     * @param int   $user_id
     * @param string ...$keys  Lista ordenada de meta keys para tentar
     * @return string
     */
    private function get_user_meta_fallback($user_id, ...$keys)
    {
        foreach ($keys as $key) {
            if (empty($key)) {
                continue;
            }
            $value = get_user_meta($user_id, $key, true);
            if (! empty($value) && false !== $value) {
                return $value;
            }
        }
        return '';
    }
}
