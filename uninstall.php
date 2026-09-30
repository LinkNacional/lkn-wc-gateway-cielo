<?php

/**
 * Uninstall Lkn_WC_Cielo_Payment.
 *
 * @license     https://opensource.org/licenses/gpl-license GNU Public License
 *
 * @since       1.0
 */

// Exit if accessed directly.
if ( ! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Clear WooCommerce Gateway options
delete_option('woocommerce_lkn_cielo_credit_settings');
delete_option('woocommerce_lkn_cielo_debit_settings');

// Metadados das notificações de atualização do plugin PRO (aviso, tela e e-mail).
// Removê-los na desinstalação permite reinstalar e testar o fluxo do zero, sem
// herdar as flags de "tela já exibida", "aviso dispensado" ou "e-mail já enviado".
delete_option('lkn_cielo_pro_update_screen_shown');
delete_option('lkn_cielo_pro_update_notice_dismissed');
delete_option('lkn_cielo_pro_update_email_sent');
