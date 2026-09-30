<?php

namespace Lkn\WCCieloPaymentGateway\Tests\Unit\Debit;

use Lkn\WCCieloPaymentGateway\Includes\LknWCGatewayCieloDebit;
use Lkn\WCCieloPaymentGateway\Includes\LknWcCieloHelper;
use PHPUnit\Framework\TestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Tests específicos para cobertura da classe LknWCGatewayCieloDebit
 * Foco nos métodos não cobertos pelos testes existentes
 */
class DebitGatewayTest extends TestCase
{
    private $gateway;
    private $mockLogger;
    private $mockOrder;

    protected function setUp(): void
    {
        parent::setUp();
        \Brain\Monkey\setUp();
        
        // Mock WordPress functions - usando apenas justReturn para evitar problemas
        Functions\when('__')->returnArg();
        Functions\when('_x')->returnArg();
        Functions\when('esc_attr__')->returnArg();
        Functions\when('esc_html__')->returnArg();
        Functions\when('esc_attr')->returnArg();
        Functions\when('wp_kses_post')->returnArg();
        Functions\when('sanitize_text_field')->returnArg();
        Functions\when('wp_unslash')->returnArg();
        Functions\when('plugin_dir_url')->justReturn('http://localhost/wp-content/plugins/test/');
        Functions\when('get_option')->justReturn([]);
        Functions\when('get_post')->justReturn(null);
        Functions\when('has_block')->justReturn(false);
        Functions\when('is_checkout')->justReturn(false);
        Functions\when('wp_enqueue_script')->justReturn('');
        Functions\when('wp_localize_script')->justReturn('');
        Functions\when('add_filter')->justReturn('');
        Functions\when('add_action')->justReturn('');
        Functions\when('get_plugins')->justReturn([]);
        Functions\when('wp_create_nonce')->justReturn('test_nonce');
        Functions\when('wp_verify_nonce')->justReturn(true);
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('wc_add_notice')->justReturn('');
        Functions\when('wc_has_notice')->justReturn(false);
        Functions\when('wp_remote_post')->justReturn([
            'body' => '{"access_token":"test_token_123","token_type":"Bearer"}'
        ]);
        Functions\when('wp_remote_get')->justReturn([
            'body' => '{"Payment":{"Status":2,"CapturedAmount":10000}}'
        ]);
        Functions\when('is_wp_error')->justReturn(false);
        Functions\when('wp_remote_retrieve_body')->returnArg();
        Functions\when('home_url')->justReturn('http://localhost');
        // Mock apply_filters com comportamento específico
        Functions\when('apply_filters')->alias(function($hook, $value = null) {
            if ($hook === 'lkn_wc_cielo_get_custom_configs') {
                return []; // Retorna array vazio para custom configs
            }
            if ($hook === 'lkn_wc_cielo_debit_add_support') {
                return is_array($value) ? $value : ['products'];
            }
            if ($hook === 'lkn_wc_cielo_gateway_icon') {
                return '';
            }
            return $value; // Retorna o valor original para outros hooks
        });
        Functions\when('wc_get_template')->justReturn('');

        // Mock constantes se não estiverem definidas
        if (!defined('LKN_WC_CIELO_VERSION')) {
            define('LKN_WC_CIELO_VERSION', '1.0.0');
        }
        if (!defined('LKN_WC_CIELO_WPP_NUMBER')) {
            define('LKN_WC_CIELO_WPP_NUMBER', '5511999999999');
        }

        // Mock WC_Logger
        $this->mockLogger = Mockery::mock('WC_Logger');
        $this->mockLogger->shouldReceive('log')->andReturn('');

        // Mock WooCommerce classes globais
        Functions\when('WC')->justReturn((object)[
            'cart' => (object)[
                'get_subtotal' => function() { return 100.0; },
                'get_shipping_total' => function() { return 10.0; },
                'get_total_tax' => function() { return 5.0; },
                'get_discount_total' => function() { return 5.0; },
                'get_fees' => function() { return []; }
            ]
        ]);

        // Mock order
        $this->mockOrder = Mockery::mock('WC_Order');
        $this->mockOrder->shouldReceive('get_billing_first_name')->andReturn('John');
        $this->mockOrder->shouldReceive('get_billing_last_name')->andReturn('Doe');
        $this->mockOrder->shouldReceive('get_total')->andReturn(100.00);
        $this->mockOrder->shouldReceive('get_currency')->andReturn('BRL');
        $this->mockOrder->shouldReceive('update_meta_data')->andReturn('');
        $this->mockOrder->shouldReceive('add_order_note')->andReturn('');
        $this->mockOrder->shouldReceive('save')->andReturn('');
        $this->mockOrder->shouldReceive('get_transaction_id')->andReturn('');
        $this->mockOrder->shouldReceive('set_transaction_id')->andReturn('');
        $this->mockOrder->shouldReceive('get_id')->andReturn(123);

        Functions\when('wc_get_order')->justReturn($this->mockOrder);

        // Mock LknWcCieloHelper
        Mockery::mock('alias:' . LknWcCieloHelper::class)
            ->shouldReceive('getIconUrl')->andReturn('http://test.com/icon.png')
            ->shouldReceive('is_pro_license_active')->andReturn(false);

        // Instanciar gateway
        $this->gateway = new LknWCGatewayCieloDebit();
    }

