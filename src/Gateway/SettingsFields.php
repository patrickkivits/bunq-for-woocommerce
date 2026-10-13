<?php
/**
 * The gateway settings form.
 *
 * @package PatrickKivits\BunqForWooCommerce
 */

namespace PatrickKivits\BunqForWooCommerce\Gateway;

defined( 'ABSPATH' ) || exit;

/**
 * The fields of the gateway settings form (WooCommerce settings API).
 */
final class SettingsFields {

	/**
	 * The form fields. Only the OAuth client of the active mode is part of the form; the API key and API context are
	 * never shown, only whether there is a connection.
	 *
	 * @param array $bank_accounts  Options of the bank account select.
	 * @param bool  $direct_gateway Whether the customer picks the payment method at checkout.
	 * @param bool  $testmode       Whether test mode is enabled.
	 * @param bool  $connected      Whether the active mode has an API context.
	 * @return array[]
	 */
	public static function get( $bank_accounts, $direct_gateway, $testmode, $connected = false ) {
		$fields = self::base( $bank_accounts );

		if ( $direct_gateway ) {
			$fields['enabled_payment_methods'] = array(
				'title'             => __( 'Payment methods', 'bunq-for-woocommerce' ),
				'type'              => 'multiselect',
				'custom_attributes' => array( 'multiple' => 'multiple' ),
				'options'           => PaymentMethods::options( PaymentMethods::all() ),
			);
		}

		$fields = array_merge( $fields, $testmode ? self::test_credentials() : self::live_credentials() );

		// A "title" field: shown, never saved, and left out of the REST API.
		$fields['connection'] = array(
			'title'       => __( 'bunq connection', 'bunq-for-woocommerce' ),
			'type'        => 'title',
			'description' => $connected
				? __( 'Connected to bunq.', 'bunq-for-woocommerce' )
				: __( 'Not connected to bunq yet. Save the OAuth Client ID and Client Secret, then click "OAuth Authorization Request".', 'bunq-for-woocommerce' ),
		);

		return $fields;
	}

	/**
	 * The fields that do not depend on other settings, among them the defaults of the direct gateway and test mode
	 * settings that decide about the others.
	 *
	 * @param array $bank_accounts Options of the bank account select.
	 * @return array[]
	 */
	public static function base( $bank_accounts ) {
		return array(
			'enabled'                  => array(
				'title'       => __( 'Enable/Disable', 'bunq-for-woocommerce' ),
				'label'       => __( 'Enable bunq Gateway', 'bunq-for-woocommerce' ),
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'no',
			),
			'testmode'                 => array(
				'title'       => __( 'Test mode', 'bunq-for-woocommerce' ),
				'label'       => __( 'Enable Test Mode', 'bunq-for-woocommerce' ),
				'type'        => 'checkbox',
				'description' => __( 'Place the payment gateway in test mode using test API keys.', 'bunq-for-woocommerce' ),
				'default'     => 'no',
				'desc_tip'    => true,
			),
			'title'                    => array(
				'title'       => __( 'Title', 'bunq-for-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'bunq-for-woocommerce' ),
				'default'     => __( 'iDEAL, Credit Card or Bancontact', 'bunq-for-woocommerce' ),
				'desc_tip'    => true,
			),
			'description'              => array(
				'title'       => __( 'Description', 'bunq-for-woocommerce' ),
				'type'        => 'textarea',
				'description' => __( 'This controls the description which the user sees during checkout.', 'bunq-for-woocommerce' ),
				'default'     => __( 'Pay with iDEAL, Credit Card or Bancontact', 'bunq-for-woocommerce' ),
			),
			'monetary_account_bank_id' => array(
				'title'   => __( 'Bank account', 'bunq-for-woocommerce' ),
				'type'    => 'select',
				'options' => $bank_accounts,
			),
			'direct_gateway'           => array(
				'title'       => __( 'Direct Gateway', 'bunq-for-woocommerce' ),
				'label'       => __( 'Enable direct gateway', 'bunq-for-woocommerce' ),
				'type'        => 'checkbox',
				'default'     => 'no',
				'description' => __( 'Allow your customers to directly select a payment method from the checkout page.', 'bunq-for-woocommerce' ),
				'desc_tip'    => true,
			),
		);
	}

	/**
	 * The credentials of the bunq sandbox.
	 *
	 * @return array[]
	 */
	private static function test_credentials() {
		return array(
			'test_oauth_client_id'     => array(
				'title' => __( 'Test OAuth Client ID', 'bunq-for-woocommerce' ),
				'type'  => 'text',
			),
			'test_oauth_client_secret' => array(
				'title' => __( 'Test OAuth Client Secret', 'bunq-for-woocommerce' ),
				'type'  => 'password',
			),
		);
	}

	/**
	 * The credentials of bunq production.
	 *
	 * @return array[]
	 */
	private static function live_credentials() {
		return array(
			'oauth_client_id'     => array(
				'title' => __( 'OAuth Client ID', 'bunq-for-woocommerce' ),
				'type'  => 'text',
			),
			'oauth_client_secret' => array(
				'title' => __( 'OAuth Client Secret', 'bunq-for-woocommerce' ),
				'type'  => 'password',
			),
		);
	}
}
