<?php
/**
 * Plugin Name: Pay with bunq for WooCommerce
 * Description: Accept payments in your WooCommerce shop with just your bunq account.
 * Version: 2.1.1
 * Author: Patrick Kivits
 * Author URI: https://www.patrickkivits.nl
 * Requires at least: 5.9
 * Requires PHP: 8.1
 * Requires Plugins: woocommerce
 * Text Domain: pay-with-bunq-for-woocommerce
 * Domain Path: /languages
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * WC requires at least: 2.2.0
 * WC tested up to: 11.2
 *
 * @package PatrickKivits\BunqForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

// Another copy is already loaded, typically the GitHub install in wp-content/plugins/bunq-for-woocommerce
// next to this wordpress.org one. Both share their settings, so the old copy only has to be removed.
if ( defined( 'BUNQ_FOR_WOOCOMMERCE_PLUGIN_FILE' ) ) {
	add_action(
		'admin_notices',
		static function () {
			echo '<div class="notice notice-warning"><p>' . esc_html__( 'Pay with bunq for WooCommerce is installed twice. Deactivate and delete the older copy; your settings and the bunq connection are kept.', 'pay-with-bunq-for-woocommerce' ) . '</p></div>';
		}
	);
	return;
}

define( 'BUNQ_FOR_WOOCOMMERCE_PLUGIN_FILE', __FILE__ );

// The plugin's classes (src/) and its dependencies, both through the Composer autoloader.
require_once __DIR__ . '/vendor/autoload.php';

PatrickKivits\BunqForWooCommerce\Plugin::instance()->boot();