    protected function tearDown(): void
    {
        \Brain\Monkey\tearDown();
        Mockery::close();
        parent::tearDown();
    }

    public function test_gateway_constructor_sets_basic_properties()
    {
        $this->assertEquals('lkn_cielo_debit', $this->gateway->id);
        $this->assertTrue($this->gateway->has_fields);
        $this->assertContains('products', $this->gateway->supports);
        $this->assertIsString($this->gateway->method_title);
        $this->assertIsString($this->gateway->method_description);
    }

    public function test_init_form_fields_creates_all_required_fields()
    {
        $this->gateway->init_form_fields();
        $fields = $this->gateway->form_fields;

        // Verificar campos essenciais
        $requiredFields = [
            'enabled', 'title', 'description', 'merchant_id', 'merchant_key', 
            'env', 'debug', 'checkout_layout_fake'
        ];

        foreach ($requiredFields as $field) {
            $this->assertArrayHasKey($field, $fields, "Campo '$field' deve existir");
        }

        // Verificar tipos de campo
        $this->assertEquals('checkbox', $fields['enabled']['type']);
        $this->assertEquals('text', $fields['title']['type']);
        $this->assertEquals('textarea', $fields['description']['type']);
        $this->assertEquals('password', $fields['merchant_id']['type']);
        $this->assertEquals('password', $fields['merchant_key']['type']);
        $this->assertEquals('select', $fields['env']['type']);
    }

    public function test_generate_debit_auth_token_success()
    {
        // Mock successful API response as string JSON
        Functions\when('wp_remote_post')->justReturn([
            'body' => '{"access_token":"test_token_123","token_type":"Bearer","expires_in":3600}'
        ]);

        $result = $this->gateway->generate_debit_auth_token();

        $this->assertIsArray($result);
        $this->assertEquals('test_token_123', $result['access_token']);
        $this->assertEquals('Bearer', $result['token_type']);
    }

    public function test_generate_debit_auth_token_failure()
    {
        // Mock failed API response as string JSON
        Functions\when('wp_remote_post')->justReturn([
            'body' => '{"error":"invalid_client","error_description":"Client authentication failed"}'
        ]);

        $result = $this->gateway->generate_debit_auth_token();

        $this->assertNull($result);
    }

    public function test_payment_fields_renders_without_errors()
    {
        ob_start();
        $this->gateway->payment_fields();
        $output = ob_get_clean();

        // Deve executar sem erros (mesmo que não renderize HTML em ambiente de teste)
        $this->assertIsString($output);
    }

    public function test_validate_card_holder_name_valid()
    {
        $reflection = new \ReflectionMethod($this->gateway, 'validate_card_holder_name');
        $reflection->setAccessible(true);

        $result = $reflection->invoke($this->gateway, 'John Doe', false);
        $this->assertTrue($result);
    }

    public function test_validate_card_holder_name_invalid()
    {
        $reflection = new \ReflectionMethod($this->gateway, 'validate_card_holder_name');
        $reflection->setAccessible(true);

        // Nome muito curto
        $result = $reflection->invoke($this->gateway, 'Jo', false);
        $this->assertFalse($result);

        // Nome com números
        $result = $reflection->invoke($this->gateway, 'John123', false);
        $this->assertFalse($result);

        // Nome vazio
        $result = $reflection->invoke($this->gateway, '', false);
        $this->assertFalse($result);
    }

    public function test_validate_card_number_valid_visa()
    {
        $reflection = new \ReflectionMethod($this->gateway, 'validate_card_number');
        $reflection->setAccessible(true);

        $result = $reflection->invoke($this->gateway, '4532015112830366', false);
        $this->assertTrue($result);
    }

