<?php

namespace Lkn\WCCieloPaymentGateway\Includes;

use Lkn\WCCieloPaymentGateway\Includes\LknWcCieloHelper;
use DateTime;
use Exception;
use WC_Logger;
use WC_Subscriptions_Order;
use WC_Payment_Gateway;
use WC_Subscription;

/**
 * Lkn_WC_Gateway_cielo_google_pay class.
 *
 * @author   Link Nacional
 *
 * @since    1.0.0
 */

// Exit if accessed directly.
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Cielo API 3.0 Credit Card Gateway.
 *
 * @class    Lkn_WC_Gateway_cielo_google_pay
 *
 * @version  1.0.0
 */
final class LknWCGatewayCieloGooglePay extends WC_Payment_Gateway
{
    public $instructions = '';

    private $version = LKN_WC_CIELO_VERSION;

    private $log;

    public function __construct()
    {
        $this->id = 'lkn_cielo_google_pay';
        $this->icon = apply_filters('lkn_wc_cielo_gateway_icon', '');
        $this->has_fields = true;
        $this->supports = array(
            'products',
        );

        $this->method_title = __('Cielo - Google Pay', 'lkn-wc-gateway-cielo');
        $this->method_description = __('Allows Google Pay payment with Cielo API 3.0.', 'lkn-wc-gateway-cielo');

        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();

        // Define user set variables.
        $this->icon = LknWcCieloHelper::getIconUrl();
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->instructions = $this->get_option('instructions', $this->description);

        $this->log = new WC_Logger();

        // Actions.
        add_filter('woocommerce_new_order_note_data', array($this, 'add_gateway_name_to_notes'), 10, 2);
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

        // Action hook to load admin JavaScript
        if (function_exists('get_plugins')) {
            add_action('admin_enqueue_scripts', array($this, 'admin_load_script'));
        }
    }

    /**
     * Load admin JavaScript for the admin page.
     */
    public function admin_load_script(): void
    {
        wp_enqueue_script('lkn-wc-gateway-admin', plugin_dir_url(__FILE__) . '../resources/js/admin/lkn-wc-gateway-admin.js', array('wp-i18n'), $this->version, 'all');

        $pro_plugin_exists = file_exists(WP_PLUGIN_DIR . '/lkn-cielo-api-pro/lkn-cielo-api-pro.php');
        $pro_plugin_active = function_exists('is_plugin_active') && is_plugin_active('lkn-cielo-api-pro/lkn-cielo-api-pro.php');

        wp_localize_script('lkn-wc-gateway-admin', 'lknCieloProStatus', array(
            'isProActive' => $pro_plugin_exists && $pro_plugin_active ? true : false,
        ));

        $page = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : '';
        $tab = isset($_GET['tab']) ? sanitize_text_field(wp_unslash($_GET['tab'])) : '';
        $section = isset($_GET['section']) ? sanitize_text_field(wp_unslash($_GET['section'])) : '';

        if ('wc-settings' === $page && 'checkout' === $tab && $section == $this->id) {
            wp_enqueue_script('lknWCGatewayCieloGooglePaySettingsLayoutScript', plugin_dir_url(__FILE__) . '../resources/js/admin/lkn-wc-gateway-admin-layout.js', array('jquery'), $this->version, false);
            $gateway_settings = $this->settings;
            wp_localize_script('lknWCGatewayCieloGooglePaySettingsLayoutScript', 'lknWcCieloTranslationsInput', array(
                'modern' => __('Modern version', 'lkn-wc-gateway-cielo'),
                'standard' => __('Standard version', 'lkn-wc-gateway-cielo'),
                'enable' => __('Enable', 'lkn-wc-gateway-cielo'),
                'disable' => __('Disable', 'lkn-wc-gateway-cielo'),
                'analytics_url' => admin_url('admin.php?page=wc-admin&path=%2Fanalytics%2Fcielo-transactions'),
                'gateway_settings' => $gateway_settings,
                'whatsapp_number' => LKN_WC_CIELO_WPP_NUMBER,
                'site_domain' => home_url(),
                'gateway_id' => $this->id,
                'version_free' => LKN_WC_CIELO_VERSION,
                'version_pro' => (is_plugin_active('lkn-cielo-api-pro/lkn-cielo-api-pro.php') && defined('LKN_CIELO_API_PRO_VERSION')) ? LKN_CIELO_API_PRO_VERSION : 'N/A'
            ));
            wp_enqueue_style('lkn-admin-layout', plugin_dir_url(__FILE__) . '../resources/css/frontend/lkn-admin-layout.css', array(), $this->version, 'all');
            wp_enqueue_script('lknWCGatewayCieloGooglePayClearButtonScript', plugin_dir_url(__FILE__) . '../resources/js/admin/lkn-clear-logs-button.js', array('jquery'), $this->version, false);
            wp_localize_script('lknWCGatewayCieloGooglePayClearButtonScript', 'lknWcCieloTranslations', array(
                'clearLogs' => __('Clear Logs', 'lkn-wc-gateway-cielo'),
                'sendConfigs' => __('Wordpress Support', 'lkn-wc-gateway-cielo'),
                'alertText' => __('Do you really want to delete all order logs?', 'lkn-wc-gateway-cielo'),
                'production' => __('Use this in the live store to charge real payments.', 'lkn-wc-gateway-cielo'),
                'sandbox' => __('Use this for testing purposes in the Cielo sandbox environment.', 'lkn-wc-gateway-cielo'),
                'enable' => __('Enable', 'lkn-wc-gateway-cielo'),
                'disable' => __('Disable', 'lkn-wc-gateway-cielo'),
                'nonce' => wp_create_nonce('lkn_cielo_clear_logs_nonce'),
                'ajaxUrl' => admin_url('admin-ajax.php')
            ));
            wp_enqueue_script('lknWCGatewayCieloGooglePaySettingsFixLayoutScript', plugin_dir_url(__FILE__) . '../resources/js/admin/lkn-wc-gateway-admin-fix-layout.js', array('jquery'), $this->version, false);

            
        }
    }

