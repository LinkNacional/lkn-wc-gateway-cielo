<?php

namespace Lkn\WCCieloPaymentGateway\Includes;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class LknWcCieloPixBlocks extends AbstractPaymentMethodType
{
    private $gateway;
    protected $name = 'lkn_wc_cielo_pix';

    public function initialize(): void
    {
        $this->settings = get_option('cielo_wc_pix_settings', array());
        $this->gateway = new LknWcCieloPix();
    }

    public function is_active()
    {
        return $this->gateway->is_available();
    }

    public function get_payment_method_script_handles()
    {
        wp_register_script(
            'lkn-cielo-pix-blocks-integration',
            plugin_dir_url(__FILE__) . '../resources/js/pix/lkn-cielo-pix-script-blocks.js',
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
        return array('lkn-cielo-pix-blocks-integration');
    }

    public function get_payment_method_data()
    {
        return array(
            'title' => $this->gateway->title,
            'description' => $this->gateway->get_option('description', __('After the purchase is completed, the PIX will be generated and made available for payment!', 'lkn-wc-gateway-cielo')),
            'supports' => $this->gateway->supports,
        );
    }
}
