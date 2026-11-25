<?php

declare(strict_types=1);

namespace Lkn\WCCieloPaymentGateway\Tests\Unit\Hooks;

use Brain\Monkey;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use Lkn\WCCieloPaymentGateway\Includes\LknWCCieloPayment;
use Lkn\WCCieloPaymentGateway\Includes\LknWcCieloHelper;
use Lkn\WCCieloPaymentGateway\Includes\LknWCGatewayCieloCredit;
use Lkn\WCCieloPaymentGateway\Includes\LknWCGatewayCieloDebit;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Reembolso automático no cancelamento (recurso PRO demonstrado no plano free).
 *
 * Cobre o que o plugin gratuito precisa entregar:
 *  - campo "fake" com selo PRO nos gateways de crédito e débito;
 *  - filtro de capacidade usado pelo PRO para estornar sem sessão de admin;
 *  - reset da opção real ao salvar sem licença ativa.
 */
class AutoRefundOnCancelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        if (! defined('ABSPATH')) {
            define('ABSPATH', '/tmp/');
        }

        if (! defined('LKN_WC_CIELO_VERSION')) {
            define('LKN_WC_CIELO_VERSION', '1.38.3');
        }

        if (! class_exists('WC_Payment_Gateway')) {
            eval('class WC_Payment_Gateway {
                public $id = "test_gateway";
                public $settings = array();
                public $form_fields = array();
                public function __construct() {}
                public function get_option($key, $default = "") {
                    return isset($this->settings[$key]) ? $this->settings[$key] : $default;
                }
                public function init_settings() {}
            }');
        }

        Functions\when('__')->returnArg();
        Functions\when('_x')->returnArg();
        Functions\when('esc_attr__')->returnArg();
        Functions\when('get_option')->justReturn(array());
        Functions\when('is_plugin_active')->justReturn(false);
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    /**
     * @test
     */
    public function credit_fake_fields_expose_auto_refund_with_pro_badge(): void
    {
        $fields = $this->buildFakeFields(LknWCGatewayCieloCredit::class);

        $this->assertArrayHasKey('auto_refund_on_cancel_fake', $fields);

        $field = $fields['auto_refund_on_cancel_fake'];

        $this->assertSame('checkbox', $field['type']);
        $this->assertSame('no', $field['default']);
        $this->assertSame('true', $field['custom_attributes']['lkn-pro-badge']);
    }

    /**
     * @test
     */
    public function debit_fake_fields_expose_auto_refund_with_pro_badge(): void
    {
        $fields = $this->buildFakeFields(LknWCGatewayCieloDebit::class);

        $this->assertArrayHasKey('auto_refund_on_cancel_fake', $fields);

        $field = $fields['auto_refund_on_cancel_fake'];

        $this->assertSame('checkbox', $field['type']);
        $this->assertSame('no', $field['default']);
        $this->assertSame('true', $field['custom_attributes']['lkn-pro-badge']);
    }

    /**
     * @test
     */
    public function refund_capability_is_filterable_for_automatic_refunds(): void
    {
        Functions\when('current_user_can')->justReturn(false);

        Filters\expectApplied('lkn_wc_cielo_user_can_refund')
            ->once()
            ->with(false, 123)
            ->andReturn(true);

        $this->assertTrue(LknWcCieloHelper::currentUserCanRefund(123));
    }

    /**
     * @test
     */
    public function refund_capability_follows_admin_permission_by_default(): void
    {
        Functions\when('current_user_can')->justReturn(false);

        $this->assertFalse(LknWcCieloHelper::currentUserCanRefund(55));

        Functions\when('current_user_can')->justReturn(true);

        $this->assertTrue(LknWcCieloHelper::currentUserCanRefund(55));
    }

    /**
     * @test
     */
    public function saving_without_license_resets_auto_refund_option(): void
    {
        $core = (new ReflectionClass(LknWCCieloPayment::class))->newInstanceWithoutConstructor();

        $settings = $core->enforce_pro_features_only(array(
            'auto_refund_on_cancel' => 'yes',
            'auto_refund_on_cancel_fake' => 'yes',
        ));

        $this->assertSame('no', $settings['auto_refund_on_cancel']);
        $this->assertArrayNotHasKey('auto_refund_on_cancel_fake', $settings);
    }

    private function buildFakeFields(string $gatewayClass): array
    {
        $gateway = (new ReflectionClass($gatewayClass))->newInstanceWithoutConstructor();
        $gateway->settings = array('installment_payment' => 'no');

        $method = new \ReflectionMethod($gateway, 'get_fake_pro_fields');
        $method->setAccessible(true);

        return $method->invoke($gateway, array());
    }
}