    /**
     * Get default merchant_id from other gateways (credit -> debit -> pix)
     */
    private function get_default_merchant_id(): string
    {
        // Tenta pegar do cartão de crédito primeiro
        $credit_settings = get_option('woocommerce_lkn_cielo_credit_settings');
        if (!empty($credit_settings['merchant_id'])) {
            return $credit_settings['merchant_id'];
        }

        // Se não encontrou no crédito, tenta no débito
        $debit_settings = get_option('woocommerce_lkn_cielo_debit_settings');
        if (!empty($debit_settings['merchant_id'])) {
            return $debit_settings['merchant_id'];
        }

        // Se não encontrou no débito, tenta no PIX
        $pix_settings = get_option('woocommerce_lkn_wc_cielo_pix_settings');
        if (!empty($pix_settings['merchant_id'])) {
            return $pix_settings['merchant_id'];
        }

        return '';
    }

    /**
     * Get default merchant_key from other gateways (credit -> debit -> pix)
     */
    private function get_default_merchant_key(): string
    {
        // Tenta pegar do cartão de crédito primeiro
        $credit_settings = get_option('woocommerce_lkn_cielo_credit_settings');
        if (!empty($credit_settings['merchant_key'])) {
            return $credit_settings['merchant_key'];
        }

        // Se não encontrou no crédito, tenta no débito
        $debit_settings = get_option('woocommerce_lkn_cielo_debit_settings');
        if (!empty($debit_settings['merchant_key'])) {
            return $debit_settings['merchant_key'];
        }

        // Se não encontrou no débito, tenta no PIX
        $pix_settings = get_option('woocommerce_lkn_wc_cielo_pix_settings');
        if (!empty($pix_settings['merchant_key'])) {
            return $pix_settings['merchant_key'];
        }

        return '';
    }

