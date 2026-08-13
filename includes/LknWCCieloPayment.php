<?php

namespace Lkn\WCCieloPaymentGateway\Includes;

if ( ! defined( 'ABSPATH' ) ) exit;

use Lkn\WCCieloPaymentGateway\Includes\LknWCGatewayCieloCredit;
use Lkn\WCCieloPaymentGateway\Includes\LknWCGatewayCieloDebit;
use Lkn\WCCieloPaymentGateway\Includes\LknWCGatewayCieloEndpoint;
use Lkn\WCCieloPaymentGateway\Includes\LknWcCieloHelper;
use Lkn\WCCieloPaymentGateway\Includes\LknWcCieloCreditBlocks;
use Lkn\WCCieloPaymentGateway\Includes\LknWcCieloDebitBlocks;
use Lkn\WCCieloPaymentGateway\Includes\LknWcCieloPix;
use Lkn\WCCieloPaymentGateway\Includes\LknWcCieloPixBlocks;
use Lkn\WCCieloPaymentGateway\Includes\LknWCGatewayCieloGooglePay;
use Lkn\WCCieloPaymentGateway\Includes\LknWCGatewayCieloGooglePayBlocks;

use Lkn\LknCieloApiPro\Includes\LknCieloApiProLicenseHelper;

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://linknacional.com.br
 * @since      1.0.0
 *
 * @package    LknWCCieloPaymentGateway
 * @subpackage LknWCCieloPaymentGateway/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    LknWCCieloPaymentGateway
 * @subpackage LknWCCieloPaymentGateway/includes
 * @author     Link Nacional <contato@linknacional.com>
 */
final class LknWCCieloPayment
{


    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      LknWcCieloPaymentGatewayLoader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * The empty value in base_64.
     *
     * @since    1.26.0
     * @access   protected
     * @var      string    $EMPTY_VALUE    The empty value in base_64.
     */
    protected $EMPTY_VALUE = 'ZW1wdHk=';

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct()
    {
        if (defined('LKN_WC_CIELO_VERSION')) {
            $this->version = LKN_WC_CIELO_VERSION;
        } else {
            $this->version = '1.25.0';
        }
        $this->plugin_name = 'lkn-wc-cielo-payment-gateway';

        $this->load_dependencies();
        $this->loader->add_action('woocommerce_init', $this, 'define_hooks');
    }

    // Gateway classes
    public $lknWcGatewayCieloCredit;
    public $lknWcGatewayCieloDebit;
    public $lknWcCieloPix;
    public $lknWcGatewayCieloGooglePay;
    public $lknWcGatewayCieloEndpoint;
    public $lknWcCieloHelper;

    /**
     * Define os hooks somente quando woocommerce está ativo
     */
    public function define_hooks(): void
    {
        if (class_exists('WooCommerce')) {
            $this->lknWcCieloPix = new LknWcCieloPix();
            $this->define_admin_hooks();
            $this->define_public_hooks();
        } else {
            // Aguarda WooCommerce ser carregado
            $this->loader->add_action('admin_notices', $this, 'woocommerceMissingNotice');
        }
        $this->run();
    }

