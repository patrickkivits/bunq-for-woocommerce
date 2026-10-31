<?php
/**
 * Plugin Name: bunq for WooCommerce
 * Description: Accept payments in your WooCommerce shop with just your bunq account.
 * Version: 1.9.2
 * Author: Patrick Kivits
 * Author URI: https://www.patrickkivits.nl
 * Requires at least: 4.7
 * Requires PHP: 7.3
 * Requires Plugins: woocommerce
 * Tested up to: 7.1
 * Text Domain: bunq-for-woocommerce
 * Domain Path: /languages
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * WC requires at least: 2.2.0
 * WC tested up to: 11.2
 *
 * @package PatrickKivits\BunqForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

define( 'BUNQ_FOR_WOOCOMMERCE_PLUGIN_FILE', __FILE__ );

// The plugin's classes (src/) and its dependencies, both through the Composer autoloader.
require_once __DIR__ . '/vendor/autoload.php';

PatrickKivits\BunqForWooCommerce\Plugin::instance()->boot();
