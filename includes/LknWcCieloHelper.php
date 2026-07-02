<?php

namespace Lkn\WCCieloPaymentGateway\Includes;

use WC_Order;
use HelgeSverre\Toon\Toon;

final class LknWcCieloHelper
{
    public function showOrderLogs(): void
    {
        $id = isset($_GET['id']) ? sanitize_text_field(wp_unslash($_GET['id'])) : '';
        if (empty($id)) {
            $id = isset($_GET['post']) ? sanitize_text_field(wp_unslash($_GET['post'])) : '';
        }
        if (! empty($id)) {
            $order_id = $id;
            $order = wc_get_order($order_id);

            if ($order && $order instanceof WC_Order) {
                $orderLogs = $order->get_meta('lknWcCieloOrderLogs');
                $payment_method_id = $order->get_payment_method();
                $options = get_option('woocommerce_' . $payment_method_id . '_settings');
                if ($orderLogs && 'yes' === $options['show_order_logs']) {
                    //carregar css
                    wp_enqueue_style('lkn-wc-cielo-order-logs', plugin_dir_url(__FILE__) . '../resources/css/frontend/lkn-admin-order-logs.css', array(), LKN_WC_CIELO_VERSION, 'all');

                    $screen = class_exists('\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController') && wc_get_container()->get('Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController')->custom_orders_table_usage_is_enabled()
                        ? wc_get_page_screen_id('shop-order')
                        : 'shop_order';

                    add_meta_box(
                        'showOrderLogs',
                        'Logs das transações',
                        array($this, 'showLogsContent'),
                        $screen,
                        'advanced',
                    );
                }
            }
        }
    }

