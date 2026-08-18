<?php

namespace Lkn\WCCieloPaymentGateway\Includes;

use Lkn\WCCieloPaymentGateway\Includes\LknWCGatewayCieloDebit;
use WC_Logger;
use WP_Error;
use WP_REST_Response;

final class LknWCGatewayCieloEndpoint
{
    public function registerOrderCaptureEndPoint(): void
    {
        register_rest_route('lknWCGatewayCielo', '/checkCard', array(
            'methods' => 'GET',
            'callback' => array($this, 'orderCapture'),
            'permission_callback' => array($this, 'check_card_permission'),
        ));

        register_rest_route('lknWCGatewayCielo', '/getAcessToken', array(
            'methods' => 'GET',
            'callback' => array($this, 'getAcessToken'),
            'permission_callback' => array($this, 'check_token_permission'),
        ));

        register_rest_route('lknWCGatewayCielo', '/getCardBrand', array(
            'methods' => 'GET',
            'callback' => array($this, 'getOfflineBinCard'),
            'permission_callback' => array($this, 'check_card_brand_permission'),
            'args' => array(
                'number' => array(
                    'required' => true,
                    'validate_callback' => function ($param) {
                        return strlen($param) >= 6;
                    },
                ),
            ),
        ));
    }

    /**
     * Check permission for checkCard endpoint
     * Requires valid nonce for frontend checkout usage
     *
     * @param WP_REST_Request $request
     * @return bool|WP_Error
     */
    public function check_card_permission($request)
    {
        $nonce = $request->get_header('X-WP-Nonce');
        
        if (empty($nonce)) {
            $nonce = $request->get_param('_wpnonce');
        }

        if (empty($nonce) || !wp_verify_nonce($nonce, 'wp_rest')) {
            return new WP_Error(
                'rest_forbidden',
                __('Invalid security token.', 'lkn-wc-gateway-cielo'),
                array('status' => 403)
            );
        }

        return true;
    }



    /**
     * Check permission for getAcessToken endpoint
     * Requires valid nonce for frontend checkout usage
     *
     * @param WP_REST_Request $request
     * @return bool|WP_Error
     */
    public function check_token_permission($request)
    {
        $nonce = $request->get_header('X-WP-Nonce');
        
        if (empty($nonce)) {
            $nonce = $request->get_param('_wpnonce');
        }

        if (empty($nonce) || !wp_verify_nonce($nonce, 'wp_rest')) {
            return new WP_Error(
                'rest_forbidden',
                __('Invalid security token.', 'lkn-wc-gateway-cielo'),
                array('status' => 403)
            );
        }

        return true;
    }

    /**
     * Check permission for getCardBrand endpoint
     * Requires valid nonce for frontend checkout usage
     *
     * @param WP_REST_Request $request
     * @return bool|WP_Error
     */
    public function check_card_brand_permission($request)
    {
        $nonce = $request->get_header('X-WP-Nonce');
        
        if (empty($nonce)) {
            $nonce = $request->get_param('_wpnonce');
        }

        if (empty($nonce) || !wp_verify_nonce($nonce, 'wp_rest')) {
            return new WP_Error(
                'rest_forbidden',
                __('Invalid security token.', 'lkn-wc-gateway-cielo'),
                array('status' => 403)
            );
        }

        return true;
    }