    public function woocommerceMissingNotice(): void
    {
        deactivate_plugins(plugin_basename(LKN_WC_CIELO_BASE_FILE));
        include_once __DIR__ . '/views/notices/html-notice-woocommerce-missing.php';
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - LknWcCieloPaymentGatewayLoader. Orchestrates the hooks of the plugin.
     * - LknWcCieloPaymentGatewayI18n. Defines internationalization functionality.
     * - LknWcCieloPaymentGatewayAdmin. Defines all hooks for the admin area.
     * - LknWcCieloPaymentGatewayPublic. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies(): void
    {
        $this->loader = new LknWCCieloPaymentLoader();
        $this->lknWcGatewayCieloEndpoint = new LknWcGatewayCieloEndpoint();
        $this->lknWcCieloHelper = new LknWcCieloHelper();
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks(): void
    {
        $this->loader->add_action('admin_notices', $this, 'lkn_admin_notice');
        $this->loader->add_filter('plugin_action_links_' . LKN_WC_CIELO_FILE_BASENAME, $this, 'lkn_wc_cielo_plugin_row_meta', 10, 2);
        $this->loader->add_filter('plugin_action_links_' . LKN_WC_CIELO_FILE_BASENAME, $this, 'lkn_wc_cielo_plugin_row_meta_pro', 10, 2);
        $this->loader->add_action('lkn_schedule_check_free_pix_payment_hook', LknWcCieloRequest::class, 'check_payment', 10, 2);
        $this->loader->add_action('lkn_remove_custom_cron_job_hook', LknWcCieloRequest::class, 'lkn_remove_custom_cron_job', 10, 2);
        
        // Analytics - registra script e menu do WooCommerce Admin
        $this->loader->add_action('admin_enqueue_scripts', $this, 'register_cielo_analytics_script');
        $this->loader->add_filter('woocommerce_analytics_report_menu_items', $this, 'add_cielo_analytics_menu_item');
        
        // Admin settings card for specific sections
        $this->loader->add_action('admin_enqueue_scripts', $this, 'setup_admin_settings_card');
        
        // AJAX hooks for admin functionality
        $this->loader->add_action('wp_ajax_lkn_cielo_clear_order_logs', $this->lknWcGatewayCieloEndpoint, 'ajax_clear_order_logs');
        
        // Partial capture hooks centralized
        $this->loader->add_action('woocommerce_order_item_add_action_buttons', $this, 'add_partial_capture_button');
        $this->loader->add_action('wp_ajax_lkn_cielo_partial_capture', $this, 'handle_partial_capture_ajax');
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks(): void
    {
        $this->loader->add_filter('woocommerce_payment_gateways', $this, 'add_gateway');
        $this->loader->add_action('rest_api_init', $this->lknWcGatewayCieloEndpoint, 'registerOrderCaptureEndPoint');
        $this->loader->add_action('add_meta_boxes', $this->lknWcCieloHelper, 'showOrderLogs');
        $this->loader->add_action('woocommerce_order_details_after_order_table', $this->lknWcCieloPix, 'showPix');
        $this->loader->add_filter('woocommerce_get_order_item_totals', $this, 'new_order_item_totals', 10, 3);
        // WooCommerce Blocks compatibility
        $this->loader->add_action('before_woocommerce_init', $this, 'wcEditorBlocksActive');
        $this->loader->add_action('woocommerce_blocks_payment_method_type_registration', $this, 'wcEditorBlocksAddPaymentMethod');

        $this->loader->add_filter('woocommerce_cart_calculate_fees', $this, 'add_checkout_fee_or_discount_in_credit_card', 10);

        $this->loader->add_action('wp_ajax_lkn_update_payment_fees', $this, 'ajax_update_payment_fees');
        $this->loader->add_action('wp_ajax_nopriv_lkn_update_payment_fees', $this, 'ajax_update_payment_fees');

        $this->loader->add_action('wp_ajax_lkn_update_card_type', $this, 'ajax_update_card_type');
        $this->loader->add_action('wp_ajax_nopriv_lkn_update_card_type', $this, 'ajax_update_card_type');

        $this->loader->add_action('wp_ajax_lkn_get_recent_cielo_orders', $this, 'ajax_get_recent_cielo_orders');
        $this->loader->add_action('wp_ajax_nopriv_lkn_get_recent_cielo_orders', $this, 'ajax_get_recent_cielo_orders');

        $this->loader->add_action('wp_ajax_lkn_dismiss_fraud_notice', $this, 'ajax_dismiss_fraud_notice');

        $this->loader->add_action('woocommerce_review_order_after_order_total', $this, 'display_payment_installment_info');
    }

    /**
     * Handler AJAX para definir parcela na sessão
     */
    public function ajax_update_payment_fees()
    {
        // Verificar se os dados POST existem
        if (!isset($_POST['nonce']) || !isset($_POST['payment_method']) || !isset($_POST['installment'])) {
            wp_send_json_error(array('message' => 'Missing required data'));
        }

        // Verificar nonce
        $nonce = sanitize_text_field(wp_unslash($_POST['nonce']));
        if (!wp_verify_nonce($nonce, 'lkn_payment_fees_nonce')) {
            wp_send_json_error(array('message' => 'Invalid nonce'));
        }

        // Sanitizar dados
        $payment_method = sanitize_text_field(wp_unslash($_POST['payment_method']));
        $installment = sanitize_text_field(wp_unslash($_POST['installment']));

        // Validar método de pagamento
        if (!in_array($payment_method, ['lkn_cielo_debit', 'lkn_cielo_credit'])) {
            wp_send_json_error(array('message' => 'Invalid payment method'));
        }

        // Verificar se WC()->session existe
        if (!WC()->session) {
            wp_send_json_error(array('message' => 'WooCommerce session not available'));
        }

        // Definir parcela na sessão
        WC()->session->set($payment_method . '_installment', $installment);

        // Garante que o método de pagamento escolhido fique na sessão para o cálculo de fees.
        // Necessário no checkout em Blocks (Store API), que não grava 'chosen_payment_method'
        // durante a navegação — só na finalização do pedido. Como $payment_method já foi
        // validado acima para ser apenas 'lkn_cielo_credit' ou 'lkn_cielo_debit', a fee
        // continua restrita aos gateways Cielo.
        WC()->session->set('chosen_payment_method', $payment_method);

        // Verificar e definir tipo de cartão se fornecido
        $response_data = array(
            'message' => 'Installment set successfully',
            'installment' => $installment,
            'payment_method' => $payment_method
        );

        if (isset($_POST['card_type'])) {
            $card_type = sanitize_text_field(wp_unslash($_POST['card_type']));
            
            // Validar tipo de cartão
            if (in_array($card_type, ['Credit', 'Debit'])) {
                WC()->session->set($payment_method . '_card_type', $card_type);
                $response_data['card_type'] = $card_type;
                $response_data['message'] = 'Installment and card type set successfully';
            }
        }

        wp_send_json_success($response_data);
    }

    /**
     * Handler AJAX para definir tipo de cartão na sessão
     */
    public function ajax_update_card_type()
    {
        // Verificar se os dados POST existem
        if (!isset($_POST['nonce']) || !isset($_POST['payment_method']) || !isset($_POST['card_type'])) {
            wp_send_json_error(array('message' => 'Missing required data'));
        }

        // Verificar nonce
        $nonce = sanitize_text_field(wp_unslash($_POST['nonce']));
        if (!wp_verify_nonce($nonce, 'lkn_payment_fees_nonce')) {
            wp_send_json_error(array('message' => 'Invalid nonce'));
        }

        // Sanitizar dados
        $payment_method = sanitize_text_field(wp_unslash($_POST['payment_method']));
        $card_type = sanitize_text_field(wp_unslash($_POST['card_type']));

        // Validar método de pagamento
        if (!in_array($payment_method, ['lkn_cielo_debit', 'lkn_cielo_credit'])) {
            wp_send_json_error(array('message' => 'Invalid payment method'));
        }

        // Validar tipo de cartão
        if (!in_array($card_type, ['Credit', 'Debit'])) {
            wp_send_json_error(array('message' => 'Invalid card type'));
        }

        // Verificar se WC()->session existe
        if (!WC()->session) {
            wp_send_json_error(array('message' => 'WooCommerce session not available'));
        }

        // Definir na sessão
        WC()->session->set($payment_method . '_card_type', $card_type);

        wp_send_json_success(array(
            'message' => 'Card type set successfully',
            'card_type' => $card_type,
            'payment_method' => $payment_method
        ));
    }

    public function add_checkout_fee_or_discount_in_credit_card()
    {
        // Verificar se WooCommerce está ativo e o carrinho existe
        if (!function_exists('WC') || !WC()->cart || !WC()->session) {
            return;
        }

        if (is_plugin_active('lkn-cielo-api-pro/lkn-cielo-api-pro.php')) {
            $licenseResult = base64_decode(get_option('lknCieloProApiLicense', $this->EMPTY_VALUE), true);

            if ('empty' === $licenseResult) {
                if (class_exists('Lkn\LknCieloApiPro\Includes\LknCieloApiProLicenseHelper')) {
                    $licenseResult = LknCieloApiProLicenseHelper::cron_check_license();
                    $licenseResult = $licenseResult ? 'active' : 'inactive';
                } else {
                    return;
                }
                $licenseResult = $licenseResult ? 'active' : 'inactive';
            }
            $licenseResult = ('active' === $licenseResult) ? true : false;

            if ($licenseResult) {
                $chosen_payment_method = WC()->session->get('chosen_payment_method');
                if (isset($chosen_payment_method) && ($chosen_payment_method === 'lkn_cielo_debit' || $chosen_payment_method === 'lkn_cielo_credit')) {
                    $settings = get_option('woocommerce_' . $chosen_payment_method . '_settings', array());

                    if (!is_array($settings) || empty($settings)) {
                        return;
                    }

                    // Para o gateway debit, verificar se o tipo de cartão selecionado é "Credit"
                    // Só aplicar parcelamento/juros se for cartão de crédito
                    if ($chosen_payment_method === 'lkn_cielo_debit') {
                        $card_type = WC()->session->get('lkn_cielo_debit_card_type');
                        // Se não está definido na sessão, assume "Credit" como padrão (estado inicial)
                        // Só bloqueia se explicitamente for "Debit"
                        if (isset($card_type) && $card_type === 'Debit') {
                            return; // Se é débito, não aplica parcelamento/juros
                        }
                    }

                    if (!is_array($settings) || empty($settings)) {
                        return;
                    }

                    $installment_limit = isset($settings['installment_limit']) ? (int) $settings['installment_limit'] : 12;
                    $installment_min = isset($settings['installment_min']) ? (int) $settings['installment_min'] : 5;

                    // Verificar se a chave interest_or_discount existe
                    $interest_or_discount = isset($settings['interest_or_discount']) ? $settings['interest_or_discount'] : '';

                    switch ($interest_or_discount) {
                        case 'discount':
                            if (isset($settings['installment_discount']) && $settings['installment_discount'] === 'yes') {
                                $installment = WC()->session->get($chosen_payment_method . '_installment', '1');
                                if (isset($installment) && $installment > 0) {
                                    $installment_rate_key = $installment . 'x_discount';
                                    $installment_rate = isset($settings[$installment_rate_key]) ? $settings[$installment_rate_key] : 0;

                                    // Verifica se há produtos no carrinho com interesse específico
                                    $product_interest_min = $this->get_cart_products_interest_minimum();
                                    if (isset($product_interest_min) && $product_interest_min > 0) {
                                        $installment_limit = $product_interest_min;
                                    }

                                    if (isset($installment_rate) && $installment_rate > 0 && $installment <= $installment_limit) {

                                        // Calcula o valor base (subtotal + frete, ignorando fees)
                                        $cart_total = $this->get_cart_subtotal_with_shipping();

                                        // Verifica se o valor total atende o mínimo para parcelamento
                                        if ($cart_total >= $installment_min) {
                                            // Calcula os descontos como porcentagem do valor total
                                            $discount_amount = ($cart_total * $installment_rate) / 100;
                                            if ($discount_amount > 0 && WC()->cart) {
                                                try {
                                                    WC()->cart->add_fee(__('Card Discount', 'lkn-wc-gateway-cielo'), -$discount_amount);
                                                } catch (Exception $e) {
                                                }
                                            }
                                        } else {
                                            return;
                                        }
                                    } else {
                                        return;
                                    }
                                } else {
                                    return;
                                }
                                break;
                            }
                            break;
                        case 'interest':
                            if (isset($settings['installment_interest']) && $settings['installment_interest'] === 'yes') {
                                $installment = WC()->session->get($chosen_payment_method . '_installment', '1');
                                if (isset($installment) && $installment > 0) {
                                    $installment_rate_key = $installment . 'x';
                                    $installment_rate = isset($settings[$installment_rate_key]) ? $settings[$installment_rate_key] : 0;

                                    // Verifica se há produtos no carrinho com interesse específico
                                    $product_interest_min = $this->get_cart_products_interest_minimum();
                                    if (isset($product_interest_min) && $product_interest_min > 0) {
                                        $installment_limit = $product_interest_min;
                                    }

                                    if (isset($installment_rate) && $installment_rate > 0 && $installment <= $installment_limit) {

                                        // Calcula o valor base (subtotal + frete, ignorando fees)
                                        $cart_total = $this->get_cart_subtotal_with_shipping();

                                        // Verifica se o valor total atende o mínimo para parcelamento
                                        if ($cart_total >= $installment_min) {
                                            // Calcula os juros como porcentagem do valor total
                                            $interest_amount = ($cart_total * $installment_rate) / 100;
                                            if ($interest_amount > 0 && WC()->cart) {
                                                try {
                                                    WC()->cart->add_fee(__('Card Interest', 'lkn-wc-gateway-cielo'), $interest_amount);
                                                } catch (Exception $e) {
                                                }
                                            }
                                        } else {
                                            return;
                                        }
                                    } else {
                                        return;
                                    }
                                } else {
                                    return;
                                }
                                break;
                            }
                            break;

                        default:
                            break;
                    }
                } else {
                    return;
                }
            }
        } else {
            return;
        }
    }

    /**
     * Exibe informações sobre o pagamento parcelado na revisão do pedido
     */
    public function display_payment_installment_info()
    {
        // Verificar se WooCommerce está ativo e a sessão existe
        if (!function_exists('WC') || !WC()->session) {
            return;
        }

        $chosen_payment_method = WC()->session->get('chosen_payment_method');

        // Verificar se é um método de pagamento Cielo
        if (!in_array($chosen_payment_method, ['lkn_cielo_debit', 'lkn_cielo_credit'])) {
            return;
        }

        // Para o gateway debit, verificar o tipo de cartão
        if ($chosen_payment_method === 'lkn_cielo_debit') {
            $card_type = WC()->session->get('lkn_cielo_debit_card_type');
            // Se é cartão de débito, não exibir informações de parcela
            if (isset($card_type) && $card_type === 'Debit') {
                return;
            }
        }

        // Obter a parcela selecionada da sessão (default 1x quando ainda não definida)
        $installment = WC()->session->get($chosen_payment_method . '_installment', '1');

        if (!$installment || $installment <= 0) {
            return;
        }

        // Obter o total do carrinho
        $cart_total = WC()->cart->get_total('raw');

        if ($cart_total <= 0) {
            return;
        }

        // Obter configurações do gateway para verificar o tipo de juros/desconto
        $settings = get_option('woocommerce_' . $chosen_payment_method . '_settings', array());
        $interest_or_discount = isset($settings['interest_or_discount']) ? $settings['interest_or_discount'] : '';

        // Determinar o nome do método de pagamento
        $payment_method_name = '';
        if ($chosen_payment_method === 'lkn_cielo_credit') {
            $payment_method_name = __('Credit Card', 'lkn-wc-gateway-cielo');
        } elseif ($chosen_payment_method === 'lkn_cielo_debit') {
            $payment_method_name = __('Debit Card', 'lkn-wc-gateway-cielo');
        }

        // Gerar a informação de pagamento e label dinâmico
        if ($installment == 1) {
            $payment_label = __('Payment', 'lkn-wc-gateway-cielo');
            $payment_info = __('Cash Payment', 'lkn-wc-gateway-cielo');
        } else {
            $payment_label = __('Installment', 'lkn-wc-gateway-cielo');
            // Calcular valor da parcela (simples divisão)
            $installment_value = $cart_total / $installment;
            $formatted_value = wc_price($installment_value);

            $payment_info = sprintf(
                // translators: %1$d is the number of installments, %2$s is the installment amount
                __('%1$dx of %2$s', 'lkn-wc-gateway-cielo'),
                $installment,
                $formatted_value
            );
        }

        // Exibir a informação
        echo '<tr>';
        echo '<th>' . esc_html($payment_label) . '</th>';
        echo '<td>' . wp_kses_post($payment_info) . '</td>';
        echo '</tr>';
    }

    /**
     * Obtém o valor mínimo de interesse dos produtos no carrinho
     * 
     * @return float
     */
    public function get_cart_products_interest_minimum()
    {
        $cart = WC()->cart;
        $max_interest_min = 0;

        if (empty($cart)) {
            return $max_interest_min;
        }

        $cart_items = $cart->get_cart();

        foreach ($cart_items as $cart_item_key => $cart_item) {
            $product = $cart_item['data'];
            $product_id = $product->get_id();

            // Verifica se o produto tem a meta 'lknCieloApiProProdutctInterest'
            $product_interest = get_post_meta($product_id, 'lknCieloApiProProdutctInterest', true);

            if (!empty($product_interest) && is_numeric($product_interest)) {
                $interest_value = (int) $product_interest;

                if ($interest_value < $max_interest_min || $max_interest_min === 0) {
                    $max_interest_min = $interest_value;
                }
            }
        }

        return $max_interest_min;
    }

    /**
     * Obtém o valor do subtotal do carrinho mais frete (ignorando todas as fees)
     * 
     * @return float
     */
    public function get_cart_subtotal_with_shipping()
    {
        $cart = WC()->cart;

        if (empty($cart)) {
            return 0;
        }

        $cart_total = $cart->get_subtotal();
        $cart_total += $cart->get_shipping_total();

        return max(0, $cart_total);
    }

    /**
     * Setup admin settings card for specific gateway sections
     *
     * @since    1.0.0
     * @access   public
     */
    public function setup_admin_settings_card(): void
    {
        $page    = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : '';
        $tab     = isset($_GET['tab']) ? sanitize_text_field(wp_unslash($_GET['tab'])) : '';
        $section = isset($_GET['section']) ? sanitize_text_field(wp_unslash($_GET['section'])) : '';

        $sections = [
            'lkn_cielo_credit',
            'lkn_cielo_debit',
            'lkn_wc_cielo_pix',
            'lkn_cielo_pix',
            'lkn_cielo_boleto',
            'lkn_cielo_google_pay'
        ];

        if (
            $page === 'wc-settings' &&
            $tab === 'checkout' &&
            in_array($section, $sections, true)
        ) {
            $versions = 'Plugin Cielo v' . LKN_WC_CIELO_VERSION;
            if (defined('LKN_CIELO_API_PRO_VERSION')) {
                $versions .= ' | Cielo Pro v' . LKN_CIELO_API_PRO_VERSION;
            } else {
                $versions .= ' | WooCommerce v' . WC()->version;
            }

            wp_enqueue_script('lknCieloForWoocommerceCard', LKN_WC_GATEWAY_CIELO_DIR_URL . 'resources/js/admin/lkn-woocommerce-admin-card.js', array('jquery'), LKN_WC_CIELO_VERSION, false);
            wp_enqueue_style('lknCieloForWoocommerceCard', LKN_WC_GATEWAY_CIELO_DIR_URL . 'resources/css/frontend/lkn-woocommerce-admin-card.css', array(), LKN_WC_CIELO_VERSION, 'all');
            wp_enqueue_script('lknCieloForWoocommerceProSettings', LKN_WC_GATEWAY_CIELO_DIR_URL . 'resources/js/admin/lkn-settings-pro-fields.js', array(), LKN_WC_CIELO_VERSION, false);

            wp_localize_script(
                'lknCieloForWoocommerceProSettings',
                'lknCieloProSettingsVars',
                array(
                    'proOnly' => __('Available only in PRO', 'lkn-wc-gateway-cielo'),
                )
            );

            wc_get_template(
                'adminSettingsCard.php',
                array(
                    'backgrounds' => array(
                        'right' => LKN_WC_GATEWAY_CIELO_DIR_URL . 'resources/img/backgroundCardRight.svg',
                        'left' => LKN_WC_GATEWAY_CIELO_DIR_URL . 'resources/img/backgroundCardLeft.svg'
                    ),
                    'logo' => LKN_WC_GATEWAY_CIELO_DIR_URL . 'resources/img/linkNacionalLogo.webp',
                    'whatsapp' => LKN_WC_GATEWAY_CIELO_DIR_URL . 'resources/img/whatsapp-icon.svg',
                    'telegram' => LKN_WC_GATEWAY_CIELO_DIR_URL . 'resources/img/telegram-icon.svg',
                    'versions' => $versions
                ),
                'woocommerce/adminSettingsCard/',
                LKN_WC_GATEWAY_CIELO_DIR . '/includes/templates/'
            );
        }
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run(): void
    {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name()
    {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     * @return    LknWcCieloPaymentGatewayLoader    Orchestrates the hooks of the plugin.
     */
    public function get_loader()
    {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version()
    {
        return $this->version;
    }

    /**
     * Add the Cielo Payment gateway to the list of available gateways.
     *
     * @param array $gateways
     */
    public function add_gateway($methods)
    {
        $lknWcGatewayCieloCreditClass = new LknWcGatewayCieloCredit();
        $lknWcGatewayCieloDebitClass = new LknWcGatewayCieloDebit();
        $lknWcGatewayCieloPixClass = new LknWcCieloPix();
        $lknWcGatewayCieloGooglePayClass = new LknWcGatewayCieloGooglePay();

        array_push($methods, $lknWcGatewayCieloCreditClass);
        array_push($methods, $lknWcGatewayCieloDebitClass);
        array_push($methods, $lknWcGatewayCieloPixClass);
        array_push($methods, $lknWcGatewayCieloGooglePayClass);

        return $methods;
    }

    /**
     * Show admin notice for fraud detection plugin
     */
    public function lkn_admin_notice(): void
    {
        // Verificar se usuario já dispensou a notificação
        $notice_dismissed = get_option('lkn_cielo_fraud_notice_dismissed', 'no');

        if ($notice_dismissed === 'no' && (!file_exists(WP_PLUGIN_DIR . '/fraud-scam-detection-woocommerce/fraud-scam-detection-woocommerce.php') && !file_exists(WP_PLUGIN_DIR . '/fraud-and-scam-detection-for-woocommerce/fraud-scam-detection-woocommerce.php')) && (!is_plugin_active('integration-rede-for-woocommerce/integration-rede-for-woocommerce.php') && !is_plugin_active('woo-rede/integration-rede-for-woocommerce.php'))) {
            // Enfileirar script para dismiss da notificação
            wp_enqueue_script('lkn-cielo-dismiss-notice', LKN_WC_GATEWAY_CIELO_DIR_URL . 'resources/js/admin/lkn-dismiss-fraud-notice.js', array('jquery'), LKN_WC_CIELO_VERSION, true);
            wp_localize_script('lkn-cielo-dismiss-notice', 'lknCieloDismissNotice', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('lkn_dismiss_fraud_notice_nonce'),
                'action' => 'lkn_dismiss_fraud_notice'
            ));
            
            require LKN_WC_GATEWAY_CIELO_DIR . 'includes/views/notices/LknWcCieloDownloadNotice.php';
        }
    }

    /**
     * Declare WooCommerce Blocks compatibility
     */
    public function wcEditorBlocksActive(): void
    {
        if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
                'cart_checkout_blocks',
                LKN_WC_CIELO_FILE,
                true
            );
        }
    }

    /**
     * Register WooCommerce Blocks payment methods
     */
    public function wcEditorBlocksAddPaymentMethod(\Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry): void
    {
        if (! class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
            return;
        }

        $payment_method_registry->register(new LknWcCieloCreditBlocks());
        $payment_method_registry->register(new LknWcCieloDebitBlocks());
        $payment_method_registry->register(new LknWcCieloPixBlocks());
        $payment_method_registry->register(new LknWcGatewayCieloGooglePayBlocks());
    }

    /**
     * Show dependency notice
     */
    public function lkn_wc_cielo_dependency_notice(): void
    {
        echo '<div class="notice notice-error"><p>';
        echo esc_html__('CIELO API PIX, credit card, debit payment for WooCommerce requires WooCommerce to be installed and active.', 'lkn-wc-gateway-cielo');
        echo '</p></div>';
    }


    /**
     * Plugin row meta links.
     *
     * @param array  $plugin_meta an array of the plugin's metadata
     * @param string $plugin_file path to the plugin file, relative to the plugins directory
     *
     * @return array
     */
    public function lkn_wc_cielo_plugin_row_meta($plugin_meta, $plugin_file)
    {
        $new_meta_links['setting'] = sprintf(
            // translators: %1$s is the settings URL, %2$s is the settings text
            '<a href="%1$s">%2$s</a>',
            admin_url('admin.php?page=wc-settings&tab=checkout'),
            __('Settings', 'lkn-wc-gateway-cielo')
        );

        return array_merge($plugin_meta, $new_meta_links);
    }

    /**
     * Plugin row meta pro link.
     */
    public function lkn_wc_cielo_plugin_row_meta_pro($plugin_meta, $plugin_file)
    {
        // Defina o URL e o texto do link
        $url = 'https://www.linknacional.com.br/wordpress/woocommerce/cielo/';
        $link_text = sprintf(
            '<span style="color: red; font-weight: bold;">%s</span>',
            __('Be pro', 'lkn-wc-gateway-cielo')
        );

        // Crie o novo link de meta
        // translators: %1$s is the pro version URL, %2$s is the link text
        $new_meta_link = sprintf('<a href="%1$s">%2$s</a>', $url, $link_text);

        // Adicione o novo link ao array de metadados do plugin
        $plugin_meta[] = $new_meta_link;

        return $plugin_meta;
    }

    /**
     * Show the Installments info in Thank you page.
     *
     * @param array $total_rows
     * @param WC_Order $order
     * @param string $tax_display
     */
    public function new_order_item_totals($total_rows, $order, $tax_display)
    {
        $payment_method = $order->get_payment_method();

        if ($payment_method === 'lkn_cielo_credit' || $payment_method === 'lkn_cielo_debit') {
            $installment = $order->get_meta('installments');
            $payment_id = $order->get_meta('paymentId');
            $order_id = $order->get_id();
            $nsu = $order->get_meta('lkn_nsu');

            // Verifica se é um pagamento Cielo (tem pelo menos payment_id ou nsu)
            $is_cielo_payment = $payment_id || $nsu;

            // Se não é pagamento Cielo, retorna os totais originais
            if (!$is_cielo_payment) {
                return $total_rows;
            }

            // Reconstrói o array com as informações do Cielo
            $order_total_row = isset($total_rows['order_total']) ? $total_rows['order_total'] : array();
            $payment_method_row = isset($total_rows['payment_method']) ? $total_rows['payment_method'] : array();

            if (isset($total_rows['order_total'])) {
                unset($total_rows['order_total']);
            }

            if (isset($total_rows['payment_method'])) {
                unset($total_rows['payment_method']);
            }

            if (!empty($order_total_row)) {
                $total_rows['order_total'] = $order_total_row;
            }

            $total_rows['order_id'] = array(
                'label' => esc_html__('Order ID', 'lkn-wc-gateway-cielo'),
                'value' => $order_id,
            );
            if ($installment) {
                $valorParcela = number_format(($order->get_total() / $installment), 2, ',', '.');
                $total_rows['installment'] = array(
                    'label' => esc_html__('Installment', 'lkn-wc-gateway-cielo'),
                    'value' => $installment . 'x de R$ ' . $valorParcela
                );
            }
            $total_rows['payment_id'] = array(
                'label' => esc_html__('Payment ID', 'lkn-wc-gateway-cielo'),
                'value' => $payment_id ?: 'N/A',
            );
            $total_rows['authorization'] = array(
                'label' => esc_html__('Authorization', 'lkn-wc-gateway-cielo'),
                'value' => $nsu ?: 'N/A',
            );
            $total_rows['payment_method'] = $payment_method_row;
        }

        return $total_rows;
    }

    /**
     * Registra o script JavaScript para analytics do Cielo
     * Usa a versão React compilada via webpack
     */
    public function register_cielo_analytics_script()
    {
        $plugin_pro_is_valid = LknWcCieloHelper::is_pro_license_active();

        // Só carregar em páginas do WooCommerce Admin
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'woocommerce') === false) {
            return;
        }

        // Usar a versão React compilada
        wp_register_script(
            'lkn-cielo-analytics',
            plugin_dir_url(__FILE__) . '../resources/js/analytics/lknCieloAnalyticsCompiled.js',
            array('wp-hooks', 'wp-element', 'wp-i18n', 'wc-components', 'react', 'react-dom'),
            LKN_WC_CIELO_VERSION,
            true
        );

        wp_enqueue_script('lkn-cielo-analytics');

        // Localiza script com dados para AJAX
        wp_localize_script('lkn-cielo-analytics', 'lknCieloAjax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('lkn_cielo_orders_nonce'),
            'action_get_recent_orders' => 'lkn_get_recent_cielo_orders',
            'gateway_brands_url' => plugin_dir_url(__FILE__) . '../resources/assets/gatewayBrands/',
        ));

        wp_localize_script('lkn-cielo-analytics', 'lknCieloAnalytics', array(
            'plugin_license' => $plugin_pro_is_valid ? 'active' : 'inactive',
            'screenshot_url' => plugin_dir_url(__FILE__) . '../resources/img/cielo-transactions.webp',
            'pro_version' => 'https://www.linknacional.com.br/wordpress/woocommerce/cielo/?utm=plugin-cielo-free-transaction',
            'site_domain' => home_url(),
            'version_free' => LKN_WC_CIELO_VERSION,
            'version_pro' => (is_plugin_active('lkn-cielo-api-pro/lkn-cielo-api-pro.php') && defined('LKN_CIELO_API_PRO_VERSION')) ? LKN_CIELO_API_PRO_VERSION : 'N/A'
        ));

        // Registra e enfileira o CSS da versão React
        wp_register_style(
            'lkn-cielo-analytics-style',
            plugin_dir_url(__FILE__) . '../resources/css/frontend/lkn-cielo-analytics-react.css',
            array(),
            LKN_WC_CIELO_VERSION
        );

        wp_enqueue_style('lkn-cielo-analytics-style');

        // Adiciona tradução se necessário
        wp_set_script_translations('lkn-cielo-analytics', 'lkn-wc-gateway-cielo');
    }

    /**
     * Adiciona item personalizado ao menu Analytics do WooCommerce
     * Insere na posição correta antes das configurações
     *
     * @param array $items Lista de itens do menu Analytics
     * @return array Lista modificada com o item Cielo
     */
    public function add_cielo_analytics_menu_item($items)
    {
        $plugin_pro_is_valid = LknWcCieloHelper::is_pro_license_active();

        // Item Cielo Transações
        $cielo_item = array(
            'id'       => 'woocommerce-analytics-cielo-transactions',
            'title'    => __('Cielo Transações', 'lkn-wc-gateway-cielo'),
            'parent'   => 'woocommerce-analytics',
            'path'     => '/analytics/cielo-transactions',
            'icon'     => 'dashicons-chart-bar',
            'position' => 2,
        );

        // Encontrar a posição das configurações para inserir antes
        $settings_key = null;
        foreach ($items as $key => $item) {
            if (isset($item['id']) && $item['id'] === 'woocommerce-analytics-settings') {
                $settings_key = $key;
                break;
            }
        }

        // Se encontrou as configurações, insere antes dela
        if ($settings_key !== null) {
            // Divide o array em duas partes
            $before_settings = array_slice($items, 0, array_search($settings_key, array_keys($items)), true);
            $from_settings = array_slice($items, array_search($settings_key, array_keys($items)), null, true);
            
            // Adiciona o item Cielo entre elas
            $items = $before_settings + ['cielo-transactions' => $cielo_item] + $from_settings;
        } else {
            // Fallback: adiciona no final se não encontrar as configurações
            $items['cielo-transactions'] = $cielo_item;
        }

        return $items;
    }

    /**
     * AJAX handler to get recent Cielo orders with metadata.
     *
     * @since 1.0.0
     */
    public function ajax_get_recent_cielo_orders()
    {
        // Verificar nonce se fornecido
        if (isset($_POST['nonce'])) {
            $nonce = sanitize_text_field(wp_unslash($_POST['nonce']));
            if (!wp_verify_nonce($nonce, 'lkn_cielo_orders_nonce')) {
                wp_send_json_error(array('message' => 'Nonce inválido'));
                return;
            }
        }

        // Verificar se é um usuário com permissões adequadas
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => 'Permissões insuficientes'));
            return;
        }

        // Verificar se o cliente quer resposta em formato TOON
        $response_format = isset($_POST['response_format']) ? sanitize_text_field(wp_unslash($_POST['response_format'])) : 'json';

        // Parâmetros de consulta
        $page = isset($_POST['page']) ? max(1, (int) sanitize_text_field(wp_unslash($_POST['page']))) : 1;
        $query_limit = isset($_POST['query_limit']) ? max(1, min(1000, (int) sanitize_text_field(wp_unslash($_POST['query_limit'])))) : 50;
        $offset = ($page - 1) * $query_limit;

        // Parâmetros de filtros de data - validação adequada
        $start_date = '';
        $end_date = '';
        
        if (isset($_POST['start_date'])) {
            $input_start = sanitize_text_field(wp_unslash($_POST['start_date']));
            // Validar formato de data Y-m-d
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $input_start)) {
                $start_date = $input_start;
            }
        }
        
        if (isset($_POST['end_date'])) {
            $input_end = sanitize_text_field(wp_unslash($_POST['end_date']));
            // Validar formato de data Y-m-d
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $input_end)) {
                $end_date = $input_end;
            }
        }

        // Se não há filtros de data especificados, usar o filtro padrão de "hoje"
        if (empty($start_date) && empty($end_date)) {
            $today = gmdate('Y-m-d');
            $start_date = $today;
            $end_date = $today;
        }

        try {
            global $wpdb;
            
            // PRIMEIRA ETAPA: Buscar TODOS os pedidos que têm dados Cielo no período (sem LIMIT)
            $cielo_order_ids = array();
            
            if (!empty($start_date) && !empty($end_date)) {
                // Consulta com ambos os filtros de data
                $cielo_order_ids = $wpdb->get_col($wpdb->prepare(
                    "SELECT o.id 
                    FROM {$wpdb->prefix}wc_orders o
                    INNER JOIN {$wpdb->prefix}wc_orders_meta om ON o.id = om.order_id
                    WHERE om.meta_key = %s AND om.meta_value != %s
                    AND o.date_created_gmt >= %s AND o.date_created_gmt <= %s
                    ORDER BY o.date_created_gmt DESC, o.id DESC",
                    'lkn_cielo_transaction_data',
                    '',
                    $start_date . ' 00:00:00',
                    $end_date . ' 23:59:59'
                ));
            } elseif (!empty($start_date)) {
                // Consulta apenas com data início
                $cielo_order_ids = $wpdb->get_col($wpdb->prepare(
                    "SELECT o.id 
                    FROM {$wpdb->prefix}wc_orders o
                    INNER JOIN {$wpdb->prefix}wc_orders_meta om ON o.id = om.order_id
                    WHERE om.meta_key = %s AND om.meta_value != %s
                    AND o.date_created_gmt >= %s
                    ORDER BY o.date_created_gmt DESC, o.id DESC",
                    'lkn_cielo_transaction_data',
                    '',
                    $start_date . ' 00:00:00'
                ));
            } elseif (!empty($end_date)) {
                // Consulta apenas com data fim
                $cielo_order_ids = $wpdb->get_col($wpdb->prepare(
                    "SELECT o.id 
                    FROM {$wpdb->prefix}wc_orders o
                    INNER JOIN {$wpdb->prefix}wc_orders_meta om ON o.id = om.order_id
                    WHERE om.meta_key = %s AND om.meta_value != %s
                    AND o.date_created_gmt <= %s
                    ORDER BY o.date_created_gmt DESC, o.id DESC",
                    'lkn_cielo_transaction_data',
                    '',
                    $end_date . ' 23:59:59'
                ));
            } else {
                // Consulta sem filtros de data
                $cielo_order_ids = $wpdb->get_col($wpdb->prepare(
                    "SELECT o.id 
                    FROM {$wpdb->prefix}wc_orders o
                    INNER JOIN {$wpdb->prefix}wc_orders_meta om ON o.id = om.order_id
                    WHERE om.meta_key = %s AND om.meta_value != %s
                    ORDER BY o.date_created_gmt DESC, o.id DESC",
                    'lkn_cielo_transaction_data',
                    ''
                ));
            }
            
            // Se não há pedidos Cielo, retornar resultado vazio
            if (empty($cielo_order_ids)) {
                $response_data = array(
                    'message' => 'Nenhuma transação Cielo encontrada',
                    'orders' => array(),
                    'pagination' => array(
                        'page' => $page,
                        'query_limit' => $query_limit,
                        'total_count' => 0,
                        'total_pages' => 0,
                        'has_next' => false
                    )
                );
                
                if ($response_format === 'toon') {
                    $this->send_toon_response(true, $response_data);
                } else {
                    wp_send_json_success($response_data);
                }
                return;
            }
            
            // SEGUNDA ETAPA: Aplicar paginação aos pedidos Cielo
            $total_cielo_count = count($cielo_order_ids);
            $paginated_order_ids = array_slice($cielo_order_ids, $offset, $query_limit);
            
            // TERCEIRA ETAPA: Processar os pedidos paginados
            $orders_data = array();

            foreach ($paginated_order_ids as $order_id) {
                // Usar função nativa do WooCommerce para pegar o pedido
                $order = wc_get_order($order_id);
                
                if (!$order) {
                    continue; // Pular se o pedido não existe
                }
                
                // Buscar metadados específicos do Cielo
                $transaction_data = $order->get_meta('lkn_cielo_transaction_data');
                $data_format = $order->get_meta('lkn_cielo_data_format') ?: 'json';
                
                // Decodificar dados baseado no formato
                if ($data_format === 'toon') {
                    $transactionData = LknWcCieloHelper::decodeToonData($transaction_data);
                } else {
                    $transactionData = json_decode($transaction_data, true);
                }
                
                // Se decodificou com sucesso, adicionar aos dados
                if ($transactionData && is_array($transactionData)) {
                    $orders_data[] = array(
                        'order_id' => (int) $order_id,
                        'data_format' => $data_format,
                        'transaction_data' => $transactionData
                    );
                }
            }

            $response_data = array(
                'message' => sprintf('Página %d - %d transações Cielo encontradas de %d total', 
                    $page, 
                    count($orders_data), 
                    $total_cielo_count
                ),
                'orders' => $orders_data,
                'pagination' => array(
                    'page' => $page,
                    'query_limit' => $query_limit,
                    'total_count' => (int) $total_cielo_count,
                    'total_pages' => ceil($total_cielo_count / $query_limit),
                    'has_next' => ($page * $query_limit) < $total_cielo_count
                )
            );

            // Enviar resposta no formato solicitado
            if ($response_format === 'toon') {
                $this->send_toon_response(true, $response_data);
            } else {
                wp_send_json_success($response_data);
            }

        } catch (Exception $e) {
            $error_data = array(
                'message' => 'Erro ao buscar pedidos: ' . $e->getMessage()
            );
            
            if ($response_format === 'toon') {
                $this->send_toon_response(false, $error_data);
            } else {
                wp_send_json_error($error_data);
            }
        }
    }

    /**
     * Send AJAX response in TOON format
     *
     * @param bool $success
     * @param array $data
     */
    private function send_toon_response($success, $data)
    {
        // Preparar dados de resposta no formato similar ao wp_send_json
        $response = array(
            'success' => $success,
            'data' => $data
        );

        // Codificar em TOON
        $toon_response = LknWcCieloHelper::encodeToonData($response);

        // Definir headers apropriados
        if (!headers_sent()) {
            header('Content-Type: text/plain; charset=' . get_option('blog_charset'));
            header('X-Content-Type-Options: nosniff');
            header('X-Robots-Tag: noindex');
        }

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- TOON encoded data is already safe encoded format, no escaping needed
        echo $toon_response;
        wp_die('', '', array('response' => null));
    }

    /**
     * Decode transaction data from database string based on format
     *
     * @param string $dataString
     * @param string $format
     * @return array|null
     */
    private function decodeTransactionDataFromDB($dataString, $format)
    {
        if (empty($dataString)) {
            return null;
        }

        try {
            if ($format === 'toon') {
                return LknWcCieloHelper::decodeToonData($dataString);
            } else {
                $decoded = json_decode($dataString, true);
                return is_array($decoded) ? $decoded : null;
            }
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Centralized partial capture button handler
     * Determines the correct gateway based on the order's payment method
     */
    public function add_partial_capture_button($order_id)
    {
        // Guarantee order_id is an integer
        if (is_object($order_id)) {
            $order_id = $order_id->get_id();
        }
        $order_id = intval($order_id);
        
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return;
        }
        
        $payment_method = $order->get_payment_method();
        
        // Only process Cielo gateways
        if (!in_array($payment_method, ['lkn_cielo_credit', 'lkn_cielo_debit'])) {
            return;
        }
        
        // Get the appropriate gateway instance
        $gateway = null;
        if ($payment_method === 'lkn_cielo_credit') {
            $gateway = new LknWCGatewayCieloCredit();
        } elseif ($payment_method === 'lkn_cielo_debit') {
            $gateway = new LknWCGatewayCieloDebit();
        }
        
        if ($gateway && method_exists($gateway, 'add_partial_capture_button')) {
            $gateway->add_partial_capture_button($order_id);
        }
    }

    /**
     * AJAX handler to dismiss fraud detection notice
     */
    public function ajax_dismiss_fraud_notice()
    {
        // Verificar nonce de segurança
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'lkn_dismiss_fraud_notice_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'lkn-wc-gateway-cielo')));
            return;
        }

        // Verificar permissões de usuário
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'lkn-wc-gateway-cielo')));
            return;
        }

        // Atualizar opção para dispensar a notificação
        update_option('lkn_cielo_fraud_notice_dismissed', 'yes');
        $updated = get_option('lkn_cielo_fraud_notice_dismissed') === 'yes';

        if ($updated) {
            wp_send_json_success(array('message' => __('Notice dismissed successfully', 'lkn-wc-gateway-cielo')));
        } else {
            wp_send_json_error(array('message' => __('Failed to dismiss notice', 'lkn-wc-gateway-cielo')));
        }
    }

    /**
     * Centralized partial capture AJAX handler
     * Routes the request to the appropriate gateway based on order payment method
     */
    public function handle_partial_capture_ajax()
    {
        // Get order_id from POST data
        $order_id = isset($_POST['order_id']) ? intval(sanitize_text_field(wp_unslash($_POST['order_id']))) : 0;
        
        if (empty($order_id)) {
            wp_send_json_error(__('Order ID is required', 'lkn-wc-gateway-cielo'));
            return;
        }
        
        $order = wc_get_order($order_id);
        
        if (!$order) {
            wp_send_json_error(__('Order not found', 'lkn-wc-gateway-cielo'));
            return;
        }
        
        $payment_method = $order->get_payment_method();
        
        // Only process Cielo gateways
        if (!in_array($payment_method, ['lkn_cielo_credit', 'lkn_cielo_debit'])) {
            wp_send_json_error(__('Invalid payment gateway', 'lkn-wc-gateway-cielo'));
            return;
        }
        
        // Get the appropriate gateway instance
        $gateway = null;
        if ($payment_method === 'lkn_cielo_credit') {
            $gateway = new LknWCGatewayCieloCredit();
        } elseif ($payment_method === 'lkn_cielo_debit') {
            $gateway = new LknWCGatewayCieloDebit();
        }
        
        if ($gateway && method_exists($gateway, 'handle_partial_capture_ajax')) {
            // Call the gateway specific method
            $gateway->handle_partial_capture_ajax();
        } else {
            wp_send_json_error(__('Gateway method not found', 'lkn-wc-gateway-cielo'));
        }
    }
}