    public function test_validate_card_number_valid_mastercard()
    {
        $reflection = new \ReflectionMethod($this->gateway, 'validate_card_number');
        $reflection->setAccessible(true);

        $result = $reflection->invoke($this->gateway, '5555555555554444', false);
        $this->assertTrue($result);
    }

    public function test_validate_card_number_invalid()
    {
        $reflection = new \ReflectionMethod($this->gateway, 'validate_card_number');
        $reflection->setAccessible(true);

        // Número inválido
        $result = $reflection->invoke($this->gateway, '1234567890123456', false);
        $this->assertFalse($result);

        // Começando com zero
        $result = $reflection->invoke($this->gateway, '0532015112830366', false);
        $this->assertFalse($result);

        // Muito curto
        $result = $reflection->invoke($this->gateway, '123456', false);
        $this->assertFalse($result);
    }

    public function test_validate_exp_date_valid()
    {
        $reflection = new \ReflectionMethod($this->gateway, 'validate_exp_date');
        $reflection->setAccessible(true);

        // Data futura válida
        $futureYear = date('Y') + 2;
        $result = $reflection->invoke($this->gateway, "12/{$futureYear}", false);
        $this->assertTrue($result);
    }

    public function test_validate_exp_date_invalid()
    {
        $reflection = new \ReflectionMethod($this->gateway, 'validate_exp_date');
        $reflection->setAccessible(true);

        // Data passada
        $result = $reflection->invoke($this->gateway, '01/2020', false);
        $this->assertFalse($result);

        // Formato inválido
        $result = $reflection->invoke($this->gateway, '13/2025', false); // Mês inválido
        $this->assertFalse($result);

        // Formato errado
        $result = $reflection->invoke($this->gateway, '12-2025', false);
        $this->assertFalse($result);
    }

    public function test_validate_cvv_valid()
    {
        $reflection = new \ReflectionMethod($this->gateway, 'validate_cvv');
        $reflection->setAccessible(true);

        $result = $reflection->invoke($this->gateway, '123', false);
        $this->assertTrue($result);

        $result = $reflection->invoke($this->gateway, '1234', false); // Amex
        $this->assertTrue($result);
    }

    public function test_validate_cvv_invalid()
    {
        $reflection = new \ReflectionMethod($this->gateway, 'validate_cvv');
        $reflection->setAccessible(true);

        // Muito curto
        $result = $reflection->invoke($this->gateway, '12', false);
        $this->assertFalse($result);

        // Muito longo
        $result = $reflection->invoke($this->gateway, '12345', false);
        $this->assertFalse($result);

        // Com letras
        $result = $reflection->invoke($this->gateway, '12a', false);
        $this->assertFalse($result);
    }

    public function test_validate_fields_with_valid_data()
    {
        $_POST['nonce_lkn_cielo_debit'] = 'valid_nonce';
        $_POST['lkn_dc_ccno'] = '4532015112830366';
        $_POST['lkn_dc_expdate'] = '12/2025';
        $_POST['lkn_dc_cvc'] = '123';
        $_POST['lkn_dc_ccname'] = 'John Doe';

        $result = $this->gateway->validate_fields();
        $this->assertTrue($result);
    }

    public function test_validate_fields_with_invalid_nonce()
    {
        Functions\when('wp_verify_nonce')->justReturn(false);

        $_POST['nonce_lkn_cielo_debit'] = 'invalid_nonce';

        $result = $this->gateway->validate_fields();
        $this->assertFalse($result);
    }

    public function test_validate_fields_with_missing_data()
    {
        $_POST['nonce_lkn_cielo_debit'] = 'valid_nonce';
        // Deixar campos obrigatórios vazios

        $result = $this->gateway->validate_fields();
        $this->assertFalse($result);
    }

    public function test_get_subtotal_plus_shipping()
    {
        $reflection = new \ReflectionMethod($this->gateway, 'get_subtotal_plus_shipping');
        $reflection->setAccessible(true);

        $result = $reflection->invoke($this->gateway);
        $this->assertEquals(110.0, $result); // 100 subtotal + 10 shipping
    }

    public function test_get_taxes_total()
    {
        $reflection = new \ReflectionMethod($this->gateway, 'get_taxes_total');
        $reflection->setAccessible(true);

        $result = $reflection->invoke($this->gateway);
        $this->assertEquals(5.0, $result);
    }

    public function test_get_discounts_total()
    {
        $reflection = new \ReflectionMethod($this->gateway, 'get_discounts_total');
        $reflection->setAccessible(true);

        $result = $reflection->invoke($this->gateway);
        $this->assertEquals(5.0, $result);
    }

