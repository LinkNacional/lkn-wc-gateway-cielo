<?php

namespace Lkn\WCCieloPaymentGateway\Includes;

if ( ! defined( 'ABSPATH' ) ) exit;

use Exception;
use WC_Logger;
use WC_Payment_Gateway;

final class LknWcCieloPix extends WC_Payment_Gateway
{
    /**
     * @var string
     */
    private $version;

    /**
     * Request
     *
     * @var LknWcCieloRequest
     */
    private static $request;
    private $log;
    private $instructions;
    private $debug;

    public function __construct()
    {
        $this->id = 'lkn_wc_cielo_pix';
        $this->icon = apply_filters('lkn_wc_cielo_gateway_icon', '');
        $this->has_fields = true;
        $this->supports = array(
            'products',
        );

        self::$request = new LknWcCieloRequest();
        $this->version = LKN_WC_CIELO_VERSION;

        $this->method_title = __('Cielo PIX Free', 'lkn-wc-gateway-cielo');
        
        $this->method_description = __('Check with Cielo if the PIX payment method is activated.', 'lkn-wc-gateway-cielo') . ' ' .
                '<a href="https://www.youtube.com/watch?v=5mYIEC9V254&t=993s" target="_blank">' .
                __('Watch the tutorial', 'lkn-wc-gateway-cielo') . '</a>' . ' ' .
                __('or', 'lkn-wc-gateway-cielo') . ' ' .
                '<a href="https://linknacional.com.br/wordpress/woocommerce/cielo/doc/#woocommerce-pix-cielo" target="_blank">' .
                __('check the documentation', 'lkn-wc-gateway-cielo') . '</a>' .
                __(' for more information.', 'lkn-wc-gateway-cielo');


        $this->supports = array(
            'products',
        );

        $this->icon = LknWcCieloHelper::getIconUrl();
        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();
        //Actions

        add_filter('woocommerce_new_order_note_data', array($this, 'add_gateway_name_to_notes'), 10, 2);
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

        // Define user set variables.

        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->debug = $this->get_option('debug');
        $this->instructions = $this->get_option('instructions', $this->description);
        $this->log = new WC_Logger();

        if (function_exists('get_plugins')) {
            add_action('admin_enqueue_scripts', array($this, 'admin_load_script'));
        }
    }