    /**
     * Initialise Gateway Settings Form Fields.
     */
    public function init_form_fields(): void
    {
        $this->form_fields = array(
            'general' => array(
                'title' => esc_attr__('General', 'lkn-wc-gateway-cielo'),
                'type' => 'title',
            ),
            'enabled' => array(
                'title' => __('Enable/Disable', 'lkn-wc-gateway-cielo'),
                'type' => 'checkbox',
                'label' => __('Enable Google Pay Payments', 'lkn-wc-gateway-cielo'),
                'default' => 'no',
                'description' => __('Habilitar ou desabilitar o método de pagamento Google Pay.', 'lkn-wc-gateway-cielo'),
                'desc_tip'    => __('Marque esta opção e salve para habilitar as configurações do Google Pay.', 'lkn-wc-gateway-cielo'),
                'custom_attributes' => array(
                    'data-title-description' => __('Disponibilize o Google Pay via API 3.0 da Cielo para os seus clientes. <a href="https://www.youtube.com/watch?v=rP_UAPcIG4I" target="_blank">Saiba mais</a>.', 'lkn-wc-gateway-cielo')
                )
            ),
            'title' => array(
                'title'       => __('Title', 'lkn-wc-gateway-cielo'),
                'type'        => 'text',
                'default'     => __('Google Pay', 'lkn-wc-gateway-cielo'),
                'description' => __('Insira o título que será exibido para os utilizadores no checkout.', 'lkn-wc-gateway-cielo'),
                'desc_tip'    => __('Enter the title that will be shown to customers during the checkout process.', 'lkn-wc-gateway-cielo'),
                'custom_attributes' => array(
                    'required' => 'required',
                    'data-title-description' => __('This text will appear as the payment method title during checkout. Choose something your customers will easily understand, like “Pay with Google Pay (Cielo)”.', 'lkn-wc-gateway-cielo')
                )
            ),
            'env' => array(
                'title'       => __('Environment', 'lkn-wc-gateway-cielo'),
                'type'        => 'select',
                'options'     => array(
                    'PRODUCTION' => __('Production', 'lkn-wc-gateway-cielo'),
                    'TEST'    => __('Development', 'lkn-wc-gateway-cielo'),
                ),
                'default'   => 'production',
                'description' => __("‘Produção’ para as suas credenciais de venda e 'Desenvolvimento' para as suas chaves de teste (Sandbox).", 'lkn-wc-gateway-cielo'),
                'desc_tip'    => __('Preencha com os dados fornecidos pela CIELO.', 'lkn-wc-gateway-cielo'),
                'custom_attributes' => array(
                    'data-title-description' => __('Selecione o ambiente (Produção ou Sandbox) em que suas chaves da API Cielo foram geradas.', 'lkn-wc-gateway-cielo')
                )
            ),
            'merchant_id' => array(
                'title'       => __('Merchant Id', 'lkn-wc-gateway-cielo'),
                'type'        => 'password',
                'default'     => $this->get_default_merchant_id(),
                'description' => __('Cielo credentials.', 'lkn-wc-gateway-cielo'),
                'custom_attributes' => array(
                    'required' => 'required',
                    'data-title-description' => __('This is your Cielo Merchant ID used to authenticate API requests. You can find it in your Cielo dashboard.', 'lkn-wc-gateway-cielo')
                )
            ),
            'merchant_key' => array(
                'title'       => __('Merchant Key', 'lkn-wc-gateway-cielo'),
                'type'        => 'password',
                'default'     => $this->get_default_merchant_key(),
                'description' => __('Cielo credentials.', 'lkn-wc-gateway-cielo'),
                'custom_attributes' => array(
                    'required' => 'required',
                    'data-title-description' => __('Esta é sua chave de comerciante secreta (Merchant Key) usada para assinar transações com a Cielo API. Mantenha-a segura e não a compartilhe.', 'lkn-wc-gateway-cielo')
                )
            ),
            'google_merchant_name' => array(
                'title'       => __('Nome do Comerciante', 'lkn-wc-gateway-cielo'),
                'type'        => 'text',
                'description' => __('Nome da loja no Google Pay.', 'lkn-wc-gateway-cielo'),
                'desc_tip'    => __('Insira os dados definidos pelo Google Pay.', 'lkn-wc-gateway-cielo'),
                'custom_attributes' => array(
                    'required' => 'required',
                    'data-title-description' => __('Insira o Nome do Comerciante Google para sua integração Google Pay.', 'lkn-wc-gateway-cielo')
                )
            ),
            'google_merchant_id' => array(
                'title'       => __('Merchant Id do Google', 'lkn-wc-gateway-cielo'),
                'type'        => 'password',
                'description' => __('Chave de produção do Google Pay.', 'lkn-wc-gateway-cielo'),
                'custom_attributes' => array(
                    'required' => 'required',
                    'data-title-description' => __('Insira o ID do Comerciante Google para sua integração Google Pay.', 'lkn-wc-gateway-cielo')
                )
            ),
            'google_text_button' => array(
                'title'       => __('Botão Google Pay', 'lkn-wc-gateway-cielo'),
                'type'        => 'select',
                'options'     => array(
                    'pay'    => __('Pay', 'lkn-wc-gateway-cielo'),
                    'buy'    => __('Buy', 'lkn-wc-gateway-cielo'),
                    'checkout' => __('Checkout', 'lkn-wc-gateway-cielo'),
                    'donate' => __('Donate', 'lkn-wc-gateway-cielo'),
                ),
                'default'   => 'pay',
                'custom_attributes' => array(
                    'data-title-description' => __('Escolha o texto a ser exibido no botão do Google Pay.', 'lkn-wc-gateway-cielo')
                )
            ),
            'require_3ds' => array(
                'title'   => __('Exigir 3DS', 'lkn-wc-gateway-cielo'),
                'type'    => 'checkbox',
                'label'   => __('Permitir apenas pagamentos com 3DS', 'lkn-wc-gateway-cielo'),
                'default' => 'no',
                'description' => __('Quando habilitado, apenas transações com autenticação 3DS serão processadas.', 'lkn-wc-gateway-cielo'),
                'desc_tip' => __('Esta configuração aumenta a segurança, mas bloqueia alguns cartões que não suportam 3DS.', 'lkn-wc-gateway-cielo'),
                'custom_attributes' => array(
                    'data-title-description' => __('Ative para exigir autenticação 3DS em todas as transações do Google Pay para maior segurança.', 'lkn-wc-gateway-cielo')
                )
            )
        );

        // Developer/Debug section
        $this->form_fields += array(
            'developer' => array(
                'title' => esc_attr__('Developer', 'lkn-wc-gateway-cielo'),
                'type'  => 'title',
            ),
            'debug' => array(
                'title'   => __('Debug', 'lkn-wc-gateway-cielo'),
                'type'    => 'checkbox',
                'label'   => sprintf(
                    // translators: %1$s is the enable log text, %2$s is the admin URL, %3$s is the view logs text
                    '%1$s. <a href="%2$s">%3$s</a>',
                    __('Enable log capture for payments', 'lkn-wc-gateway-cielo'),
                    admin_url('admin.php?page=wc-status&tab=logs'),
                    __('View logs', 'lkn-wc-gateway-cielo')
                ),
                'default'  => 'no',
                'description' => __('Enable this option to log payment requests and responses for troubleshooting purposes.', 'lkn-wc-gateway-cielo'),
                'desc_tip' => __('Useful for identifying errors in payment requests or responses during development or support.', 'lkn-wc-gateway-cielo'),
                'custom_attributes' => array(
                    'data-title-description' => __('Useful for developers to monitor errors and status.', 'lkn-wc-gateway-cielo')
                )
            ),
        );

        // PRO section (send configs)
        $pro_plugin_active = LknWcCieloHelper::is_pro_license_active();
        if ($pro_plugin_active) {
            $this->form_fields['send_configs'] = array(
                'title' => __('WhatsApp Support', 'lkn-wc-gateway-cielo'),
                'type'  => 'button',
                'id'    => 'sendConfigs',
                'description' => __('Enable Debug Mode and click Save Changes to get quick support via WhatsApp.', 'lkn-wc-gateway-cielo'),
                'desc_tip' => null,
                'custom_attributes' => array(
                    'merge-top' => "woocommerce_{$this->id}_debug",
                    'data-title-description' => __('Send the settings for this payment method to WordPress Support.', 'lkn-wc-gateway-cielo')
                )
            );
        }

        // Logs section (order logs and clear logs)
        $this->form_fields += array(
            'show_order_logs' => array(
                'title'   => __('Visualizar Log no Pedido', 'lkn-wc-gateway-cielo'),
                'type'    => 'checkbox',
                'label'   => __('Habilita visualização do log da transação dentro do pedido.', 'lkn-wc-gateway-cielo'),
                'default' => 'no',
                'description' => __('Displays Cielo transaction logs inside WooCommerce order details.', 'lkn-wc-gateway-cielo'),
                'desc_tip' => __('Useful for quickly viewing payment log data without accessing the system log files.', 'lkn-wc-gateway-cielo'),
                'custom_attributes' => array(
                    'data-title-description' => __('Allows transaction logs to be viewed directly on the order page.', 'lkn-wc-gateway-cielo')
                )
            ),
            'clear_order_records' => array(
                'title' => __('Limpar logs nos Pedidos', 'lkn-wc-gateway-cielo'),
                'type'  => 'button',
                'id'    => 'clearOrderLogs',
                'class' => 'woocommerce-save-button components-button is-primary',
                'description' => null,
                'desc_tip' => null,
                'custom_attributes' => array(
                    'merge-top' => "woocommerce_{$this->id}_show_order_logs",
                    'data-title-description' => __('Button to clear logs stored in orders.', 'lkn-wc-gateway-cielo')
                )
            ),
        );

        $this->form_fields['transactions'] = array(
            'title' => esc_attr__('Transactions', 'lkn-wc-gateway-cielo'),
            'id' => 'transactions_title',
            'type'  => 'title',
        );
    }

