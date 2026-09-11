<?php

namespace Lkn\WCCieloPaymentGateway\Includes;

// Exit if accessed directly.
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Central catalog of Cielo (API E-commerce / Gateway) return codes.
 *
 * Maps the codes returned by the Cielo API to their official messages, so the
 * messages surfaced in logs, order notes and error responses can be translated
 * (the source strings use the `lkn-wc-gateway-cielo` text domain).
 *
 * Cielo uses two different namespaces of codes, and this plugin surfaces both
 * through `Payment.ReturnCode`:
 *
 *  - Provider/API codes: returned by the Cielo acquirer for a processed request
 *    (integration/validation errors and the `00`/`4`/`6` success codes). The same
 *    values are returned as `ProviderReturnCode` (HTTP 2xx) or as `Code` in the
 *    error array `[{ "Code": ..., "Message": ... }]` (HTTP 4xx).
 *  - ABECS codes: the standardized authorization/decline codes (issued by the
 *    brand/issuer), returned when a card transaction is denied.
 *
 * Because the Provider codes are 3-digit (or "0") and the ABECS codes are
 * 2-digit / alphanumeric, they do not collide and live in the same map.
 *
 * All messages are written in English (the source language) so they can be
 * translated on translate.wordpress.org.
 *
 * Official sources:
 *  - https://docs.cielo.com.br/gateway/reference/códigos-de-erros-da-api
 *  - https://docs.cielo.com.br/gateway/reference/lista-de-reasoncodereasonmessage
 *  - https://docs.cielo.com.br/ecommerce-cielo/page/abecs
 *  - https://docs.cielo.com.br/gateway/docs/return-codes-3ds
 *  - https://docs.cielo.com.br/gateway/reference/lista-de-http-status-code
 *
 * @see LknCieloErrorCodes::translate() Provider + ABECS codes (Payment.ReturnCode).
 * @see LknCieloErrorCodes::reason()    ReasonCode / ReasonMessage.
 * @see LknCieloErrorCodes::threeDs()   3DS return codes.
 */
final class LknCieloErrorCodes
{
    /**
     * Translate a Provider/ABECS return code into its official message.
     *
     * When the code is not mapped, the original message is returned. When there
     * is no message at all, a generic "Unknown error" is returned.
     *
     * @param string|int $code     Return code (`Payment.ReturnCode`).
     * @param string     $fallback Original message used when the code is not mapped.
     * @return string Official message, the original message, or "Unknown error".
     */
    public static function translate($code, $fallback = '')
    {
        return self::lookup(self::codes(), $code, $fallback);
    }

    /**
     * Translate a ReasonCode (`ReasonCode` / `ReasonMessage`) into its message.
     *
     * @param string|int $code     Reason code.
     * @param string     $fallback Original message used when the code is not mapped.
     * @return string Official message, the original message, or "Unknown error".
     */
    public static function reason($code, $fallback = '')
    {
        return self::lookup(self::reasonCodes(), $code, $fallback);
    }

    /**
     * Translate a 3DS return code into its message.
     *
     * The 3DS return codes share numeric values with the Provider codes (e.g.
     * 100/101/102), so they are kept in a separate map and must be translated
     * through this method.
     *
     * @param string|int $code     3DS return code.
     * @param string     $fallback Original message used when the code is not mapped.
     * @return string Official message, the original message, or "Unknown error".
     */
    public static function threeDs($code, $fallback = '')
    {
        return self::lookup(self::threeDsCodes(), $code, $fallback);
    }

    /**
     * Shared lookup with fallback.
     *
     * @param array<string, string> $map
     * @param string|int            $code
     * @param string                $fallback
     * @return string
     */
    private static function lookup($map, $code, $fallback)
    {
        $code = trim((string) $code);

        if ('' !== $code && isset($map[$code])) {
            return $map[$code];
        }

        $fallback = trim((string) $fallback);

        if ('' !== $fallback) {
            return $fallback;
        }

        return __('Unknown error', 'lkn-wc-gateway-cielo');
    }

