<?php

namespace Lkn\WCCieloPaymentGateway\Includes;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use Lkn\WCCieloPaymentGateway\Includes\LknWCGatewayCieloDebit;

final class LknWCGatewayCieloGooglePayBlocks extends AbstractPaymentMethodType
{
    private $gateway;
    protected $name = 'lkn_cielo_google_pay';

    public function initialize(): void
    {
        $this->settings = get_option('woocommerce_lkn_cielo_google_pay_settings', array());
        $lknWcGateWayCieloGooglePay = new LknWCGatewayCieloGooglePay();
        $this->gateway = $lknWcGateWayCieloGooglePay;
    }

    public function is_active()
    {
        return $this->gateway->is_available();
    }

    public function get_payment_method_script_handles()
    {
        if (is_checkout()) {
            wp_enqueue_script('lknWCGatewayCieloGooglePayScript', 'https://pay.google.com/gp/p/js/pay.js', array('jquery'), LKN_WC_CIELO_VERSION, false);
            wp_register_script(
                'lkn_cielo_google_pay-blocks-integration',
                plugin_dir_url(__FILE__) . '../resources/js/googlePay/lknCieloGooglePay.js',
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

            return array('lkn_cielo_google_pay-blocks-integration');
        }

        return array();
    }

    public function get_payment_method_data()
    {

        return array(
            'title' => $this->gateway->title,
            'description' => $this->gateway->description,
            'supports' => $this->gateway->supports,
            'env' => $this->gateway->get_option('env', 'TEST'),
            'googleMerchantId' => $this->gateway->get_option('google_merchant_id'),
            'googleMerchantName' => $this->gateway->get_option('google_merchant_name'),
            'buttonText' => $this->gateway->get_option('google_text_button', 'pay'),
            'currency' => get_woocommerce_currency(),
            'locale' => substr(get_locale(), 0, 2),
            'require3ds' => $this->gateway->get_option('require_3ds', 'no'),
            'thousandSeparator' => wc_get_price_thousand_separator(),
            'decimalSeparator' => wc_get_price_decimal_separator(),
            'nonce' => wp_create_nonce('nonce_lkn_cielo_google_pay')
        );
    }
}