    /**
     * Render the payment fields.
     */
    public function payment_fields(): void
    {
        if (!is_checkout()) {
            return;
        }

        // Carregar scripts apenas quando necessário
        wp_enqueue_style('lknWCGatewayCieloGooglePayStyle', plugin_dir_url(__FILE__) . '../resources/css/frontend/lkn-wc-google-pay.css', array(), $this->version, 'all');
        wp_enqueue_script('lknWCGatewayCieloGooglePayScript', 'https://pay.google.com/gp/p/js/pay.js', array('jquery'), $this->version, false);
        wp_enqueue_script('lknWCGatewayCieloGooglePayCheckoutScript', plugin_dir_url(__FILE__) . '../resources/js/googlePay/lkn-wc-google-pay.js', array('jquery', 'wp-api'), $this->version, false);
        wp_localize_script('lknWCGatewayCieloGooglePayCheckoutScript', 'lknWcCieloGooglePayVars', array(
            'env' => $this->get_option('env', 'TEST'),
            'googleMerchantId' => $this->get_option('google_merchant_id'),
            'googleMerchantName' => $this->get_option('google_merchant_name'),
            'buttonText' => $this->get_option('google_text_button', 'pay'),
            'currency' => get_woocommerce_currency(),
            'locale' => substr(get_locale(), 0, 2),
            'thousandSeparator' => wc_get_price_thousand_separator(),
            'decimalSeparator' => wc_get_price_decimal_separator(),
            'require3ds' => $this->get_option('require_3ds', 'no'),
            'nonce' => wp_create_nonce('nonce_lkn_cielo_google_pay'),
        ));
    }