    public function orderCapture($request)
    {
        // Obtém os parâmetros da requisição
        $parameters = $request->get_params();
        $cardBin = $parameters['cardbin'];

        // Inicializa a classe para obter as credenciais
        $debitOption = get_option('woocommerce_lkn_cielo_debit_settings');
        $merchantId = $debitOption['merchant_id'];
        $merchantKey = $debitOption['merchant_key'];
        $log = new WC_Logger();

        // Define a URL da API com o BIN do cartão
        $url = ('production' == $debitOption['env']) ? 'https://apiquery.cieloecommerce.cielo.com.br/1/cardBin/' : 'https://apiquerysandbox.cieloecommerce.cielo.com.br/1/cardBin/';
        $url = $url . $cardBin;
        $url = apply_filters('lkn_wc_change_bin_url', $url);

        // Configura os cabeçalhos da requisição
        $headers = array(
            'Accept' => 'application/json',
            'MerchantId' => $merchantId,
            'MerchantKey' => $merchantKey
        );
        $headers = apply_filters('lkn_wc_change_bin_headers', $headers);

        // Faz a requisição utilizando wp_remote_get
        $response = wp_remote_get($url, array(
            'headers' => $headers,
            'timeout' => 120
        ));

        if ('yes' === $debitOption['debug']) {
            $log->log('info', json_encode($response), array('source' => 'woocommerce-cielo-debit-bin'));
        }

        $response = apply_filters('lkn_wc_check_bin_response', $response, $debitOption, $cardBin);

        // Verifica se houve algum erro na requisição
        if (is_wp_error($response)) {
            return new \WP_Error('request_failed', __('Failed to retrieve card type', 'lkn-wc-gateway-cielo'), array('status' => 500));
        }

        // Obtém o corpo da resposta
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        // Resposta com wrapper cardQuery (formato antigo da API)
        if (isset($data['cardQuery'])) {
            if (isset($data['cardQuery']['Provider'])) {
                $data['cardQuery']['Provider'] = ucfirst(strtolower($data['cardQuery']['Provider']));
            }
            $data['cardQuery']['Source'] = 'online';
            return new WP_REST_Response($data['cardQuery'], 200);
        }

        // Resposta direta bem-sucedida: {Status, Provider, CardType, ...}
        if (isset($data['Status']) && '00' === $data['Status']) {
            if (isset($data['Provider'])) {
                $data['Provider'] = ucfirst(strtolower($data['Provider']));
            }
            $data['Source'] = 'online';
            return new WP_REST_Response($data, 200);
        }

        // API online retornou erro → fallback offline por regex de BIN
        $offlineResult = $this->detectCardByBinOffline($cardBin);
        if ($offlineResult) {
            $offlineResult['Source'] = 'offline';
            return new WP_REST_Response($offlineResult, 200);
        }

        return new WP_REST_Response($data, 200);
    }

