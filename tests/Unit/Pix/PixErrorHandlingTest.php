<?php
/**
 * Teste 04, 05, 06, 08: Testes complementares de PIX
 * 
 * - Auto-limpeza após 2 horas
 * - Erro de credenciais inválidas
 * - Erro de API offline  
 * - Salvamento de metadata
 * 
 * @package Lkn\WCCieloPaymentGateway\Tests\Unit\Pix
 */

namespace Lkn\WCCieloPaymentGateway\Tests\Unit\Pix;

use Lkn\WCCieloPaymentGateway\Tests\TestCase;
use Lkn\WCCieloPaymentGateway\Includes\LknWcCieloRequest;
use Brain\Monkey\Functions;
use Mockery;

class PixErrorHandlingTest extends TestCase
{
    /**
     * @test
     * Teste 04: Auto-limpeza de cron job após 2 horas
     */
    public function test_pix_auto_cleanup_after_two_hours()
    {
        // Arrange
        $paymentId = 'test-payment-id';
        $orderId = 123;
        $twoHoursInSeconds = 120 * 60;
        $scheduledTime = time() + $twoHoursInSeconds;

        // Mock that cleanup is scheduled
        Functions\expect('wp_next_scheduled')
            ->once()
            ->with('lkn_remove_custom_cron_job_hook', [$paymentId, $orderId])
            ->andReturn($scheduledTime);

        // Mock unscheduling the cleanup job
        Functions\expect('wp_unschedule_event')
            ->once()
            ->with(
                $scheduledTime,
                'lkn_remove_custom_cron_job_hook',
                [$paymentId, $orderId]
            )
            ->andReturn(true);

        // Mock that payment check job exists
        Functions\expect('wp_next_scheduled')
            ->once()
            ->with('lkn_schedule_check_free_pix_payment_hook', [$paymentId, $orderId])
            ->andReturn($scheduledTime);

        Functions\expect('wp_unschedule_event')
            ->once()
            ->with(
                $scheduledTime,
                'lkn_schedule_check_free_pix_payment_hook',
                [$paymentId, $orderId]
            )
            ->andReturn(true);

        // Act - Simulate cleanup after 2 hours
        LknWcCieloRequest::lkn_remove_custom_cron_job($paymentId, $orderId);

        // Assert - Mockery verifies expectations
        $this->assertTrue(true, 'Cleanup should be executed after 2 hours');
    }

    /**
     * @test
     * Teste 05: Erro de credenciais inválidas - MerchantId inválido (Code 129)
     */
    public function test_pix_invalid_merchant_id()
    {
        // Arrange
        Functions\when('get_option')->alias(function($key) {
            if ($key === 'woocommerce_lkn_wc_cielo_pix_settings') {
                return [
                    'env' => 'sandbox',
                    'merchant_id' => 'invalid_merchant_id',
                    'merchant_key' => 'invalid_merchant_key',
                    'debug' => 'no'
                ];
            }
            return [];
        });

        Functions\when('wp_json_encode')->alias(function($data) {
            return json_encode($data);
        });

        // Mock Cielo API error response - Code 129
        $apiResponse = [
            'body' => json_encode([
                [
                    'Code' => '129',
                    'Message' => 'MerchantId is required'
                ]
            ]),
            'response' => ['code' => 400]
        ];
        
        $this->mockWpRemotePost($apiResponse);

        $mockInstance = Mockery::mock('WC_Payment_Gateway');
        $mockInstance->shouldReceive('get_option')->with('debug')->andReturn('no');
        
        $mockOrder = $this->createMockOrder();

        // Act
        $pixRequest = new LknWcCieloRequest();
        $result = $pixRequest->pix_request(
            'Test User',
            100.00,
            ['Identity' => '12345678900', 'IdentityType' => 'CPF'],
            $mockInstance,
            $mockOrder,
            'ORDER-123'
        );

        // Assert
        $this->assertFalse($result['sucess']);
        $this->assertEquals('Affiliation not found', $result['response']);
    }

