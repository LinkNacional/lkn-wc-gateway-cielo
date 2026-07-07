<?php

use Lkn\WCCieloPaymentGateway\Includes\LknWCCieloPayment;
use Lkn\WCCieloPaymentGateway\Includes\LknWCCieloPaymentActivator;
use Lkn\WCCieloPaymentGateway\Includes\LknWCCieloPaymentDeactivator;

// If this file is called directly, abort.
if (! defined('WPINC')) {
    die;
}

require_once __DIR__ . '/vendor/autoload.php';

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
if (! defined('LKN_WC_CIELO_VERSION')) {
    define('LKN_WC_CIELO_VERSION', '1.35.0');
}

if (! defined('LKN_WC_CIELO_FILE')) {
    define('LKN_WC_CIELO_FILE', __DIR__ . '/lkn-wc-gateway-cielo.php');
}

if (! defined('LKN_WC_GATEWAY_CIELO_DIR')) {
    define('LKN_WC_GATEWAY_CIELO_DIR', __DIR__ . '/');
}

if (! defined('LKN_WC_GATEWAY_CIELO_DIR_URL')) {
    define('LKN_WC_GATEWAY_CIELO_DIR_URL', plugin_dir_url(__FILE__));
}

if (! defined('LKN_WC_GATEWAY_CIELO_BASENAME')) {
    define('LKN_WC_GATEWAY_CIELO_BASENAME', 'lkn-wc-gateway-cielo/lkn-wc-gateway-cielo.php');
}

if (! defined('LKN_WC_GATEWAY_CIELO_URL')) {
    define('LKN_WC_GATEWAY_CIELO_URL', plugin_dir_url(__FILE__));
}

if (! defined('LKN_WC_GATEWAY_CIELO_MIN_WC_VERSION')) {
    define('LKN_WC_GATEWAY_CIELO_MIN_WC_VERSION', '5.0.0');
}

if (! defined('LKN_WC_CIELO_TRANSLATION_PATH')) {
    define('LKN_WC_CIELO_TRANSLATION_PATH', __DIR__ . '/languages/');
}

if (! defined('LKN_WC_CIELO_FILE_BASENAME')) {
    define('LKN_WC_CIELO_FILE_BASENAME', 'lkn-wc-gateway-cielo/lkn-wc-gateway-cielo.php');
}

if (! defined('LKN_WC_CIELO_BASE_FILE')) {
    define('LKN_WC_CIELO_BASE_FILE', __DIR__ . '/lkn-wc-gateway-cielo.php');
}

if (! defined('LKN_WC_CIELO_WPP_NUMBER')) {
    define('LKN_WC_CIELO_WPP_NUMBER', '551135223406');
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/LknWCCieloPaymentActivator.php
 */
function activate_LknWCCieloPayment(): void
{
    LknWCCieloPaymentActivator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/LknWCCieloPaymentDeactivator.php
 */
function deactivate_LknWCCieloPayment(): void
{
    LknWCCieloPaymentDeactivator::deactivate();
}

register_activation_hook(LKN_WC_CIELO_FILE, 'activate_LknWCCieloPayment');
register_deactivation_hook(LKN_WC_CIELO_FILE, 'deactivate_LknWCCieloPayment');

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_LknWCCieloPayment(): void
{
    $plugin = new LknWCCieloPayment();
    $plugin->run();
}

run_LknWCCieloPayment();