    public function ajax_clear_order_logs()
    {
        // Verificar se é requisição POST e se nonce existe
        if (!isset($_POST['nonce'])) {
            wp_send_json_error(array(
                'message' => __('Missing security token.', 'lkn-wc-gateway-cielo')
            ));
        }

        // Verificar nonce para segurança
        $nonce = sanitize_text_field(wp_unslash($_POST['nonce']));
        if (!wp_verify_nonce($nonce, 'lkn_cielo_clear_logs_nonce')) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'lkn-wc-gateway-cielo')
            ));
        }

        // Verificar permissões do usuário
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array(
                'message' => __('You do not have permission to clear order logs.', 'lkn-wc-gateway-cielo')
            ));
        }

        $args = array(
            'limit' => -1, // Sem limite, pega todas as ordens
            'meta_key' => 'lknWcCieloOrderLogs', // Meta key específica
            'meta_compare' => 'EXISTS', // Verifica se a meta key existe
        );

        $orders = wc_get_orders($args);
        $count = 0;

        foreach ($orders as $order) {
            $order->delete_meta_data('lknWcCieloOrderLogs');
            $order->save();
            $count++;
        }

        // Log the action for audit trail
        if (class_exists('WC_Logger')) {
            $log = new WC_Logger();
            $log->info(
                sprintf('Order logs cleared by user %d. Total orders affected: %d', get_current_user_id(), $count),
                array('source' => 'woocommerce-cielo-security')
            );
        }

        wp_send_json_success(array(
            /* translators: %d: number of order logs cleared */
            'message' => sprintf(__('%d order logs cleared successfully.', 'lkn-wc-gateway-cielo'), $count),
            'count' => $count
        ));
    }

    public function getAcessToken()
    {
        $LknWCGatewayCieloDebitClass = new LknWCGatewayCieloDebit();
        $acessToken = $LknWCGatewayCieloDebitClass->generate_debit_auth_token();

        if (empty($acessToken) || ! is_array($acessToken) || empty($acessToken['access_token'])) {
            return new WP_Error(
                'token_generation_failed',
                __('Auth token generation failed.', 'lkn-wc-gateway-cielo'),
                array('status' => 502)
            );
        }

        return new WP_REST_Response($acessToken, 200);
    }

    /**
     * Retrieves the card brand based on the BIN number.
     *
     * @param WP_REST_Request $request The request object containing the 'number' parameter.
     *
     * @return WP_REST_Response Returns a response with the card brand if recognized, or an error message if not.
     */
    public function getOfflineBinCard($request)
    {
        $number = str_replace(' ', '', trim($request->get_param('number')));
        $gateway = $request->get_param('gateway'); // 'debit' or 'credit'

        // Se brand_validation estiver ativo e gateway informado, tenta online primeiro
        if (in_array($gateway, array('debit', 'credit'), true)) {
            $optionKey = 'woocommerce_lkn_cielo_' . $gateway . '_settings';
            $option = get_option($optionKey, array());

            if (isset($option['brand_validation']) && 'yes' === $option['brand_validation']) {
                $onlineBrand = $this->tryOnlineBin($number, $option);

                if ($onlineBrand) {
                    return new WP_REST_Response(array(
                        'status'   => true,
                        'brand'    => $onlineBrand['provider'],
                        'cardType' => $onlineBrand['cardType'],
                        'source'   => 'online',
                    ), 200);
                }
                // Fallthrough para offline em caso de falha
            }
        }

        $bin = [
            // visa
            '/^4[0-9]{2,15}$/',
            // elo
            '/^(431274|438935|451416|457393|4576|457631|457632|504175|627780|636297|636368|636369|(6503[1-3])|(6500(3[5-9]|4[0-9]|5[0-1]))|(6504(0[5-9]|1[0-9]|2[0-9]|3[0-9]))|(650(48[5-9]|49[0-9]|50[0-9]|51[1-9]|52[0-9]|53[0-7]))|(6505(4[0-9]|5[0-9]|6[0-9]|7[0-9]|8[0-9]|9[0-8]))|(6507(0[0-9]|1[0-8]))|(6507(2[0-7]))|(650(90[1-9]|91[0-9]|920))|(6516(5[2-9]|6[0-9]|7[0-9]))|(6550(0[0-9]|1[1-9]))|(6550(2[1-9]|3[0-9]|4[0-9]|5[0-8]))|(506(699|77[0-8]|7[1-6][0-9))|(509([0-9][0-9][0-9])))/',
            // hipercard
            '/^(606282|3841)\d{0,13}$/',
            // diners
            '/^3(?:0[0-5]|[68][0-9])[0-9]{0,11}$/',
            // discover
            '/^6(?:011|5[0-9]{2})[0-9]{0,12}$/',
            // jcb
            '/^(?:2131|1800|35\d{2})\d{0,11}$/',
            // aura
            '/^50[0-9]{2,17}$/',
            // amex
            '/^3[47][0-9]{2,13}$/',
            // mastercard
            '/^5[1-5]\d{0,14}$|^2(?:2(?:2[1-9]|[3-9]\d)|[3-6]\d\d|7(?:[01]\d|20))\d{0,12}$/',
        ];

        // Test the cardNumber bin
        foreach ($bin as $index => $regex) {
            if (preg_match($regex, $number)) {
                $brands = [
                    'visa',
                    'elo',
                    'hipercard',
                    'diners',
                    'discover',
                    'jcb',
                    'aura',
                    'amex',
                    'mastercard',
                ];

                return new WP_REST_Response([
                    'status' => true,
                    'brand' => $brands[$index],
                    'cardType' => 'Multiplo',
                    'source' => 'offline',
                ], 200);
            }
        }

        // Caso não encontre nenhuma correspondência
        return new WP_REST_Response([
            'status' => false,
            'message' => __('Card brand not found', 'lkn-wc-gateway-cielo'),
        ], 200);
    }

    /**
     * Detecção offline de bandeira por BIN — fallback quando a API Cielo falha.
     * Retorna array com Provider e CardType no mesmo formato do cardQuery da Cielo,
     * ou false se não identificar.
     *
     * @param string $cardBin 6 primeiros dígitos do cartão
     * @return array|false
     */
    private function detectCardByBinOffline($cardBin)
    {
        $number = str_replace(' ', '', trim($cardBin));

        // Mapeamento: [regex, Provider, CardType]
        // CardType sempre "Multiplo" porque sem a API online não dá pra distinguir
        $binMap = [
            ['/^4/',                         'Visa',       'Multiplo'],
            ['/^5[1-5]/',                    'Mastercard', 'Multiplo'],
            ['/^2(?:2(?:2[1-9]|[3-9]\d)|[3-6]\d\d|7(?:[01]\d|20))/', 'Mastercard', 'Multiplo'],
            ['/^3[47]/',                     'Amex',       'Multiplo'],
            ['/^(431274|438935|451416|457393|4576|504175|627780|636297|636368|636369)/', 'Elo', 'Multiplo'],
            ['/^(506|509|650)/',             'Elo',        'Multiplo'],
            ['/^(606282|3841)/',             'Hipercard',  'Multiplo'],
            ['/^3(?:0[0-5]|[68])/',          'Diners',     'Multiplo'],
            ['/^6(?:011|5)/',                'Discover',   'Multiplo'],
            ['/^(?:2131|1800|35)/',          'Jcb',        'Multiplo'],
            ['/^50/',                        'Aura',       'Multiplo'],
        ];

        foreach ($binMap as $entry) {
            if (preg_match($entry[0], $number)) {
                return [
                    'Provider' => $entry[1],
                    'CardType' => $entry[2],
                ];
            }
        }

        return false;
    }

    /**
     * Tenta consulta online do BIN na API Cielo.
     * Retorna array com provider e cardType, ou false se falhar.
     *
     * @param string $cardBin 6 primeiros dígitos do cartão
     * @param array  $option  Configurações do gateway (debit ou credit)
     * @return array{provider: string, cardType: string}|false
     */
    private function tryOnlineBin($cardBin, $option)
    {
        $bin = substr(str_replace(' ', '', $cardBin), 0, 6);

        if (strlen($bin) < 6) {
            return false;
        }

        $url = ('production' === $option['env'])
            ? 'https://apiquery.cieloecommerce.cielo.com.br/1/cardBin/'
            : 'https://apiquerysandbox.cieloecommerce.cielo.com.br/1/cardBin/';
        $url .= $bin;

        $headers = array(
            'Accept'      => 'application/json',
            'MerchantId'  => $option['merchant_id'],
            'MerchantKey' => $option['merchant_key'],
        );

        $response = wp_remote_get($url, array(
            'headers' => $headers,
            'timeout' => 30,
        ));

        if (is_wp_error($response)) {
            if (isset($option['debug']) && 'yes' === $option['debug']) {
                $log = new WC_Logger();
                $log->log('error', '[CIELO BIN] cardBin=' . $bin . ' | wp_error=' . $response->get_error_message(), array('source' => 'woocommerce-cielo-debit-bin'));
            }
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (isset($option['debug']) && 'yes' === $option['debug']) {
            $log = new WC_Logger();
            $log->log('info', '[CIELO BIN] cardBin=' . $bin . ' | response=' . $body, array('source' => 'woocommerce-cielo-debit-bin'));
        }

        // Resposta com wrapper cardQuery (formato antigo)
        if (isset($data['cardQuery']['Provider'])) {
            return array(
                'provider' => $this->mapProviderToBrand($data['cardQuery']['Provider']),
                'cardType' => $this->mapCardType(isset($data['cardQuery']['CardType']) ? $data['cardQuery']['CardType'] : 'Multiplo'),
            );
        }

        // Resposta direta: {Status, Provider, CardType, ...}
        if (isset($data['Status']) && '00' === $data['Status'] && isset($data['Provider'])) {
            return array(
                'provider' => $this->mapProviderToBrand($data['Provider']),
                'cardType' => $this->mapCardType(isset($data['CardType']) ? $data['CardType'] : 'Multiplo'),
            );
        }

        // Qualquer outra resposta é considerada falha
        return false;
    }

    /**
     * Mapeia o nome do Provider retornado pela API Cielo para o nome
     * lowercase usado internamente pelo plugin.
     *
     * @param string $provider Nome do provider (ex: "VISA", "MASTERCARD")
     * @return string Nome da bandeira (ex: "visa", "mastercard")
     */
    private function mapProviderToBrand($provider)
    {
        $map = array(
            'VISA'             => 'visa',
            'MASTERCARD'       => 'mastercard',
            'AMERICAN EXPRESS' => 'amex',
            'ELO'              => 'elo',
            'HIPERCARD'        => 'hipercard',
            'DINERS CLUB'      => 'diners',
            'DINERS'           => 'diners',
            'DISCOVER'         => 'discover',
            'JCB'              => 'jcb',
            'AURA'             => 'aura',
        );

        $provider = strtoupper(trim($provider));

        return isset($map[$provider]) ? $map[$provider] : strtolower($provider);
    }

    /**
     * Normaliza o CardType vindo da API Cielo para um formato consistente.
     * A API pode retornar "Crédito", "Débito", "Multiplo" (com ou sem acento).
     * Normalizamos para: Credito, Debito, Multiplo (sem acento, primeira maiúscula).
     *
     * @param string $cardType Valor bruto do CardType da API
     * @return string "Credito", "Debito" ou "Multiplo"
     */
    private function mapCardType($cardType)
    {
        // Remove acentos
        $normalized = preg_replace(
            array('/[áàãâä]/u', '/[éèêë]/u', '/[íìîï]/u', '/[óòõôö]/u', '/[úùûü]/u', '/[ç]/u'),
            array('a',          'e',          'i',          'o',          'u',          'c'),
            mb_strtolower(trim($cardType))
        );

        $map = array(
            'credito'  => 'Credito',
            'debito'   => 'Debito',
            'multiplo' => 'Multiplo',
        );

        return isset($map[$normalized]) ? $map[$normalized] : 'Multiplo';
    }
}