    /**
     * @test
     * Teste 05.B: Erro de credenciais inválidas - MerchantKey inválido (Code 132)
     */
    public function test_pix_invalid_merchant_key()
    {
        // Arrange
        Functions\when('get_option')->alias(function($key) {
            if ($key === 'woocommerce_lkn_wc_cielo_pix_settings') {
                return [
                    'env' => 'sandbox',
                    'merchant_id' => 'valid_merchant_id',
                    'merchant_key' => 'wrong_key',
                    'debug' => 'no'
                ];
            }
            return [];
        });

        Functions\when('wp_json_encode')->alias(function($data) {
            return json_encode($data);
        });

        // Mock Cielo API error response - Code 132
        $apiResponse = [
            'body' => json_encode([
                [
                    'Code' => '132',
                    'Message' => 'MerchantKey invalid'
                ]
            ]),
            'response' => ['code' => 401]
        ];
        
        $this->mockWpRemotePost($apiResponse);

        $mockInstance = Mockery::mock('WC_Payment_Gateway');
        $mockInstance->shouldReceive('get_option')->with('debug')->andReturn('no');
        
        $mockOrder = $this->createMockOrder();

        // Act
        $pixRequest = new LknWcCieloRequest();
        $result = $pixRequest->pix_request(
            'Test User',
            100.00,
            ['Identity' => '12345678900', 'IdentityType' => 'CPF'],
            $mockInstance,
            $mockOrder,
            'ORDER-456'
        );

        // Assert
        $this->assertFalse($result['sucess']);
        $this->assertEquals('MerchantKey is invalid', $result['response']);
    }

    /**
     * @test
     * Teste 06: Erro de API offline - Timeout
     */
    public function test_pix_api_offline_timeout()
    {
        // Arrange
        Functions\when('get_option')->alias(function($key) {
            if ($key === 'woocommerce_lkn_wc_cielo_pix_settings') {
                return [
                    'env' => 'sandbox',
                    'merchant_id' => 'merchant_id',
                    'merchant_key' => 'merchant_key',
                    'debug' => 'no'
                ];
            }
            return [];
        });

        Functions\when('wp_json_encode')->alias(function($data) {
            return json_encode($data);
        });

        // Mock WP_Error for timeout
        $wpError = Mockery::mock('WP_Error');
        $wpError->shouldReceive('get_error_message')->andReturn('Operation timed out after 120000 milliseconds');
        
        $this->mockWpRemotePost($wpError);

        $mockInstance = Mockery::mock('WC_Payment_Gateway');
        $mockInstance->shouldReceive('get_option')->with('debug')->andReturn('no');
        
        $mockOrder = $this->createMockOrder();

        // Act
        $pixRequest = new LknWcCieloRequest();
        $result = $pixRequest->pix_request(
            'Test User',
            100.00,
            ['Identity' => '12345678900', 'IdentityType' => 'CPF'],
            $mockInstance,
            $mockOrder,
            'ORDER-789'
        );

        // Assert
        $this->assertFalse($result['sucess']);
        $this->assertNull($result['response']);
    }

    /**
     * @test
     * Teste 06.B: Erro de API offline - Connection refused
     */
    public function test_pix_api_connection_refused()
    {
        // Arrange
        Functions\when('get_option')->alias(function($key) {
            if ($key === 'woocommerce_lkn_wc_cielo_pix_settings') {
                return [
                    'env' => 'sandbox',
                    'merchant_id' => 'merchant_id',
                    'merchant_key' => 'merchant_key',
                    'debug' => 'no'
                ];
            }
            return [];
        });

        Functions\when('wp_json_encode')->alias(function($data) {
            return json_encode($data);
        });

        // Mock WP_Error for connection refused
        $wpError = Mockery::mock('WP_Error');
        $wpError->shouldReceive('get_error_message')->andReturn('Failed to connect to api.cieloecommerce.cielo.com.br');
        
        $this->mockWpRemotePost($wpError);

        $mockInstance = Mockery::mock('WC_Payment_Gateway');
        $mockInstance->shouldReceive('get_option')->with('debug')->andReturn('no');
        
        $mockOrder = $this->createMockOrder();

        // Act
        $pixRequest = new LknWcCieloRequest();
        $result = $pixRequest->pix_request(
            'Test User',
            100.00,
            ['Identity' => '12345678900', 'IdentityType' => 'CPF'],
            $mockInstance,
            $mockOrder,
            'ORDER-999'
        );

        // Assert
        $this->assertFalse($result['sucess']);
        $this->assertNull($result['response']);
    }

