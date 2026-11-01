<?php
/**
 * One bunq.me payment method as a payment option of its own.
 *
 * @package PatrickKivits\BunqForWooCommerce
 */

namespace PatrickKivits\BunqForWooCommerce\Gateway;

use InvalidArgumentException;
use PatrickKivits\BunqForWooCommerce\Bunq\ApiClient;
use PatrickKivits\BunqForWooCommerce\Bunq\OAuthClient;

defined( 'ABSPATH' ) || exit;

/**
 * Offers one payment method (iDEAL | Wero, card or Bancontact) at checkout, when the bunq gateway's "Separate
 * payment methods" setting is on. Customers who pick it go straight to that method on bunq.me.
 *
 * It shares the settings of the bunq gateway (woocommerce_bunq_settings): the connection, the bank account and the
 * enabled payment methods are set up there once. Orders paid with it carry its own id (such as "bunq_ideal") and
 * are confirmed, cancelled and refunded like those of the bunq gateway.
 */
class MethodGateway extends Gateway {

	/**
	 * The payment method.
	 *
	 * @var array See PaymentMethods::all().
	 */
	protected $payment_method;

	/**
	 * Constructor.
	 *
	 * @param string           $method_id One of PaymentMethods::SEPARATE.
	 * @param ApiClient|null   $api       The bunq API; the plugin's by default.
	 * @param OAuthClient|null $oauth     The bunq OAuth authorization.
	 * @throws InvalidArgumentException For a method that cannot be offered on its own.
	 */
	public function __construct( $method_id, ?ApiClient $api = null, ?OAuthClient $oauth = null ) {
		$ids = self::method_gateway_ids();

		if ( ! isset( $ids[ $method_id ] ) ) {
			throw new InvalidArgumentException( 'No separate gateway for payment method ' . $method_id ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Not output.
		}

		$this->payment_method = PaymentMethods::get( $method_id );

		parent::__construct( $api, $oauth );

		$this->id           = $ids[ $method_id ];
		$this->title        = $this->payment_method['description'];
		$this->description  = '';
		$this->method_title = sprintf(
			/* translators: %s: payment method, such as "Bancontact" */
			__( '%s (bunq)', 'bunq-for-woocommerce' ),
			$this->payment_method['description']
		);
		$this->method_description = sprintf(
			/* translators: %s: payment method, such as "Bancontact" */
			__( 'Offers %s as a payment option of its own. It uses the connection and settings of the bunq payment method.', 'bunq-for-woocommerce' ),
			$this->payment_method['description']
		);
		$this->direct_gateway = false;
		$this->has_fields     = false;
		$this->enabled        = $this->is_enabled_in_settings() ? 'yes' : 'no';
	}

	/**
	 * The payment method id, such as "ideal".
	 *
	 * @return string
	 */
	public function get_payment_method_id() {
		return $this->payment_method['id'];
	}

	/**
	 * The settings of the bunq gateway.
	 *
	 * @return string
	 */
	public function get_option_key() {
		return $this->plugin_id . Gateway::ID . '_settings';
	}

	/**
	 * No settings form of its own: everything is set up on the bunq gateway's settings page.
	 */
	public function init_form_fields() {
		$this->form_fields = array();
	}

	/**
	 * No settings of its own to save, and the bunq callback is handled by the bunq gateway.
	 */
	protected function register_hooks() {}

	/**
	 * The settings page of this payment option points to the bunq gateway's.
	 */
	public function admin_options() {
		echo '<h2>' . esc_html( $this->get_method_title() ) . '</h2>';
		echo wpautop( esc_html( $this->get_method_description() ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by esc_html().
		echo '<p><a class="button" href="' . esc_url( self::settings_url() ) . '">' . esc_html__( 'Open the bunq settings', 'bunq-for-woocommerce' ) . '</a></p>';
	}

	/**
	 * Load the settings, and with them whether this payment option is enabled: WooCommerce would take that from the
	 * bunq gateway's "enabled" setting.
	 */
	public function init_settings() {
		parent::init_settings();

		$this->enabled = $this->is_enabled_in_settings() ? 'yes' : 'no';
	}

	/**
	 * Whether the settings offer this payment option: the bunq gateway is enabled, its payment methods are offered
	 * separately and this one is among the enabled payment methods.
	 *
	 * @return bool
	 */
	public function is_enabled_in_settings() {
		$settings = $this->settings;

		return isset( $settings['enabled'], $settings['separate_gateways'] )
			&& 'yes' === $settings['enabled']
			&& 'yes' === $settings['separate_gateways']
			&& $this->is_allowed( PaymentMethods::allowed( isset( $settings['enabled_payment_methods'] ) ? $settings['enabled_payment_methods'] : null ) );
	}

	/**
	 * Whether this payment method is among the given ones.
	 *
	 * @param array[] $methods See PaymentMethods::all().
	 * @return bool
	 */
	private function is_allowed( array $methods ) {
		return in_array( $this->get_payment_method_id(), array_column( $methods, 'id' ), true );
	}

	/**
	 * Turning the payment option on or off in WooCommerce's list of payment methods: on also turns on the bunq
	 * gateway and its separate payment methods; off leaves this method out of the enabled payment methods. Turning off
	 * the last one goes back to the single bunq payment option. Other settings are the bunq gateway's.
	 *
	 * @param string $key   Setting.
	 * @param mixed  $value Value; "yes" or "no" for "enabled".
	 * @return bool
	 */
	public function update_option( $key, $value = '' ) {
		if ( 'enabled' !== $key ) {
			return parent::update_option( $key, $value );
		}

		$all     = array_column( PaymentMethods::all(), 'id' );
		$saved   = $this->get_setting( 'enabled_payment_methods' );
		$enabled = is_array( $saved ) && ! empty( $saved ) ? array_values( $saved ) : $all;
		$id      = $this->get_payment_method_id();

		if ( 'yes' === $value ) {
			if ( ! in_array( $id, $enabled, true ) ) {
				$enabled[] = $id;
			}

			parent::update_option( 'enabled', 'yes' );
			parent::update_option( 'separate_gateways', 'yes' );
		} else {
			$enabled = array_values( array_diff( $enabled, array( $id ) ) );

			if ( ! array_intersect( $enabled, PaymentMethods::SEPARATE ) ) {
				parent::update_option( 'separate_gateways', 'no' );
				$enabled = is_array( $saved ) ? $saved : array();
			}
		}

		// Every method enabled is saved as none selected, which keeps offering methods bunq.me adds later.
		$result = parent::update_option( 'enabled_payment_methods', array_diff( $all, $enabled ) ? $enabled : array() );

		$this->separate_gateways = 'yes' === $this->get_setting( 'separate_gateways' );
		$this->enabled           = $this->is_enabled_in_settings() ? 'yes' : 'no';

		return $result;
	}

	/**
	 * Offered when the separate payment methods are on and bunq accepts the checkout total for this method.
	 *
	 * @return bool
	 */
	protected function is_offered() {
		return $this->separate_gateways && $this->is_allowed( $this->get_allowed_payment_methods( $this->get_checkout_total() ) );
	}

	/**
	 * This payment method's logo.
	 *
	 * @param float|null $total Not used.
	 * @return array[] See PaymentMethods::icons().
	 */
	public function get_icons( $total = null ) {
		return PaymentMethods::icons( array( $this->payment_method ) );
	}

	/**
	 * Open this payment method on bunq.me.
	 *
	 * @return string
	 */
	protected function get_requested_bunqme_payment_method() {
		return $this->payment_method['bunqme'];
	}
}