    /**
     * Provider + ABECS return codes (`Payment.ReturnCode`).
     *
     * @return array<string, string>
     */
    private static function codes()
    {
        static $codes = null;

        if (null !== $codes) {
            return $codes;
        }

        $codes = array(
            // ===== Provider success codes =====
            '00'   => __('Transaction authorized', 'lkn-wc-gateway-cielo'),
            '4'    => __('Transaction authorized (available for capture)', 'lkn-wc-gateway-cielo'),
            '6'    => __('Transaction captured successfully', 'lkn-wc-gateway-cielo'),
            '9'    => __('Transaction voided/refunded successfully', 'lkn-wc-gateway-cielo'),

            // ===== Provider/API integration error codes =====
            '0'    => __('Internal error', 'lkn-wc-gateway-cielo'),
            '100'  => __('RequestId is required', 'lkn-wc-gateway-cielo'),
            '101'  => __('MerchantId is required', 'lkn-wc-gateway-cielo'),
            '102'  => __('Payment Type is required', 'lkn-wc-gateway-cielo'),
            '103'  => __('Payment Type can only contain letters', 'lkn-wc-gateway-cielo'),
            '104'  => __('Customer Identity is required', 'lkn-wc-gateway-cielo'),
            '105'  => __('Customer Name is required', 'lkn-wc-gateway-cielo'),
            '106'  => __('Transaction ID is required', 'lkn-wc-gateway-cielo'),
            '107'  => __('OrderId is invalid or does not exist', 'lkn-wc-gateway-cielo'),
            '108'  => __('Amount must be greater or equal to zero', 'lkn-wc-gateway-cielo'),
            '109'  => __('Payment Currency is required', 'lkn-wc-gateway-cielo'),
            '110'  => __('Invalid Payment Currency', 'lkn-wc-gateway-cielo'),
            '111'  => __('Payment Country is required', 'lkn-wc-gateway-cielo'),
            '112'  => __('Invalid Payment Country', 'lkn-wc-gateway-cielo'),
            '113'  => __('Invalid Payment Code', 'lkn-wc-gateway-cielo'),
            '114'  => __('The provided MerchantId is not in correct format', 'lkn-wc-gateway-cielo'),
            '115'  => __('The provided MerchantId was not found', 'lkn-wc-gateway-cielo'),
            '116'  => __('The provided MerchantId is blocked', 'lkn-wc-gateway-cielo'),
            '117'  => __('Credit Card Holder is required', 'lkn-wc-gateway-cielo'),
            '118'  => __('Credit Card Number is required', 'lkn-wc-gateway-cielo'),
            '119'  => __('At least one Payment is required', 'lkn-wc-gateway-cielo'),
            '120'  => __('Request IP not allowed. Check your IP White List', 'lkn-wc-gateway-cielo'),
            '121'  => __('Customer is required', 'lkn-wc-gateway-cielo'),
            '122'  => __('MerchantOrderId is required', 'lkn-wc-gateway-cielo'),
            '123'  => __('Installments must be greater or equal to one', 'lkn-wc-gateway-cielo'),
            '124'  => __('Credit Card is Required', 'lkn-wc-gateway-cielo'),
            '125'  => __('Credit Card Expiration Date is required', 'lkn-wc-gateway-cielo'),
            '126'  => __('Credit Card Expiration Date is invalid', 'lkn-wc-gateway-cielo'),
            '127'  => __('You must provide CreditCard Number', 'lkn-wc-gateway-cielo'),
            '128'  => __('Card Number length exceeded', 'lkn-wc-gateway-cielo'),
            '129'  => __('Affiliation not found', 'lkn-wc-gateway-cielo'),
            '130'  => __('Could not get Credit Card', 'lkn-wc-gateway-cielo'),
            '131'  => __('MerchantKey is required', 'lkn-wc-gateway-cielo'),
            '132'  => __('MerchantKey is invalid', 'lkn-wc-gateway-cielo'),
            '133'  => __('Provider is not supported for this Payment Type', 'lkn-wc-gateway-cielo'),
            '134'  => __('FingerPrint length exceeded', 'lkn-wc-gateway-cielo'),
            '135'  => __('MerchantDefinedFieldValue length exceeded', 'lkn-wc-gateway-cielo'),
            '136'  => __('ItemDataName length exceeded', 'lkn-wc-gateway-cielo'),
            '137'  => __('ItemDataSKU length exceeded', 'lkn-wc-gateway-cielo'),
            '138'  => __('PassengerDataName length exceeded', 'lkn-wc-gateway-cielo'),
            '139'  => __('PassengerDataStatus length exceeded', 'lkn-wc-gateway-cielo'),
            '140'  => __('PassengerDataEmail length exceeded', 'lkn-wc-gateway-cielo'),
            '141'  => __('PassengerDataPhone length exceeded', 'lkn-wc-gateway-cielo'),
            '142'  => __('TravelDataRoute length exceeded', 'lkn-wc-gateway-cielo'),
            '143'  => __('TravelDataJourneyType length exceeded', 'lkn-wc-gateway-cielo'),
            '144'  => __('TravelLegDataDestination length exceeded', 'lkn-wc-gateway-cielo'),
            '145'  => __('TravelLegDataOrigin length exceeded', 'lkn-wc-gateway-cielo'),
            '146'  => __('SecurityCode length exceeded', 'lkn-wc-gateway-cielo'),
            '147'  => __('Address Street length exceeded', 'lkn-wc-gateway-cielo'),
            '148'  => __('Address Number length exceeded', 'lkn-wc-gateway-cielo'),
            '149'  => __('Address Complement length exceeded', 'lkn-wc-gateway-cielo'),
            '150'  => __('Address ZipCode length exceeded', 'lkn-wc-gateway-cielo'),
            '151'  => __('Address City length exceeded', 'lkn-wc-gateway-cielo'),
            '152'  => __('Address State length exceeded', 'lkn-wc-gateway-cielo'),
            '153'  => __('Address Country length exceeded', 'lkn-wc-gateway-cielo'),
            '154'  => __('Address District length exceeded', 'lkn-wc-gateway-cielo'),
            '155'  => __('Customer Name length exceeded', 'lkn-wc-gateway-cielo'),
            '156'  => __('Customer Identity length exceeded', 'lkn-wc-gateway-cielo'),
            '157'  => __('Customer IdentityType length exceeded', 'lkn-wc-gateway-cielo'),
            '158'  => __('Customer Email length exceeded', 'lkn-wc-gateway-cielo'),
            '159'  => __('ExtraData Name length exceeded', 'lkn-wc-gateway-cielo'),
            '160'  => __('ExtraData Value length exceeded', 'lkn-wc-gateway-cielo'),
            '161'  => __('Boleto Instructions length exceeded', 'lkn-wc-gateway-cielo'),
            '162'  => __('Boleto Demostrative length exceeded', 'lkn-wc-gateway-cielo'),
            '163'  => __('Return Url is required', 'lkn-wc-gateway-cielo'),
            '166'  => __('AuthorizeNow is required', 'lkn-wc-gateway-cielo'),
            '167'  => __('Antifraud not configured', 'lkn-wc-gateway-cielo'),
            '168'  => __('Recurrent Payment not found', 'lkn-wc-gateway-cielo'),
            '169'  => __('Recurrent Payment is not active', 'lkn-wc-gateway-cielo'),
            '170'  => __('Cartão Protegido not configured', 'lkn-wc-gateway-cielo'),
            '171'  => __('Affiliation data not sent', 'lkn-wc-gateway-cielo'),
            '172'  => __('Credential Code is required', 'lkn-wc-gateway-cielo'),
            '173'  => __('Payment method is not enabled', 'lkn-wc-gateway-cielo'),
            '174'  => __('Card Number is required', 'lkn-wc-gateway-cielo'),
            '175'  => __('EAN is required', 'lkn-wc-gateway-cielo'),
            '176'  => __('Payment Currency is not supported', 'lkn-wc-gateway-cielo'),
            '177'  => __('Card Number is invalid', 'lkn-wc-gateway-cielo'),
            '178'  => __('EAN is invalid', 'lkn-wc-gateway-cielo'),
            '179'  => __('The max number of installments allowed for recurring payment is 1', 'lkn-wc-gateway-cielo'),
            '180'  => __('The provided Card PaymentToken was not found', 'lkn-wc-gateway-cielo'),
            '181'  => __('The MerchantIdJustClick is not configured', 'lkn-wc-gateway-cielo'),
            '182'  => __('Brand is required', 'lkn-wc-gateway-cielo'),
            '183'  => __('Invalid customer bithdate', 'lkn-wc-gateway-cielo'),
            '184'  => __('Request could not be empty', 'lkn-wc-gateway-cielo'),
            '185'  => __('Brand is not supported by selected provider', 'lkn-wc-gateway-cielo'),
            '186'  => __('The selected provider does not support the options provided (Capture, Authenticate, Recurrent or Installments)', 'lkn-wc-gateway-cielo'),
            '187'  => __('ExtraData Collection contains one or more duplicated names', 'lkn-wc-gateway-cielo'),
            '188'  => __('Avs with CPF invalid', 'lkn-wc-gateway-cielo'),
            '189'  => __('Avs with length of street exceeded', 'lkn-wc-gateway-cielo'),
            '190'  => __('Avs with length of number exceeded', 'lkn-wc-gateway-cielo'),
            '191'  => __('Avs with length of district exceeded', 'lkn-wc-gateway-cielo'),
            '192'  => __('Avs with zip code invalid', 'lkn-wc-gateway-cielo'),
            '193'  => __('Split Amount must be greater than zero', 'lkn-wc-gateway-cielo'),
            '194'  => __('Split Establishment is Required', 'lkn-wc-gateway-cielo'),
            '195'  => __('PlatformId is required', 'lkn-wc-gateway-cielo'),
            '196'  => __('DeliveryAddress is required', 'lkn-wc-gateway-cielo'),
            '197'  => __('Street is required', 'lkn-wc-gateway-cielo'),
            '198'  => __('Number is required', 'lkn-wc-gateway-cielo'),
            '199'  => __('ZipCode is required', 'lkn-wc-gateway-cielo'),
            '200'  => __('City is required', 'lkn-wc-gateway-cielo'),
            '201'  => __('State is required', 'lkn-wc-gateway-cielo'),
            '202'  => __('District is required', 'lkn-wc-gateway-cielo'),
            '203'  => __('Cart item name is required', 'lkn-wc-gateway-cielo'),
            '204'  => __('Cart item quantity is required', 'lkn-wc-gateway-cielo'),
            '205'  => __('Cart item type is required', 'lkn-wc-gateway-cielo'),
            '206'  => __('Cart item name length exceeded', 'lkn-wc-gateway-cielo'),
            '207'  => __('Cart item description length exceeded', 'lkn-wc-gateway-cielo'),
            '208'  => __('Cart item sku length exceeded', 'lkn-wc-gateway-cielo'),
            '209'  => __('Shipping addressee sku length exceeded', 'lkn-wc-gateway-cielo'),
            '210'  => __('Shipping data cannot be null', 'lkn-wc-gateway-cielo'),
            '211'  => __('WalletKey is invalid', 'lkn-wc-gateway-cielo'),
            '212'  => __('Merchant Wallet Configuration not found', 'lkn-wc-gateway-cielo'),
            '213'  => __('Credit Card Number is invalid', 'lkn-wc-gateway-cielo'),
            '214'  => __('Credit Card Holder Must Have Only Letters', 'lkn-wc-gateway-cielo'),
            '215'  => __('Agency is required in Boleto Credential', 'lkn-wc-gateway-cielo'),
            '216'  => __('Customer IP address is invalid', 'lkn-wc-gateway-cielo'),
            '220'  => __('Service tax can not be sent with 1 installment', 'lkn-wc-gateway-cielo'),
            '228'  => __('Customer Address Country is required', 'lkn-wc-gateway-cielo'),
            '233'  => __('Service tax not supported for the given brand', 'lkn-wc-gateway-cielo'),
            '298'  => __('Split payment facilitator data not found', 'lkn-wc-gateway-cielo'),
            '300'  => __('MerchantId was not found', 'lkn-wc-gateway-cielo'),
            '301'  => __('Request IP is not allowed', 'lkn-wc-gateway-cielo'),
            '302'  => __('Sent MerchantOrderId is duplicated', 'lkn-wc-gateway-cielo'),
            '303'  => __('Sent OrderId does not exist', 'lkn-wc-gateway-cielo'),
            '304'  => __('Customer Identity is required', 'lkn-wc-gateway-cielo'),
            '306'  => __('Merchant is blocked', 'lkn-wc-gateway-cielo'),
            '307'  => __('Transaction not found', 'lkn-wc-gateway-cielo'),
            '308'  => __('Transaction not available to capture', 'lkn-wc-gateway-cielo'),
            '309'  => __('Transaction not available to void', 'lkn-wc-gateway-cielo'),
            '310'  => __('Payment method does not support this operation', 'lkn-wc-gateway-cielo'),
            '311'  => __('Refund is not enabled for this merchant', 'lkn-wc-gateway-cielo'),
            '312'  => __('Transaction not available to refund', 'lkn-wc-gateway-cielo'),
            '313'  => __('Recurrent Payment not found', 'lkn-wc-gateway-cielo'),
            '314'  => __('Invalid Integration', 'lkn-wc-gateway-cielo'),
            '315'  => __('Cannot change NextRecurrency with pending payment', 'lkn-wc-gateway-cielo'),
            '316'  => __('Cannot set NextRecurrency to past date', 'lkn-wc-gateway-cielo'),
            '317'  => __('Invalid Recurrency Day', 'lkn-wc-gateway-cielo'),
            '318'  => __('No transaction found', 'lkn-wc-gateway-cielo'),
            '319'  => __('Smart Recurrency is not enabled', 'lkn-wc-gateway-cielo'),
            '320'  => __('Cannot Update Affiliation because this recurrency has no affiliation saved', 'lkn-wc-gateway-cielo'),
            '321'  => __('Cannot Set EndDate to before next recurrency', 'lkn-wc-gateway-cielo'),
            '322'  => __('Zero Dollar Auth is not enabled', 'lkn-wc-gateway-cielo'),
            '323'  => __('Bin Query is not enabled', 'lkn-wc-gateway-cielo'),
            '841'  => __('Status does not allow capture', 'lkn-wc-gateway-cielo'),
            '902'  => __('Error processing the payment response', 'lkn-wc-gateway-cielo'),

            // ===== 3DS / fraud (Provider codes used by the plugin) =====
            'BP171' => __('Rejected by fraud risk (Velocity)', 'lkn-wc-gateway-cielo'),
            'BP172' => __('Transaction aborted during card validation', 'lkn-wc-gateway-cielo'),
            'BP335' => __('Cancelled due to transactional error in Payment Split', 'lkn-wc-gateway-cielo'),
            'BP900' => __('Could not authenticate the transaction (3DS)', 'lkn-wc-gateway-cielo'),

            // ===== Pix =====
            '422'  => __('Error on Merchant Integration', 'lkn-wc-gateway-cielo'),
            'BP901' => __('Failure in the Pix operation', 'lkn-wc-gateway-cielo'),
            'BP904' => __('The provided JSON is not valid', 'lkn-wc-gateway-cielo'),

            // ===== Generic failure returned by Cielo =====
            'GF'   => __('General failure', 'lkn-wc-gateway-cielo'),

            // ===== ABECS standardized authorization/decline codes =====
            // The ABECS table is organized per brand; when a code has brand
            // specific texts, the generic/POS message is used here.
            '03'   => __('Invalid merchant', 'lkn-wc-gateway-cielo'),
            '04'   => __('Retry the transaction', 'lkn-wc-gateway-cielo'),
            '05'   => __('Generic decline', 'lkn-wc-gateway-cielo'),
            '06'   => __('Card error', 'lkn-wc-gateway-cielo'),
            '07'   => __('Confirmed fraud - transaction not allowed on this card', 'lkn-wc-gateway-cielo'),
            '12'   => __('Card error', 'lkn-wc-gateway-cielo'),
            '13'   => __('Invalid transaction amount', 'lkn-wc-gateway-cielo'),
            '14'   => __('Card number does not belong to the issuer / invalid card', 'lkn-wc-gateway-cielo'),
            '15'   => __('Issuer not found (incorrect BIN)', 'lkn-wc-gateway-cielo'),
            '19'   => __('Acquirer problem - card error', 'lkn-wc-gateway-cielo'),
            '23'   => __('Invalid installment amount - invalid installment plan', 'lkn-wc-gateway-cielo'),
            '30'   => __('Format error (messaging) - card error', 'lkn-wc-gateway-cielo'),
            '38'   => __('Too many PIN attempts', 'lkn-wc-gateway-cielo'),
            '39'   => __('Incorrect function (credit)', 'lkn-wc-gateway-cielo'),
            '41'   => __('Lost card - transaction not allowed', 'lkn-wc-gateway-cielo'),
            '43'   => __('Stolen card - transaction not allowed', 'lkn-wc-gateway-cielo'),
            '46'   => __('Account closed - transaction not allowed on this card', 'lkn-wc-gateway-cielo'),
            '51'   => __('Insufficient funds/limit', 'lkn-wc-gateway-cielo'),
            '52'   => __('Incorrect function (debit)', 'lkn-wc-gateway-cielo'),
            '53'   => __('Incorrect function (debit)', 'lkn-wc-gateway-cielo'),
            '54'   => __('Expired card / invalid expiration date', 'lkn-wc-gateway-cielo'),
            '55'   => __('Invalid PIN', 'lkn-wc-gateway-cielo'),
            '56'   => __('Invalid card number / does not belong to the issuer', 'lkn-wc-gateway-cielo'),
            '57'   => __('Transaction not allowed on this card', 'lkn-wc-gateway-cielo'),
            '58'   => __('Transaction not allowed - terminal capability', 'lkn-wc-gateway-cielo'),
            '59'   => __('Suspected fraud / travel notice', 'lkn-wc-gateway-cielo'),
            '61'   => __('Amount exceeded / withdrawal', 'lkn-wc-gateway-cielo'),
            '62'   => __('Temporary block', 'lkn-wc-gateway-cielo'),
            '63'   => __('Security violation', 'lkn-wc-gateway-cielo'),
            '64'   => __('Invalid minimum transaction amount', 'lkn-wc-gateway-cielo'),
            '65'   => __('Withdrawal count exceeded', 'lkn-wc-gateway-cielo'),
            '66'   => __('Anti-money-laundering non-compliance', 'lkn-wc-gateway-cielo'),
            '73'   => __('Withdrawal not available', 'lkn-wc-gateway-cielo'),
            '75'   => __('Too many PIN attempts', 'lkn-wc-gateway-cielo'),
            '76'   => __('Invalid or nonexistent destination account', 'lkn-wc-gateway-cielo'),
            '77'   => __('Invalid or nonexistent source account', 'lkn-wc-gateway-cielo'),
            '78'   => __('New card not activated / blocked card (NFC)', 'lkn-wc-gateway-cielo'),
            '79'   => __('Security/fraud - transaction not allowed on this card', 'lkn-wc-gateway-cielo'),
            '80'   => __('Invalid reversal', 'lkn-wc-gateway-cielo'),
            '81'   => __('Function blocked', 'lkn-wc-gateway-cielo'),
            '82'   => __('Invalid card (cryptogram) - card error', 'lkn-wc-gateway-cielo'),
            '83'   => __('Expired PIN / PIN encryption error', 'lkn-wc-gateway-cielo'),
            '86'   => __('Invalid PIN', 'lkn-wc-gateway-cielo'),
            '88'   => __('Expired PIN / invalid card (cryptogram)', 'lkn-wc-gateway-cielo'),
            '91'   => __('Issuer unavailable - communication failure', 'lkn-wc-gateway-cielo'),
            '92'   => __('Not found by the router', 'lkn-wc-gateway-cielo'),
            '93'   => __('Transaction denied due to a legal restriction', 'lkn-wc-gateway-cielo'),
            '94'   => __('Duplicated tracing data value', 'lkn-wc-gateway-cielo'),
            '96'   => __('System failure - communication failure', 'lkn-wc-gateway-cielo'),

            // ===== ABECS alphanumeric codes =====
            'A'    => __('Incorrect function (debit)', 'lkn-wc-gateway-cielo'),
            'C'    => __('Incorrect function (credit)', 'lkn-wc-gateway-cielo'),
            '5C'   => __('Transaction not supported or blocked by the issuer', 'lkn-wc-gateway-cielo'),
            '9G'   => __('Transaction not authorized', 'lkn-wc-gateway-cielo'),
            'B1'   => __('Surcharge not supported', 'lkn-wc-gateway-cielo'),
            'B2'   => __('Surcharge not supported by the debit network', 'lkn-wc-gateway-cielo'),
            'FM'   => __('Use the chip', 'lkn-wc-gateway-cielo'),
            'P5'   => __('PIN change / unlock - invalid PIN', 'lkn-wc-gateway-cielo'),
            'P6'   => __('New PIN not accepted', 'lkn-wc-gateway-cielo'),
            'N0'   => __('Force STIP', 'lkn-wc-gateway-cielo'),
            'N3'   => __('Withdrawal not available', 'lkn-wc-gateway-cielo'),
            'N4'   => __('Amount exceeded / withdrawal', 'lkn-wc-gateway-cielo'),
            'N7'   => __('Security violation', 'lkn-wc-gateway-cielo'),
            'N8'   => __('Pre-authorization mismatch - amount differs from the authorized one', 'lkn-wc-gateway-cielo'),
            'R0'   => __('Recurring payment suspended for one service', 'lkn-wc-gateway-cielo'),
            'R1'   => __('Recurring payment suspended for all services', 'lkn-wc-gateway-cielo'),
            'R2'   => __('Transaction not qualified for Visa PIN', 'lkn-wc-gateway-cielo'),
            'R3'   => __('All authorization orders suspended', 'lkn-wc-gateway-cielo'),
        );

        return $codes;
    }