    /**
     * Fields validation.
     *
     * @return bool
     */
    public function validate_fields()
    {
        return true;
    }

    /**
     * Process the payment and return the result.
     *
     * @param int $order_id
     *
     * @return array
     */
    public function process_payment($order_id)
    {
        $nonceInactive = $this->get_option('nonce_compatibility', 'no');
        $nonce = isset($_POST['nonce_lkn_cielo_google_pay']) ? sanitize_text_field(wp_unslash($_POST['nonce_lkn_cielo_google_pay'])) : '';

        if (! wp_verify_nonce($nonce, 'nonce_lkn_cielo_google_pay') && 'no' === $nonceInactive) {
            $this->log->log('error', 'Nonce verification failed. Nonce: ' . var_export($nonce, true), array('source' => 'woocommerce-cielo-google-pay'));
            $this->add_notice_once(__('Nonce verification failed, try reloading the page', 'lkn-wc-gateway-cielo'), 'error');
            throw new Exception(esc_attr(__('Nonce verification failed, try reloading the page', 'lkn-wc-gateway-cielo')));
        }
        // Validate and sanitize google_pay_data
        if (!isset($_POST['google_pay_data']) || empty($_POST['google_pay_data'])) {
            $this->log->log('error', 'Google Pay data is missing', array('source' => 'woocommerce-cielo-google-pay'));
            throw new Exception(esc_attr(__('Payment data is missing, please try again.', 'lkn-wc-gateway-cielo')));
        }

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
        $raw_gpay_data = isset($_POST['google_pay_data']) ? $_POST['google_pay_data'] : '';
        
        if (is_string($raw_gpay_data)) {
            $data = json_decode(wp_unslash($raw_gpay_data));
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $data = json_decode($raw_gpay_data);
            }
        } elseif (is_array($raw_gpay_data)) {
            // Se for array (Blocks via REST API), transformamos em Objeto
            $data = json_decode(wp_json_encode($raw_gpay_data));
        } else {
            $data = $raw_gpay_data;
        }

