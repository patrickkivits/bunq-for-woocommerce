<?php
/**
 * PHPUnit bootstrap: loads the WordPress/WooCommerce stand-ins and then the plugin itself, the way WordPress
 * would (the plugin file, followed by the plugins_loaded and woocommerce_blocks_loaded actions).
 */

// The bunq SDK still declares implicitly nullable parameters, which PHP 8.4+ reports as deprecated whenever one
// of its classes is compiled. That is the SDK's concern, not the plugin's, so deprecations are left out here just
// as they are in the compatibility workflows.
error_reporting(E_ALL & ~E_DEPRECATED);

require __DIR__ . '/Environment.php';
require __DIR__ . '/stubs/wordpress.php';
require __DIR__ . '/stubs/woocommerce-namespaced.php';
require __DIR__ . '/TestCase.php';

BunqTest\Environment::reset();

// The requirements check generates an RSA key pair once to prove OpenSSL works and remembers the outcome per
// PHP/OpenSSL build; seed that so loading the plugin does not depend on it (RequirementsTest covers the check).
BunqTest\Environment::$options['wc_bunq_gateway.requirements'] = md5(PHP_VERSION . '|' . (defined('OPENSSL_VERSION_TEXT') ? OPENSSL_VERSION_TEXT : ''));

require dirname(__DIR__) . '/bunq-for-woocommerce.php';

do_action('plugins_loaded');            // declares WC_Bunq_Gateway
do_action('woocommerce_blocks_loaded');  // loads the block checkout integration

BunqTest\Environment::$bootHooks = BunqTest\Environment::$hooks;
BunqTest\Environment::reset();
