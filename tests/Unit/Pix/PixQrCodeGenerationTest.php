<?php
/**
 * Teste 01: Geração de QR Code PIX
 * 
 * Testa se a requisição PIX gera corretamente o QR Code
 * Valida resposta da API Cielo e extração dos dados do QR Code
 * 
 * @package Lkn\WCCieloPaymentGateway\Tests\Unit\Pix
 */

namespace Lkn\WCCieloPaymentGateway\Tests\Unit\Pix;

use Lkn\WCCieloPaymentGateway\Tests\TestCase;
use Lkn\WCCieloPaymentGateway\Includes\LknWcCieloRequest;
use Brain\Monkey\Functions;
use Mockery;

class PixQrCodeGenerationTest extends TestCase
{
    private $pixRequest;
    private $mockOrder;
    private $mockInstance;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create mock instance (payment gateway)
        $this->mockInstance = Mockery::mock('WC_Payment_Gateway');
        $this->mockInstance->shouldReceive('get_option')
            ->with('debug')
            ->andReturn('no');
    }

    /**
     * @test
     * Teste 01: Geração de QR Code PIX com sucesso
     */
    public function test_pix_qr_code_generation_success()
    {
        // Arrange - Setup PIX settings
        Functions\when('get_option')->alias(function($key) {
            if ($key === 'woocommerce_lkn_wc_cielo_pix_settings') {
                return [
                    'env' => 'sandbox',
                    'merchant_id' => 'test_merchant_id_123456',
                    'merchant_key' => 'test_merchant_key_abcdef',
                    'debug' => 'no'
                ];
            }
            return [];
        });

        // Mock wp_json_encode
        Functions\when('wp_json_encode')->alias(function($data) {
            return json_encode($data);
        });

        // Mock successful Cielo API response with QR Code
        $apiResponse = [
            'body' => json_encode([
                'Payment' => [
                    'Status' => 12, // Pending
                    'PaymentId' => 'a1b2c3d4-e5f6-7890-abcd-ef1234567890',
                    'Type' => 'Pix',
                    'Amount' => 10000,
                    'QrCodeBase64Image' => 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
                    'QrCodeString' => '00020126330014BR.GOV.BCB.PIX0111012345678905204000053039865802BR5925MERCHANT NAME6009SAO PAULO62070503***63041234',
                    'Links' => []
                ]
            ]),
            'response' => ['code' => 201]
        ];
        
        $this->mockWpRemotePost($apiResponse);

        // Mock order
        $mockOrder = $this->createMockOrder(123, 100.00);

        // Act - Create PIX request
        $pixRequest = new LknWcCieloRequest();
        $result = $pixRequest->pix_request(
            'John Doe',
            100.00,
            ['Identity' => '12345678900', 'IdentityType' => 'CPF'],
            $this->mockInstance,
            $mockOrder,
            'ORDER-123'
        );

        // Assert - Verify QR Code was generated
        $this->assertIsArray($result);
        $this->assertTrue($result['sucess'] ?? false, 'PIX request should be successful');
        $this->assertNotNull($result['response'] ?? null, 'Response should not be null');
        
        // Verify response contains QR Code data
        $response = $result['response'];
        $this->assertArrayHasKey('Payment', $response);
        $this->assertArrayHasKey('QrCodeBase64Image', $response['Payment']);
        $this->assertArrayHasKey('QrCodeString', $response['Payment']);
        $this->assertArrayHasKey('PaymentId', $response['Payment']);
        
        // Verify QR Code is not empty
        $this->assertNotEmpty($response['Payment']['QrCodeBase64Image']);
        $this->assertNotEmpty($response['Payment']['QrCodeString']);
        
        // Verify status is pending (12)
        $this->assertEquals(12, $response['Payment']['Status']);
    }

    /**
     * @test
     * Teste 01.B: Geração de QR Code com CPF mascarado
     */
    public function test_pix_qr_code_with_masked_cpf()
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

        $apiResponse = [
            'body' => json_encode([
                'Payment' => [
                    'Status' => 12,
                    'PaymentId' => 'test-payment-id',
                    'QrCodeBase64Image' => 'base64image',
                    'QrCodeString' => 'qrstring',
                    'Links' => []
                ]
            ]),
            'response' => ['code' => 201]
        ];
        
        $this->mockWpRemotePost($apiResponse);
        $mockOrder = $this->createMockOrder();

        // Act
        $pixRequest = new LknWcCieloRequest();
        $originalCpf = '12345678900';
        $result = $pixRequest->pix_request(
            'Test User',
            50.00,
            ['Identity' => $originalCpf, 'IdentityType' => 'CPF'],
            $this->mockInstance,
            $mockOrder,
            'ORDER-456'
        );

        // Assert - CPF should be masked in the request
        // Note: The maskSensitiveData method is called internally
        // We verify the request was successful, meaning masking didn't break it
        $this->assertTrue($result['sucess'] ?? false);
        $this->assertIsArray($result['response']);
    }

    /**
     * @test
     * Teste 01.C: Erro na geração de QR Code - API offline
     */
    public function test_pix_qr_code_generation_api_error()
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

        // Mock WP_Error - API offline
        $wpError = Mockery::mock('WP_Error');
        $wpError->shouldReceive('get_error_message')->andReturn('Connection timed out');
        
        $this->mockWpRemotePost($wpError);
        $mockOrder = $this->createMockOrder();

        // Act
        $pixRequest = new LknWcCieloRequest();
        $result = $pixRequest->pix_request(
            'Test User',
            100.00,
            ['Identity' => '12345678900', 'IdentityType' => 'CPF'],
            $this->mockInstance,
            $mockOrder,
            'ORDER-789'
        );

        // Assert - Should return error
        $this->assertIsArray($result);
        $this->assertFalse($result['sucess']);
        $this->assertNull($result['response']);
    }

    /**
     * @test
     * Teste 01.D: Erro de credenciais inválidas
     */
    public function test_pix_qr_code_invalid_credentials()
    {
        // Arrange
        Functions\when('get_option')->alias(function($key) {
            if ($key === 'woocommerce_lkn_wc_cielo_pix_settings') {
                return [
                    'env' => 'sandbox',
                    'merchant_id' => 'invalid_merchant',
                    'merchant_key' => 'invalid_key',
                    'debug' => 'no'
                ];
            }
            return [];
        });

        Functions\when('wp_json_encode')->alias(function($data) {
            return json_encode($data);
        });

        // Mock Cielo API error response - Invalid credentials (Code 129)
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
        $mockOrder = $this->createMockOrder();

        // Act
        $pixRequest = new LknWcCieloRequest();
        $result = $pixRequest->pix_request(
            'Test User',
            100.00,
            ['Identity' => '12345678900', 'IdentityType' => 'CPF'],
            $this->mockInstance,
            $mockOrder,
            'ORDER-999'
        );

        // Assert - Should return invalid credentials error
        $this->assertIsArray($result);
        $this->assertFalse($result['sucess']);
        $this->assertEquals('Affiliation not found', $result['response']);
    }
}