        // 3. Validação de segurança para evitar os erros de "Attempt to read property on null"
        if (!is_object($data) || !isset($data->paymentMethodData->tokenizationData->token)) {
            $this->log->log('error', 'Google Pay Data structure is invalid or missing token.');
            throw new Exception(esc_attr(__('Invalid payment data structure.', 'lkn-wc-gateway-cielo')));
        }

        // 4. Pegando o token bruto (que é uma string JSON)
        $raw_token = $data->paymentMethodData->tokenizationData->token;

        // 5. Extraindo o $paymentData e o $walletKey
        // Fazemos o decode do token para acessar o signedMessage
        $paymentData = is_string($raw_token) ? json_decode($raw_token) : $raw_token;
        
        if (is_object($paymentData) && isset($paymentData->signedMessage)) {
            $walletKey = $paymentData->signedMessage;
        } else {
            // Fallback caso a estrutura mude ou o signedMessage não exista
            $walletKey = $raw_token; 
        }

        $order = wc_get_order($order_id);
        $merchantId = sanitize_text_field($this->get_option('merchant_id'));
        $merchantSecret = sanitize_text_field($this->get_option('merchant_key'));
        $amount = $order->get_total();
        $merchantOrderId = uniqid('invoice_');
        $currency = get_woocommerce_currency();

        // Convert the amount to equivalent in BRL
        if ('BRL' !== $currency) {
            $amount = apply_filters('lkn_wc_cielo_convert_amount', $amount, $currency, $this);

            $order->add_meta_data('amount_converted', $amount, true);
        }

        $amountFormated = number_format($amount, 2, '', '');
        $url = ($this->get_option('env') == 'production') ? 'https://api.cieloecommerce.cielo.com.br/' : 'https://apisandbox.cieloecommerce.cielo.com.br/';
        
        $args['headers'] = array(
            'Content-Type' => 'application/json',
            'MerchantId' => $merchantId,
            'MerchantKey' => $merchantSecret,
        );

        $body = array(
            'MerchantOrderId' => $merchantOrderId,
            'Payment' => array(
                'Type' => 'CreditCard',
                'Amount' => (int) $amountFormated,
                'Installments' => 1,
                'Wallet' => array(
                    'Type' => 'AndroidPay',
                    'WalletKey' => $walletKey,
                ),
                "AdditionalData" => array(
                    "Signature" => isset($paymentData) && isset($paymentData->signature) ? $paymentData->signature : '', // Adiciona a assinatura se estiver disponível
                )
            ),
        );

        $args['body'] = wp_json_encode($body);
        $args['timeout'] = 120;

        $response = wp_remote_post($url . '1/sales', $args);

        if (is_wp_error($response)) {
            if ('yes' === $this->get_option('debug')) {
                $this->log->log('error', var_export($response->get_error_messages(), true), array('source' => 'woocommerce-cielo-google-pay'));
            }

            $message = __('Order payment failed. Please review the gateway settings.', 'lkn-wc-gateway-cielo');

            throw new Exception(esc_attr($message));
        }
        $responseDecoded = json_decode($response['body']);

        // Verificar erro 212 (Google Pay não configurado na Cielo)
        if (is_array($responseDecoded) && isset($responseDecoded[0]->Code) && $responseDecoded[0]->Code == 212) {
            throw new Exception(esc_attr(__('Google Pay is not configured in Cielo.', 'lkn-wc-gateway-cielo')));
        }

        if ($this->get_option('debug') === 'yes') {
            $lknWcCieloHelper = new LknWcCieloHelper();

            $orderLogsArray = array(
                'url' => $url . '1/sales',
                'headers' => array(
                    'Content-Type' => $args['headers']['Content-Type'],
                    'MerchantId' => $lknWcCieloHelper->censorString($args['headers']['MerchantId'], 10),
                    'MerchantKey' => $lknWcCieloHelper->censorString($args['headers']['MerchantKey'], 10)
                ),
                'body' => json_decode($args['body'], true), // Decodificar como array associativo
                'response' => json_decode(json_encode($responseDecoded), true) // Certificar que responseDecoded é um array associativo
            );

            $orderLogs = json_encode($orderLogsArray);
            $order->update_meta_data('lknWcCieloOrderLogs', $orderLogs);
            $order->save();
        }

