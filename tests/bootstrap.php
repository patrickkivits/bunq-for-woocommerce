<?php
/**
 * PHPUnit bootstrap: loads the WordPress/WooCommerce stand-ins and then the plugin itself, the way WordPress
 * would (the plugin file, followed by the woocommerce_blocks_loaded action).
 */

// The plugin's files refuse to run outside WordPress.
define('ABSPATH', __DIR__ . '/');

require __DIR__ . '/Environment.php';
require __DIR__ . '/stubs/wordpress.php';
require __DIR__ . '/stubs/woocommerce-namespaced.php';
require __DIR__ . '/TestCase.php';

BunqTest\Environment::reset();

// The requirements check generates an RSA key pair once to prove OpenSSL works and remembers the outcome per
// PHP/OpenSSL build; seed that so loading the plugin does not depend on it (RequirementsTest covers the check).
BunqTest\Environment::$options['wc_bunq_gateway.requirements'] = md5(PHP_VERSION . '|' . (defined('OPENSSL_VERSION_TEXT') ? OPENSSL_VERSION_TEXT : ''));

require dirname(__DIR__) . '/bunq-for-woocommerce.php';

do_action('woocommerce_blocks_loaded');  // registers the block checkout integration

require __DIR__ . '/FakeBunqApi.php';
require __DIR__ . '/FakeBunqServer.php';

BunqTest\Environment::$bootHooks = BunqTest\Environment::$hooks;
BunqTest\Environment::reset();