    /**
     * ReasonCode / ReasonMessage codes.
     *
     * @return array<string, string>
     */
    private static function reasonCodes()
    {
        static $codes = null;

        if (null !== $codes) {
            return $codes;
        }

        $codes = array(
            '0'   => __('Successful', 'lkn-wc-gateway-cielo'),
            '1'   => __('AffiliationNotFound', 'lkn-wc-gateway-cielo'),
            '2'   => __('IssuficientFunds', 'lkn-wc-gateway-cielo'),
            '3'   => __('CouldNotGetCreditCard', 'lkn-wc-gateway-cielo'),
            '4'   => __('ConnectionWithAcquirerFailed', 'lkn-wc-gateway-cielo'),
            '5'   => __('InvalidTransactionType', 'lkn-wc-gateway-cielo'),
            '6'   => __('InvalidPaymentPlan', 'lkn-wc-gateway-cielo'),
            '7'   => __('Denied', 'lkn-wc-gateway-cielo'),
            '8'   => __('Scheduled', 'lkn-wc-gateway-cielo'),
            '9'   => __('Waiting', 'lkn-wc-gateway-cielo'),
            '10'  => __('Authenticated', 'lkn-wc-gateway-cielo'),
            '11'  => __('NotAuthenticated', 'lkn-wc-gateway-cielo'),
            '12'  => __('ProblemsWithCreditCard', 'lkn-wc-gateway-cielo'),
            '13'  => __('CardCanceled', 'lkn-wc-gateway-cielo'),
            '14'  => __('BlockedCreditCard', 'lkn-wc-gateway-cielo'),
            '15'  => __('CardExpired', 'lkn-wc-gateway-cielo'),
            '16'  => __('AbortedByFraud', 'lkn-wc-gateway-cielo'),
            '17'  => __('CouldNotAntifraud', 'lkn-wc-gateway-cielo'),
            '18'  => __('TryAgain', 'lkn-wc-gateway-cielo'),
            '19'  => __('InvalidAmount', 'lkn-wc-gateway-cielo'),
            '20'  => __('ProblemsWithIssuer', 'lkn-wc-gateway-cielo'),
            '21'  => __('InvalidCardNumber', 'lkn-wc-gateway-cielo'),
            '22'  => __('TimeOut', 'lkn-wc-gateway-cielo'),
            '23'  => __('CartaoProtegidoIsNotEnabled', 'lkn-wc-gateway-cielo'),
            '24'  => __('PaymentMethodIsNotEnabled', 'lkn-wc-gateway-cielo'),
            '25'  => __('CouldNotFindPaymentToken', 'lkn-wc-gateway-cielo'),
            '26'  => __('MerchantIdJustClickNotFound', 'lkn-wc-gateway-cielo'),
            '27'  => __('BrandNotSupported', 'lkn-wc-gateway-cielo'),
            '28'  => __('CardOptionsNotSupported', 'lkn-wc-gateway-cielo'),
            '29'  => __('WalletKeyIsInvalid', 'lkn-wc-gateway-cielo'),
            '30'  => __('MerchantWalletConfigurationNotFound', 'lkn-wc-gateway-cielo'),
            '31'  => __('BoletoRequiredDataNotSupported', 'lkn-wc-gateway-cielo'),
            '32'  => __('ConnectionWithAntifraudFailed', 'lkn-wc-gateway-cielo'),
            '33'  => __('AbortedByCardVerification', 'lkn-wc-gateway-cielo'),
            '34'  => __('ProblemsWithAcquirer', 'lkn-wc-gateway-cielo'),
            '35'  => __('ValidationError', 'lkn-wc-gateway-cielo'),
            '36'  => __('AcquirerTransactionNotFound', 'lkn-wc-gateway-cielo'),
            '37'  => __('SplitTransactionalError', 'lkn-wc-gateway-cielo'),
            '38'  => __('MerchantSplitConfigurationNotFound', 'lkn-wc-gateway-cielo'),
            '39'  => __('SplitSoftDescriptorIsRequired', 'lkn-wc-gateway-cielo'),
            '40'  => __('SplitFraudAnalysisIsRequired', 'lkn-wc-gateway-cielo'),
            '41'  => __('SplitAntifraudMerchantConfigurationNotFound', 'lkn-wc-gateway-cielo'),
            '42'  => __('ProviderNotFound', 'lkn-wc-gateway-cielo'),
            '43'  => __('PaymentSettingsNotFound', 'lkn-wc-gateway-cielo'),
            '44'  => __('SubAcquirerMerchantConfigurationNotFound', 'lkn-wc-gateway-cielo'),
            '45'  => __('AbortedBySubAcquirer', 'lkn-wc-gateway-cielo'),
            '98'  => __('InvalidRequest', 'lkn-wc-gateway-cielo'),
            '99'  => __('InternalError', 'lkn-wc-gateway-cielo'),
            '100' => __('CieloPayCardHolderIsNotActive', 'lkn-wc-gateway-cielo'),
            '101' => __('CieloPayStrongValidationIsInvalid', 'lkn-wc-gateway-cielo'),
            '102' => __('CieloPayExpireDateDoesNotMatch', 'lkn-wc-gateway-cielo'),
            '103' => __('CieloPayCardHolderApiError', 'lkn-wc-gateway-cielo'),
            '104' => __('SplitPaymentFacilitatorDataNotFound', 'lkn-wc-gateway-cielo'),
        );

        return $codes;
    }

