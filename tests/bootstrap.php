<?php
/**
 * PHPUnit Bootstrap — WordPress + WooCommerce + Plugin
 *
 * Carrega o WordPress test suite, WooCommerce e o plugin Cielo.
 * NÃO usa Brain\Monkey — funções WordPress são reais (banco local_tests).
 *
 * Requer: wp-tests-config.php na raiz do projeto com credenciais do banco.
 */

// ── 1. Configurar caminho para wp-tests-config.php ───────────────────
//    wp-phpunit espera essa variável de ambiente
putenv('WP_PHPUNIT__TESTS_CONFIG=' . dirname(__DIR__) . '/wp-tests-config.php');

// ── 2. Carregar WordPress test suite ─────────────────────────────────
//    Isto inicializa o WordPress completo (banco local_tests)
$_tests_dir = dirname(__DIR__) . '/vendor/wp-phpunit/wp-phpunit/includes';

if (! file_exists("$_tests_dir/bootstrap.php")) {
    fwrite(STDERR, "Erro: WordPress test suite não encontrada em $_tests_dir\n");
    fwrite(STDERR, "Execute: composer install\n");
    exit(1);
}

require_once "$_tests_dir/bootstrap.php";

// ── 3. Carregar WooCommerce ──────────────────────────────────────────
//    Instalado por wpackagist-plugin/woocommerce em wp-content/plugins/
$wc_dir = dirname(__DIR__) . '/wp-content/plugins/woocommerce/woocommerce.php';

if (file_exists($wc_dir)) {
    // Define constantes que o WC espera antes de carregar
    if (! defined('WC_TAX_ROUNDING_MODE')) {
        define('WC_TAX_ROUNDING_MODE', 'auto');
    }
    if (! defined('WC_USE_TRANSACTIONS')) {
        define('WC_USE_TRANSACTIONS', false);
    }

    require_once $wc_dir;

    // Ativar WooCommerce no site de teste (se ainda não estiver)
    if (! is_plugin_active('woocommerce/woocommerce.php')) {
        activate_plugin('woocommerce/woocommerce.php');
    }
} else {
    fwrite(STDERR, "Aviso: WooCommerce não encontrado em $wc_dir\n");
    fwrite(STDERR, "Execute: composer require --dev wpackagist-plugin/woocommerce\n");
}

// ── 4. Carregar o plugin Cielo ───────────────────────────────────────
//    O plugin já está em wp-content/plugins/lkn-wc-gateway-cielo/
//    WP_PLUGIN_DIR foi configurado em wp-tests-config.php
$plugin_main_file = dirname(__DIR__) . '/lkn-wc-gateway-cielo.php';

if (file_exists($plugin_main_file)) {
    // O plugin define constantes e chama run_LknWCCieloPayment()
    // que registra gateways, hooks, endpoints etc.
    // is_plugin_active() espera o basename relativo a WP_PLUGIN_DIR
    $plugin_basename = 'lkn-wc-gateway-cielo/lkn-wc-gateway-cielo.php';

    // Ativar no banco de testes
    $active_plugins = get_option('active_plugins', []);
    if (! in_array($plugin_basename, $active_plugins, true)) {
        $active_plugins[] = $plugin_basename;
        update_option('active_plugins', array_unique($active_plugins));
    }

    // Carregar o bootstrap do plugin (define constantes, autoloader, e executa)
    require_once $plugin_main_file;
} else {
    fwrite(STDERR, "Erro: Plugin Cielo não encontrado em $plugin_main_file\n");
    exit(1);
}

// ── 6. Load test helpers ─────────────────────────────────────────────
if (file_exists(__DIR__ . '/TestHelpers.php')) {
    require_once __DIR__ . '/TestHelpers.php';
}
