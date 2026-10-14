<?php
/**
 * The plugin.
 *
 * @package PatrickKivits\BunqForWooCommerce
 */

namespace PatrickKivits\BunqForWooCommerce;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;
use Automattic\WooCommerce\Utilities\FeaturesUtil;
use PatrickKivits\BunqForWooCommerce\Blocks\CheckoutIntegration;
use PatrickKivits\BunqForWooCommerce\Bunq\ApiClient;
use PatrickKivits\BunqForWooCommerce\Gateway\Gateway;
use PatrickKivits\BunqForWooCommerce\Orders\PaymentMonitor;

defined( 'ABSPATH' ) || exit;

/**
 * Wires the plugin into WordPress and WooCommerce and holds the services that are shared within a request.
 */
final class Plugin {

	/**
	 * The plugin version; matches the Version header of the main plugin file.
	 */
	const VERSION = '1.8.1';

	/**
	 * The text domain.
	 */
	const TEXT_DOMAIN = 'bunq-for-woocommerce';

	/**
	 * Class names of earlier versions, still available for code that refers to them, with the WooCommerce class
	 * that must be loaded before they can be.
	 */
	const LEGACY_CLASSES = array(
		'wc_bunq_gateway'                    => array( Gateway::class, 'WC_Payment_Gateway' ),
		'wc_bunq_woocommerce_block_checkout' => array( CheckoutIntegration::class, AbstractPaymentMethodType::class ),
	);

	/**
	 * The instance.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * The main plugin file.
	 *
	 * @var string
	 */
	private $file;

	/**
	 * The bunq API, shared so its rate limiting covers every call in the request.
	 *
	 * @var ApiClient|null
	 */
	private $api_client = null;

	/**
	 * Payment confirmation outside the bunq callback.
	 *
	 * @var PaymentMonitor|null
	 */
	private $payment_monitor = null;

	/**
	 * Constructor.
	 *
	 * @param string $file The main plugin file.
	 */
	private function __construct( $file ) {
		$this->file = $file;
	}

	/**
	 * The plugin.
	 *
	 * @return Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self( BUNQ_FOR_WOOCOMMERCE_PLUGIN_FILE );
		}

		return self::$instance;
	}

	/**
	 * Register the hooks. Runs when WordPress loads the plugin.
	 */
	public function boot() {
		spl_autoload_register( array( $this, 'load_legacy_class' ) );

		add_action( 'before_woocommerce_init', array( $this, 'declare_compatibility' ) );
		add_action( 'init', array( $this, 'load_textdomain' ), 1 );

		$requirements = new Requirements( $this->file );

		if ( ! $requirements->check() ) {
			add_action( 'admin_init', array( $requirements, 'disable_plugin' ) );
			add_action( 'admin_notices', array( $requirements, 'show_notice' ) );

			return;
		}

		add_action( 'plugins_loaded', array( $this, 'define_legacy_classes' ) );
		add_filter( 'woocommerce_payment_gateways', array( $this, 'add_gateway' ) );
		add_action( 'admin_init', array( $this, 'handle_oauth_return' ) );
		add_action( 'admin_init', array( $this, 'handle_disconnect' ) );
		add_filter( 'woocommerce_rest_prepare_payment_gateway', array( Gateway::class, 'hide_secrets_from_rest' ), 10, 2 );
		// Not Gateway::ID: that would load the gateway class before WooCommerce, which it extends.
		add_filter( 'woocommerce_gateway_bunq_settings_values', array( Gateway::class, 'keep_secrets_on_rest_update' ), 10, 2 );
		add_action( 'woocommerce_blocks_loaded', array( $this, 'register_block_checkout' ) );
		$this->payment_monitor()->register_hooks();
	}

	/**
	 * The main plugin file.
	 *
	 * @return string
	 */
	public function file() {
		return $this->file;
	}

	/**
	 * The bunq API.
	 *
	 * @return ApiClient
	 */
	public function api_client() {
		if ( null === $this->api_client ) {
			$this->api_client = new ApiClient();
		}

		return $this->api_client;
	}

	/**
	 * Payment confirmation outside the bunq callback.
	 *
	 * @return PaymentMonitor
	 */
	public function payment_monitor() {
		if ( null === $this->payment_monitor ) {
			$this->payment_monitor = new PaymentMonitor( $this );
		}

		return $this->payment_monitor;
	}

