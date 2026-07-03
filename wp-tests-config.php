<?php
/**
 * wp-tests-config.php — Configuração do banco de testes local
 *
 * Carregado pelo bootstrap do wp-phpunit/wp-phpunit.
 * Define credenciais do banco local_tests e ABSPATH.
 */

// ── Banco de dados de teste ──────────────────────────────────────────
define('DB_NAME', 'local_tests');
define('DB_USER', 'root');
define('DB_PASSWORD', 'root');
define('DB_HOST', '127.0.0.1:10024');   // Porta padrão do Local WP MySQL via socket

// Table prefix — use wptests_ para não colidir com tabelas reais
$table_prefix = 'wptests_';

// ── WordPress Core path ──────────────────────────────────────────────
// roots/wordpress instala o core em ./wordpress/
define('ABSPATH', dirname(__FILE__) . '/wordpress/');

// ── URLs do site de teste ────────────────────────────────────────────
define('WP_TESTS_DOMAIN', 'example.org');
define('WP_TESTS_EMAIL', 'admin@example.org');
define('WP_TESTS_TITLE', 'Test Site');

define('WP_SITEURL', 'http://' . WP_TESTS_DOMAIN);
define('WP_HOME', 'http://' . WP_TESTS_DOMAIN);

// ── Caminhos customizados de conteúdo ────────────────────────────────
// O projeto está em: wp-content/plugins/lkn-wc-gateway-cielo/
// WordPress core está em: wordpress/
// Plugins estão no diretório pai do projeto (wp-content/plugins/)
define('WP_CONTENT_DIR', dirname(__FILE__, 3));
define('WP_PLUGIN_DIR', dirname(__FILE__, 2));

// ── Constantes de debug ──────────────────────────────────────────────
define('WP_DEBUG', true);
define('WP_DEBUG_DISPLAY', false);
define('WP_DEBUG_LOG', false);

define('SAVEQUERIES', false);
define('JETPACK_DEV_DEBUG', true);
