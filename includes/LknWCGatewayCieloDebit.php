<?php

namespace Lkn\WCCieloPaymentGateway\Includes;

use DateTime;
use Exception;
use Lkn\WCCieloPaymentGateway\Includes\LknWcCieloHelper;
use WC_Logger;
use WC_Payment_Gateway;

/**
 * Lkn_WC_Gateway_Cielo_Debit class.
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
 * Cielo API 3.0 Debit Gateway.
 *
 * @class    Lkn_WC_Gateway_Cielo_Debit
 *
 * @version  1.0.0
 */
final class LknWCGatewayCieloDebit extends WC_Payment_Gateway
{
    /**
     * Define instructions to configure and use this plugin.
     *
     * @since   1.3.2
     *
     * @var string
     */
    public $instructions = '';

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     *
     * @var string the current version of this plugin
     */
    private $version = LKN_WC_CIELO_VERSION;

    /**
     * Log instance to store debug messages and codes.
     *
     * @since   1.3.2
     *
     * @var WC_Logger
     */
    private $log;
    private $accessToken;

    /**
     * Constructor for the gateway.
     */
    public function __construct()
    {
        $this->id = 'lkn_cielo_debit';
        $this->icon = apply_filters('lkn_wc_cielo_gateway_icon', '');
        $this->has_fields = true;
        $this->supports = array(
            'products',
        );

        $this->supports = apply_filters('lkn_wc_cielo_debit_add_support', $this->supports);

        $this->method_title = __('Cielo - Debit and credit card', 'lkn-wc-gateway-cielo');

        $this->method_description = __('Allows debit and credit card payment with Cielo API 3.0.', 'lkn-wc-gateway-cielo');

        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();

        $this->icon = LknWcCieloHelper::getIconUrl();
        // Define user set variables.
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->instructions = $this->get_option('instructions', $this->description);
        $this->log = new WC_Logger();
        $gateway_enabled = get_option('woocommerce_' . $this->id . '_settings');

        // Actions.
        add_filter('woocommerce_new_order_note_data', array($this, 'add_gateway_name_to_notes'), 10, 2);
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_scheduled_subscription_payment_' . $this->id, array($this, 'process_subscription_payment'), 10, 3);

        // Action hook to load admin JavaScript
        if (function_exists('get_plugins')) {
            add_action('admin_enqueue_scripts', array($this, 'admin_load_script'));
        }
    }

    /**
     * Hide gateway for free orders (cart total = 0).
     * Prevents 3DS authentication from being triggered unnecessarily.
     *
     * @return bool
     */
    public function is_available()
    {
        if (! parent::is_available()) {
            return false;
        }

        if (\WC()->cart && \WC()->cart->total <= 0) {
            
            if (class_exists('\WC_Subscriptions_Cart') && \WC_Subscriptions_Cart::cart_contains_subscription()) { 
                return true; // Permite checkout de assinatura com trial gratuito 
            } 
            return false;
        }
        return true;
    }

    /**
     * Process subscription payment.
     *
     * @param  float     $amount
     * @param  WC_Order  $order
     * @param  bool      $isRetry
     * @return void
     */
    public function process_subscription_payment($amount, $order, $isRetry = false): void
    {
        do_action('lkn_wc_cielo_debit_scheduled_subscription_payment', $amount, $order, $isRetry);
    }

    /**
     * Admin options - usa o sistema universal
     */
    public function admin_options()
    {
        if (defined('LKN_WC_CIELO_VERSION') && class_exists('Lkn\WCCieloPaymentGateway\Includes\LknWcCieloUniversalTemplateManager')) {
            LknWcCieloUniversalTemplateManager::render_admin_page($this);
        } else {
            // Fallback para método padrão
            parent::admin_options();
        }
    }