    public function admin_load_script(): void
    {
        $page = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : '';
        $tab = isset($_GET['tab']) ? sanitize_text_field(wp_unslash($_GET['tab'])) : '';
        $section = isset($_GET['section']) ? sanitize_text_field(wp_unslash($_GET['section'])) : '';

        if ('wc-settings' === $page && 'checkout' === $tab && $section == $this->id) {

            wp_enqueue_script('lkn-wc-gateway-admin', plugin_dir_url(__FILE__) . '../resources/js/admin/lkn-wc-gateway-admin.js', array('wp-i18n'), $this->version, 'all');

            $pro_plugin_exists = file_exists(WP_PLUGIN_DIR . '/lkn-cielo-api-pro/lkn-cielo-api-pro.php');
            $pro_plugin_active = function_exists('is_plugin_active') && is_plugin_active('lkn-cielo-api-pro/lkn-cielo-api-pro.php');

            wp_localize_script('lkn-wc-gateway-admin', 'lknCieloProStatus', array(
                'isProActive' => $pro_plugin_exists && $pro_plugin_active ? true : false,
            ));

            if (isset($_GET['section']) && sanitize_text_field(wp_unslash($_GET['section'])) === 'lkn_wc_cielo_pix') {
                wp_enqueue_script('LknCieloPixSettingsPix', LKN_WC_GATEWAY_CIELO_URL . 'resources/js/admin/lkn-settings-pix.js', array(), $this->version, false);

                wp_localize_script(
                    'LknCieloPixSettingsPix',
                    'lknCieloProSettingsVars',
                    array(
                        'proOnly' => __('Available only in PRO', 'lkn-wc-gateway-cielo'),
                    )
                );
            }
            wp_enqueue_script('LknCieloPixSettingsLayoutScript', LKN_WC_GATEWAY_CIELO_URL . 'resources/js/admin/lkn-wc-gateway-admin-layout.js', array('jquery'), $this->version, false);
            $gateway_settings = $this->settings;
            wp_localize_script('LknCieloPixSettingsLayoutScript', 'lknWcCieloTranslationsInput', array(
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
            wp_enqueue_style('lkn-admin-cielo-layout', LKN_WC_GATEWAY_CIELO_URL . 'resources/css/frontend/lkn-admin-layout.css', array(), $this->version, 'all');
            wp_enqueue_script('LknCieloPixClearButtonScript', LKN_WC_GATEWAY_CIELO_URL . '/resources/js/admin/lkn-clear-logs-button.js', array('jquery'), $this->version, false);
            wp_localize_script('LknCieloPixClearButtonScript', 'lknWcCieloTranslations', array(
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

    public function init_form_fields(): void
    {
        $this->form_fields = array(
            'general' => array(
                'title' => esc_attr__('General', 'lkn-wc-gateway-cielo'),
                'type' => 'title',
            ),
            'enabled' => array(
                'title'       => __('Enable/Disable', 'lkn-wc-gateway-cielo'),
                'type'        => 'checkbox',
                'label'       => __('Enable Pix Payments', 'lkn-wc-gateway-cielo'),
                'default'     => 'no',
                'description' => __('Enable this option to allow Pix payments at checkout.', 'lkn-wc-gateway-cielo'),
                'desc_tip'    => __('Select this option to enable Pix payment method.', 'lkn-wc-gateway-cielo'),
                'custom_attributes' => array(
                    'data-title-description' => __('Enable this option to allow Pix payments at checkout.', 'lkn-wc-gateway-cielo'),
                ),
            ),
            'title' => array(
                'title'       => __('Title', 'lkn-wc-gateway-cielo'),
                'type'        => 'text',
                'default'     => __('Pix', 'lkn-wc-gateway-cielo'),
                'description' => __('This controls the title which the user sees during checkout.', 'lkn-wc-gateway-cielo'),
                'desc_tip'    => __('Displayed name for this payment method at checkout.', 'lkn-wc-gateway-cielo'),
                'custom_attributes' => array(
                    'required' => 'required',
                    'data-title-description' => __('Displayed name for this payment method at checkout.', 'lkn-wc-gateway-cielo'),
                ),
            ),
            'description' => array(
                'title'       => __('Description', 'lkn-wc-gateway-cielo'),
                'type'        => 'textarea',
                'default'     => __('After the purchase is completed, the PIX will be generated and made available for payment!', 'lkn-wc-gateway-cielo'),
                'description' => __('Payment method description that the customer will see on your checkout.', 'lkn-wc-gateway-cielo'),
                'desc_tip'    => __('Additional info shown next to the payment method.', 'lkn-wc-gateway-cielo'),
                'custom_attributes' => array(
                    'required' => 'required',
                    'data-title-description' => __('Additional info shown next to the payment method.', 'lkn-wc-gateway-cielo'),
                ),
            ),
            'env' => array(
                'title'       => __('Environment', 'lkn-wc-gateway-cielo'),
                'type'        => 'select',
                'options'     => array(
                    'production' => __('Production', 'lkn-wc-gateway-cielo'),
                    'sandbox'    => __('Development', 'lkn-wc-gateway-cielo'),
                ),
                'default'     => 'production',
                'desc_tip'    => __('Choose "Production" for live payments or "Development" for sandbox testing.', 'lkn-wc-gateway-cielo'),
                'custom_attributes' => array(
                    'data-title-description' => __('Select the Cielo API 3.0 environment.', 'lkn-wc-gateway-cielo'),
                ),
            ),
            'merchant_id' => array(
                'title'       => __('Merchant Id', 'lkn-wc-gateway-cielo'),
                'type'        => 'password',
                'description' => __('Cielo credentials.', 'lkn-wc-gateway-cielo'),
                'desc_tip'    => __('Enter your Merchant ID from Cielo API 3.0.', 'lkn-wc-gateway-cielo'),
                'custom_attributes' => array(
                    'required' => 'required',
                    'data-title-description' => __('Enter your Merchant ID from Cielo API 3.0.', 'lkn-wc-gateway-cielo'),
                ),
            ),
            'merchant_key' => array(
                'title'       => __('Merchant Key', 'lkn-wc-gateway-cielo'),
                'type'        => 'password',
                'description' => __('Cielo credentials.', 'lkn-wc-gateway-cielo'),
                'desc_tip'    => __('Enter your Merchant Key from Cielo API 3.0.', 'lkn-wc-gateway-cielo'),
                'custom_attributes' => array(
                    'required' => 'required',
                    'data-title-description' => __('Enter your Merchant Key from Cielo API 3.0.', 'lkn-wc-gateway-cielo'),
                ),
            ),
            'payment_complete_status' => array(
                'title'       => esc_attr__('Payment Complete Status', 'lkn-wc-gateway-cielo'),
                'type'        => 'select',
                'options'     => array(
                    'processing' => _x('Processing', 'Order status', 'lkn-wc-gateway-cielo'),
                    'on-hold'    => _x('On hold', 'Order status', 'lkn-wc-gateway-cielo'),
                    'completed'  => _x('Completed', 'Order status', 'lkn-wc-gateway-cielo'),
                ),
                'default'     => 'processing',
                'description' => esc_attr__('Opção para definir automaticamente o status do pedido após confirmação do pagamento por este gateway.', 'lkn-wc-gateway-cielo'),
                'desc_tip'    => esc_attr__('Escolha o status que será atribuído automaticamente após a confirmação do pagamento.', 'lkn-wc-gateway-cielo'),
                'custom_attributes' => array(
                    'data-title-description' => esc_attr__('Define automaticamente o status do pedido após pagamento.', 'lkn-wc-gateway-cielo'),
                ),
            ),
            'pix_layout' => array(
                'title'       => __('PIX Layout', 'lkn-wc-gateway-cielo'),
                'type'        => 'select',
                'default'     => 'standard',
                'options'     => array(
                    'standard' => __('Standard', 'lkn-wc-gateway-cielo'),
                    'new'      => __('New', 'lkn-wc-gateway-cielo'),
                ),
                'description' => __('Selecione o layout do PIX que o cliente verá no checkout.', 'lkn-wc-gateway-cielo'),
                'desc_tip'    => __('Escolha entre o layout padrão ou o novo layout para o PIX.', 'lkn-wc-gateway-cielo'),
                'custom_attributes' => array(
                    'data-title-description' => __('Layout do PIX mostrado para o cliente no checkout.', 'lkn-wc-gateway-cielo'),
                ),
            ),
            'layout_location' => array(
                'title'       => __('Layout Location', 'lkn-wc-gateway-cielo'),
                'type'        => 'select',
                'default'     => 'bottom',
                'options'     => array(
                    'top'    => __('Top', 'lkn-wc-gateway-cielo'),
                    'bottom' => __('Bottom', 'lkn-wc-gateway-cielo'),
                ),
                'description' => __('Selecione a posição onde o layout PIX será exibido na página de checkout.', 'lkn-wc-gateway-cielo'),
                'desc_tip'    => __('Defina se o layout PIX aparece no topo ou rodapé do checkout.', 'lkn-wc-gateway-cielo'),
                'custom_attributes' => array(
                    'data-title-description' => __('Posição do layout PIX na página de checkout.', 'lkn-wc-gateway-cielo'),
                ),
            ),
            'show_button' => array(
                'title' => esc_attr__('Botão Gerar PIX', 'lkn-wc-gateway-cielo'),
                'type' => 'checkbox',
                'desc_tip' => esc_attr__('Exibe o botão "Finalizar e Gerar PIX" no checkout.', 'lkn-wc-gateway-cielo'),
                'description' => esc_attr__('Exibe o botão "Finalizar e Gerar PIX" no checkout.', 'lkn-wc-gateway-cielo'),
                'default' => 'no',
                'custom_attributes' => array(
                    'data-title-description' => __('Exibe um botão adicional para gerar o PIX', 'lkn-wc-gateway-cielo'),
                    'disabled' => 'disabled',
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

        $customConfigs = apply_filters('lkn_wc_cielo_get_custom_configs', array(), $this->id);

        if (! empty($customConfigs) && is_array($customConfigs)) {
            $this->form_fields = array_merge($this->form_fields, $customConfigs);
        }
    }

    public function payment_fields(): void
    {
        wp_enqueue_style('lknWCGatewayCieloFixIconsStyle', plugin_dir_url(__FILE__) . '../resources/css/frontend/lkn-fix-icons-styles.css', array(), $this->version, 'all');
        $description = $this->get_option('description', __('After the purchase is completed, the PIX will be generated and made available for payment!', 'lkn-wc-gateway-cielo'));
        echo "
            <div style=\"text-align:center;font-weight: bold;\">
                <p>" . esc_attr($description) . "</p>
            </div>
        ";

        $wcbcf_settings = get_option('wcbcf_settings');
        $woo_better_calc_person_type = get_option('woo_better_calc_person_type_select', 'none');

        $activeWooBetter = is_plugin_active('woo-better-shipping-calculator-for-brazil/wc-better-shipping-calculator-for-brazil.php') || is_plugin_active('wc-better-shipping-calculator-for-brazil/wc-better-shipping-calculator-for-brazil.php') ? true : false;

        if (
            (
                is_array($wcbcf_settings) &&
                "0" === $wcbcf_settings['person_type'] ||
                ! is_plugin_active('woocommerce-extra-checkout-fields-for-brazil/woocommerce-extra-checkout-fields-for-brazil.php')
            ) &&
            (
                ! $activeWooBetter ||
                'none' === $woo_better_calc_person_type
            )
        ) {
?>
            <br />
            <div class="form-row form-row">
                <label
                    id="labels-with-icons"
                    for="lknCieloApiPixBillingCpf"
                    style="display: flex; align-items: center;">
                    <?php echo esc_attr('CPF / CNPJ'); ?><span
                        class="required">*</span>
                    <div>
                        <svg
                            version="1.1"
                            id="Capa_1"
                            xmlns="http://www.w3.org/2000/svg"
                            xmlns:xlink="http://www.w3.org/1999/xlink"
                            x="0px"
                            y="4px"
                            width="24px"
                            height="16px"
                            viewBox="0 0 216 146"
                            enable-background="new 0 0 216 146"
                            xml:space="preserve">
                            <g>
                                <path
                                    class="svg"
                                    d="M107.999,73c8.638,0,16.011-3.056,22.12-9.166c6.111-6.11,9.166-13.483,9.166-22.12c0-8.636-3.055-16.009-9.166-22.12c-6.11-6.11-13.484-9.165-22.12-9.165c-8.636,0-16.01,3.055-22.12,9.165c-6.111,6.111-9.166,13.484-9.166,22.12c0,8.637,3.055,16.01,9.166,22.12C91.99,69.944,99.363,73,107.999,73z"
                                    style="fill: rgb(21, 140, 186);"></path>
                                <path
                                    class="svg"
                                    d="M165.07,106.037c-0.191-2.743-0.571-5.703-1.141-8.881c-0.57-3.178-1.291-6.124-2.16-8.84c-0.869-2.715-2.037-5.363-3.504-7.943c-1.466-2.58-3.15-4.78-5.052-6.6s-4.223-3.272-6.965-4.358c-2.744-1.086-5.772-1.63-9.085-1.63c-0.489,0-1.63,0.584-3.422,1.752s-3.815,2.472-6.069,3.911c-2.254,1.438-5.188,2.743-8.799,3.909c-3.612,1.168-7.237,1.752-10.877,1.752c-3.639,0-7.264-0.584-10.876-1.752c-3.611-1.166-6.545-2.471-8.799-3.909c-2.254-1.439-4.277-2.743-6.069-3.911c-1.793-1.168-2.933-1.752-3.422-1.752c-3.313,0-6.341,0.544-9.084,1.63s-5.065,2.539-6.966,4.358c-1.901,1.82-3.585,4.02-5.051,6.6s-2.634,5.229-3.503,7.943c-0.869,2.716-1.589,5.662-2.159,8.84c-0.571,3.178-0.951,6.137-1.141,8.881c-0.19,2.744-0.285,5.554-0.285,8.433c0,6.517,1.983,11.664,5.948,15.439c3.965,3.774,9.234,5.661,15.806,5.661h71.208c6.572,0,11.84-1.887,15.806-5.661c3.966-3.775,5.948-8.921,5.948-15.439C165.357,111.591,165.262,108.78,165.07,106.037z"
                                    style="fill: rgb(21, 140, 186);"></path>
                            </g>
                        </svg>
                    </div>
                </label>
                <input
                    id="lknCieloApiPixBillingCpf"
                    name="billing_cielo_pix_free_cpf_cnpj"
                    class="input-text"
                    type="text"
                    pattern="[0-9]*"
                    placeholder="<?php echo esc_attr('CPF / CNPJ'); ?>"
                    maxlength="14"
                    autocomplete="off"
                    style="font-size: 1.5em; padding: 8px 45px;"
                    oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                    required />
            </div>
<?php
        }

        $this->payment_gateway_scripts();
    }

    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);
        $first_name = $order->get_billing_first_name();
        $last_name = $order->get_billing_last_name();
        $merchantOrderId = $order_id . '-' . time();
        $merchantId = sanitize_text_field($this->get_option('merchant_id'));
        $merchantSecret = sanitize_text_field($this->get_option('merchant_key'));
        $paymentComplete = true;
        try {
            // Verificação de nome
            $firstName = sanitize_text_field($first_name);
            $lastName = sanitize_text_field($last_name);
            $currency = (string) $order->get_currency();

            $fullName = $firstName . ' ' . $lastName;
            if (empty($fullName)) {
                throw new Exception('Nome não informado');
            }
            // Busca CPF/CNPJ em ordem de prioridade: campo personalizado > billing_cpf > billing_cnpj
            $billing_document = '';
            
            if (!empty($_POST['billing_cielo_pix_free_cpf_cnpj'])) {
                // 1ª prioridade: Campo personalizado do PIX
                $billing_document = sanitize_text_field(wp_unslash($_POST['billing_cielo_pix_free_cpf_cnpj']));
            } elseif (!empty($_POST['billing_cpf'])) {
                // 2ª prioridade: Campo universal billing_cpf
                $billing_document = sanitize_text_field(wp_unslash($_POST['billing_cpf']));
            } elseif (!empty($_POST['billing_cnpj'])) {
                // 3ª prioridade: Campo billing_cnpj (fallback)
                $billing_document = sanitize_text_field(wp_unslash($_POST['billing_cnpj']));
            }
            
            $billing_document_clean = preg_replace('/[^0-9]/', '', $billing_document);
            
            $billingCpfCpnj = array(
                'Identity' => $billing_document,
                'IdentityType' => strlen($billing_document_clean) === 11 ? 'CPF' : 'CNPJ'
            );

            $amount = number_format((float) $order->get_total(), 2, '.', '');

            if ('BRL' != $currency) {
                $amount = apply_filters('lkn_wc_cielo_convert_amount', $amount, $currency);
                $order->add_order_note('[' . $this->id . '] Amount converted: ' . $amount);
            }

            if (! $amount) {
                throw new Exception('Não foi possivel recuperar o valor da compra!', 1);
            }

            if ('' === $billingCpfCpnj['Identity'] || ! $this->validateCpfCnpj($billingCpfCpnj['Identity'])) {
                $customErrorResponse = LknWcCieloHelper::createCustomErrorResponse(
                    400,
                    '188',
                    'Please enter a valid CPF or CNPJ'
                );
                LknWcCieloHelper::saveTransactionMetadata($order, $customErrorResponse, 'N/A', 'N/A', $fullName, 1, $amount, $currency, 'PIX', $merchantId, $merchantSecret, $merchantOrderId, $order_id, 'N/A', null, 'Pix', 'N/A', $this, 'N/A', 'N/A', 'N/A', 'N/A', 'N/A');
                $order->save();
                throw new Exception(__('Please enter a valid CPF or CNPJ.', 'lkn-wc-gateway-cielo'));
            }

            $response = self::$request->pix_request($fullName, $amount, $billingCpfCpnj, $this, $order, $merchantOrderId);

            if (isset($response['sucess']) && $response['sucess'] === false) {
                LknWcCieloHelper::saveTransactionMetadata($order, $response, 'N/A', 'N/A', $fullName, 1, $amount, $currency, 'PIX', $merchantId, $merchantSecret, $merchantOrderId, $order_id, 'N/A', null, 'Pix', 'N/A', $this, 'N/A', 'N/A', 'N/A', 'N/A', 'N/A');
                $order->save();
                throw new Exception(json_encode($response['response']), 1);
            }
            if (! is_array($response) && ! is_object($response)) {
                LknWcCieloHelper::saveTransactionMetadata($order, $response, 'N/A', 'N/A', $fullName, 1, $amount, $currency, 'PIX', $merchantId, $merchantSecret, $merchantOrderId, $order_id, 'N/A', null, 'Pix', 'N/A', $this, 'N/A', 'N/A', 'N/A', 'N/A', 'N/A');
                $order->save();
                throw new Exception(json_encode($response), 1);
            }
            if (! $response['response']) {
                $customErrorResponse = LknWcCieloHelper::createCustomErrorResponse(
                    400,
                    '184',
                    'Request error, try again!'
                );
                LknWcCieloHelper::saveTransactionMetadata($order, $customErrorResponse, 'N/A', 'N/A', $fullName, 1, $amount, $currency, 'PIX', $merchantId, $merchantSecret, $merchantOrderId, $order_id, 'N/A', null, 'Pix', 'N/A', $this, 'N/A', 'N/A', 'N/A', 'N/A', 'N/A');
                $order->save();
                throw new Exception('Erro na Requisição. Tente novamente!', 1);
            }

            // Validar se os campos essenciais do PIX estão presentes e não vazios
            if (empty($response['response']['qrcodeImage']) || empty($response['response']['qrcodeString'])) {
                $customErrorResponse = LknWcCieloHelper::createCustomErrorResponse(
                    400,
                    '185',
                    'Cielo API returned incomplete PIX data. QR Code information missing.'
                );
                LknWcCieloHelper::saveTransactionMetadata($order, $customErrorResponse, 'N/A', 'N/A', $fullName, 1, $amount, $currency, 'PIX', $merchantId, $merchantSecret, $merchantOrderId, $order_id, 'N/A', null, 'Pix', 'N/A', $this, 'N/A', 'N/A', 'N/A', 'N/A', 'N/A');
                $order->save();
                throw new Exception(__('Error generating PIX: QR Code information is missing. Please try again or contact support.', 'lkn-wc-gateway-cielo'), 1);
            }

            if (! wp_next_scheduled('lkn_schedule_check_free_pix_payment_hook', array($response["response"]["paymentId"], $order_id))) {
                wp_schedule_event(time(), "every_minute", 'lkn_schedule_check_free_pix_payment_hook', array($response["response"]["paymentId"], $order_id));
            }

            $order->update_meta_data('_wc_cielo_qrcode_image', $response['response']['qrcodeImage']);
            $order->update_meta_data('_wc_cielo_qrcode_string', $response['response']['qrcodeString']);
            $order->update_meta_data('_wc_cielo_qrcode_payment_id', $response['response']['paymentId']);

            // Salvar metadados de exibição (padronizado com Credit/Debit)
            $order->update_meta_data('_lkn_used_card_brand', 'PIX');
            $order->update_meta_data('_lkn_used_card_last4', '');
            $order->update_meta_data('_lkn_tid', $response['response']['paymentId']);
            $order->update_meta_data('_lkn_installments', 1);
            $order->update_meta_data('_lkn_card_type', 'Pix');
            if (isset($response['response']['status'])) $order->update_meta_data('_lkn_payment_status', $response['response']['status']);

            LknWcCieloHelper::saveTransactionMetadata($order, $response, $response['response']['qrcodeString'], 'N/A', $fullName, 1, $amount, $currency, 'PIX', $merchantId, $merchantSecret, $merchantOrderId, $order_id, 'N/A', null, 'Pix', 'N/A', $this, 'N/A', 'N/A', 'N/A', 'N/A', $response['response']['paymentId']);
            $order->save();
        } catch (Exception $err) {
            $paymentComplete = false;
            $customErrorResponse = LknWcCieloHelper::createCustomErrorResponse(
                400,
                '184',
                $err->getMessage()
            );
            LknWcCieloHelper::saveTransactionMetadata($order, $customErrorResponse, 'N/A', 'N/A', $fullName, 1, $amount, $currency, 'PIX', $merchantId, $merchantSecret, $merchantOrderId, $order_id, 'N/A', null, 'Pix', 'N/A', $this, 'N/A', 'N/A', 'N/A', 'N/A', 'N/A');
            $this->add_error($err->getMessage());
        }

        if ($paymentComplete) {
            return array(
                'result' => 'success',
                'redirect' => $this->get_return_url($order),
            );
        } else {
            $this->log->log('error', 'PIX Payment failed: ' . var_export($response, true), array('source' => 'woocommerce-cielo-pix'));
            $this->add_notice_once(__('PIX Payment Failed', 'lkn-wc-gateway-cielo'), 'error');

            $customErrorResponse = LknWcCieloHelper::createCustomErrorResponse(
                400,
                '184',
                'PIX Payment Failed'
            );
            LknWcCieloHelper::saveTransactionMetadata($order, $customErrorResponse, 'N/A', 'N/A', $fullName, 1, $amount, $currency, 'PIX', $merchantId, $merchantSecret, $merchantOrderId, $order_id, 'N/A', null, 'Pix', 'N/A', $this, 'N/A', 'N/A', 'N/A', 'N/A', 'N/A');
            $order->save();

            throw new Exception(esc_attr(__('PIX Payment Failed', 'lkn-wc-gateway-cielo')));
        }
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

    public function validateCpfCnpj($cpfCnpj)
    {
        // Remove caracteres especiais
        $cpfCnpj = preg_replace('/[^0-9]/', '', $cpfCnpj);

        // Verifica se é CPF
        if (strlen($cpfCnpj) === 11) {
            // Verifica se todos os dígitos são iguais
            if (preg_match('/(\d)\1{10}/', $cpfCnpj)) {
                return false;
            }

            // Calcula o primeiro dígito verificador
            $sum = 0;
            for ($i = 0; $i < 9; $i++) {
                $sum += (int) ($cpfCnpj[$i]) * (10 - $i);
            }
            $digit1 = ($sum % 11 < 2) ? 0 : 11 - ($sum % 11);

            // Calcula o segundo dígito verificador
            $sum = 0;
            for ($i = 0; $i < 10; $i++) {
                $sum += (int) ($cpfCnpj[$i]) * (11 - $i);
            }
            $digit2 = ($sum % 11 < 2) ? 0 : 11 - ($sum % 11);

            // Verifica se os dígitos verificadores estão corretos
            if ($cpfCnpj[9] == $digit1 && $cpfCnpj[10] == $digit2) {
                return true;
            } else {
                return false;
            }
        }
        // Verifica se é CNPJ
        elseif (strlen($cpfCnpj) === 14) {
            // Verifica se todos os dígitos são iguais
            if (preg_match('/(\d)\1{13}/', $cpfCnpj)) {
                return false;
            }

            // Calcula o primeiro dígito verificador
            $sum = 0;
            $weights = array(5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2);
            for ($i = 0; $i < 12; $i++) {
                $sum += (int) ($cpfCnpj[$i]) * $weights[$i];
            }
            $digit1 = ($sum % 11 < 2) ? 0 : 11 - ($sum % 11);

            // Calcula o segundo dígito verificador
            $sum = 0;
            $weights = array(6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2);
            for ($i = 0; $i < 13; $i++) {
                $sum += (int) ($cpfCnpj[$i]) * $weights[$i];
            }
            $digit2 = ($sum % 11 < 2) ? 0 : 11 - ($sum % 11);

            // Verifica se os dígitos verificadores estão corretos
            if ($cpfCnpj[12] == $digit1 && $cpfCnpj[13] == $digit2) {
                return true;
            } else {
                return false;
            }
        }

        return false;
    }

    public function add_error($message): void
    {
        global $woocommerce;

        $title = '<strong>' . esc_html($this->title) . ':</strong> ';

        if (function_exists('wc_add_notice')) {
            $message = wp_kses($message, array());
            throw new Exception(wp_kses_post("{$title} {$message}"));
        } else {
            $woocommerce->add_error($title . $message);
        }
    }

    // Agendar cron job

    public function payment_gateway_scripts(): void
    {
        // Don't load scripts outside payment page
        if (
            ! is_checkout()
            && ! isset($_GET['pay_for_order']) // wpcs: csrf ok.
            && ! is_add_payment_method_page()
            && ! isset($_GET['change_payment_method']) // wpcs: csrf ok.
            || is_order_received_page()
        ) {
            return;
        }

        // If is not enabled bail.
        if ('yes' !== $this->enabled) {
            return;
        }
    }

    public static function showPix($order_id): void
    {
        $order = wc_get_order($order_id);
        $paymentMethod = $order->get_payment_method();

        if ('lkn_wc_cielo_pix' === $paymentMethod && $order->get_total() > 0) {
            $paymentId = $order->get_meta('_wc_cielo_qrcode_payment_id');
            $bas64Image = $order->get_meta('_wc_cielo_qrcode_image');
            $pixString = $order->get_meta('_wc_cielo_qrcode_string');
            wc_get_template('lkn-cielo-pix-template.php', array(
                'paymentId' => $paymentId,
                'pixString' => $pixString,
                'base64Image' => $bas64Image
            ), 'includes/templates', LKN_WC_GATEWAY_CIELO_DIR . 'includes/templates/');

            wp_enqueue_style('lkn-cielo-wc-payment-pix-style', LKN_WC_GATEWAY_CIELO_URL . 'resources/css/frontend/lkn-cielo-pix-style.css', array(), '1.0.0', 'all');

            wp_enqueue_script('lkn-cielo-wc-payment-pix-script', LKN_WC_GATEWAY_CIELO_URL . 'resources/js/pix/lkn-cielo-pix-script.js', array(), '1.0.0', false);
            wp_localize_script('lkn-cielo-wc-payment-pix-script', 'phpVariables', array(
                'copiedText' => __('Copied!', 'lkn-wc-gateway-cielo'),
                'currentTheme' => wp_get_theme()->get('Name') ?? ''
            ));
        }
    }

    public function process_admin_options()
    {
        static $already_saved = false;

        if ($already_saved) {
            $already_saved = false;
            return false; // Impede a execução múltipla
        }

        $already_saved = true;

        // Obtém o valor antigo diretamente da opção no banco de dados
        $options = get_option('woocommerce_lkn_wc_cielo_pix_settings');
        $old_description = $options['description'] ?? '';
        $old_payment_complete_status = $options['payment_complete_status'] ?? '';
        $old_pix_layout = $options['pix_layout'] ?? '';
        $old_layout_location = $options['layout_location'] ?? '';

        $saved = parent::process_admin_options();

        if ($saved) {

            $new_description = $this->get_option('description');
            if ($new_description !== $old_description && !empty($old_description) && !empty($new_description)) {
                $this->update_option('description', $old_description);

                // Mostra a mensagem de erro
                add_action('admin_notices', function () {
                    echo '<div class="notice notice-error is-dismissible">
                            <p>' . esc_html__('You are not allowed to modify this field (Description).', 'lkn-wc-gateway-cielo') . '</p>
                        </div>';
                });
            } else {
                $this->update_option('description', 'After the purchase is completed, the PIX will be generated and made available for payment!');
            }

            $new_payment_complete_status = $this->get_option('payment_complete_status');
            if ($new_payment_complete_status !== $old_payment_complete_status && !empty($old_payment_complete_status) && !empty($new_payment_complete_status)) {
                $this->update_option('payment_complete_status', $old_payment_complete_status);

                // Mostra a mensagem de erro
                add_action('admin_notices', function () {
                    echo '<div class="notice notice-error is-dismissible">
                            <p>' . esc_html__('You are not allowed to modify this field (Payment Complete Status).', 'lkn-wc-gateway-cielo') . '</p>
                        </div>';
                });
            } else {
                $this->update_option('payment_complete_status', 'processing');
            }

            $new_pix_layout = $this->get_option('pix_layout');
            if ($new_pix_layout !== $old_pix_layout && !empty($old_pix_layout) && !empty($new_pix_layout)) {
                $this->update_option('pix_layout', $old_pix_layout);

                // Mostra a mensagem de erro
                add_action('admin_notices', function () {
                    echo '<div class="notice notice-error is-dismissible">
                            <p>' . esc_html__('You are not allowed to modify this field (Pix Layout).', 'lkn-wc-gateway-cielo') . '</p>
                        </div>';
                });
            } else {
                $this->update_option('pix_layout', 'standard');
            }

            $new_layout_location = $this->get_option('layout_location');

            if ($new_layout_location !== $old_layout_location && !empty($old_layout_location) && !empty($new_layout_location)) {
                $this->update_option('layout_location', $old_layout_location);

                // Mostra a mensagem de erro
                add_action('admin_notices', function () {
                    echo '<div class="notice notice-error is-dismissible">
                            <p>' . esc_html__('You are not allowed to modify this field (Layout Location).', 'lkn-wc-gateway-cielo') . '</p>
                        </div>';
                });
            } else {
                $this->update_option('layout_location', 'bottom');
            }
        }

        return $saved;
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
?>