	/**
	 * The gateway instance WooCommerce registered.
	 *
	 * @return Gateway|null
	 */
	public function gateway() {
		if ( ! function_exists( 'WC' ) || ! WC()->payment_gateways() ) {
			return null;
		}

		$gateways = WC()->payment_gateways()->payment_gateways();

		return isset( $gateways[ Gateway::ID ] ) && $gateways[ Gateway::ID ] instanceof Gateway ? $gateways[ Gateway::ID ] : null;
	}

	/**
	 * Complete the bunq authorization when the merchant returns from bunq to the settings page (admin_init). Ends the
	 * request with a redirect.
	 */
	public function handle_oauth_return() {
		if ( ! Gateway::is_oauth_return() ) {
			return;
		}

		$gateway = $this->gateway();

		if ( $gateway ) {
			$gateway->handle_oauth_callback();
		}
	}

	/**
	 * Forget the bunq authorization when the merchant clicks "Disconnect" on the settings page (admin_init). Ends the
	 * request with a redirect.
	 */
	public function handle_disconnect() {
		if ( ! Gateway::is_disconnect_request() ) {
			return;
		}

		$gateway = $this->gateway();

		if ( $gateway ) {
			$gateway->handle_disconnect();
		}
	}

	/**
	 * Declare compatibility with High-Performance Order Storage and the block checkout (before_woocommerce_init).
	 */
	public function declare_compatibility() {
		if ( class_exists( FeaturesUtil::class ) ) {
			FeaturesUtil::declare_compatibility( 'custom_order_tables', $this->file, true );
			FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', $this->file, true );
		}
	}

	/**
	 * Register the translations bundled in /languages (init). WordPress prefers the language packs from
	 * translate.wordpress.org in wp-content/languages/plugins and falls back to these, so the bundled Dutch, French
	 * and German translations also work for installs from the GitHub release zip and before the language packs exist.
	 */
	public function load_textdomain() {
		// phpcs:ignore PluginCheck.CodeAnalysis.DiscouragedFunctions.load_plugin_textdomainFound -- Fallback for the bundled translations, see above.
		load_plugin_textdomain( self::TEXT_DOMAIN, false, dirname( plugin_basename( $this->file ) ) . '/languages' );
	}

	/**
	 * Add the gateway to WooCommerce (woocommerce_payment_gateways).
	 *
	 * @param string[] $gateways Gateway class names.
	 * @return string[]
	 */
	public function add_gateway( $gateways ) {
		$gateways[] = Gateway::class;

		return $gateways;
	}

	/**
	 * Offer the gateway in the block checkout (woocommerce_blocks_loaded).
	 */
	public function register_block_checkout() {
		if ( ! class_exists( AbstractPaymentMethodType::class ) ) {
			return;
		}

		class_exists( 'WC_Bunq_WooCommerce_Block_Checkout' ); // Defines the class name of earlier versions.
		add_action( 'woocommerce_blocks_payment_method_type_registration', array( $this, 'register_payment_method_type' ) );
	}

	/**
	 * Register the block checkout payment method (woocommerce_blocks_payment_method_type_registration).
	 *
	 * @param PaymentMethodRegistry $registry The block checkout payment methods.
	 */
	public function register_payment_method_type( $registry ) {
		$registry->register( new CheckoutIntegration() );
	}

	/**
	 * Define the class names of earlier versions whose WooCommerce parent is loaded, so that code checking
	 * `instanceof WC_Bunq_Gateway` keeps working (plugins_loaded, woocommerce_blocks_loaded). Earlier versions
	 * declared them at these points as well.
	 */
	public function define_legacy_classes() {
		foreach ( array_keys( self::LEGACY_CLASSES ) as $class_name ) {
			class_exists( $class_name );
		}
	}

	/**
	 * Autoloader for the class names of earlier versions.
	 *
	 * @param string $class_name Class name.
	 */
	public function load_legacy_class( $class_name ) {
		$key = strtolower( ltrim( $class_name, '\\' ) );

		if ( isset( self::LEGACY_CLASSES[ $key ] ) && class_exists( self::LEGACY_CLASSES[ $key ][1], false ) ) {
			class_alias( self::LEGACY_CLASSES[ $key ][0], $class_name );
		}
	}
}