    public function process_admin_options()
    {
        if (isset($_POST['woocommerce_lkn_cielo_debit_fake_layout-control'])) {
            $_POST['woocommerce_lkn_cielo_debit_fake_layout-control'] = '0';
        }

        parent::process_admin_options();
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
            wp_enqueue_script('lknWCGatewayCieloDebitSettingsLayoutScript', plugin_dir_url(__FILE__) . '../resources/js/admin/lkn-wc-gateway-admin-layout.js', array('jquery'), $this->version, false);
            $gateway_settings = $this->settings;
            wp_localize_script('lknWCGatewayCieloDebitSettingsLayoutScript', 'lknWcCieloTranslationsInput', array(
                'modern' => __('Modern version', 'lkn-wc-gateway-cielo'),
                'standard' => __('Standard version', 'lkn-wc-gateway-cielo'),
                'enable' => __('Enable', 'lkn-wc-gateway-cielo'),
                'disable' => __('Disable', 'lkn-wc-gateway-cielo'),
                'mordernVersion' => plugin_dir_url(__FILE__) . '../resources/img/modern-version.png',
                'standardVersion' => plugin_dir_url(__FILE__) . '../resources/img/standard-version.png',
                'analytics_url' => admin_url('admin.php?page=wc-admin&path=%2Fanalytics%2Fcielo-transactions'),
                'gateway_settings' => $gateway_settings,
                'whatsapp_number' => LKN_WC_CIELO_WPP_NUMBER,
                'site_domain' => home_url(),
                'gateway_id' => $this->id,
                'version_free' => LKN_WC_CIELO_VERSION,
                'version_pro' => (is_plugin_active('lkn-cielo-api-pro/lkn-cielo-api-pro.php') && defined('LKN_CIELO_API_PRO_VERSION')) ? LKN_CIELO_API_PRO_VERSION : 'N/A'
            ));
            wp_enqueue_style('lkn-admin-layout', plugin_dir_url(__FILE__) . '../resources/css/frontend/lkn-admin-layout.css', array(), $this->version, 'all');
            wp_enqueue_script('lknWCGatewayCieloDebitClearButtonScript', plugin_dir_url(__FILE__) . '../resources/js/admin/lkn-clear-logs-button.js', array('jquery'), $this->version, false);
            wp_localize_script('lknWCGatewayCieloDebitClearButtonScript', 'lknWcCieloTranslations', array(
                'clearLogs' => __('Limpar Logs', 'lkn-wc-gateway-cielo'),
                'sendConfigs' => __('Wordpress Support', 'lkn-wc-gateway-cielo'),
                'alertText' => __('Deseja realmente deletar todos logs dos pedidos?', 'lkn-wc-gateway-cielo'),
                'production' => __('Use this in the live store to charge real payments.', 'lkn-wc-gateway-cielo'),
                'sandbox' => __('Use this for testing purposes in the Cielo sandbox environment.', 'lkn-wc-gateway-cielo'),
                'enable' => __('Enable', 'lkn-wc-gateway-cielo'),
                'disable' => __('Disable', 'lkn-wc-gateway-cielo'),
                'nonce' => wp_create_nonce('lkn_cielo_clear_logs_nonce'),
                'ajaxUrl' => admin_url('admin-ajax.php')
            ));
        }
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
                'label' => __('Enable Debit and Credit Card Payments', 'lkn-wc-gateway-cielo'),
                'default' => 'no',
                'description' => __('Enable this option to allow debit and credit card payments via Cielo.', 'lkn-wc-gateway-cielo'),
                'desc_tip' => __('Select to enable debit and credit card payment options for your store.', 'lkn-wc-gateway-cielo'),
                'custom_attributes' => array(
                    'data-title-description' => __('Enable this option to allow debit and credit card payments via Cielo.', 'lkn-wc-gateway-cielo'),
                ),
            ),
            'title' => array(
                'title' => __('Title', 'lkn-wc-gateway-cielo'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'lkn-wc-gateway-cielo'),
                'default' => __('Debit and credit card', 'lkn-wc-gateway-cielo'),
                'desc_tip' => __('The name displayed to customers on the checkout page.', 'lkn-wc-gateway-cielo'),
                'custom_attributes' => array(
                    'required' => 'required',
                    'data-title-description' => __('Displayed name for this payment method at checkout.', 'lkn-wc-gateway-cielo'),
                ),
            ),
            'description' => array(
                'title' => __('Description', 'lkn-wc-gateway-cielo'),
                'type' => 'textarea',
                'default' => __('Payment processed by Cielo API 3.0', 'lkn-wc-gateway-cielo'),
                'description' => __('Payment method description that the customer will see on your checkout.', 'lkn-wc-gateway-cielo'),
                'desc_tip' => __('This description appears below the payment method name during checkout.', 'lkn-wc-gateway-cielo'),
                'custom_attributes' => array(
                    'required' => 'required',
                    'data-title-description' => __('Additional info shown next to the payment method.', 'lkn-wc-gateway-cielo'),
                ),
            ),
            'client_id' => array(
                'title' => __('Client Id', 'lkn-wc-gateway-cielo'),
                'type' => 'password',
                'description' => __('Cielo 3DS 2.2 registration required (ask for eCommerce support).', 'lkn-wc-gateway-cielo'),
                'desc_tip' => __('Enter your Cielo 3DS 2.2 client identifier, required for authentication.', 'lkn-wc-gateway-cielo'),
                'custom_attributes' => array(
                    'required' => 'required',
                    'data-title-description' => __('Cielo 3DS 2.2 client identifier (used in authentication).', 'lkn-wc-gateway-cielo'),
                )
            ),
            'client_secret' => array(
                'title' => __('Client Secret', 'lkn-wc-gateway-cielo'),
                'type' => 'password',
                'description' => __('Cielo 3DS 2.2 registration required (ask for eCommerce support).', 'lkn-wc-gateway-cielo'),
                'desc_tip' => __('Enter your Cielo 3DS 2.2 secret key, required for authentication.', 'lkn-wc-gateway-cielo'),
                'custom_attributes' => array(
                    'required' => 'required',
                    'data-title-description' => __('Cielo 3DS 2.2 secret key (used in authentication).', 'lkn-wc-gateway-cielo'),
                )
            ),
            'merchant_id' => array(
                'title' => __('Merchant Id', 'lkn-wc-gateway-cielo'),
                'type' => 'password',
                'description' => __('Cielo credentials.', 'lkn-wc-gateway-cielo'),
                'desc_tip' => __('Your Merchant Id used for Cielo API 3.0 authentication.', 'lkn-wc-gateway-cielo'),
                'custom_attributes' => array(
                    'required' => 'required',
                    'data-title-description' => __('Merchant Id credential for API authentication.', 'lkn-wc-gateway-cielo'),
                )
            ),
            'merchant_key' => array(
                'title' => __('Merchant Key', 'lkn-wc-gateway-cielo'),
                'type' => 'password',
                'description' => __('Cielo credentials.', 'lkn-wc-gateway-cielo'),
                'desc_tip' => __('Your Merchant Key used for Cielo API 3.0 authentication.', 'lkn-wc-gateway-cielo'),
                'custom_attributes' => array(
                    'required' => 'required',
                    'data-title-description' => __('Merchant Key credential for API authentication.', 'lkn-wc-gateway-cielo'),
                )
            ),
            'env' => array(
                'title'       => __('Environment', 'lkn-wc-gateway-cielo'),
                'type'        => 'select',
                'options'     => array(
                    'production' => __('Production', 'lkn-wc-gateway-cielo'),
                    'sandbox'    => __('Development', 'lkn-wc-gateway-cielo'),
                ),
                'default'     => 'production',
                'desc_tip'    => __('Choose between Production or Sandbox environment for the Cielo API.', 'lkn-wc-gateway-cielo'),
                'custom_attributes' => array(
                    'data-title-description' => __('Choose between Production or Sandbox environment for the Cielo API.', 'lkn-wc-gateway-cielo'),
                ),
            ),
            'establishment_code' => array(
                'title' => __('Establishment Code', 'lkn-wc-gateway-cielo'),
                'type' => 'text',
                'description' => __('Establishment code for Cielo 3DS E-Commerce 3.0.', 'lkn-wc-gateway-cielo'),
                'desc_tip' => __('Code identifying your establishment for Cielo 3DS E-Commerce 3.0.', 'lkn-wc-gateway-cielo'),
                'custom_attributes' => array(
                    'required' => 'required',
                    'data-title-description' => __('Establishment code used in Cielo 3DS E-Commerce 3.0 configuration.', 'lkn-wc-gateway-cielo'),
                )
            ),
            'merchant_name' => array(
                'title' => __('Merchant Name', 'lkn-wc-gateway-cielo'),
                'type' => 'text',
                'description' => __('Establishment name registered on Cielo 3DS E-Commerce 3.0.', 'lkn-wc-gateway-cielo'),
                'desc_tip' => __('Name of your establishment registered in Cielo 3DS E-Commerce 3.0.', 'lkn-wc-gateway-cielo'),
                'custom_attributes' => array(
                    'required' => 'required',
                    'data-title-description' => __('Merchant name registered for 3DS E-Commerce 3.0.', 'lkn-wc-gateway-cielo'),
                )
            ),
            'mcc' => array(
                'title' => __('Establishment Category Code', 'lkn-wc-gateway-cielo'),
                'type' => 'text',
                'description' => __('Establishment category code for Cielo 3DS E-Commerce 3.0.', 'lkn-wc-gateway-cielo'),
                'desc_tip' => __('Category code (MCC) of your establishment for Cielo 3DS E-Commerce 3.0.', 'lkn-wc-gateway-cielo'),
                'custom_attributes' => array(
                    'required' => 'required',
                    'data-title-description' => __('Merchant Category Code (MCC) for 3DS E-Commerce 3.0.', 'lkn-wc-gateway-cielo'),
                )
            ),
            'invoiceDesc' => array(
                'title' => __('Invoice Description', 'lkn-wc-gateway-cielo'),
                'type' => 'text',
                'default' => __('order', 'lkn-wc-gateway-cielo'),
                'description' => __('Invoice description that the customer will see on your checkout (special characters are not accepted).', 'lkn-wc-gateway-cielo'),
                'desc_tip' => __('Description that appears on the customer invoice, only letters and spaces allowed.', 'lkn-wc-gateway-cielo'),
                'custom_attributes' => array(
                    'maxlength' => 50,
                    'pattern' => '[a-zA-Z]+( [a-zA-Z]+)*',
                    'required' => 'required',
                    'data-title-description' => __('Text shown on invoice, must not contain special characters or numbers.', 'lkn-wc-gateway-cielo'),
                )
            ),
            'card' => array(
                'title' => esc_attr__('Card', 'lkn-wc-gateway-cielo'),
                'type'  => 'title',
            ),
            'installment_payment' => array(
                'title'       => __('Installment payments', 'lkn-wc-gateway-cielo'),
                'type'        => 'checkbox',
                'label'       => __('Enables installment payments for amounts greater than 10,00 R$', 'lkn-wc-gateway-cielo'),
                'default'     => 'no',
                'description' => __('When enabled and using the PRO version of the plugin, an additional tab will appear for advanced installment configuration.', 'lkn-wc-gateway-cielo'),
                'desc_tip'    => __('Enable this to allow installment payments.', 'lkn-wc-gateway-cielo'),
                'custom_attributes' => array(
                    'data-title-description' => __('Allows customers to pay in installments for amounts over R$10.00.', 'lkn-wc-gateway-cielo'),
                ),
            ),
            'placeholder' => array(
                'title'       => __('Input placeholders', 'lkn-wc-gateway-cielo'),
                'type'        => 'checkbox',
                'label'       => __('Enables input placeholders for debit/credit card fields for classic checkout', 'lkn-wc-gateway-cielo'),
                'default'     => 'no',
                'description' => __('Show placeholder texts inside the card input fields at checkout.', 'lkn-wc-gateway-cielo'),
                'desc_tip'    => __('Enable to improve user experience with input hints.', 'lkn-wc-gateway-cielo'),
                'custom_attributes' => array(
                    'data-title-description' => __('Show placeholder texts inside the card input fields at checkout.', 'lkn-wc-gateway-cielo'),
                ),
            ),
            'nonce_compatibility' => array(
                'title'       => __('Nonce verification compatibility mode', 'lkn-wc-gateway-cielo'),
                'description' => __('Enable this only if your checkout page is facing nonce verification issues.', 'lkn-wc-gateway-cielo'),
                'label'       => __('Enable for checkout page validation compatibility', 'lkn-wc-gateway-cielo'),
                'type'        => 'checkbox',
                'default'     => 'no',
                'desc_tip'    => __('Activate this to fix nonce validation problems on the checkout page.', 'lkn-wc-gateway-cielo'),
                'custom_attributes' => array(
                    'data-title-description' => __('Activate this to fix nonce validation problems on the checkout page.', 'lkn-wc-gateway-cielo'),
                ),
            ),
            'allow_card_ineligible' => array(
                'title'       => __('Cartão inelegível para autenticação', 'lkn-wc-gateway-cielo'),
                'type'        => 'checkbox',
                'label'       => __('Permitir pagamentos sem verficação 3DS', 'lkn-wc-gateway-cielo'),
                'description' => __('Para cartões inelegíveis, permite seguir com a transação sem autenticação, menos seguro mas com maior conversão.', 'lkn-wc-gateway-cielo'),
                'desc_tip'    => __('Ative para permitir transações com cartões que não suportam 3DS.', 'lkn-wc-gateway-cielo'),
                'default'     => 'no',
                'custom_attributes' => array(
                    'data-title-description' => __('Para cartões inelegíveis, permite seguir com a transação sem autenticação, menos seguro mas com maior conversão.', 'lkn-wc-gateway-cielo'),
                ),
            ),
            'show_card_animation' => array(
                'title'       => __('Exibir cartão animado', 'lkn-wc-gateway-cielo'),
                'type'        => 'checkbox',
                'label'       => __('Exibir cartão animado durante o checkout', 'lkn-wc-gateway-cielo'),
                'description' => __('Mostra um cartão com animações visuais durante o checkout de pagamento do pedido.', 'lkn-wc-gateway-cielo'),
                'desc_tip'    => __('Ative para melhorar a experiência visual no checkout.', 'lkn-wc-gateway-cielo'),
                'default'     => 'yes',
                'custom_attributes' => array(
                    'data-title-description' => __('Mostra um cartão com animações visuais durante o checkout de pagamento do pedido.', 'lkn-wc-gateway-cielo'),
                ),
            ),
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

        if (
            ! file_exists(WP_PLUGIN_DIR . '/lkn-cielo-api-pro/lkn-cielo-api-pro.php') ||
            ! (function_exists('is_plugin_active') && is_plugin_active('lkn-cielo-api-pro/lkn-cielo-api-pro.php'))
        ) {
            $this->form_fields['pro'] = array(
                'title' => esc_attr__('PRO', 'lkn-wc-gateway-cielo'),
                'type'  => 'title',
            );

            $this->form_fields['fake_license_field'] = array(
                'title'       => __('License', 'lkn-wc-gateway-cielo'),
                'type'        => 'text',
                'description' => __('Enter your license key here. This field is disabled for editing.', 'lkn-wc-gateway-cielo'),
                'desc_tip'    => __('Enter your Link nacional license key to activate PRO features.', 'lkn-wc-gateway-cielo'),
                'id'          => 'fake_license_field',
                'custom_attributes' => array(
                    'readonly' => 'readonly',
                    'data-title-description' => __('This field displays your current license key. Editing is disabled.', 'lkn-wc-gateway-cielo'),
                ),
            );

            $this->form_fields['fake_cardholder_field'] = array(
                'title'       => __('Cardholder Name', 'lkn-wc-gateway-cielo'),
                'type'        => 'text',
                'description' => __('Enter the cardholder name. This field is not editable.', 'lkn-wc-gateway-cielo'),
                'desc_tip'    => __('This cardholder name field is read-only for security reasons.', 'lkn-wc-gateway-cielo'),
                'id'          => 'fake_cardholder_field',
                'custom_attributes' => array(
                    'readonly' => 'readonly',
                    'data-title-description' => __('This field displays the cardholder name but is disabled for editing.', 'lkn-wc-gateway-cielo'),
                ),
            );

            $this->form_fields['fake_layout'] = array(
                'title'       => __('Layout', 'lkn-wc-gateway-cielo'),
                'type'        => 'checkbox',
                'description' => __('Choose the layout style for the checkout page.', 'lkn-wc-gateway-cielo'),
                'desc_tip'    => __('Select between Modern Version and Standard Version for the checkout layout.', 'lkn-wc-gateway-cielo'),
                'options'     => array(
                    'yes'  => __('Modern Version', 'lkn-wc-gateway-cielo'),
                    'no' => __('Standard Version', 'lkn-wc-gateway-cielo'),
                ),
                'default'     => 'no',
                'custom_attributes' => array(
                    'readonly' => 'readonly',
                    'data-title-description' => __('Choose the layout style for the checkout page.', 'lkn-wc-gateway-cielo'),
                ),
            );

            $this->form_fields['save_card_token'] = array(
                'title'       => __('Save Card Option', 'lkn-wc-gateway-cielo-pro'),
                'type'        => 'select',
                'description' => __('Allows you to configure if the customer card will be saved automatically, optionally, or disabled.', 'lkn-wc-gateway-cielo-pro'),
                'desc_tip'    => __('Choose if the customer can opt to save the card, if it will be required, or if the feature will be disabled.', 'lkn-wc-gateway-cielo-pro'),
                'options'     => array(
                    'optional'    => __('Optional (customer can choose to save the card at checkout)', 'lkn-wc-gateway-cielo-pro'),
                    'required'    => __('Required (always save the card automatically, no option for the customer)', 'lkn-wc-gateway-cielo-pro'),
                    'disabled'    => __('Disabled (do not save the card, default)', 'lkn-wc-gateway-cielo-pro'),
                ),
                'default'     => 'disabled',
                'custom_attributes' => array(
                    'data-title-description' => __('Allows you to define if the card will be saved automatically, optionally, or disabled.', 'lkn-wc-gateway-cielo-pro'),
                    'readonly' => 'readonly',
                ),
            );

            $this->form_fields['fake_and_more_field'] = array(
                'title'       => __('And much more...', 'lkn-wc-gateway-cielo'),
                'type'        => 'text',
                'description' => __('Discover all PRO features by activating your license.', 'lkn-wc-gateway-cielo'),
                'desc_tip'    => __('Unlock advanced features and enhancements with the PRO version.', 'lkn-wc-gateway-cielo'),
                'id'          => 'fake_and_more_field',
                'custom_attributes' => array(
                    'readonly' => 'readonly',
                    'data-title-description' => __('This is just a sample field to highlight more PRO features.', 'lkn-wc-gateway-cielo'),
                ),
            );

            
        }

        $customConfigs = apply_filters('lkn_wc_cielo_get_custom_configs', array(), $this->id);

        if (! empty($customConfigs)) {
            $this->form_fields = array_merge($this->form_fields, $customConfigs);
        }
    }

    /**
     * Generate Cielo API 3.0 in auth token.
     */
    public function generate_debit_auth_token()
    {
        try {
            $env = $this->get_option('env');
            $clientId = $this->get_option('client_id');
            $clientSecret = $this->get_option('client_secret');
            $url = ('sandbox' === $env) ? 'https://mpisandbox.braspag.com.br/v2/auth/token/' : 'https://mpi.braspag.com.br/v2/auth/token/';

            $establishmentCode = $this->get_option('establishment_code');
            $merchantName = $this->get_option('merchant_name');
            $mcc = $this->get_option('mcc');
            $debug = $this->get_option('debug');

            $authCode = base64_encode($clientId . ':' . $clientSecret);

            $args['headers'] = array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ' . $authCode,
            );

            $args['body'] = wp_json_encode(array(
                'EstablishmentCode' => $establishmentCode,
                'MerchantName' => $merchantName,
                'MCC' => $mcc,
            ));
            $args['timeout'] = 120;

            $response = wp_remote_post($url, $args);

            if (is_wp_error($response)) {
                if ('yes' === $debug) {
                    $this->log->log('error', var_export($response->get_error_messages(), true), array('source' => 'woocommerce-cielo-debit'));
                }

                $message = __('Auth token generation failed.', 'lkn-wc-gateway-cielo');

                throw new Exception($message);
            }
            $responseDecoded = json_decode($response['body']);

            if (isset($responseDecoded->access_token)) {
                return array(
                    'access_token' => $responseDecoded->access_token,
                    'expires_in' => $responseDecoded->expires_in,
                );
            }
        } catch (Exception $e) {
            $this->add_error($e->getMessage());
            $debug = $this->get_option('debug');

            if ('yes' === $debug) {
                $this->log->log('error', var_export($e->getMessage(), true), array('source' => 'woocommerce-cielo-debit'));
            }
            return false;
        }
    }

    /**
     * Calculate the total value of items in the WooCommerce cart.
     */
    public static function lknGetCartTotal()
    {
        $cart = WC()->cart;

        if (empty($cart)) {
            return 0;
        }

        $cart_items = $cart->get_cart();
        $total = 0;
        foreach ($cart_items as $cart_item_key => $cart_item) {
            $product = $cart_item['data'];
            $total += $product->get_price() * $cart_item['quantity'];
        }
        return number_format($total, 2, '', '');

        return 0;
    }

    /**
     * Get cart subtotal plus shipping total.
     */
    private function get_subtotal_plus_shipping()
    {
        // Se estiver no pay_for_order, pegar do pedido específico
        if (isset($_GET['pay_for_order'])) {
            $key = isset($_GET['key']) ? sanitize_text_field(wp_unslash($_GET['key'])) : '';
            $order_id = wc_get_order_id_by_order_key($key);
            $order = wc_get_order($order_id);
            
            if ($order) {
                return $order->get_subtotal() + $order->get_shipping_total();
            }
        }
        
        // Para checkout normal, usar o carrinho
        if (WC()->cart) {
            return WC()->cart->get_subtotal() + WC()->cart->get_shipping_total();
        }
        
        return 0;
    }

    /**
     * Get fees total (excluding plugin-generated fees like card interest/discount).
     */
    private function get_fees_total()
    {
        // Se estiver no pay_for_order, pegar do pedido específico
        if (isset($_GET['pay_for_order'])) {
            $key = isset($_GET['key']) ? sanitize_text_field(wp_unslash($_GET['key'])) : '';
            $order_id = wc_get_order_id_by_order_key($key);
            $order = wc_get_order($order_id);
            
            if ($order) {
                // Para pedidos, filtrar fees excluindo as do plugin
                $fees = $order->get_fees();
                $external_fees_total = 0;
                
                foreach ($fees as $fee) {
                    $fee_name = $fee->get_name();
                    // Excluir fees criadas pelo plugin Cielo
                    if ($fee_name !== __('Card Interest', 'lkn-wc-gateway-cielo') && 
                        $fee_name !== __('Card Discount', 'lkn-wc-gateway-cielo')) {
                        $external_fees_total += $fee->get_total();
                    }
                }
                
                return $external_fees_total;
            }
        }
        
        // Para checkout normal, usar o carrinho
        if (WC()->cart) {
            $fees = WC()->cart->get_fees();
            $external_fees_total = 0;
            
            foreach ($fees as $fee) {
                // Excluir fees criadas pelo plugin Cielo
                if ($fee->name !== __('Card Interest', 'lkn-wc-gateway-cielo') && 
                    $fee->name !== __('Card Discount', 'lkn-wc-gateway-cielo')) {
                    $external_fees_total += $fee->amount;
                }
            }
            
            return $external_fees_total;
        }
        
        return 0;
    }

    /**
     * Get taxes total.
     */
    private function get_taxes_total()
    {
        // Se estiver no pay_for_order, pegar do pedido específico
        if (isset($_GET['pay_for_order'])) {
            $key = isset($_GET['key']) ? sanitize_text_field(wp_unslash($_GET['key'])) : '';
            $order_id = wc_get_order_id_by_order_key($key);
            $order = wc_get_order($order_id);
            
            if ($order) {
                return $order->get_total_tax();
            }
        }
        
        // Para checkout normal, usar o carrinho
        if (WC()->cart) {
            return WC()->cart->get_total_tax();
        }
        
        return 0;
    }

    /**
     * Get discounts total.
     */
    private function get_discounts_total()
    {
        // Se estiver no pay_for_order, pegar do pedido específico
        if (isset($_GET['pay_for_order'])) {
            $key = isset($_GET['key']) ? sanitize_text_field(wp_unslash($_GET['key'])) : '';
            $order_id = wc_get_order_id_by_order_key($key);
            $order = wc_get_order($order_id);
            
            if ($order) {
                return $order->get_total_discount();
            }
        }
        
        // Para checkout normal, usar o carrinho
        if (WC()->cart) {
            return WC()->cart->get_discount_total();
        }
        
        return 0;
    }

    /**
     * Render the payment fields.
     */
    public function payment_fields(): void
    {
        // Generate access token and enqueue fix script for shortcode checkout
        $this->accessToken = $this->generate_debit_auth_token();
        wp_enqueue_script('lkn-fix-script', plugin_dir_url(__FILE__) . '../resources/js/frontend/lkn-dc-script-fix.js', array('wp-i18n', 'jquery'), $this->version, false);
        $accessToken = isset($this->accessToken['access_token']) ? $this->accessToken['access_token'] : '';
        wp_localize_script('lkn-fix-script', 'lknWcCieloPaymentGatewayToken', array('access_token' => $accessToken));
        
        // Enqueue base styles
        wp_enqueue_style('lknWCGatewayCieloFixIconsStyle', plugin_dir_url(__FILE__) . '../resources/css/frontend/lkn-fix-icons-styles.css', array(), $this->version, 'all');
        wp_enqueue_style('lkn-dc-style', plugin_dir_url(__FILE__) . '../resources/css/frontend/lkn-dc-style.css', array(), $this->version, 'all');
        wp_enqueue_style('lkn-mask', plugin_dir_url(__FILE__) . '../resources/css/frontend/lkn-mask.css', array(), $this->version, 'all');
        
        // Setup environment and scripts
        $env = $this->get_option('env');
        $installmentArgs = apply_filters('lkn_wc_cielo_js_3ds_args', array('installment_min' => '5'));

        if (WC()->session) {
            WC()->session->set('lkn_cielo_debit_card_type', 'Credit');
        }

        // Recuperar parcela atual da sessão
        $current_installment = WC()->session ? WC()->session->get('lkn_cielo_debit_installment', '1') : '1';

        // Enqueue environment-specific scripts
        if ('production' === $env) {
            wp_enqueue_script('lkn-dc-script', plugin_dir_url(__FILE__) . '../resources/js/frontend/lkn-dc-script-prd.js', array('wp-i18n', 'jquery'), $this->version, false);
            wp_set_script_translations('lkn-dc-script', 'lkn-wc-gateway-cielo', LKN_WC_CIELO_TRANSLATION_PATH);
        } else {
            wp_enqueue_script('lkn-dc-script', plugin_dir_url(__FILE__) . '../resources/js/frontend/lkn-dc-script-sdb.js', array('wp-i18n', 'jquery'), $this->version, false);
            wp_set_script_translations('lkn-dc-script', 'lkn-wc-gateway-cielo', LKN_WC_CIELO_TRANSLATION_PATH);
        }
        
        // Setup 3DS and other scripts
        wp_localize_script('lkn-dc-script', 'lknDCDirScript3DSCieloShortCode', array('url' => LKN_WC_GATEWAY_CIELO_URL . 'resources/js/debitCard/BP.Mpi.3ds20.min.js'));
        wp_localize_script('lkn-dc-script', 'lknDCScriptAllowCardIneligible', array('allow' => $this->get_option('allow_card_ineligible', 'no')));
        wp_localize_script('lkn-dc-script', 'lknCieloRestSettings', array(
            'rest_url'  => esc_url_raw(rest_url()),
            'nonce' => wp_create_nonce('wp_rest'),
        ));
        
        // Enqueue mask and token scripts
        wp_enqueue_script('lkn-mask-script', plugin_dir_url(__FILE__) . '../resources/js/frontend/formatter.js', array('jquery'), $this->version, false);
        wp_enqueue_script('lkn-mask-script-load', plugin_dir_url(__FILE__) . '../resources/js/frontend/define-mask.js', array('lkn-mask-script', 'jquery'), $this->version, false);
        wp_enqueue_script('lkn-fix-token-script', plugin_dir_url(__FILE__) . '../resources/js/frontend/lkn-fix-token-script.js', array('jquery'), $this->version, false);
        wp_localize_script('lkn-fix-token-script', 'lknCieloRestSettings', array(
            'rest_url'  => esc_url_raw(rest_url()),
            'nonce' => wp_create_nonce('wp_rest'),
        ));

        // Setup installment script
        wp_enqueue_script('lkn-cc-dc-installment-script', plugin_dir_url(__FILE__) . '../resources/js/frontend/lkn-cc-dc-installment.js', array('jquery'), $this->version, false);
        wp_localize_script('lkn-cc-dc-installment-script', 'lknWCCielo3ds', $installmentArgs);
        wp_localize_script('lkn-cc-dc-installment-script', 'lknWCCielo3dsConfig', array(
            'interest_or_discount' => $this->get_option('interest_or_discount'),
            'installment_discount' => $this->get_option('installment_discount')
        ));
        wp_localize_script('lkn-cc-dc-installment-script', 'lknWCCielo3dsAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('lkn_payment_fees_nonce'),
            'current_installment' => $current_installment,
            'current_card_type' => WC()->session ? WC()->session->get('lkn_cielo_debit_card_type', 'Credit') : 'Credit'
        ));
        
        // Check checkout layout option
        $checkout_layout = $this->get_option('checkout_layout', 'no');
        $show_card_brand_icons = $this->get_option('show_card_brand_icons', 'yes');
        $use_modern_layout = ('yes' === $checkout_layout);

        // Enqueue modern layout assets if enabled
        if ($use_modern_layout) {
            // Check if modern layout CSS is already enqueued to avoid duplicates
            if (!wp_style_is('lkn-cielo-modern-layout', 'enqueued') && !wp_style_is('lkn-cielo-modern-layout', 'done')) {
                wp_enqueue_style('lkn-cielo-modern-layout', plugin_dir_url(__FILE__) . '../resources/css/frontend/lkn-cielo-modern-layout.css', array(), $this->version, 'all');
            }
            
            // Always enqueue debit brand detector script
            if (!wp_script_is('lkn-cielo-debit-brand-detector', 'enqueued') && !wp_script_is('lkn-cielo-debit-brand-detector', 'done')) {
                wp_enqueue_script('lkn-cielo-debit-brand-detector', plugin_dir_url(__FILE__) . '../resources/js/debitCard/lkn-cielo-brand-detector.js', array('jquery'), $this->version, true);
                
                // Always send variable to JavaScript (JS decides what to do with icons)
                wp_localize_script('lkn-cielo-debit-brand-detector', 'lknCieloDebitBrandConfig', array(
                    'show_card_brand_icons' => $show_card_brand_icons
                ));
            }
        }
        
        // Enqueue card animation scripts only if enabled
        if ('yes' === $this->get_option('show_card_animation')) {
            // Enqueue jquery.card.js only if not already enqueued
            if (!wp_script_is('lkn-cielo-jquery-card', 'enqueued')) {
                wp_enqueue_script('lkn-cielo-jquery-card', plugin_dir_url(__FILE__) . '../resources/js/frontend/jquery.card.js', array('jquery'), $this->version, true);
            }
            // Enqueue card.css only if not already enqueued
            if (!wp_style_is('lkn-cielo-card-css', 'enqueued')) {
                wp_enqueue_style('lkn-cielo-card-css', plugin_dir_url(__FILE__) . '../resources/css/frontend/card.css', array(), $this->version, 'all');
            }
            // Enqueue cielo card script only if not already enqueued
            if (!wp_script_is('lkn-cielo-card-script', 'enqueued')) {
                wp_enqueue_script('lkn-cielo-card-script', plugin_dir_url(__FILE__) . '../resources/js/frontend/lkn-cielo-shortcode-card.js', array('jquery'), $this->version, true);
                wp_localize_script('lkn-cielo-card-script', 'lknCieloCardConfig', array(
                    'show_cardholder_name' => $this->get_option('show_cardholder_name', 'no')
                ));
            }
        }
        $activeInstallment = $this->get_option('installment_payment');
        $total_cart = number_format($this->get_subtotal_plus_shipping(), 2, '.', '');
        // Para 3DS 2.2, o valor deve estar em centavos (sem vírgula decimal)
        $total_cart_3ds = number_format($this->get_subtotal_plus_shipping(), 2, '', '');
        $fees_total = number_format($this->get_fees_total(), 2, '.', '');
        $taxes_total = number_format($this->get_taxes_total(), 2, '.', '');
        $discounts_total = number_format($this->get_discounts_total(), 2, '.', '');
        $accessToken = isset($this->accessToken) ? $this->accessToken : array('access_token' => '', 'expires_in' => 0);
        $url = get_page_link();
        $nonce = wp_create_nonce('nonce_lkn_cielo_debit');
        $placeholder = $this->get_option('placeholder', 'no');
        $placeholderEnabled = false;
        $noLoginCheckout = isset($_GET['pay_for_order']) ? sanitize_text_field(wp_unslash($_GET['pay_for_order'])) : 'false';
        $installmentLimit = $this->get_option('installment_limit', 12);
        $installments = array();
        $installmentMin = preg_replace('/,/', '.', $this->get_option('installment_min', '5,00'));

        $installmentLimit = apply_filters('lkn_wc_cielo_set_installment_limit', $installmentLimit, $this);

        if ('yes' === $placeholder) {
            $placeholderEnabled = true;
        }

        for ($c = 1; $c <= $installmentLimit; ++$c) {
            // Usar a lógica correta baseada na configuração interest_or_discount
            switch ($this->get_option('interest_or_discount')) {
                case 'discount':
                    if ($this->get_option('installment_discount') == 'yes') {
                        $discount = $this->get_option($c . 'x_discount', 0);
                        if ($discount > 0) {
                            $installments[] = array('id' => $c, 'discount' => $discount);
                        }
                    }
                    break;
                    
                case 'interest':
                    if ($this->get_option('installment_interest') == 'yes') {
                        $interest = $this->get_option($c . 'x', 0);
                        if ($interest > 0) {
                            $installments[] = array('id' => $c, 'interest' => $interest);
                        }
                    }
                    break;
                    
                default:
                    // Fallback para compatibilidade com configurações antigas
                    $interest = $this->get_option($c . 'x', 0);
                    if ($interest > 0) {
                        $installments[] = array('id' => $c, 'interest' => $interest);
                    }
                    break;
            }
        }

        if ('yes' === $activeInstallment) {
            if (isset($_GET['pay_for_order'])) {
                $key = isset($_GET['key']) ? sanitize_text_field(wp_unslash($_GET['key'])) : '';
                $order_id = wc_get_order_id_by_order_key($key);
                $order = wc_get_order($order_id);
                $total_cart = number_format($order->get_total(), 2, '.', '');
                // Para 3DS 2.2, o valor deve estar em centavos (sem vírgula decimal)
                $total_cart_3ds = number_format($order->get_total(), 2, '', '');
            }
        }

        $user = wp_get_current_user();

        $billingDocument = get_user_meta($user->ID, 'billing_cpf', true);

        if (empty($billingDocument) || false === $billingDocument) {
            $billingDocument = get_user_meta($user->ID, 'billing_cnpj', true);

            if (empty($billingDocument) || false === $billingDocument) {
                $billingDocument = '';
            }
        }

        $bec = $this->get_option('establishment_code');
        $client_ip = LknWcCieloHelper::getClientIp();
        $user_guest = ! is_user_logged_in();
        $authentication_method = is_user_logged_in() ? '02' : '01';

        $name = $user->display_name;
        $email = $user->user_email;
        // Fallback: billing → shipping → custom → vazio, para garantir dados ELO obrigatórios
        $billing_phone = get_user_meta($user->ID, 'billing_phone', true);
        if (empty($billing_phone)) {
            $billing_phone = get_user_meta($user->ID, 'shipping_phone', true);
        }
        if (empty($billing_phone)) {
            $billing_phone = get_user_meta($user->ID, 'phone', true);
        }
        $billing_address_1 = get_user_meta($user->ID, 'billing_address_1', true);
        if (empty($billing_address_1)) {
            $billing_address_1 = get_user_meta($user->ID, 'shipping_address_1', true);
        }
        $billing_address_2 = get_user_meta($user->ID, 'billing_address_2', true);
        if (empty($billing_address_2)) {
            $billing_address_2 = get_user_meta($user->ID, 'shipping_address_2', true);
        }
        $billing_city = get_user_meta($user->ID, 'billing_city', true);
        if (empty($billing_city)) {
            $billing_city = get_user_meta($user->ID, 'shipping_city', true);
        }
        $billing_state = get_user_meta($user->ID, 'billing_state', true);
        if (empty($billing_state)) {
            $billing_state = get_user_meta($user->ID, 'shipping_state', true);
        }
        $billing_postcode = get_user_meta($user->ID, 'billing_postcode', true);
        if (empty($billing_postcode)) {
            $billing_postcode = get_user_meta($user->ID, 'shipping_postcode', true);
        }
        $billing_country = get_user_meta($user->ID, 'billing_country', true);
        if (empty($billing_country)) {
            $billing_country = get_user_meta($user->ID, 'shipping_country', true);
        }
        $billing_document = $billingDocument;

        ?>

        <?php
        // Prepare template variables for both layouts
        $gateway_id = $this->id;
        $description = $this->description;
        $show_card_animation = $this->get_option('show_card_animation');
        $active_installment = $activeInstallment;
        $access_token = $accessToken;
        $placeholder_enabled = $placeholderEnabled;
        $no_login_checkout = $noLoginCheckout;
        $installment_limit = $installmentLimit;
        $installment_min = $installmentMin;
        $fees_total = $fees_total;
        $taxes_total = $taxes_total;
        $discounts_total = $discounts_total;
        $total_cart = $total_cart;
        $total_cart_3ds = $total_cart_3ds;
        $installments = $installments;
        $nonce = $nonce;
        $url = $url;
        $bec = $bec;
        $user_guest = $user_guest;
        $authentication_method = $authentication_method;
        $client_ip = $client_ip;
        $billing_address_1 = $billing_address_1;
        $billing_address_2 = $billing_address_2;
        $billing_city = $billing_city;
        $billing_state = $billing_state;
        $billing_postcode = $billing_postcode;
        $billing_country = $billing_country;
        $billing_document = $billingDocument;
        $name = $name;
        $email = $email;
        $billing_phone = $billing_phone;

        // --- Saved Cards (PRO feature) ---
        // card_array is only created by the PRO plugin; if it exists, show the saved cards list.
        $save_card_token = $this->get_option('save_card_token', 'disabled');
        $user_id = get_current_user_id();
        $cards_array = array();
        $default_card = '';

        if ($user_id) {
            $raw_cards = get_user_meta($user_id, 'card_array', true);
            $default_card = get_user_meta($user_id, 'default_card', true);

            if (is_array($raw_cards) && ! empty($raw_cards)) {
                foreach ($raw_cards as $key => $card) {
                    if (! is_array($card)) continue;
                    // Remove expired cards
                    if (isset($card['expirationDate'])) {
                        $exp = $card['expirationDate'];
                        if (preg_match('/^(0[1-9]|1[0-2])\/(\d{4})$/', $exp, $matches)) {
                            $expMonth = (int) $matches[1];
                            $expYear = (int) $matches[2];
                            $now = new \DateTime();
                            $expDate = \DateTime::createFromFormat('Y-m', $expYear . '-' . str_pad($expMonth, 2, '0', STR_PAD_LEFT));
                            $expDate->modify('last day of this month');
                            if ($now > $expDate) {
                                continue;
                            }
                        }
                    }
                    // Remove cardToken from frontend data
                    unset($card['cardToken']);
                    $cards_array[] = $card;
                }
            }
        }

        $show_saved_cards = ! empty($cards_array);

        // Card brand icon URLs
        $card_brand_icons = array(
            'visa'       => plugin_dir_url(__FILE__) . '../resources/img/visa-icon.svg',
            'mastercard' => plugin_dir_url(__FILE__) . '../resources/img/mastercard-icon.svg',
            'amex'       => plugin_dir_url(__FILE__) . '../resources/img/amex-icon.svg',
            'elo'        => plugin_dir_url(__FILE__) . '../resources/img/elo-icon.svg',
            'other_card' => plugin_dir_url(__FILE__) . '../resources/img/other-card.svg',
        );

        // Enqueue saved cards JS for shortcode
        if ($show_saved_cards) {
            wp_enqueue_script(
                'lkn-cielo-saved-cards-shortcode',
                plugin_dir_url(__FILE__) . '../resources/js/frontend/lkn-cielo-saved-cards-shortcode.js',
                array('jquery'),
                $this->version,
                true
            );
            wp_localize_script('lkn-cielo-saved-cards-shortcode', 'lknCieloSavedCardsShortcode', array(
                'cards' => $cards_array,
                'default_card' => $default_card,
                'card_brand_icons' => $card_brand_icons,
                'gateway_id' => $this->id,
                'save_card_token' => $save_card_token,
            ));
        }
        
        // Check checkout layout and load appropriate template
        if ($use_modern_layout) {
            // Include modern layout template
            include plugin_dir_path(__FILE__) . 'templates/lkn-cielo-debit-payment-fields-modern-layout.php';
        } else {
            // Include default layout template
            include plugin_dir_path(__FILE__) . 'templates/lkn-cielo-debit-payment-fields-default-layout.php';
        }
        // End of layout conditional
        ?>

