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
	 * The OAuth client fields by mode ("live", "sandbox"). The API key and API context are never part of the form.
	 */
	const OAUTH_CLIENT_FIELDS = array(
		'live'    => array( 'oauth_client_id', 'oauth_client_secret' ),
		'sandbox' => array( 'test_oauth_client_id', 'test_oauth_client_secret' ),
	);

	/**
	 * The form fields. Both modes' OAuth clients are part of the form; the settings page shows those of the mode the
	 * test mode checkbox selects. The fields of type "title" are shown, never saved, and left out of the REST API;
	 * those with a "view" are drawn by the gateway (Gateway::generate_title_html()).
	 *
	 * @param array $bank_accounts Options of the bank account select.
	 * @param bool  $connected     Whether the active mode has an API context.
	 * @return array[]
	 */
	public static function get( $bank_accounts, $connected = false ) {
		$fields = self::base( $bank_accounts );

		$fields['enabled']['description'] = $connected ? '' : __( 'Customers see bunq at checkout once your bunq account is connected.', 'bunq-for-woocommerce' );

		return array(
			'enabled'                  => $fields['enabled'],
			'account'                  => array(
				'title'       => __( 'bunq account', 'bunq-for-woocommerce' ),
				'type'        => 'title',
				'view'        => 'account',
				'description' => sprintf(
					/* translators: %s: link to the setup guide */
					__( 'Payments go straight to your own bunq account. Connect it with an OAuth client that you create in the bunq app. %s', 'bunq-for-woocommerce' ),
					'<a href="' . esc_url( SettingsPage::HELP_URL ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Setup guide', 'bunq-for-woocommerce' ) . '</a>'
				),
			),
			'testmode'                 => $fields['testmode'],
			'connection'               => array(
				'title' => '',
				'type'  => 'title',
				'view'  => 'connection',
			),
			'oauth_client_id'          => self::client_id_field( 'live' ),
			'oauth_client_secret'      => self::client_secret_field( 'live' ),
			'test_oauth_client_id'     => self::client_id_field( 'sandbox' ),
			'test_oauth_client_secret' => self::client_secret_field( 'sandbox' ),
			'connect'                  => array(
				'title' => '',
				'type'  => 'title',
				'view'  => 'connect',
			),
			'receiving'                => array(
				'title' => __( 'Receiving payments', 'bunq-for-woocommerce' ),
				'type'  => 'title',
			),
			'monetary_account_bank_id' => $fields['monetary_account_bank_id'],
			'checkout'                 => array(
				'title' => __( 'Checkout', 'bunq-for-woocommerce' ),
				'type'  => 'title',
			),
			'title'                    => $fields['title'],
			'description'              => $fields['description'],
			'direct_gateway'           => $fields['direct_gateway'],
			'enabled_payment_methods'  => array(
				'title'             => __( 'Payment methods', 'bunq-for-woocommerce' ),
				'type'              => 'multiselect',
				'class'             => 'wc-enhanced-select',
				'css'               => 'width: 400px;',
				'description'       => __( 'The payment methods customers can choose from. Leave empty to offer all of them.', 'bunq-for-woocommerce' ),
				'custom_attributes' => array(
					'multiple'         => 'multiple',
					'data-placeholder' => __( 'All payment methods', 'bunq-for-woocommerce' ),
				),
				'options'           => PaymentMethods::options( PaymentMethods::all() ),
			),
		);
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
				'label'       => __( 'Use the bunq sandbox', 'bunq-for-woocommerce' ),
				'type'        => 'checkbox',
				'description' => __( 'Try payments without real money. The sandbox has its own OAuth client and connection.', 'bunq-for-woocommerce' ),
				'default'     => 'no',
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
				'desc_tip'    => true,
			),
			'monetary_account_bank_id' => array(
				'title'       => __( 'Bank account', 'bunq-for-woocommerce' ),
				'type'        => 'select',
				'description' => __( 'Payments are paid into this bunq account.', 'bunq-for-woocommerce' ),
				'options'     => $bank_accounts,
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
	 * The OAuth client id field of a mode.
	 *
	 * @param string $mode "live" or "sandbox".
	 * @return array
	 */
	private static function client_id_field( $mode ) {
		return array(
			'title'             => 'sandbox' === $mode ? __( 'Sandbox Client ID', 'bunq-for-woocommerce' ) : __( 'Client ID', 'bunq-for-woocommerce' ),
			'type'              => 'text',
			'custom_attributes' => array(
				'data-bunq-mode' => $mode,
				'autocomplete'   => 'off',
				'spellcheck'     => 'false',
			),
		);
	}

	/**
	 * The OAuth client secret field of a mode. Rendered without its value (Gateway::generate_password_html()).
	 *
	 * @param string $mode "live" or "sandbox".
	 * @return array
	 */
	private static function client_secret_field( $mode ) {
		return array(
			'title'             => 'sandbox' === $mode ? __( 'Sandbox Client Secret', 'bunq-for-woocommerce' ) : __( 'Client Secret', 'bunq-for-woocommerce' ),
			'type'              => 'password',
			'custom_attributes' => array(
				'data-bunq-mode' => $mode,
				// Keeps the browser from filling in the WordPress login password.
				'autocomplete'   => 'new-password',
			),
		);
	}
}