    // Metabox content
    public function showLogsContent($object): void
    {
        // Obter o objeto WC_Order
        $order = is_a($object, 'WP_Post') ? wc_get_order($object->ID) : $object;
        $orderLogs = $order->get_meta('lknWcCieloOrderLogs');

        // Decodificar o JSON armazenado
        $decodedLogs = json_decode($orderLogs, true);

        if ($decodedLogs && is_array($decodedLogs)) {
            // Preparar cada seção para exibição com formatação
            $url = $decodedLogs['url'] ?? 'N/A';
            $headers = isset($decodedLogs['headers']) ? json_encode($decodedLogs['headers'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : 'N/A';
            $body = isset($decodedLogs['body']) ? json_encode($decodedLogs['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : 'N/A';
            $response = isset($decodedLogs['response']) ? json_encode($decodedLogs['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : 'N/A';

            // Exibir as seções formatadas
?>
            <div id="lknWcCieloOrderLogs">
                <div>
                    <h3>URL:</h3>
                    <pre class="wc-pre"><?php echo esc_html($url); ?></pre>
                </div>

                <h3>Headers:</h3>
                <pre class="wc-pre"><?php echo esc_html($headers); ?></pre>

                <h3>Body:</h3>
                <pre class="wc-pre"><?php echo esc_html($body); ?></pre>

                <h3>Response:</h3>
                <pre class="wc-pre"><?php echo esc_html($response); ?></pre>
            </div>
<?php
        }
    }

    public function censorString($string, $censorLength)
    {
        // Para compatibilidade com código existente, manter esta função
        // Mas recomendar uso da maskCredential para credenciais
        $length = strlen($string);

        if ($censorLength >= $length) {
            // Se o número de caracteres a censurar for maior ou igual ao comprimento total, censura tudo
            return str_repeat('*', $length);
        }

        $startLength = floor(($length - $censorLength) / 2); // Dividir o restante igualmente entre início e fim
        $endLength = $length - $startLength - $censorLength; // O que sobra para o final

        $start = substr($string, 0, $startLength);
        $end = substr($string, -$endLength);

        $censored = str_repeat('*', $censorLength);
        return $start . $censored . $end;
    }

    /**
     * Verifica se o plugin pro está ativo e se a licença está ativa
     * 
     * @return bool True se o plugin pro e a licença estiverem ativos, false caso contrário
     */
    public static function is_pro_license_active()
    {
        // Verifica se o plugin pro está ativo
        if (!is_plugin_active('lkn-cielo-api-pro/lkn-cielo-api-pro.php')) {
            return false;
        }

        // Verifica o status da licença
        $license_result = base64_decode(get_option('lknCieloProApiLicense', 'empty'), true);

        $license_result = ('active' === $license_result) ? true : false;

        return $license_result;
    }

    public static function getIconUrl()
    {
        return plugin_dir_url(__FILE__) . '../includes/assets/icon.svg';
    }

    /**
     * Create a standardized custom error response object
     *
     * @param int $httpStatus HTTP status code (e.g., 400, 401)
     * @param string $returnCode Cielo/Braspag error code (e.g., '126', 'BP172')
     * @param string $returnMessage Error message
     * @param string $paymentId Payment ID (optional, defaults to empty)
     * @param string $proofOfSale Proof of sale (optional, defaults to empty)
     * @param string $tid Transaction ID (optional, defaults to empty)
     * @return object Standardized error response object
     */
    public static function createCustomErrorResponse($httpStatus, $returnCode, $returnMessage, $paymentId = '', $proofOfSale = '', $tid = '')
    {
        return (object) [
            'Payment' => (object) [
                'Http_status' => $httpStatus,
                'ReturnCode' => $returnCode,
                'ReturnMessage' => $returnMessage,
                'PaymentId' => $paymentId,
                'ProofOfSale' => $proofOfSale,
                'Tid' => $tid
            ]
        ];
    }

    /**
     * Encode data using TOON format
     *
     * @param array $data
     * @return string|false
     */
    public static function encodeToonData($data)
    {
        try {
            return Toon::encode($data);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Decode TOON data
     *
     * @param string $toonString
     * @return array|false
     */
    public static function decodeToonData($toonString)
    {
        try {
            return Toon::decode($toonString);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Mask credentials dynamically based on string length.
     *
     * @param string $credential
     * @return string
     */
    public static function maskCredential($credential)
    {
        if (empty($credential)) {
            return 'N/A';
        }
        
        $length = strlen($credential);
        
        // Para strings muito pequenas, mascarar tudo
        if ($length <= 6) {
            return str_repeat('*', $length);
        }
        
        // Para strings de 7-8 caracteres, usar 3+asteriscos+3
        if ($length <= 8) {
            $showChars = 3;
        } 
        // Para strings de 9-12 caracteres, usar 4+asteriscos+4
        elseif ($length <= 12) {
            $showChars = 4;
        }
        // Para strings maiores que 12, usar mais caracteres visíveis
        else {
            $showChars = min(6, floor($length / 3)); // Máximo 6 caracteres de cada lado
        }
        
        $start = substr($credential, 0, $showChars);
        $end = substr($credential, -$showChars);
        $middleLength = $length - (2 * $showChars);
        $middle = str_repeat('*', $middleLength);
        
        return $start . $middle . $end;
    }

    /**
     * Get HTTP status description.
     *
     * @param int $httpStatus
     * @return string
     */
    public static function getHttpStatusDescription($httpStatus)
    {
        $httpStatusDescriptions = array(
            200 => 'Sucesso',
            201 => 'Criado com sucesso',
            400 => 'Requisição inválida',
            401 => 'Não autorizado',
            403 => 'Proibido',
            404 => 'Não encontrado',
            405 => 'Método não permitido',
            422 => 'Entidade não processável',
            429 => 'Muitas requisições',
            500 => 'Erro interno do servidor',
            502 => 'Gateway inválido',
            503 => 'Serviço indisponível',
            504 => 'Timeout do gateway'
        );

        return isset($httpStatusDescriptions[$httpStatus]) ? $httpStatusDescriptions[$httpStatus] : 'Status HTTP desconhecido';
    }

    /**
     * Get status description from Cielo response code.
     *
     * @param int $status
     * @return string
     */
    public static function getStatusDescription($status)
    {
        $statusDescriptions = array(
            0 => 'Pendente',
            1 => 'Autorizada',
            2 => 'Autorizada',
            3 => 'Negada',
            10 => 'Cancelada',
            11 => 'Cancelada (Refund)',
            12 => 'Pendente',
            13 => 'Abortada',
            20 => 'Aguardando',
        );

        return isset($statusDescriptions[$status]) ? $statusDescriptions[$status] : 'Desconhecido';
    }

    /**
     * Save transaction metadata to order.
     *
     * @param WC_Order $order
     * @param object $responseDecoded
     * @param string $gatewayNum
     * @param string $cardExpShort
     * @param string $gatewayCardOrPix
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
     * @param string $gatewayType
     * @param string $cvvField
     * @param string $gatewayInstance
     * @param string $xid
     * @param string $cavv
     * @param string $eci
     * @param string $version
     * @param string $refId
     */
    public static function saveTransactionMetadata($order, $responseDecoded, $gatewayNum, $cardExpShort, $gatewayCardOrPix, $installments, $amount, $currency, $provider, $merchantId, $merchantSecret, $merchantOrderId, $order_id, $capture, $response, $gatewayType = 'Credit', $cvvField = 'lkn_cc_cvc', $gatewayInstance = null, $xid = '', $cavv = '', $eci = '', $version = '', $refId = '')
    {
        // Extrair CVV dos dados apenas se não for PIX
        $cardCvv = ($gatewayType !== 'Pix' && isset($_POST[$cvvField])) ? sanitize_text_field(wp_unslash($_POST[$cvvField])) : '';

        // Calcular valor das parcelas
        $installmentAmount = $installments > 1 ? ($amount / $installments) : $amount;
        $installmentAmount = round($installmentAmount, wc_get_price_decimals());
        
        // Calcular juros/desconto baseado nas parcelas
        $interestDiscountAmount = 0;
        $totalWithFees = $amount; // Valor total já pode incluir juros
        $originalAmount = $order->get_subtotal() + $order->get_shipping_total();
        $difference = $totalWithFees - $originalAmount;
        if ($difference != 0) {
            $interestDiscountAmount = round(abs($difference), wc_get_price_decimals());
        }
        
        // Verificar se é pagamento recorrente
        $isRecurrent = 'Não';
        if (($gatewayType === 'Credit' || $gatewayType === 'Debit' || $gatewayType === 'CreditCard') && function_exists('wcs_order_contains_subscription')) {
            if (wcs_order_contains_subscription($order_id)) {
                $isRecurrent = 'Sim';
            }
        }
        
        // Data da requisição
        $requestDateTime = current_time('Y-m-d H:i:s');
        
        // Formatar dados baseado no tipo de gateway
        $gatewayMasked = 'N/A';
        $pixPaymentId = 'N/A';
        
        if ($gatewayType === 'Pix') {
            $pixPaymentId = $order->get_meta('_wc_cielo_qrcode_payment_id');
            
            if (empty($pixPaymentId)) {
                if (is_array($responseDecoded) && isset($responseDecoded['response']['paymentId'])) {
                    $pixPaymentId = $responseDecoded['response']['paymentId'];
                } elseif (is_object($responseDecoded) && isset($responseDecoded->response->paymentId)) {
                    $pixPaymentId = $responseDecoded->response->paymentId;
                } else {
                    $pixPaymentId = 'N/A';
                }
            }
            
            $gatewayMasked = 'PIX - ' . substr($pixPaymentId, 0, 8) . '...' . substr($pixPaymentId, -4);
        } else {
            // Para cartões, usar máscara tradicional: 4 primeiros + 6 asteriscos + 4 últimos
            $gatewayMasked = !empty($gatewayNum) && strlen($gatewayNum) >= 8 ? 
                substr($gatewayNum, 0, 4) . ' **** **** ' . substr($gatewayNum, -4) : 'N/A';
        }
        
        // Return code com descrição da resposta - verificar se é erro direto da API
        $returnCode = '';
        $returnMessage = '';

        // Verificar se é erro direto da API (array de erros)
        if (is_array($responseDecoded) && isset($responseDecoded[0]) && isset($responseDecoded[0]->Code)) {
            $returnCode = (string)$responseDecoded[0]->Code;
            $returnMessage = (string)$responseDecoded[0]->Message;
        }
        // Verificar se é resposta normal com Payment
        elseif (isset($responseDecoded->Payment->ReturnCode)) {
            $returnCode = $responseDecoded->Payment->ReturnCode;
            $returnMessage = isset($responseDecoded->Payment->ReturnMessage) ? $responseDecoded->Payment->ReturnMessage : '';
        }
        // Para PIX, verificar estrutura específica da resposta
        elseif ($gatewayType === 'Pix') {
            $hasSuccess = false;
            if (is_array($responseDecoded) && isset($responseDecoded['success'])) {
                $hasSuccess = $responseDecoded['success'];
            } elseif (is_object($responseDecoded) && isset($responseDecoded->success)) {
                $hasSuccess = $responseDecoded->success;
            } elseif (is_array($responseDecoded) && isset($responseDecoded['sucess'])) {
                // Suporte ao typo 'sucess' que aparece no código
                $hasSuccess = $responseDecoded['sucess'];
            } elseif (is_object($responseDecoded) && isset($responseDecoded->sucess)) {
                $hasSuccess = $responseDecoded->sucess;
            }

            if ($hasSuccess === true) {
                $returnCode = '12';
                $returnMessage = 'PIX criado';
            } else {
                $returnCode = '0';
                $returnMessage = isset($responseDecoded['response']) ? $responseDecoded['response'] : 'Erro ao criar PIX';
            }
        }
        
        $returnCodeFormatted = ($returnCode !== '' && $returnCode !== null) ? $returnCode . ' - ' . $returnMessage : 'N/A';
        
        // Gateway via ID do método de pagamento (mais confiável que o título)
        $gatewayName = $order->get_payment_method();
        $gatewayName = !empty($gatewayName) ? $gatewayName : 'N/A';

        if($gatewayName === 'lkn_wc_cielo_pix' || $gatewayName === 'lkn_cielo_pix' ) {
            // Para PIX, verificar success tanto em array quanto objeto
            $hasSuccess = false;
            if (is_array($responseDecoded) && isset($responseDecoded['success'])) {
                $hasSuccess = $responseDecoded['success'];
            } elseif (is_object($responseDecoded) && isset($responseDecoded->success)) {
                $hasSuccess = $responseDecoded->success;
            } elseif (is_array($responseDecoded) && isset($responseDecoded['sucess'])) {
                // Suporte ao typo 'sucess' que aparece no código
                $hasSuccess = $responseDecoded['sucess'];
            } elseif (is_object($responseDecoded) && isset($responseDecoded->sucess)) {
                $hasSuccess = $responseDecoded->sucess;
            }
            
            $httpStatus = $hasSuccess ? '201' : '400';
            $httpStatusDescription = self::getHttpStatusDescription($httpStatus);
            $httpStatusFormatted = $httpStatus ? $httpStatus . ' - ' . $httpStatusDescription : 'N/A';
        } else {
            // Status HTTP da requisição
            $httpStatus = isset($responseDecoded->Payment->Http_status) ? $responseDecoded->Payment->Http_status : wp_remote_retrieve_response_code($response);
            $httpStatusDescription = self::getHttpStatusDescription($httpStatus);
            $httpStatusFormatted = $httpStatus ? $httpStatus . ' - ' . $httpStatusDescription : 'N/A';
        }
        
        // Formatar dados 3DS
        $threeDSData = '';
        if (!empty($cavv) && !empty($eci) && !empty($xid)) {
            $threeDSData = 'CAVV: ' . substr($cavv, 0, 8) . '..., ECI: ' . $eci . ', Version: ' . $version;
        } else {
            $threeDSData = 'N/A';
        }
        
        // Environment baseado no gateway
        $environment = 'Sandbox';
        if ($gatewayInstance && method_exists($gatewayInstance, 'get_option')) {
            $environment = ($gatewayInstance->get_option('env') == 'production') ? 'Produção' : 'Sandbox';
        }
        
        // Validar e mascarar merchant credentials dinamicamente
        $merchantIdMasked = self::maskCredential($merchantId);
        $merchantKeyMasked = self::maskCredential($merchantSecret);
        
        // Validar data de expiração
        $cardExpiryFormatted = !empty($cardExpShort) ? $cardExpShort : 'N/A';
        
        // Validar CVV baseado no tipo de pagamento
        $cvvSent = 'N/A';
        if (in_array($gatewayType, ['Credit', 'Debit'])) {
            $cvvSent = !empty($cardCvv) ? 'Sim' : 'Não';
        }
        
        // Validar Capture baseado no tipo de pagamento
        $captureFormatted = 'N/A';
        if (in_array($gatewayType, ['Credit', 'Debit'])) {
            $captureFormatted = $capture ? 'Auto' : 'Manual';
        }
        
        // Validar Recorrente baseado no tipo de pagamento
        $recurrentFormatted = 'N/A';
        if ($gatewayType === 'Credit') {
            $recurrentFormatted = $isRecurrent;
        }
        
        // Validar 3DS baseado no gateway - só existe para débito
        $threeDSFormatted = 'N/A';
        if ($gatewayType === 'Debit') {
            // Verificar se houve erro relacionado a 3DS
            $is3DSError = false;
            if (isset($responseDecoded->Payment->ReturnCode)) {
                $returnCode = $responseDecoded->Payment->ReturnCode;
                // Códigos de erro relacionados ao 3DS
                $threeDSErrorCodes = ['BP171', 'BP172', 'BP900'];
                $is3DSError = in_array($returnCode, $threeDSErrorCodes);
            }
            
            if ($is3DSError) {
                // Se há erro específico de 3DS, marcar como falhou
                $threeDSFormatted = 'Falhou';
            } elseif (!empty($cavv) || !empty($eci) || !empty($xid)) {
                // Se há dados de 3DS, verificar se está completo
                if (!empty($cavv) && !empty($eci) && !empty($xid)) {
                    $threeDSFormatted = 'Sucesso';
                } else {
                    $threeDSFormatted = 'Falhou';
                }
            }
        }
        
        // Formatar valor das parcelas - apenas valor numérico
        $installmentFormatted = 'N/A';
        if ($installments > 0 && $installmentAmount > 0) {
            $installmentFormatted = round((float) $installmentAmount, wc_get_price_decimals());
        }
        
        // Determinar tipo de gateway/cartão para exibição
        $displayType = 'N/A';
        $brand = 'N/A';
        
        if ($gatewayType === 'Pix') {
            $displayType = 'PIX';
            $brand = 'PIX';
        } elseif ($gatewayType === 'Debit') {
            $displayType = 'Débito';
            $brand = !empty($provider) ? $provider : 'N/A';
        } elseif ($gatewayType === 'Credit') {
            $displayType = 'Crédito';
            $brand = !empty($provider) ? $provider : 'N/A';
        }

        if ($gatewayType === 'Pix') {
            $tid = $pixPaymentId;
        } else {
            $tid = isset($responseDecoded->Payment->Tid) && !empty($responseDecoded->Payment->Tid) 
                    ? $responseDecoded->Payment->Tid : 'N/A';
        }
        
        // Criar estrutura centralizada com metadados da transação
        $transactionMetadata = [
            'gateway' => [
                'masked' => $gatewayMasked,
                'type' => $displayType,
                'brand' => $brand,
                'expiry' => ($gatewayType === 'Pix') ? 'N/A' : $cardExpiryFormatted,
                'holder_name' => ($gatewayType === 'Pix') ? $gatewayCardOrPix : (!empty($gatewayCardOrPix) ? $gatewayCardOrPix : 'N/A')
            ],
            'transaction' => [
                'cvv_sent' => $cvvSent,
                'installments' => ($gatewayType === 'Pix') ? 1 : ($installments > 0 ? $installments : 'N/A'),
                'installment_amount' => ($gatewayType === 'Pix') ? round((float) $amount, wc_get_price_decimals()) : $installmentFormatted,
                'capture' => $captureFormatted,
                'recurrent' => $recurrentFormatted,
                '3ds_auth' => $threeDSFormatted,
                'tid' => $tid
            ],
            'amounts' => [
                'total' => round((float) $amount, wc_get_price_decimals()),
                'subtotal' => round((float) $order->get_subtotal(), wc_get_price_decimals()),
                'shipping' => round((float) $order->get_shipping_total(), wc_get_price_decimals()),
                'interest_discount' => round((float) $interestDiscountAmount, wc_get_price_decimals()),
                'currency' => $currency
            ],
            'system' => [
                'request_datetime' => $requestDateTime,
                'environment' => $environment,
                'gateway' => $gatewayName,
                'order_id' => $order_id,
                'reference' => !empty($merchantOrderId) ? $merchantOrderId : 'N/A'
            ],
            'merchant' => [
                'id_masked' => $merchantIdMasked,
                'key_masked' => $merchantKeyMasked
            ],
            'response' => [
                'return_code' => $returnCodeFormatted,
                'http_status' => $httpStatusFormatted
            ]
        ];

        // Tentar codificar com TOON
        $toonEncoded = self::encodeToonData($transactionMetadata);

        if ($toonEncoded !== false) {
            // Salvar dados como TOON
            $order->add_meta_data('lkn_cielo_transaction_data', $toonEncoded, true);
            $order->add_meta_data('lkn_cielo_data_format', 'toon', true);
        } else {
            // Fallback para JSON se TOON falhar
            $jsonEncoded = wp_json_encode($transactionMetadata);
            $order->add_meta_data('lkn_cielo_transaction_data', $jsonEncoded, true);
            $order->add_meta_data('lkn_cielo_data_format', 'json', true);
        }

        // Manter campos críticos para compatibilidade backward  
        $paymentId = isset($responseDecoded->Payment->PaymentId) && !empty($responseDecoded->Payment->PaymentId) 
            ? $responseDecoded->Payment->PaymentId : '';
        $nsu = isset($responseDecoded->Payment->ProofOfSale) && !empty($responseDecoded->Payment->ProofOfSale) 
            ? $responseDecoded->Payment->ProofOfSale : '';

        if (!empty($paymentId)) {
            $order->add_meta_data('paymentId', $paymentId, true);
        }
        if (!empty($nsu)) {
            $order->update_meta_data('lkn_nsu', $nsu);
        }
    }



    /**
     * Get card provider from number.
     *
     * @param string $gatewayNumber
     * @param string $gatewayId
     *
     * @return string|bool
     */
    public static function getCardProvider($gatewayNumber, $gatewayId = '')
    {
        $brand = '';
        $brand = apply_filters('lkn_wc_cielo_get_card_brand', $brand, $gatewayNumber, $gatewayId);

        if (empty($brand)) {
            // Stores regex for Card Bin Tests
            $bin = array(
                // elo
                '/(4011|431274|438935|451416|457393|4576|457631|457632|504175|627780|636297|636368|636369|(6503[1-3])|(6500(3[5-9]|4[0-9]|5[0-1]))|(6504(0[5-9]|1[0-9]|2[0-9]|3[0-9]))|(650(48[5-9]|49[0-9]|50[0-9]|51[1-9]|52[0-9]|53[0-7]))|(6505(4[0-9]|5[0-9]|6[0-9]|7[0-9]|8[0-9]|9[0-8]))|(6507(0[0-9]|1[0-8]))|(6507(2[0-7]))|(650(90[1-9]|91[0-9]|920))|(6516(5[2-9]|6[0-9]|7[0-9]))|(6550(0[0-9]|1[1-9]))|(6550(2[1-9]|3[0-9]|4[0-9]|5[0-8]))|(506(699|77[0-8]|7[1-6][0-9))|(509([0-9][0-9][0-9])))/',
                // hipercard
                '/^(606282\d{10}(\d{3})?)|(3841\d{15})$/',
                // diners
                '/^3(?:0[0-5]|[68][0-9])[0-9]{11}$/',
                // discover
                '/^6(?:011|5[0-9]{2})[0-9]{12}$/',
                // jcb
                '/^(?:2131|1800|35\d{3})\d{11}$/',
                // aura
                '/^50[0-9]{14,17}$/',
                // amex
                '/^3[47][0-9]{13}$/',
                // mastercard
                '/^5[1-5]\d{14}$|^2(?:2(?:2[1-9]|[3-9]\d)|[3-6]\d\d|7(?:[01]\d|20))\d{12}$/',
                // visa
                '/^4[0-9]{12}(?:[0-9]{3})?$/',
            );

            // Test the cardNumber bin
            for ($c = 0; $c < count($bin); ++$c) {
                if ($c > 10) {
                    break;
                }
                if (preg_match($bin[$c], $gatewayNumber) == 1) {
                    switch ($c) {
                        case 0:
                            return 'Elo';

                            break;

                        case 1:
                            return 'Hipercard';

                            break;

                        case 2:
                            return 'Diners';

                            break;

                        case 3:
                            return 'Discover';

                            break;

                        case 4:
                            return 'JCB';

                            break;

                        case 5:
                            return 'Aura';

                            break;

                        case 6:
                            return 'Amex';

                            break;

                        case 7:
                            return 'Master';

                            break;

                        case 8:
                            return 'Visa';

                            break;
                    }
                }
            }
        } else {
            return sanitize_text_field($brand);
        }

        return false;
    }

    /**
     * Get client IP address.
     *
     * @return string
     */
    public static function getClientIp()
    {
        $ip_address = '';
        $client_ip = isset($_SERVER['HTTP_CLIENT_IP']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_CLIENT_IP'])) : '';
        $forwarded_ip = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_X_FORWARDED_FOR'])) : '';
        $real_ip = isset($_SERVER['HTTP_X_REAL_IP']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_X_REAL_IP'])) : '';
        $remote_ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';

        if (! empty($client_ip)) {
            $ip_address = $client_ip;
        } elseif (! empty($forwarded_ip)) {
            // Se estiver atrás de um proxy, `HTTP_X_FORWARDED_FOR` pode conter uma lista de IPs.
            $ip_list = explode(',', $forwarded_ip);
            $ip_address = trim($ip_list[0]); // Pega o primeiro IP da lista
        } elseif (! empty($real_ip)) {
            $ip_address = $real_ip;
        } else {
            $ip_address = $remote_ip;
        }

        return $ip_address;
    }
}