    public function test_get_fees_total()
    {
        $reflection = new \ReflectionMethod($this->gateway, 'get_fees_total');
        $reflection->setAccessible(true);

        $result = $reflection->invoke($this->gateway);
        $this->assertEquals(0.0, $result); // Sem fees mockadas
    }

    public function test_add_notice_once()
    {
        Functions\expect('wc_add_notice')
            ->once()
            ->with('Test message', 'error');

        $this->gateway->add_notice_once('Test message', 'error');
    }

    public function test_add_gateway_name_to_notes()
    {
        $note_data = [
            'comment_content' => 'Test note'
        ];
        $args = [
            'order_id' => 123
        ];

        $result = $this->gateway->add_gateway_name_to_notes($note_data, $args);

        $this->assertStringContainsString('[lkn_cielo_debit]', $result['comment_content']);
    }

    public function test_process_refund_with_valid_data()
    {
        // Mock successful refund response as string JSON
        Functions\when('wp_remote_put')->justReturn([
            'body' => '{"Status":2,"ReasonCode":0,"ReasonMessage":"Successful","ProviderReturnCode":0,"ProviderReturnMessage":"Operation Successful","Links":[]}'
        ]);

        $this->mockOrder->shouldReceive('get_transaction_id')->andReturn('test_payment_id');

        $result = $this->gateway->process_refund(123, 50.00, 'Test refund');

        $this->assertTrue($result);
    }

    public function test_process_refund_without_transaction_id()
    {
        $this->mockOrder->shouldReceive('get_transaction_id')->andReturn('');

        $result = $this->gateway->process_refund(123, 50.00, 'Test refund');

        $this->assertInstanceOf('WP_Error', $result);
    }

    public function test_process_refund_api_error()
    {
        Functions\when('wp_remote_put')->justReturn([
            'body' => '{"Status":0,"ReasonCode":99,"ReasonMessage":"General Error"}'
        ]);

        $this->mockOrder->shouldReceive('get_transaction_id')->andReturn('test_payment_id');

        $result = $this->gateway->process_refund(123, 50.00, 'Test refund');

        $this->assertInstanceOf('WP_Error', $result);
    }

    public function test_admin_load_script_enqueues_assets()
    {
        Functions\expect('wp_enqueue_script')
            ->atLeast()
            ->once();

        $_GET['page'] = 'wc-settings';
        $_GET['tab'] = 'checkout';
        $_GET['section'] = 'lkn_cielo_debit';

        $this->gateway->admin_load_script();

        // Se não jogar exceção, scripts foram enfileirados
        $this->assertTrue(true);
    }

    public function test_process_admin_options()
    {
        $_POST['woocommerce_lkn_cielo_debit_checkout_layout_fake'] = 'modern';

        Functions\expect('update_option')->atLeast()->once();

        $result = $this->gateway->process_admin_options();

        $this->assertTrue($result);
        $this->assertEquals('standard', $_POST['woocommerce_lkn_cielo_debit_checkout_layout_fake']);
    }

    public function test_add_partial_capture_button()
    {
        $_GET['post'] = '123';
        $_GET['action'] = 'edit';

        ob_start();
        $this->gateway->add_partial_capture_button(123);
        $output = ob_get_clean();

        // Deve executar sem erros
        $this->assertIsString($output);
    }

    public function test_admin_options_uses_universal_template()
    {
        if (!defined('LKN_WC_CIELO_VERSION')) {
            $this->markTestSkipped('Universal template not available');
        }

        ob_start();
        $this->gateway->admin_options();
        $output = ob_get_clean();

        $this->assertIsString($output);
    }

    public function test_gateway_supports_required_features()
    {
        $this->assertContains('products', $this->gateway->supports);

        // Test filter application
        $filtered_supports = apply_filters('lkn_wc_cielo_debit_add_support', $this->gateway->supports);
        $this->assertIsArray($filtered_supports);
    }

    public function test_process_payment_with_invalid_order()
    {
        Functions\when('wc_get_order')->justReturn(false);

        $result = $this->gateway->process_payment(999);

        $this->assertArrayHasKey('result', $result);
        $this->assertEquals('failure', $result['result']);
    }

    public function test_process_payment_validation_failure()
    {
        // Mock validation failure
        $_POST['nonce_lkn_cielo_debit'] = 'invalid';
        Functions\when('wp_verify_nonce')->justReturn(false);

        $result = $this->gateway->process_payment(123);

        $this->assertArrayHasKey('result', $result);
        $this->assertEquals('failure', $result['result']);
    }
}