    /**
     * 3DS return codes.
     *
     * @return array<string, string>
     */
    private static function threeDsCodes()
    {
        static $codes = null;

        if (null !== $codes) {
            return $codes;
        }

        $codes = array(
            '100'    => __('Transaction successful', 'lkn-wc-gateway-cielo'),
            '101'    => __('One or more required fields are missing from the request', 'lkn-wc-gateway-cielo'),
            '102'    => __('One or more request fields contain invalid data', 'lkn-wc-gateway-cielo'),
            '150'    => __('Error: general system failure', 'lkn-wc-gateway-cielo'),
            '151'    => __('Error: the request was received but the server timed out', 'lkn-wc-gateway-cielo'),
            '152'    => __('Error: the request was received but the service timed out', 'lkn-wc-gateway-cielo'),
            '234'    => __('There is a problem with your merchant configuration', 'lkn-wc-gateway-cielo'),
            '475'    => __('The customer is enrolled in payer authentication', 'lkn-wc-gateway-cielo'),
            '476'    => __('The customer could not be authenticated', 'lkn-wc-gateway-cielo'),
            'MPI600' => __('Brand does not support authentication', 'lkn-wc-gateway-cielo'),
            'MPI601' => __('Challenge skipped', 'lkn-wc-gateway-cielo'),
            'MPI900' => __('An error occurred', 'lkn-wc-gateway-cielo'),
            'MPI901' => __('Unexpected error', 'lkn-wc-gateway-cielo'),
            'MPI902' => __('Unexpected authentication response', 'lkn-wc-gateway-cielo'),
        );

        return $codes;
    }
}
