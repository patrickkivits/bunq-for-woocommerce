<?php
/**
 * Environment requirements.
 *
 * @package PatrickKivits\BunqForWooCommerce
 */

namespace PatrickKivits\BunqForWooCommerce;

use Exception;
use PatrickKivits\BunqForWooCommerce\Bunq\ApiClient;

defined( 'ABSPATH' ) || exit;

/**
 * Checks that WordPress, WooCommerce and PHP can run the plugin, and deactivates it when they cannot.
 */
final class Requirements {

	/**
	 * Remembers the PHP/OpenSSL build the OpenSSL check passed on.
	 */
	const OPTION = 'wc_bunq_gateway.requirements';

	const MIN_WP_VERSION  = '4.7';
	const MIN_PHP_VERSION = '7.3.0';

	/**
	 * The main plugin file.
	 *
	 * @var string
	 */
	private $plugin_file;

	/**
	 * Constructor.
	 *
	 * @param string $plugin_file The main plugin file.
	 */
	public function __construct( $plugin_file ) {
		$this->plugin_file = $plugin_file;
	}

	/**
	 * The PHP extensions the bunq API client needs.
	 *
	 * @return string[]
	 */
	public static function extensions() {
		return array( 'openssl', 'curl', 'json', 'mbstring' );
	}

	/**
	 * Whether the plugin can run here.
	 *
	 * @return bool
	 */
	public function check() {
		if ( version_compare( get_bloginfo( 'version' ), self::MIN_WP_VERSION, '<' ) ) {
			return false;
		}

		if ( ! in_array( 'woocommerce/woocommerce.php', (array) apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WordPress core filter.
			return false;
		}

		if ( version_compare( PHP_VERSION, self::MIN_PHP_VERSION, '<' ) ) {
			return false;
		}

		foreach ( self::extensions() as $extension ) {
			if ( ! extension_loaded( $extension ) ) {
				return false;
			}
		}

		// Generating a key pair proves OpenSSL can do what the bunq API client needs, but it costs tens of milliseconds, so
		// the outcome is remembered per PHP/OpenSSL build in an autoloaded option (a transient would cost two queries
		// per request without an object cache).
		$fingerprint = md5( PHP_VERSION . '|' . ( defined( 'OPENSSL_VERSION_TEXT' ) ? OPENSSL_VERSION_TEXT : '' ) );

		if ( get_option( self::OPTION ) === $fingerprint ) {
			return true;
		}

		try {
			ApiClient::generate_key_pair();
		} catch ( Exception $exception ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Only under WP_DEBUG.
				error_log( $exception->getMessage() );
			}

			return false;
		}

		update_option( self::OPTION, $fingerprint, true );

		return true;
	}

	/**
	 * Deactivate the plugin (admin_init).
	 */
	public function disable_plugin() {
		$plugin = plugin_basename( $this->plugin_file );

		if ( current_user_can( 'activate_plugins' ) && is_plugin_active( $plugin ) ) {
			deactivate_plugins( $plugin );

			// Hide the default "Plugin activated" notice.
			if ( isset( $_GET['activate'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				unset( $_GET['activate'] );
			}
		}
	}

	/**
	 * Explain why the plugin was deactivated (admin_notices).
	 */
	public function show_notice() {
		echo '<div class="error"><p><strong>bunq for WooCommerce</strong> ' . esc_html__( 'cannot be activated due to incompatible environment.', 'bunq-for-woocommerce' ) . '</p></div>';
	}
}