        if (isset($responseDecoded->Payment) && (1 == $responseDecoded->Payment->Status || 2 == $responseDecoded->Payment->Status)) {
            // Salvar metadados completos do pagamento (como Credit/Debit)
            $payment = $responseDecoded->Payment;
            $cc = isset($payment->CreditCard) ? $payment->CreditCard : null;
            $cardNum = $cc ? $cc->CardNumber : '';
            $brand = $cc ? $cc->Brand : '';
            $expDate = $cc ? $cc->ExpirationDate : '';
            $holder = $cc ? $cc->Holder : '';

            $lastFourDigits = $cardNum ? substr($cardNum, -4) : '';
            $bin = $cardNum ? substr($cardNum, 0, 6) : '';

            $order->update_meta_data('_lkn_used_card_brand', $brand);
            $order->update_meta_data('_lkn_used_card_last4', $lastFourDigits);
            $order->update_meta_data('_lkn_card_bin', $bin);
            if (!empty($expDate)) $order->update_meta_data('_lkn_card_expiration', $expDate);
            if (!empty($holder)) $order->update_meta_data('_lkn_card_holder', $holder);
            $order->update_meta_data('_lkn_installments', 1);
            $order->update_meta_data('_lkn_card_type', 'Credit');
            if (isset($payment->ProofOfSale)) $order->update_meta_data('_lkn_nsu', $payment->ProofOfSale);
            if (isset($payment->Tid)) $order->update_meta_data('_lkn_tid', $payment->Tid);
            if (isset($payment->ReturnCode)) $order->update_meta_data('_lkn_return_code', $payment->ReturnCode);
            if (isset($payment->ReturnMessage)) $order->update_meta_data('_lkn_return_message', $payment->ReturnMessage);
            if (isset($payment->ReceivedDate)) $order->update_meta_data('_lkn_received_date', $payment->ReceivedDate);
            if (isset($payment->Status)) $order->update_meta_data('_lkn_payment_status', $payment->Status);

            $order->add_meta_data('paymentId', $payment->PaymentId, true);

            //Atualiza order para processando
            $order->update_status('processing');
            // Executar ações de mudança de status
            do_action("lkn_wc_cielo_change_order_status", $order, $this, true);

            // Adicionar nota do pedido com detalhes do pagamento
            $order->add_order_note(
                '[' . $this->id . '] ' .
                __('Payment completed successfully. Payment id:', 'lkn-wc-gateway-cielo') .
                    ' ' .
                    $responseDecoded->Payment->PaymentId .
                    PHP_EOL .
                    __('Proof of sale (NSU)', 'lkn-wc-gateway-cielo') .
                    ' - ' .
                    $responseDecoded->Payment->ProofOfSale .
                    PHP_EOL .
                    'TID ' .
                    $responseDecoded->Payment->Tid .
                    ' - ' .
                    PHP_EOL .
                    __('Return code', 'lkn-wc-gateway-cielo') .
                    ' - ' .
                    $responseDecoded->Payment->ReturnCode
            );

            // Finalizar processo
            WC()->cart->empty_cart();
            do_action("lkn_wc_cielo_update_order", $order_id, $this);
            $order->save();

            // Return thankyou redirect
            return array(
                'result' => 'success',
                'redirect' => $this->get_return_url($order),
            );
        }
        if (isset($responseDecoded->Payment->ReturnCode) && 'GF' == $responseDecoded->Payment->ReturnCode) {
            // Error GF detected, notify site admin
            $error_message = "Return Code: " . $responseDecoded->Payment->ReturnCode . '. Return Message: ' . $responseDecoded->Payment->ReturnMessage . '.' . __('Please contact Cielo for further assistance.', 'lkn-wc-gateway-cielo');
            //wp_mail(get_option('admin_email'), 'Erro na transação Cielo', $error_message);

            // Registrar a mensagem de erro em um arquivo de log
            $this->log->log('error', $error_message, array('source' => 'woocommerce-cielo-credit'));

            throw new Exception(esc_attr($error_message));
        }
        if ('yes' === $this->get_option('debug')) {
            $this->log->log('error', var_export($response, true), array('source' => 'woocommerce-cielo-google-pay'));
        }