<?php

        do_action('lkn_wc_cielo_remove_cardholder_name_3ds', $this);
    }

    /**
     * Fields validation.
     *
     * @return bool
     */
    public function validate_fields()
    {
        $nonceInactive = $this->get_option('nonce_compatibility', 'no');
        $validateCompatMode = $this->get_option('input_validation_compatibility', 'no');
        $nonce = isset($_POST['nonce_lkn_cielo_debit']) ? sanitize_text_field(wp_unslash($_POST['nonce_lkn_cielo_debit'])) : '';
        $saveCardIndex = isset($_POST['lkn_selected_saved_card_index']) ? sanitize_text_field(wp_unslash($_POST['lkn_selected_saved_card_index'])) : '';

        if (! wp_verify_nonce($nonce, 'nonce_lkn_cielo_debit') && 'no' === $nonceInactive) {
            $this->log->log('error', 'Nonce verification failed. Nonce: ' . var_export($nonce, true), array('source' => 'woocommerce-cielo-debit'));
            $this->add_notice_once(__('Nonce verification failed, try reloading the page', 'lkn-wc-gateway-cielo'), 'error');
            return false;
        }
        if ('no' === $validateCompatMode && $saveCardIndex == '') {
            $dcnum = isset($_POST['lkn_dcno']) ? sanitize_text_field(wp_unslash($_POST['lkn_dcno'])) : '';
            $expDate = isset($_POST['lkn_dc_expdate']) ? sanitize_text_field(wp_unslash($_POST['lkn_dc_expdate'])) : '';
            $cvv = isset($_POST['lkn_dc_cvc']) ? sanitize_text_field(wp_unslash($_POST['lkn_dc_cvc'])) : '';

            $validdcNumber = $this->validate_card_number($dcnum, true);
            $validExpDate = $this->validate_exp_date($expDate, true);
            $validCvv = $this->validate_cvv($cvv, true);

            if (true === $validdcNumber && true === $validExpDate && true === $validCvv) {
                return true;
            }

            return false;
        }

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
        $saveCardIndex = isset($_POST['lkn_selected_saved_card_index']) ? sanitize_text_field(wp_unslash($_POST['lkn_selected_saved_card_index'])) : '';
        $order = wc_get_order($order_id);

        if($saveCardIndex == ''){
            $nonceInactive = $this->get_option('nonce_compatibility', 'no');
            $nonce = isset($_POST['nonce_lkn_cielo_debit']) ? sanitize_text_field(wp_unslash($_POST['nonce_lkn_cielo_debit'])) : '';

            if (! wp_verify_nonce($nonce, 'nonce_lkn_cielo_debit') && 'no' === $nonceInactive) {
                $this->log->log('error', 'Nonce verification failed. Nonce: ' . var_export($nonce, true), array('source' => 'woocommerce-cielo-debit'));
                $this->add_notice_once(__('Nonce verification failed, try reloading the page', 'lkn-wc-gateway-cielo'), 'error');
                throw new Exception(esc_attr(__('Nonce verification failed, try reloading the page', 'lkn-wc-gateway-cielo')));
            }


            // Card parameters
            $cardNum = preg_replace('/\s/', '', isset($_POST['lkn_dcno']) ? sanitize_text_field(wp_unslash($_POST['lkn_dcno'])) : '');
            $cardExpSplit = explode('/', preg_replace('/\s/', '', isset($_POST['lkn_dc_expdate']) ? sanitize_text_field(wp_unslash($_POST['lkn_dc_expdate'])) : ''));
            if (count($cardExpSplit) === 2 && strlen($cardExpSplit[0]) === 2 && strlen($cardExpSplit[1]) === 2) {
                $cardExp = $cardExpSplit[0] . '/20' . $cardExpSplit[1];
                $cardExpShort = $cardExpSplit[0] . '/' . $cardExpSplit[1];
            } else {
                $cardExp = '';
                $cardExpShort = '';
            }
            $cardCvv = isset($_POST['lkn_dc_cvc']) ? sanitize_text_field(wp_unslash($_POST['lkn_dc_cvc'])) : '';
            $cardName = isset($_POST['lkn_dc_cardholder_name']) ? sanitize_text_field(wp_unslash($_POST['lkn_dc_cardholder_name'])) : '';
            $cardName = apply_filters('lkn_wc_cielo_get_cardholder_name', $cardName, $this, $order);

            // Fallback: se o nome do titular do cartão estiver vazio, usa o nome de cobrança do pedido
            if (empty(trim($cardName))) {
                $firstName = $order->get_billing_first_name();
                $lastName = $order->get_billing_last_name();
                $cardName = trim($firstName . ' ' . $lastName);
            }

            $cardType = isset($_POST['lkn_cc_type']) ? sanitize_text_field(wp_unslash($_POST['lkn_cc_type'])) : '';
            $installments = (int) (isset($_POST['lkn_cc_dc_installments']) ? sanitize_text_field(wp_unslash($_POST['lkn_cc_dc_installments'])) : 1);
            $saveCard = isset($_POST['lkn_save_debit_credit_card']) && ($_POST['lkn_save_debit_credit_card'] === '1' || $this->get_option('save_card_token') == 'required' ) ? true : false;

            // Assinaturas (WooCommerce Subscriptions): apenas Cartão de Crédito é permitido.
            // Cartão de Débito exige autenticação do portador a cada cobrança — inviável para renovações automáticas.
            if (function_exists('wcs_order_contains_subscription') && wcs_order_contains_subscription($order_id)) {
                if ($cardType === 'Debit') {
                    throw new Exception(esc_attr(__('Debit cards are not accepted for subscription payments. Please use a credit card.', 'lkn-wc-gateway-cielo')));
                }
                $saveCard = true;
                $order = apply_filters('lkn_wc_cielo_debit_process_recurring_payment', $order);
            }
                        
            // Authentication parameters
            $xid = isset($_POST['lkn_cielo_3ds_xid']) ? sanitize_text_field(wp_unslash($_POST['lkn_cielo_3ds_xid'])) : '';
            $cavv = isset($_POST['lkn_cielo_3ds_cavv']) ? sanitize_text_field(wp_unslash($_POST['lkn_cielo_3ds_cavv'])) : '';
            $eci = isset($_POST['lkn_cielo_3ds_eci']) ? sanitize_text_field(wp_unslash($_POST['lkn_cielo_3ds_eci'])) : '';
            $version = isset($_POST['lkn_cielo_3ds_version']) ? sanitize_text_field(wp_unslash($_POST['lkn_cielo_3ds_version'])) : '';
            $refId = isset($_POST['lkn_cielo_3ds_ref_id']) ? sanitize_text_field(wp_unslash($_POST['lkn_cielo_3ds_ref_id'])) : '';

            // Normalize JS setAttribute pass-through values ('null', 'undefined', 'true') to empty strings
            foreach (array('xid', 'cavv', 'eci', 'version', 'refId') as $field) {
                if (in_array($$field, array('null', 'undefined', 'true'), true)) {
                    $$field = '';
                }
            }

            // POST parameters
            $url = ($this->get_option('env') == 'production') ? 'https://api.cieloecommerce.cielo.com.br/' : 'https://apisandbox.cieloecommerce.cielo.com.br/';
            $merchantId = sanitize_text_field($this->get_option('merchant_id'));
            $merchantSecret = sanitize_text_field($this->get_option('merchant_key'));
            $merchantOrderId = $order_id . '-' . time();
            $amount = $order->get_total();
            $capture = ($this->get_option('capture', 'yes') == 'yes') ? true : false;
            $description = sanitize_text_field($this->get_option('invoiceDesc'));
            $description = preg_replace('/[^a-zA-Z\s]+/', '', $description);
            $description = preg_replace('/\s+/', ' ', $description);
            
            // Salvar metadado indicando se foi captura automática ou manual
            // Para cartão de débito, a captura é SEMPRE automática na Cielo, independente da configuração
            if ($cardType === 'Debit') {
                $order->update_meta_data('_lkn_cielo_capture_type', 'automatic');
            } else {
                $order->update_meta_data('_lkn_cielo_capture_type', $capture ? 'automatic' : 'manual');
            }
            $provider = LknWcCieloHelper::getCardProvider($cardNum, $this->id);
            $debug = $this->get_option('debug');
            $currency = $order->get_currency();
            $activeInstallment = $this->get_option('installment_payment');

            if (empty($provider)) {
                $message = __("Bin query is not enabled for this merchant.", 'lkn-wc-gateway-cielo');

                if ('yes' === $debug) {
                    $this->log->log('error', var_export($message, true), array('source' => 'woocommerce-cielo-debit'));
                }

                // Salvar metadados da transação com dados customizados para erro de validação
                $customErrorResponse = LknWcCieloHelper::createCustomErrorResponse(
                    400,
                    '57',
                    'Expired card'
                );
                LknWcCieloHelper::saveTransactionMetadata($order, $customErrorResponse, $cardNum, $cardExpShort, $cardName, $installments, $amount, $currency, $provider, $merchantId, $merchantSecret, $merchantOrderId, $order_id, $capture, null, $cardType, 'lkn_dc_cvc', $this, $xid, $cavv, $eci, $version, $refId);
                $order->save();

                throw new Exception(esc_attr($message));
            }

            if ($this->validate_card_holder_name($cardName, false) === false) {
                $message = __('Card Holder Name is required!', 'lkn-wc-gateway-cielo');

                // Salvar metadados da transação com dados customizados para erro de validação
                $customErrorResponse = LknWcCieloHelper::createCustomErrorResponse(
                    400,
                    '126',
                    'Credit Card Holder is required'
                );
                LknWcCieloHelper::saveTransactionMetadata($order, $customErrorResponse, $cardNum, $cardExpShort, $cardName, $installments, $amount, $currency, $provider, $merchantId, $merchantSecret, $merchantOrderId, $order_id, $capture, null, $cardType, 'lkn_dc_cvc', $this, $xid, $cavv, $eci, $version, $refId);
                $order->save();

                throw new Exception(esc_attr($message));
            }
            // Check if card starts with 0 (specific validation with metadata saving)
            $cleanCardNum = preg_replace('/\s/', '', $cardNum);
            if (isset($cleanCardNum[0]) && $cleanCardNum[0] === '0') {
                $message = __('Cards starting with 0 are not accepted', 'lkn-wc-gateway-cielo');

                // Salvar metadados da transação com dados customizados para erro de validação
                $customErrorResponse = LknWcCieloHelper::createCustomErrorResponse(
                    400,
                    '126',
                    'Card number starting with 0 is not valid'
                );
                LknWcCieloHelper::saveTransactionMetadata($order, $customErrorResponse, $cardNum, $cardExpShort, $cardName, $installments, $amount, $currency, $provider, $merchantId, $merchantSecret, $merchantOrderId, $order_id, $capture, null, $cardType, 'lkn_dc_cvc', $this, $xid, $cavv, $eci, $version, $refId);
                $order->save();

                throw new Exception(esc_attr($message));
            }
            if ($this->validate_card_number($cardNum, false) === false) {
                $message = __('Debit Card number is invalid!', 'lkn-wc-gateway-cielo');

                // Salvar metadados da transação com dados customizados para erro de validação
                $customErrorResponse = LknWcCieloHelper::createCustomErrorResponse(
                    400,
                    '126',
                    'Credit Card number is required'
                );
                LknWcCieloHelper::saveTransactionMetadata($order, $customErrorResponse, $cardNum, $cardExpShort, $cardName, $installments, $amount, $currency, $provider, $merchantId, $merchantSecret, $merchantOrderId, $order_id, $capture, null, $cardType, 'lkn_dc_cvc', $this, $xid, $cavv, $eci, $version, $refId);
                $order->save();

                throw new Exception(esc_attr($message));
            }
            if ($this->validate_exp_date($cardExpShort, false) === false) {
                $message = __('Expiration date is invalid!', 'lkn-wc-gateway-cielo');

                // Salvar metadados da transação com dados customizados para erro de validação
                $customErrorResponse = LknWcCieloHelper::createCustomErrorResponse(
                    400,
                    '126',
                    'Credit Card Expiration Date is required'
                );
                LknWcCieloHelper::saveTransactionMetadata($order, $customErrorResponse, $cardNum, $cardExpShort, $cardName, $installments, $amount, $currency, $provider, $merchantId, $merchantSecret, $merchantOrderId, $order_id, $capture, null, $cardType, 'lkn_dc_cvc', $this, $xid, $cavv, $eci, $version, $refId);
                $order->save();

                throw new Exception(esc_attr($message));
            }
            if ($this->validate_cvv($cardCvv, false) === false) {
                $message = __('CVV is invalid!', 'lkn-wc-gateway-cielo');

                // Salvar metadados da transação com dados customizados para erro de validação
                $customErrorResponse = LknWcCieloHelper::createCustomErrorResponse(
                    400,
                    '146',
                    'SecurityCode length exceeded'
                );
                LknWcCieloHelper::saveTransactionMetadata($order, $customErrorResponse, $cardNum, $cardExpShort, $cardName, $installments, $amount, $currency, $provider, $merchantId, $merchantSecret, $merchantOrderId, $order_id, $capture, null, $cardType, 'lkn_dc_cvc', $this, $xid, $cavv, $eci, $version, $refId);
                $order->save();

                throw new Exception(esc_attr($message));
            }
            if (empty($merchantId)) {
                $message = __('Invalid Cielo API 3.0 credentials.', 'lkn-wc-gateway-cielo');

                // Salvar metadados da transação com dados customizados para erro de validação
                $customErrorResponse = LknWcCieloHelper::createCustomErrorResponse(
                    401,
                    '126',
                    'MerchantId is required'
                );
                LknWcCieloHelper::saveTransactionMetadata($order, $customErrorResponse, $cardNum, $cardExpShort, $cardName, $installments, $amount, $currency, $provider, $merchantId, $merchantSecret, $merchantOrderId, $order_id, $capture, null, $cardType, 'lkn_dc_cvc', $this, $xid, $cavv, $eci, $version, $refId);
                $order->save();

                throw new Exception(esc_attr($message));
            }
            if (empty($merchantSecret)) {
                $message = __('Invalid Cielo API 3.0 credentials.', 'lkn-wc-gateway-cielo');

                // Salvar metadados da transação com dados customizados para erro de validação
                $customErrorResponse = LknWcCieloHelper::createCustomErrorResponse(
                    401,
                    '126',
                    'MerchantKey is required'
                );
                LknWcCieloHelper::saveTransactionMetadata($order, $customErrorResponse, $cardNum, $cardExpShort, $cardName, $installments, $amount, $currency, $provider, $merchantId, $merchantSecret, $merchantOrderId, $order_id, $capture, null, $cardType, 'lkn_dc_cvc', $this, $xid, $cavv, $eci, $version, $refId);
                $order->save();

                throw new Exception(esc_attr($message));
            }
            // Exigir 3DS para todos os tipos de cartão
            if (empty($eci) && $this->get_option('allow_card_ineligible', 'no') == 'no') {
                $message = __('Invalid Cielo 3DS 2.2 authentication.', 'lkn-wc-gateway-cielo');

                // Salvar metadados da transação com dados customizados para erro de a  utenticação 3DS
                $customErrorResponse = LknWcCieloHelper::createCustomErrorResponse(
                    401,
                    'BP171',
                    'Rejected due to fraud risk (Velocity)'
                );
                LknWcCieloHelper::saveTransactionMetadata($order, $customErrorResponse, $cardNum, $cardExpShort, $cardName, $installments, $amount, $currency, $provider, $merchantId, $merchantSecret, $merchantOrderId, $order_id, $capture, null, $cardType, 'lkn_dc_cvc', $this, $xid, $cavv, $eci, $version, $refId);
                $order->save();

                throw new Exception(esc_attr($message));
            }

            if ('BRL' !== $currency) {
                $amount = apply_filters('lkn_wc_cielo_convert_amount', $amount, $currency, $this);

                $order->add_meta_data('amount_converted', $amount, true);
            }

            // If installments option is active verify $_POST attribute
            if ('yes' === $activeInstallment && 'Credit' == $cardType) {
                $installments = (int) isset($_POST['lkn_cc_dc_installments']) ? sanitize_text_field(wp_unslash($_POST['lkn_cc_dc_installments'])) : 1;

                if ($installments > 12) {
                    if (
                        'Elo' !== $provider
                        && 'Visa' !== $provider
                        && 'Master' !== $provider
                        && 'Amex' !== $provider
                        && 'Hipercard' !== $provider
                    ) {
                        $message = __('Order payment failed. Installment quantity invalid.', 'lkn-wc-gateway-cielo');

                        // Salvar metadados da transação com dados customizados para erro de validação
                        $customErrorResponse = LknWcCieloHelper::createCustomErrorResponse(
                            400,
                            'BP335',
                            'Cancelled due to transactional error in Payment Split'
                        );
                        LknWcCieloHelper::saveTransactionMetadata($order, $customErrorResponse, $cardNum, $cardExpShort, $cardName, $installments, $amount, $currency, $provider, $merchantId, $merchantSecret, $merchantOrderId, $order_id, $capture, null, $cardType, 'lkn_dc_cvc', $this, $xid, $cavv, $eci, $version, $refId);
                        $order->save();

                        throw new Exception(esc_attr($message));
                    }
                }

                $order->add_order_note('[' . $this->id . '] ' . __('Installments quantity', 'lkn-wc-gateway-cielo') . ' ' . $installments);
                $order->add_meta_data('installments', $installments, true);
            }

            $amountFormated = number_format($amount, 2, '', '');

            // Para cartão de débito, a captura é SEMPRE automática na Cielo, independente da configuração
            $actualCapture = ($cardType === 'Debit') ? true : $capture;

            // Cartão de débito - verificar se permite cartão inelegível ou se tem validação 3DS
            // Bypass 3DS when allowed AND: no 3DS was attempted, OR auth failed (cavv empty, not data-only ECI 04)
            $bypass3ds = $this->get_option('allow_card_ineligible', 'no') == 'yes' && 
                (empty($refId) || 'null' == $refId || (empty($cavv) && 4 != $eci));
            
            if ($bypass3ds) {
                $args['headers'] = array(
                    'Content-Type' => 'application/json',
                    'MerchantId' => $merchantId,
                    'MerchantKey' => $merchantSecret,
                    'RequestId' => uniqid(),
                );
                
                $body = array(
                    'MerchantOrderId' => $merchantOrderId,
                    'Customer' => array(
                        'Name' => $cardName,
                    ),
                    'Payment' => array(
                        'Type' => $cardType . "Card",
                        'Amount' => (int) $amountFormated,
                        'Installments' => $installments,
                        'Capture' => (bool) $actualCapture,
                        'SoftDescriptor' => $description,
                        $cardType . "Card" => array(
                            'CardNumber' => $cardNum,
                            'Holder' => $cardName,
                            'ExpirationDate' => $cardExp,
                            'SecurityCode' => $cardCvv,
                            'SaveCard' => $saveCard,
                            'CardOnFile' => array(
                                'Usage' => 'First'
                            ),
                            'Brand' => $provider,
                        ),
                    ),
                );

                $body = apply_filters('lkn_wc_cielo_process_body', $body, $_POST, $order_id);
                $args['body'] = wp_json_encode($body);
                $args['timeout'] = 120;

                $response = wp_remote_post($url . '1/sales', $args);

                // Salvar metadados da transação
                $responseDecoded = !is_wp_error($response) ? json_decode($response['body']) : null;
                LknWcCieloHelper::saveTransactionMetadata($order, $responseDecoded, $cardNum, $cardExpShort, $cardName, $installments, $amount, $currency, $provider, $merchantId, $merchantSecret, $merchantOrderId, $order_id, $actualCapture, $response, $cardType, 'lkn_dc_cvc', $this, $xid, $cavv, $eci, $version, $refId);
                
                // Salvar o pedido para garantir que os metadados sejam persistidos
                $order->save();

                $order->add_order_note('[' . $this->id . '] ' . __('Debit card payment processed without 3DS validation', 'lkn-wc-gateway-cielo'));
            } else {
                $order->add_order_note('[' . $this->id . '] ' . __('Debit card payment processed with 3DS validation', 'lkn-wc-gateway-cielo'));
                    
                // Verify if authentication is data-only
                // @see {https://developercielo.github.io/manual/3ds}
                if (4 == $eci && empty($cavv)) {
                    $args['headers'] = array(
                        'Content-Type' => 'application/json',
                        'MerchantId' => $merchantId,
                        'MerchantKey' => $merchantSecret,
                        'RequestId' => uniqid(),
                    );

                    $body = array(
                        'MerchantOrderId' => $merchantOrderId,
                        'Customer' => array(
                            'Name' => $cardName,
                        ),
                        'Payment' => array(
                            'Type' => $cardType . "Card",
                            'Amount' => (int) $amountFormated,
                            'Installments' => $installments,
                            'Authenticate' => true,
                            'Capture' => (bool) $actualCapture,
                            'SoftDescriptor' => $description,
                            $cardType . "Card" => array(
                                'CardNumber' => $cardNum,
                                'Holder' => $cardName,
                                'ExpirationDate' => $cardExp,
                                'SecurityCode' => $cardCvv,
                                'SaveCard' => $saveCard,
                                'Brand' => $provider,
                            ),
                            'ExternalAuthentication' => array(
                                'Eci' => $eci,
                                'ReferenceId' => $refId,
                                'dataonly' => false,
                            ),
                        ),
                    );

                    $body = apply_filters('lkn_wc_cielo_process_body', $body, $_POST, $order_id);
                    $args['body'] = wp_json_encode($body);
                    $args['timeout'] = 120;

                    $response = wp_remote_post($url . '1/sales', $args);

                    // Salvar metadados da transação
                    $responseDecoded = !is_wp_error($response) ? json_decode($response['body']) : null;
                    LknWcCieloHelper::saveTransactionMetadata($order, $responseDecoded, $cardNum, $cardExpShort, $cardName, $installments, $amount, $currency, $provider, $merchantId, $merchantSecret, $merchantOrderId, $order_id, $actualCapture, $response, $cardType, 'lkn_dc_cvc', $this, $xid, $cavv, $eci, $version, $refId);
                    
                    // Salvar o pedido para garantir que os metadados sejam persistidos
                    $order->save();
                } else {
                    if (empty($cavv) && $this->get_option('allow_card_ineligible', 'no') == 'no') {
                        $message = __('Invalid Cielo 3DS 2.2 authentication.', 'lkn-wc-gateway-cielo');

                        // Salvar metadados da transação com dados customizados para erro de autenticação 3DS
                        $customErrorResponse = LknWcCieloHelper::createCustomErrorResponse(
                            401,
                            'BP172',
                            'Transaction aborted during card validation'
                        );
                        LknWcCieloHelper::saveTransactionMetadata($order, $customErrorResponse, $cardNum, $cardExpShort, $cardName, $installments, $amount, $currency, $provider, $merchantId, $merchantSecret, $merchantOrderId, $order_id, $capture, null, $cardType, 'lkn_dc_cvc', $this, $xid, $cavv, $eci, $version, $refId);
                        $order->save();

                        throw new Exception(esc_attr($message));
                    }
                    if (empty($xid) && $this->get_option('allow_card_ineligible', 'no') == 'no') {
                        $message = __('Invalid Cielo 3DS 2.2 authentication.', 'lkn-wc-gateway-cielo');

                        // Salvar metadados da transação com dados customizados para erro de autenticação 3DS
                        $customErrorResponse = LknWcCieloHelper::createCustomErrorResponse(
                            401,
                            'BP900',
                            'Operation failure'
                        );
                        LknWcCieloHelper::saveTransactionMetadata($order, $customErrorResponse, $cardNum, $cardExpShort, $cardName, $installments, $amount, $currency, $provider, $merchantId, $merchantSecret, $merchantOrderId, $order_id, $capture, null, $cardType, 'lkn_dc_cvc', $this, $xid, $cavv, $eci, $version, $refId);
                        $order->save();

                        throw new Exception(esc_attr($message));
                    }

                    $args['headers'] = array(
                        'Content-Type' => 'application/json',
                        'MerchantId' => $merchantId,
                        'MerchantKey' => $merchantSecret,
                        'RequestId' => uniqid(),
                    );

                    $body = array(
                        'MerchantOrderId' => $merchantOrderId,
                        'Customer' => array(
                            'Name' => $cardName,
                        ),
                        'Payment' => array(
                            'Type' => $cardType . "Card",
                            'Amount' => (int) $amountFormated,
                            'Installments' => $installments,
                            'Authenticate' => true,
                            'Capture' => (bool) $actualCapture,
                            'SoftDescriptor' => $description,
                            $cardType . "Card" => array(
                                'CardNumber' => $cardNum,
                                'Holder' => $cardName,
                                'ExpirationDate' => $cardExp,
                                'SecurityCode' => $cardCvv,
                                'SaveCard' => $saveCard,
                                'Brand' => $provider,
                            ),
                            'ExternalAuthentication' => array(
                                'Cavv' => $cavv,
                                'Xid' => $xid,
                                'Eci' => $eci,
                                'Version' => $version,
                                'ReferenceId' => $refId,
                            ),
                        ),
                    );

                    $body = apply_filters('lkn_wc_cielo_process_body', $body, $_POST, $order_id);
                    $args['body'] = wp_json_encode($body);
                    $args['timeout'] = 120;

                    $response = wp_remote_post($url . '1/sales', $args);

                    // Salvar metadados da transação
                    $responseDecoded = !is_wp_error($response) ? json_decode($response['body']) : null;
                    LknWcCieloHelper::saveTransactionMetadata($order, $responseDecoded, $cardNum, $cardExpShort, $cardName, $installments, $amount, $currency, $provider, $merchantId, $merchantSecret, $merchantOrderId, $order_id, $actualCapture, $response, $cardType, 'lkn_dc_cvc', $this, $xid, $cavv, $eci, $version, $refId);
                    
                    // Salvar o pedido para garantir que os metadados sejam persistidos
                    $order->save();
                }
            }

            // Salvar metadados do cartão/pagamento no pedido (independente de saveCard)
            $lastFourDigits = substr($cardNum, -4);
            $bin = substr($cardNum, 0, 6);
            $order->update_meta_data('_lkn_used_card_brand', $provider);
            $order->update_meta_data('_lkn_used_card_last4', $lastFourDigits);
            $order->update_meta_data('_lkn_card_bin', $bin);
            $order->update_meta_data('_lkn_card_expiration', $cardExp);
            $order->update_meta_data('_lkn_card_holder', $cardName);
            $order->update_meta_data('_lkn_installments', $installments);
            if (isset($responseDecoded->Payment->ProofOfSale)) {
                $order->update_meta_data('_lkn_nsu', $responseDecoded->Payment->ProofOfSale);
            }
            if (isset($responseDecoded->Payment->Tid)) {
                $order->update_meta_data('_lkn_tid', $responseDecoded->Payment->Tid);
            }
            if (isset($responseDecoded->Payment->ReturnCode)) {
                $order->update_meta_data('_lkn_return_code', $responseDecoded->Payment->ReturnCode);
            }
            if (isset($responseDecoded->Payment->ReturnMessage)) {
                $order->update_meta_data('_lkn_return_message', $responseDecoded->Payment->ReturnMessage);
            }
            if (isset($responseDecoded->Payment->ReceivedDate)) {
                $order->update_meta_data('_lkn_received_date', $responseDecoded->Payment->ReceivedDate);
            }
            if (isset($responseDecoded->Payment->Status)) {
                $order->update_meta_data('_lkn_payment_status', $responseDecoded->Payment->Status);
            }
            $order->update_meta_data('_lkn_card_type', $cardType);

            // Gerenciar salvamento de cartão (se aplicável)
            if ($saveCard &&  isset($responseDecoded->Payment->CreditCard->CardToken)) {
                $user_id = $order->get_user_id();
                if (! isset($responseDecoded->Payment->CreditCard->CardToken)) {
                    $order->add_order_note('[' . $this->id . '] O token para cobranças automáticas não foi gerado, então as cobranças automáticas não poderão ser efetuadas.');
                }

                // Dados do cartão de pagamento
                $cardPayment = array(
                    'cardToken' => $responseDecoded->Payment->CreditCard->CardToken,
                    'brand' => $provider,
                );

                $lastFourDigits = $responseDecoded->Payment->CreditCard->CardNumber;

                if (0 != $user_id) {
                    $cardsArray = get_user_meta($user_id, 'card_array', true);
                    $cardsArray = is_array($cardsArray) ? $cardsArray : array();
                    $lastFourDigits = $responseDecoded->Payment->CreditCard->CardNumber;
                    $expirationDate = $responseDecoded->Payment->CreditCard->ExpirationDate;
                    // Adiciona o novo cartão à lista
                    $cardsArray[] = array(
                        'cardToken' => $cardPayment['cardToken'],
                        'brand' => $provider,
                        'cardDigits' => $lastFourDigits,
                        'expirationDate' => $expirationDate,
                        'securityCode' => $cardCvv,
                    );

                    // Atualiza os metadados do usuário
                    update_user_meta($user_id, 'card_array', $cardsArray);
                    update_user_meta($user_id, 'default_card', array_key_last($cardsArray));
                }

            }

        }else{
            // Processar pagamento com cartão salvo
            $saveCardIndex = (int) $saveCardIndex;
            $user_id = get_current_user_id();
            $cardsArray = get_user_meta($user_id, 'card_array', true);
            
            $selectedCard = isset($cardsArray[$saveCardIndex]) ? $cardsArray[$saveCardIndex] : null;

            try {

                if (!$selectedCard) {
                    throw new Exception(esc_attr(__('Selected card not found.', 'lkn-wc-gateway-cielo')));
                }
    
                $cardToken = $selectedCard['cardToken'];
                $provider = $selectedCard['brand'];
                $cardCvv = $selectedCard['securityCode'];

                // Salvar bandeira e últimos 4 dígitos no pedido (cartão salvo)
                $order->update_meta_data('_lkn_used_card_brand', $selectedCard['brand']);
                $order->update_meta_data('_lkn_used_card_last4', $selectedCard['cardDigits']);

                // Gerar o corpo da requisição usando o token do cartão salvo
                $args['headers'] = array(
                    'Content-Type' => 'application/json',
                    'MerchantId' => sanitize_text_field($this->get_option('merchant_id')),
                    'MerchantKey' => sanitize_text_field($this->get_option('merchant_key')),
                );
    
                $body = array(
                    'MerchantOrderId' => sanitize_text_field($order_id . '-' . time()),
                    'Customer' => array(
                        'Name' => $cardName,
                    ),
                    'Payment' => array(
                        'Type' => "CreditCard",
                        'Amount' => (int) number_format($order->get_total(), 2, '', ''),
                        'Installments' => 1,
                        'Capture' => true,
                        'CreditCard' => array(
                            'CardToken' => $cardToken,
                            'SecurityCode' => $cardCvv,
                            'Brand' => $provider,
                        ),
                    ),
                );
    
                $body = apply_filters('lkn_wc_cielo_process_body', $body, $_POST, $order_id);
                $args['body'] = wp_json_encode($body);
                $args['timeout'] = 120;
    
                $response = wp_remote_post(($this->get_option('env') == 'production') ? 'https://api.cieloecommerce.cielo.com.br/1/sales' : 'https://apisandbox.cieloecommerce.cielo.com.br/1/sales', $args);
            } catch (\Throwable $th) {
                throw new Exception(esc_attr($th->getMessage()));
            }


        }


        /*  throw new Exception(json_encode($args)); */
        if (is_wp_error($response)) {
            if ('yes' === $debug) {
                $this->log->log('error', var_export($response->get_error_messages(), true), array('source' => 'woocommerce-cielo-debit'));
            }

            $message = __('Order payment failed. To make a successful payment using debit card, please review the gateway settings.', 'lkn-wc-gateway-cielo');

            throw new Exception(esc_attr($message));
        }
        $responseDecoded = json_decode($response['body']);

        if (isset($responseDecoded->Code) && isset($responseDecoded->Message)) {
            throw new Exception(esc_attr($responseDecoded->Message));
        }

        // Salvar metadados da resposta para o pedido (ambos os caminhos: novo cartão e cartão salvo)
        if (isset($responseDecoded->Payment->ProofOfSale)) {
            $order->update_meta_data('_lkn_nsu', $responseDecoded->Payment->ProofOfSale);
        }
        if (isset($responseDecoded->Payment->Tid)) {
            $order->update_meta_data('_lkn_tid', $responseDecoded->Payment->Tid);
        }
        if (isset($responseDecoded->Payment->ReturnCode)) {
            $order->update_meta_data('_lkn_return_code', $responseDecoded->Payment->ReturnCode);
        }
        if (isset($responseDecoded->Payment->ReturnMessage)) {
            $order->update_meta_data('_lkn_return_message', $responseDecoded->Payment->ReturnMessage);
        }
        if (isset($responseDecoded->Payment->ReceivedDate)) {
            $order->update_meta_data('_lkn_received_date', $responseDecoded->Payment->ReceivedDate);
        }
        if (isset($responseDecoded->Payment->Status)) {
            $order->update_meta_data('_lkn_payment_status', $responseDecoded->Payment->Status);
        }
        if ($saveCardIndex !== '') {
            // Saved card path: cardType was never explicitly set yet
            $order->update_meta_data('_lkn_card_type', 'Credit');
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

            // Censurar o número do cartão - detectar se é crédito ou débito
            $cardTypeKey = isset($orderLogsArray['body']['Payment']['CreditCard']) ? 'CreditCard' : 'DebitCard';
            if (isset($orderLogsArray['body']['Payment'][$cardTypeKey]['CardNumber'])) {
                $orderLogsArray['body']['Payment'][$cardTypeKey]['CardNumber'] = substr($orderLogsArray['body']['Payment'][$cardTypeKey]['CardNumber'], 0, 6) . '******' . substr($orderLogsArray['body']['Payment'][$cardTypeKey]['CardNumber'], -4);
            }

            // Remover a parte de "Links"
            unset($orderLogsArray['response']['Payment']['Links']);

            $orderLogs = json_encode($orderLogsArray);
            $order->update_meta_data('lknWcCieloOrderLogs', $orderLogs);
        }

        if (isset($responseDecoded->Payment) && (1 == $responseDecoded->Payment->Status || 2 == $responseDecoded->Payment->Status)) {
            //Atualiza order para processando
            $order->update_status('processing');
            // Executar ações de mudança de status
            do_action("lkn_wc_cielo_change_order_status", $order, $this, $actualCapture);

            // Remove cart
            WC()->cart->empty_cart();
            do_action("lkn_wc_cielo_update_order", $order_id, $this);
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
                    $provider .
                    ' (****' .
                    substr($cardNum, -4) .
                    ')' .
                    PHP_EOL .
                    __('Return code', 'lkn-wc-gateway-cielo') .
                    ' - ' .
                    $responseDecoded->Payment->ReturnCode
            );
            
            // Salvar o transaction ID para permitir reembolsos e integrações
            if (isset($responseDecoded->Payment->PaymentId)) {
                $order->set_transaction_id($responseDecoded->Payment->PaymentId);
            }
            
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

            $message = __('Order payment failed. Make sure your credit card is valid.', 'lkn-wc-gateway-cielo');

            throw new Exception(esc_attr($message));
        }
        if ('yes' === $debug) {
            $this->log->log('error', var_export($response, true), array('source' => 'woocommerce-cielo-debit'));
        }

        // Garantir que mesmo erros não tratados tenham seus metadados salvos
        if (!isset($responseDecoded->Payment) || (1 != $responseDecoded->Payment->Status && 2 != $responseDecoded->Payment->Status)) {
            // Forçar save final se ainda não foi salvo
            $order->save();
        }

        if ($cardType == 'Credit') {
            $message = __('Order payment failed. Make sure your credit card is valid.', 'lkn-wc-gateway-cielo');
        } else {
            $message = __('Order payment failed. Make sure your debit card is valid.', 'lkn-wc-gateway-cielo');
        }

        throw new Exception(esc_attr($message));
    }

    private function validate_card_holder_name($cardName, $renderNotice)
    {
        if (empty($cardName) || strlen($cardName) < 3) {
            if ($renderNotice) {
                $this->add_notice_once(__('Card Holder Name is required!', 'lkn-wc-gateway-cielo'), 'error');
            }

            return false;
        }

        return true;
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
                $this->log->log('error', 'Refund attempt without proper permissions for order: ' . $order_id, array('source' => 'woocommerce-cielo-debit-security'));
            }
            return new WP_Error('permission_denied', __('You do not have permission to process refunds.', 'lkn-wc-gateway-cielo'));
        }

        // Do your refund here. Refund $amount for the order with ID $order_id
        $url = ($this->get_option('env') == 'production') ? 'https://api.cieloecommerce.cielo.com.br/' : 'https://apisandbox.cieloecommerce.cielo.com.br/';
        $urlQuery = ($this->get_option('env') == 'production') ? 'https://apiquery.cieloecommerce.cielo.com.br/' : 'https://apiquerysandbox.cieloecommerce.cielo.com.br/';
        $merchantId = sanitize_text_field($this->get_option('merchant_id'));
        $merchantSecret = sanitize_text_field($this->get_option('merchant_key'));
        $debug = $this->get_option('debug');
        $order = wc_get_order($order_id);
        $transactionId = $order->get_transaction_id();
        $orderTotal = $order->get_total();
        
        // Verificar se transação foi capturada
        $queryUrl = $urlQuery . '1/sales/' . $transactionId;
        $queryResponse = wp_remote_get($queryUrl, array(
            'headers' => array(
                'MerchantId' => $merchantId,
                'MerchantKey' => $merchantSecret,
            ),
            'timeout' => 120
        ));

        // Verificar se houve erro na consulta
        if (is_wp_error($queryResponse)) {
            $order->add_order_note('[' . $this->id . '] ' . __('Failed to query transaction status', 'lkn-wc-gateway-cielo'));
            return new \WP_Error('cielo_query_failed', __('Failed to query transaction status. Please try again.', 'lkn-wc-gateway-cielo'));
        }
        
        $queryDecoded = json_decode($queryResponse['body']);
            
        if (isset($queryDecoded->Payment)) {
            // Verificar se a transação foi capturada (CapturedAmount > 0 OU CapturedDate existe OU Status = 2)
            $isCaptured = false;
            
            if (isset($queryDecoded->Payment->CapturedAmount) && $queryDecoded->Payment->CapturedAmount > 0) {
                $isCaptured = true;
            } elseif (isset($queryDecoded->Payment->CapturedDate) && !empty($queryDecoded->Payment->CapturedDate)) {
                $isCaptured = true;
            } elseif (isset($queryDecoded->Payment->Status) && $queryDecoded->Payment->Status != 1) {
                $isCaptured = true;
            }
            
            // Validação: Se não foi capturada E valor não confere com total do pedido
            if (!$isCaptured && $amount != $orderTotal) {
                $order->add_order_note('[' . $this->id . '] ' . __('Partial refund not allowed for non-captured transactions', 'lkn-wc-gateway-cielo'));
                return new \WP_Error('cielo_partial_refund_denied', __('Partial refund not allowed for non-captured transactions', 'lkn-wc-gateway-cielo'));
            }
        }

        // Allow filtering of refund parameters, but verify permission after filter
        $response = apply_filters('lkn_wc_cielo_debit_refund', $url, $merchantId, $merchantSecret, $order_id, $amount);

        // If filter was hooked and returned a value, verify user still has permission
        if (has_filter('lkn_wc_cielo_debit_refund')) {
            if (!current_user_can('manage_woocommerce')) {
                if ('yes' === $debug) {
                    $this->log->log('error', 'Refund filter used without proper permissions for order: ' . $order_id, array('source' => 'woocommerce-cielo-debit-security'));
                }
                $order->add_order_note(__('Order refund blocked: insufficient permissions', 'lkn-wc-gateway-cielo'));
                return false;
            }
            
            // Log filter usage for audit trail
            if ('yes' === $debug) {
                $this->log->log('info', 'Refund filter was used for order: ' . $order_id, array('source' => 'woocommerce-cielo-debit-security'));
            }
        }

        if (is_wp_error($response)) {
            if ('yes' === $debug) {
                $this->log->log('error', var_export($response->get_error_messages(), true), array('source' => 'woocommerce-cielo-debit'));
            }

            $order->add_order_note('[' . $this->id . '] ' . __('Order refund failed, payment id:', 'lkn-wc-gateway-cielo') . ' ' . $transactionId);

            return new \WP_Error('cielo_refund_failed', __('Refund failed. Please try again later.', 'lkn-wc-gateway-cielo'));
        }
        $responseDecoded = json_decode($response['body']);

        if (10 == $responseDecoded->Status || 11 == $responseDecoded->Status || 2 == $responseDecoded->Status || 1 == $responseDecoded->Status) {
            $order->add_order_note('[' . $this->id . '] ' . __('Order refunded, payment id:', 'lkn-wc-gateway-cielo') . ' ' . $transactionId);

            return true;
        }
        if ('yes' === $debug) {
            $this->log->log('error', var_export($response, true), array('source' => 'woocommerce-cielo-debit'));
        }

        $order->add_order_note('[' . $this->id . '] ' . __('Order refund failed, payment id:', 'lkn-wc-gateway-cielo') . ' ' . $transactionId);

        return new \WP_Error('cielo_refund_failed', __('Refund processing failed. Please check the transaction status.', 'lkn-wc-gateway-cielo'));
    }

    /**
     * Validate card number.
     *
     * @param string $dcnum
     * @param bool   $renderNotice
     *
     * @return bool
     */
    private function validate_card_number($dcnum, $renderNotice)
    {
        if (empty($dcnum)) {
            if ($renderNotice) {
                $this->add_notice_once(__('Debit Card number is required!', 'lkn-wc-gateway-cielo'), 'error');
            }

            return false;
        }
        
        // Remove spaces for validation
        $cleanCardNum = preg_replace('/\s/', '', $dcnum);
        
        // Check if card starts with 0
        if (isset($cleanCardNum[0]) && $cleanCardNum[0] === '0') {
            if ($renderNotice) {
                $this->add_notice_once(__('Cards starting with 0 are not accepted', 'lkn-wc-gateway-cielo'), 'error');
            }

            return false;
        }
        
        $isValid = ! preg_match('/[^0-9\s]/', $dcnum);

        if (true !== $isValid || strlen($dcnum) < 12) {
            if ($renderNotice) {
                $this->add_notice_once(__('Debit Card number is invalid!', 'lkn-wc-gateway-cielo'), 'error');
            }

            return false;
        }

        return true;
    }

    /**
     * Validate card expiration date.
     *
     * @param string $expDate
     * @param bool   $renderNotice
     *
     * @return bool
     */
    private function validate_exp_date($expDate, $renderNotice)
    {
        if (empty($expDate)) {
            if ($renderNotice) {
                $this->add_notice_once(__('Expiration date is required!', 'lkn-wc-gateway-cielo'), 'error');
            }

            return false;
        }
        $expDateSplit = explode('/', $expDate);

        try {
            $expDate = new DateTime('20' . trim($expDateSplit[1]) . '-' . trim($expDateSplit[0]) . '-01');
            $today = new DateTime();

            if ($today > $expDate) {
                if ($renderNotice) {
                    $this->add_notice_once(__('Debit card is expired!', 'lkn-wc-gateway-cielo'), 'error');
                }

                return false;
            }

            return true;
        } catch (Exception $e) {
            $this->add_notice_once(__('Expiration date is invalid!', 'lkn-wc-gateway-cielo'), 'error');

            return false;
        }
    }

    /**
     * Validate card cvv.
     *
     * @param string $cvv
     * @param bool   $renderNotice
     *
     * @return bool
     */
    private function validate_cvv($cvv, $renderNotice)
    {
        if (empty($cvv)) {
            $this->add_notice_once(__('CVV is required!', 'lkn-wc-gateway-cielo'), 'error');

            return false;
        }
        $isValid = ! preg_match('/\D/', $cvv);

        if (true !== $isValid || strlen($cvv) < 3) {
            $this->add_notice_once(__('CVV is invalid!', 'lkn-wc-gateway-cielo'), 'error');

            return false;
        }

        return true;
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

    /**
     * Save transaction metadata to order.
     *
     * @param WC_Order $order
     * @param object $responseDecoded
     * @param string $cardNum
     * @param string $cardExpShort
     * @param string $cardName
     * @param int $installments
     * @param float $amount
     * @param string $currency
     * @param string $provider
     * @param string $merchantId
     * @param string $merchantSecret
     * @param string $merchantOrderId
     * @param int $order_id
     * @param bool $capture
     * @param array $response
     * @param string $cardType
     * @param string $xid
     * @param string $cavv
     * @param string $eci
     * @param string $version
     * @param string $refId
     */
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

    /**
     * Add partial capture button to order actions
     */
    public function add_partial_capture_button($order_id)
    {
        // Verificar se a versão pro está ativa
        if (!LknWcCieloHelper::is_pro_license_active()) {
            return;
        }

        // Garantir que $order_id é um inteiro
        if (is_object($order_id)) {
            $order_id = $order_id->get_id();
        }
        $order_id = intval($order_id);
        
        $order = wc_get_order($order_id);
        
        // Verificar se o pedido usa este gateway de pagamento
        if (!$order || $order->get_payment_method() !== $this->id) {
            return;
        }

        // Verificar se o pedido foi feito com captura manual
        $captureType = $order->get_meta('_lkn_cielo_capture_type');
        if ($captureType !== 'manual') {
            return;
        }

        // Verificar se tem transaction ID
        $transactionId = $order->get_transaction_id();
        if (empty($transactionId)) {
            return;
        }

        // Verificar se já foi feita captura parcial (só permite uma vez)
        $partialCapturePerformed = $order->get_meta('_lkn_cielo_capture_performed');
        if ($partialCapturePerformed) {
            return;
        }

        // Verificação adicional: verificar se já existe reembolso de captura parcial
        $refunds = $order->get_refunds();
        foreach ($refunds as $refund) {
            if (strpos($refund->get_reason(), 'partial capture') !== false || 
                strpos($refund->get_reason(), 'captura parcial') !== false) {
                return;
            }
        }

        // Verificar se o status permite captura
        if (!in_array($order->get_status(), array('pending', 'on-hold'))) {
            return;
        }

        // Evitar duplicação - verificar se já foi renderizado
        static $rendered = array();
        if (isset($rendered[$order_id])) {
            return;
        }
        $rendered[$order_id] = true;

        // Enfileirar o script e CSS JavaScript aqui onde temos acesso ao order_id
        wp_enqueue_script('lkn-cielo-partial-capture', plugin_dir_url(__FILE__) . '../resources/js/admin/lkn-cielo-partial-capture.js', array('jquery'), $this->version, true);
        wp_enqueue_style('lkn-cielo-partial-capture', plugin_dir_url(__FILE__) . '../resources/css/admin/lkn-cielo-partial-capture.css', array(), $this->version);
        
        $orderTotal = $order->get_total();
        
        // Localizar variáveis para o JavaScript
        wp_localize_script('lkn-cielo-partial-capture', 'lknCieloPartialCapture', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('lkn_cielo_partial_capture_nonce'),
            'orderTotal' => $orderTotal,
            'currencySymbol' => 'R$',
            'messages' => array(
                'invalidAmount' => __('Please enter a valid amount to capture. The value cannot be below 0 or above the total order amount.', 'lkn-wc-gateway-cielo'),
                'confirmCapture' => __('Confirm capture of', 'lkn-wc-gateway-cielo'),
                'discountWillBeApplied' => __('A refund will be processed of', 'lkn-wc-gateway-cielo'),
                'processing' => __('Processing...', 'lkn-wc-gateway-cielo'),
                'error' => __('Error:', 'lkn-wc-gateway-cielo'),
                'processError' => __('Error processing capture.', 'lkn-wc-gateway-cielo'),
                'buttonText' => __('Capture', 'lkn-wc-gateway-cielo'),
                // translators: %s is the maximum amount that can be captured
                'helpTooltip' => __('Warning: The capture amount cannot exceed %s. Lower amounts generate automatic refund for the difference. Once processed, the capture cannot be changed or repeated for this order.', 'lkn-wc-gateway-cielo')
            )
        ));

        ?>
        <div class="lkn-partial-capture-container">
            <label><?php esc_html_e('Amount to capture:', 'lkn-wc-gateway-cielo'); ?></label>
            <input type="number" id="lkn-capture-amount" class="lkn-capture-amount-input" step="0.01" min="0.01" max="<?php echo esc_attr($orderTotal); ?>" value="<?php echo esc_attr($orderTotal); ?>" />
            <span id="lkn-capture-help-icon" class="lkn-capture-help-icon" title="">
                ?
            </span>
            <button type="button" id="lkn-partial-capture-btn" class="button button-primary" 
                    data-order-id="<?php echo esc_attr($order_id); ?>" 
                    data-transaction-id="<?php echo esc_attr($transactionId); ?>"
                    data-order-total="<?php echo esc_attr($orderTotal); ?>">
                <?php esc_html_e('Capture', 'lkn-wc-gateway-cielo'); ?>
            </button>
        </div>
        <?php
    }



    /**
     * Handle AJAX partial capture request
     */
    public function handle_partial_capture_ajax()
    {
        // Verificar se a versão pro está ativa
        if (!LknWcCieloHelper::is_pro_license_active()) {
            wp_send_json_error(__('Pro version is not active', 'lkn-wc-gateway-cielo'));
        }

        // Verificar nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'lkn_cielo_partial_capture_nonce')) {
            wp_send_json_error(__('Security verification failed', 'lkn-wc-gateway-cielo'));
        }

        // Verificar permissões
        if (!current_user_can('edit_shop_orders')) {
            wp_send_json_error(__('Insufficient permissions', 'lkn-wc-gateway-cielo'));
        }

        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        $capture_amount = isset($_POST['capture_amount']) ? floatval($_POST['capture_amount']) : 0;
        $transaction_id = isset($_POST['transaction_id']) ? sanitize_text_field(wp_unslash($_POST['transaction_id'])) : '';

        if (empty($order_id) || empty($capture_amount) || empty($transaction_id)) {
            wp_send_json_error(__('Required data not provided', 'lkn-wc-gateway-cielo'));
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            wp_send_json_error(__('Order not found', 'lkn-wc-gateway-cielo'));
        }

        // Verificar se é o gateway correto
        if ($order->get_payment_method() !== $this->id) {
            wp_send_json_error(__('Incorrect payment gateway', 'lkn-wc-gateway-cielo'));
        }

        // Verificar se o pedido foi feito com captura manual
        $captureType = $order->get_meta('_lkn_cielo_capture_type');
        if ($captureType !== 'manual') {
            wp_send_json_error(__('Partial capture is only available for orders with manual capture', 'lkn-wc-gateway-cielo'));
        }

        // Verificar se já foi feita captura parcial
        $partialCapturePerformed = $order->get_meta('_lkn_cielo_capture_performed');
        if ($partialCapturePerformed) {
            wp_send_json_error(__('Partial capture has already been performed for this order', 'lkn-wc-gateway-cielo'));
        }

        // Verificação adicional: verificar se já existe reembolso de captura parcial
        $refunds = $order->get_refunds();
        foreach ($refunds as $refund) {
            if (strpos($refund->get_reason(), 'partial capture') !== false || 
                strpos($refund->get_reason(), 'captura parcial') !== false) {
                wp_send_json_error(__('Partial capture has already been performed for this order', 'lkn-wc-gateway-cielo'));
            }
        }

        // Processar captura
        $url = ($this->get_option('env') == 'production') ? 'https://api.cieloecommerce.cielo.com.br/' : 'https://apisandbox.cieloecommerce.cielo.com.br/';
        $merchantId = sanitize_text_field($this->get_option('merchant_id'));
        $merchantSecret = sanitize_text_field($this->get_option('merchant_key'));

        $result = $this->processPartialCapture($transaction_id, $capture_amount, $order, $url, $merchantId, $merchantSecret);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        // Calcular valor restante (que será reembolsado)
        $orderTotal = $order->get_total();
        $remainingAmount = $orderTotal - $capture_amount;
        
        // Se há valor restante, criar reembolso oficial
        if ($remainingAmount > 0) {
            // Criar reembolso usando o sistema WooCommerce
            $refund = wc_create_refund(array(
                'order_id' => $order_id,
                'amount' => $remainingAmount,
                'reason' => sprintf(
                    // translators: %1$s is the captured amount, %2$s is the total order amount
                    __('Automatic refund due to partial capture. Captured: %1$s of %2$s', 'lkn-wc-gateway-cielo'),
                    'R$ ' . number_format($capture_amount, 2, ',', '.'),
                    'R$ ' . number_format($orderTotal, 2, ',', '.')
                ),
                'refunded_by' => get_current_user_id()
            ));
            
            if (is_wp_error($refund)) {
                // Se falhou ao criar o refund, reverter e retornar erro
                wp_send_json_error(
                    sprintf(
                        // translators: %s is the refund error message
                        __('Capture completed but failed to create refund: %s', 'lkn-wc-gateway-cielo'),
                        $refund->get_error_message()
                    )
                );
            }
        }
        
        // Marcar que captura parcial foi realizada
        $order->update_meta_data('_lkn_cielo_capture_performed', true);
        $order->update_meta_data('_lkn_cielo_captured_amount', $capture_amount);
        $order->update_meta_data('_lkn_cielo_remaining_amount', $remainingAmount);
        
        // Mudar status para processando
        $order->update_status('processing', 
            sprintf(
                // translators: %1$s is the gateway ID, %2$s is the captured amount, %3$s is the refunded amount
                __('[%1$s] Partial capture completed. Captured amount: %2$s. Refunded amount: %3$s', 'lkn-wc-gateway-cielo'),
                $this->id,
                'R$ ' . number_format($capture_amount, 2, ',', '.'),
                'R$ ' . number_format($remainingAmount, 2, ',', '.')
            )
        );
        
        $order->save();
        
        $message = sprintf(
            // translators: %s is the captured amount
            __('Partial capture of %s completed successfully!', 'lkn-wc-gateway-cielo'),
            'R$ ' . number_format($capture_amount, 2, ',', '.')
        );
        
        if ($remainingAmount > 0) {
            $message .= ' ' . sprintf(
                // translators: %s is the refunded amount
                __('Refund of %s processed.', 'lkn-wc-gateway-cielo'),
                'R$ ' . number_format($remainingAmount, 2, ',', '.')
            );
        }
        
        wp_send_json_success(array(
            'message' => $message
        ));
    }

    /**
     * Process partial capture request using Cielo API
     *
     * @param string $transactionId
     * @param float $captureAmount
     * @param WC_Order $order
     * @param string $url
     * @param string $merchantId
     * @param string $merchantSecret
     * @return bool|WP_Error
     */
    private function processPartialCapture($transactionId, $captureAmount, $order, $url, $merchantId, $merchantSecret)
    {
        // Converter valor para centavos (formato da API Cielo)
        $amountInCents = number_format($captureAmount, 2, '', '');

        // Headers da requisição
        $headers = array(
            'Content-Type' => 'application/json',
            'MerchantId' => $merchantId,
            'MerchantKey' => $merchantSecret,
        );

        // Endpoint para captura parcial com valor no query parameter
        $captureUrl = $url . "1/sales/{$transactionId}/capture?amount={$amountInCents}";

        // Fazer a requisição PUT para captura (sem body)
        $response = wp_remote_request($captureUrl, array(
            'method' => 'PUT',
            'headers' => $headers,
            'timeout' => 120
        ));

        // Verificar se houve erro na requisição
        if (is_wp_error($response)) {
            $error_message = sprintf(
                // translators: %s is the error message
                __('Error in partial capture: %s', 'lkn-wc-gateway-cielo'),
                $response->get_error_message()
            );
            $order->add_order_note('[' . $this->id . '] ' . $error_message);
            
            if ('yes' === $this->get_option('debug')) {
                $this->log->log('error', var_export($response->get_error_messages(), true), array('source' => 'woocommerce-cielo-debit'));
            }
            
            return new WP_Error('cielo_capture_failed', $error_message);
        }

        // Decodificar resposta
        $responseDecoded = json_decode($response['body']);

        // Verificar se a captura foi bem-sucedida
        if (isset($responseDecoded->Status) && ($responseDecoded->Status == 2)) {
            // Fazer consulta GET para obter informações completas do pedido
            $getSelfUrl = null;
            if (isset($responseDecoded->Links) && is_array($responseDecoded->Links)) {
                foreach ($responseDecoded->Links as $link) {
                    if (isset($link->Method) && $link->Method === 'GET' && 
                        isset($link->Rel) && $link->Rel === 'self' && 
                        isset($link->Href)) {
                        $getSelfUrl = $link->Href;
                        break;
                    }
                }
            }

            // Se encontrou o link GET self, fazer a consulta
            $orderDetailsDecoded = null;

            // Salvar logs detalhados no pedido se debug estiver ativo
            if ('yes' === $this->get_option('debug')) {
                if ($getSelfUrl) {
                    $orderDetailsResponse = wp_remote_get($getSelfUrl, array(
                        'headers' => array(
                            'MerchantId' => $merchantId,
                            'MerchantKey' => $merchantSecret,
                        ),
                        'timeout' => 120
                    ));

                    if (!is_wp_error($orderDetailsResponse)) {
                        $orderDetailsDecoded = json_decode($orderDetailsResponse['body']);
                    }
                }

                $lknWcCieloHelper = new LknWcCieloHelper();
                
                $partialCaptureLogsArray = array(
                    'partial_capture_request' => array(
                        'url' => $captureUrl,
                        'headers' => array(
                            'Content-Type' => $headers['Content-Type'],
                            'MerchantId' => $lknWcCieloHelper->censorString($headers['MerchantId'], 10),
                            'MerchantKey' => $lknWcCieloHelper->censorString($headers['MerchantKey'], 10)
                        ),
                        'method' => 'PUT',
                        'amount_in_cents' => $amountInCents,
                        'capture_amount' => $captureAmount
                    ),
                    'partial_capture_response' => json_decode(json_encode($responseDecoded), true)
                );

                // Adicionar detalhes da consulta GET se disponível
                if ($orderDetailsDecoded) {
                    $partialCaptureLogsArray['order_details_request'] = array(
                        'url' => $getSelfUrl,
                        'headers' => array(
                            'MerchantId' => $lknWcCieloHelper->censorString($headers['MerchantId'], 10),
                            'MerchantKey' => $lknWcCieloHelper->censorString($headers['MerchantKey'], 10)
                        ),
                        'method' => 'GET'
                    );
                    $partialCaptureLogsArray['order_details_response'] = json_decode(json_encode($orderDetailsDecoded), true);
                    
                    // Remover Links da resposta para manter logs limpos
                    if (isset($partialCaptureLogsArray['order_details_response']['Payment']['Links'])) {
                        unset($partialCaptureLogsArray['order_details_response']['Payment']['Links']);
                    }
                }

                // Remover Links da resposta de captura também
                if (isset($partialCaptureLogsArray['partial_capture_response']['Links'])) {
                    unset($partialCaptureLogsArray['partial_capture_response']['Links']);
                }

                $partialCaptureLogs = json_encode($partialCaptureLogsArray);
                $order->update_meta_data('lknWcCieloPartialCaptureLogs', $partialCaptureLogs);
            }
            
            $order->add_order_note(sprintf(
                '[%s] %s %s. TID: %s',
                $this->id,
                __('Partial capture completed successfully. Captured amount:', 'lkn-wc-gateway-cielo'),
                'R$ ' . number_format($captureAmount, 2, ',', '.'),
                isset($responseDecoded->Tid) ? $responseDecoded->Tid : $transactionId
            ));
            
            if ('yes' === $this->get_option('debug')) {
                if ($orderDetailsDecoded) {
                    $this->log->log('info', 'Detalhes do captura parcial: ' . var_export($orderDetailsDecoded, true), array('source' => 'woocommerce-cielo-debit'));
                }
            }
            
            return true;
        }

        // Se chegou aqui, houve erro na captura
        // Verificar se é um array (caso de erro da API) e pegar o primeiro elemento
        if (is_array($responseDecoded) && !empty($responseDecoded)) {
            $errorObj = $responseDecoded[0];
            $error_message = isset($errorObj->Message) ? $errorObj->Message : __('Unknown error in partial capture', 'lkn-wc-gateway-cielo');
        } else {
            $error_message = isset($responseDecoded->Message) 
                ? $responseDecoded->Message 
                : __('Unknown error in partial capture', 'lkn-wc-gateway-cielo');
        }
        
        $order->add_order_note(sprintf(
            '[%s] %s: %s',
            $this->id,
            __('Partial capture failed', 'lkn-wc-gateway-cielo'),
            $error_message
        ));
        
        if ('yes' === $this->get_option('debug')) {
            $this->log->log('error', 'Erro na captura parcial: ' . var_export($responseDecoded, true), array('source' => 'woocommerce-cielo-debit'));
        }
        
        return new \WP_Error('cielo_capture_failed', $error_message);
    }


}
?>