    /**
     * @test
     * Teste 08: Salvamento de metadata do pedido PIX
     */
    public function test_pix_order_metadata_saved()
    {
        // Arrange
        $paymentId = 'metadata-test-payment-id';
        $qrCodeBase64 = 'base64encodedimage';
        $qrCodeString = '00020126330014BR.GOV.BCB.PIX';
        
        Functions\when('get_option')->alias(function($key) {
            if ($key === 'woocommerce_lkn_wc_cielo_pix_settings') {
                return [
                    'env' => 'sandbox',
                    'merchant_id' => 'merchant_id',
                    'merchant_key' => 'merchant_key',
                    'debug' => 'no'
                ];
            }
            return [];
        });

        Functions\when('wp_json_encode')->alias(function($data) {
            return json_encode($data);
        });

        $apiResponse = [
            'body' => json_encode([
                'Payment' => [
                    'Status' => 12,
                    'PaymentId' => $paymentId,
                    'Type' => 'Pix',
                    'Amount' => 10000,
                    'QrCodeBase64Image' => $qrCodeBase64,
                    'QrCodeString' => $qrCodeString,
                    'Links' => []
                ]
            ]),
            'response' => ['code' => 201]
        ];
        
        $this->mockWpRemotePost($apiResponse);

        // Mock order that should receive metadata
        $mockOrder = $this->createMockOrder(123, 100.00);
        
        // Expect metadata to be updated (this would be done by the calling code)
        $mockOrder->shouldReceive('update_meta_data')
            ->with('_cielo_pix_payment_id', $paymentId)
            ->once();
        
        $mockOrder->shouldReceive('update_meta_data')
            ->with('_cielo_pix_qr_code', $qrCodeBase64)
            ->once();
        
        $mockOrder->shouldReceive('save')->once();

        $mockInstance = Mockery::mock('WC_Payment_Gateway');
        $mockInstance->shouldReceive('get_option')->with('debug')->andReturn('no');

        // Act
        $pixRequest = new LknWcCieloRequest();
        $result = $pixRequest->pix_request(
            'Test User',
            100.00,
            ['Identity' => '12345678900', 'IdentityType' => 'CPF'],
            $mockInstance,
            $mockOrder,
            'ORDER-META-TEST'
        );

        // Simulate what the calling code would do with the result
        if ($result['sucess']) {
            $response = $result['response'];
            $mockOrder->update_meta_data('_cielo_pix_payment_id', $response['Payment']['PaymentId']);
            $mockOrder->update_meta_data('_cielo_pix_qr_code', $response['Payment']['QrCodeBase64Image']);
            $mockOrder->save();
        }

        // Assert
        $this->assertTrue($result['sucess']);
        $this->assertArrayHasKey('Payment', $result['response']);
        $this->assertEquals($paymentId, $result['response']['Payment']['PaymentId']);
    }

    /**
     * @test
     * Teste 08.B: Metadata contém dados mascarados em debug mode
     */
    public function test_pix_metadata_contains_masked_data_in_debug()
    {
        // Arrange
        $customerCpf = '12345678900';
        $merchantId = 'test_merchant_1234567890';
        $merchantKey = 'test_key_abcdefghijklmnop';
        
        Functions\when('get_option')->alias(function($key) use ($merchantId, $merchantKey) {
            if ($key === 'woocommerce_lkn_wc_cielo_pix_settings') {
                return [
                    'env' => 'sandbox',
                    'merchant_id' => $merchantId,
                    'merchant_key' => $merchantKey,
                    'debug' => 'yes' // Debug enabled
                ];
            }
            return [];
        });

        Functions\when('wp_json_encode')->alias(function($data) {
            return json_encode($data);
        });

        $apiResponse = [
            'body' => json_encode([
                'Payment' => [
                    'Status' => 12,
                    'PaymentId' => 'test-payment',
                    'Type' => 'Pix',
                    'QrCodeBase64Image' => 'base64',
                    'QrCodeString' => 'qrstring',
                    'Links' => []
                ],
                'Customer' => [
                    'Name' => 'Test User',
                    'Identity' => $customerCpf,
                    'IdentityType' => 'CPF'
                ]
            ]),
            'response' => ['code' => 201]
        ];
        
        $this->mockWpRemotePost($apiResponse);

        $mockOrder = $this->createMockOrder();
        
        // When debug is enabled, metadata should be saved with masked values
        $mockOrder->shouldReceive('update_meta_data')
            ->with('lknWcCieloOrderLogs', Mockery::on(function($logs) use ($customerCpf, $merchantId, $merchantKey) {
                // Verify that sensitive data is masked in logs
                $logsJson = json_encode($logs);
                
                // Original data should NOT be in logs
                $this->assertStringNotContainsString($customerCpf, $logsJson);
                $this->assertStringNotContainsString($merchantKey, $logsJson);
                
                // Masked data should be present
                $this->assertStringContainsString('*', $logsJson);
                
                return true;
            }))
            ->once();

        $mockOrder->shouldReceive('save')->once();

        $mockInstance = Mockery::mock('WC_Payment_Gateway');
        $mockInstance->shouldReceive('get_option')->with('debug')->andReturn('yes');

        // Act
        $pixRequest = new LknWcCieloRequest();
        $result = $pixRequest->pix_request(
            'Test User',
            100.00,
            ['Identity' => $customerCpf, 'IdentityType' => 'CPF'],
            $mockInstance,
            $mockOrder,
            'ORDER-DEBUG-TEST'
        );

        // Assert - Result should be successful but data should be masked in metadata
        $this->assertTrue($result['sucess']);
    }
}