        throw new Exception(esc_attr(__('Order payment failed, please try again.', 'lkn-wc-gateway-cielo')));
    }

    /**
     * Proccess refund request in order.
     *
     * @param int    $order_id
     * @param float  $amount
     * @param string $reason
     *
     * @return bool
     */
    public function process_refund($order_id, $amount = null, $reason = '')
    {
        // Verify user has permission to process refunds
        if (!current_user_can('manage_woocommerce')) {
            if ('yes' === $this->get_option('debug')) {
                $this->log->log('error', 'Refund attempt without proper permissions for order: ' . $order_id, array('source' => 'woocommerce-cielo-google-pay-security'));
            }
            return new WP_Error('permission_denied', __('You do not have permission to process refunds.', 'lkn-wc-gateway-cielo'));
        }

        // Do your refund here. Refund $amount for the order with ID $order_id
        $url = ($this->get_option('env') == 'production') ? 'https://api.cieloecommerce.cielo.com.br/' : 'https://apisandbox.cieloecommerce.cielo.com.br/';
        $merchantId = sanitize_text_field($this->get_option('merchant_id'));
        $merchantSecret = sanitize_text_field($this->get_option('merchant_key'));
        $debug = $this->get_option('debug');
        $order = wc_get_order($order_id);
        $transactionId = $order->get_transaction_id();

        // Allow filtering of refund parameters, but verify permission after filter
        $response = apply_filters('lkn_wc_cielo_google_pay_refund', $url, $merchantId, $merchantSecret, $order_id, $amount);

        // If filter was hooked and returned a value, verify user still has permission
        if (has_filter('lkn_wc_cielo_google_pay_refund')) {
            if (!current_user_can('manage_woocommerce')) {
                if ('yes' === $debug) {
                    $this->log->log('error', 'Refund filter used without proper permissions for order: ' . $order_id, array('source' => 'woocommerce-cielo-google-pay-security'));
                }
                $order->add_order_note(__('Order refund blocked: insufficient permissions', 'lkn-wc-gateway-cielo'));
                return false;
            }
            
            // Log filter usage for audit trail
            if ('yes' === $debug) {
                $this->log->log('info', 'Refund filter was used for order: ' . $order_id, array('source' => 'woocommerce-cielo-google-pay-security'));
            }
        }

        if (is_wp_error($response)) {
            if ('yes' === $debug) {
                $this->log->log('error', var_export($response->get_error_messages(), true), array('source' => 'woocommerce-cielo-google-pay'));
            }

            $order->add_order_note('[' . $this->id . '] ' . __('Order refund failed, payment id:', 'lkn-wc-gateway-cielo') . ' ' . $transactionId);

            return false;
        }
        $responseDecoded = json_decode($response['body']);

        if (isset($responseDecoded->Status) && (10 == $responseDecoded->Status || 11 == $responseDecoded->Status || 2 == $responseDecoded->Status || 1 == $responseDecoded->Status)) {
            $order->add_order_note('[' . $this->id . '] ' . __('Order refunded, payment id:', 'lkn-wc-gateway-cielo') . ' ' . $transactionId);

            return true;
        }
        if ('yes' === $debug) {
            $this->log->log('error', var_export($response, true), array('source' => 'woocommerce-cielo-google-pay'));
        }

        $order->add_order_note('[' . $this->id . '] ' . __('Order refund failed, payment id:', 'lkn-wc-gateway-cielo') . ' ' . $transactionId);

        return false;
    }


    /**
     * Verify if WooCommerce notice exists before adding.
     *
     * @param string $message
     * @param string $type
     */
    public function add_notice_once($message, $type): void
    {
        if (! wc_has_notice($message, $type)) {
            wc_add_notice($message, $type);
        }
    }

    public function add_gateway_name_to_notes($note_data, $args)
    {
        // Verificar se é uma nota de mudança de status e se o pedido usa este gateway
        if (isset($args['order_id'])) {
            $order = wc_get_order($args['order_id']);

            if ($order && $order->get_payment_method() === $this->id) {
                // PRIMEIRO: Verificar se o texto contém [$this->id] - só processa se existir
                $pattern = '/\[' . preg_quote($this->id, '/') . '\]\s*/';
                if (preg_match($pattern, $note_data['comment_content'])) {
                    // Remover o padrão [$this->id] e espaço após ele
                    $note_data['comment_content'] = preg_replace($pattern, '', $note_data['comment_content']);
                    
                    // Verificar se o prefixo já existe para evitar duplicação
                    if (strpos($note_data['comment_content'], $this->method_title . ' — ') === false) {
                        // Adicionar prefixo com nome do gateway
                        $note_data['comment_content'] = $this->method_title . ' — ' . $note_data['comment_content'];
                    }
                }
            }
        }
        return $note_data;
    }
}
