<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://linknacional.com.br
 * @since             1.0.0
 * @package           LknWCCieloPaymentGateway
 *
 * @wordpress-plugin
 * Plugin Name:       CIELO API PIX, credit card, debit payment for WooCommerce
 * Plugin URI:        https://www.linknacional.com.br/wordpress/woocommerce/cielo/
 * Description:       Adds the Cielo API 3.0 Payments gateway to your WooCommerce website.
 * Version:           1.34.0
 * Requires PHP:      8.2
 * Requires at least: 5.8
 * Author:            Link Nacional
 * Author URI:        https://linknacional.com.br
 * Text Domain:       lkn-wc-gateway-cielo
 * Domain Path:       /languages/
 * Requires Plugins:  woocommerce
 * License:           GPL-3.0+
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.txt
 */

// If this file is called directly, abort.
if (! defined('WPINC')) {
    die;
}

require_once 'lkn-wc-gateway-cielo-file.php';
