<?php
/**
 * Connects the demo shop to the bunq sandbox: wp eval-file .agents/demo-shop/connect-sandbox.php
 *
 * Idempotent, run whenever the shop starts. bunq ties an API context to the IP address it was created from, and the
 * shop's public URL changes per thread, so this checks both every time:
 * - creates a bunq sandbox company user (once per orb) and uses its API key the way the OAuth access token is used;
 * - (re)creates the API context when the stored one cannot be loaded from this orb;
 * - creates a sandbox OAuth client with the shop's settings page as redirect URL, so the settings page shows a real
 *   client and "Connect again" leads to the sandbox authorization page;
 * - picks the first bank account, enables the gateway and registers the callback URL when the shop has a public URL.
 */

use PatrickKivits\BunqForWooCommerce\Bunq\ApiClient;
use PatrickKivits\BunqForWooCommerce\Gateway\CallbackHandler;
use PatrickKivits\BunqForWooCommerce\Gateway\Gateway;
use PatrickKivits\BunqForWooCommerce\Plugin;
use PatrickKivits\BunqForWooCommerce\Support\Url;

defined( 'WP_CLI' ) || exit( 1 );

const BUNQ_DEMO_STATE = 'bunq_demo_sandbox';

$state    = get_option( BUNQ_DEMO_STATE, array() );
$settings = get_option( 'woocommerce_bunq_settings', array() );
$settings = is_array( $settings ) ? $settings : array();

// Sandbox mode, the way a merchant would leave it after connecting.
$settings = array_merge(
	array(
		'title'       => 'iDEAL | Wero, Credit Card or Bancontact',
		'description' => 'Pay with iDEAL | Wero, Credit Card or Bancontact',
	),
	$settings,
	array(
		'enabled'  => 'yes',
		'testmode' => 'yes',
	)
);
update_option( 'woocommerce_bunq_settings', $settings );

$credentials = get_option( Gateway::CREDENTIALS_OPTION, array() );
$credentials = is_array( $credentials ) ? $credentials : array();

if ( empty( $credentials['test_api_key'] ) ) {
	$response = wp_remote_post(
		'https://public-api.sandbox.bunq.com/v1/sandbox-user-company',
		array(
			'timeout' => 30,
			'headers' => array(
				'Content-Type'             => 'application/json',
				'Cache-Control'            => 'no-cache',
				'User-Agent'               => 'bunq-for-woocommerce-demo',
				'X-Bunq-Client-Request-Id' => wp_generate_uuid4(),
				'X-Bunq-Geolocation'       => '0 0 0 0 000',
				'X-Bunq-Language'          => 'en_US',
				'X-Bunq-Region'            => 'nl_NL',
			),
			'body'    => '{}',
		)
	);
	$body     = json_decode( wp_remote_retrieve_body( $response ), true );
	$api_key  = isset( $body['Response'][0]['ApiKey']['api_key'] ) ? $body['Response'][0]['ApiKey']['api_key'] : '';

	if ( ! $api_key ) {
		WP_CLI::error( 'Could not create a bunq sandbox user: ' . ( is_wp_error( $response ) ? $response->get_error_message() : substr( wp_remote_retrieve_body( $response ), 0, 300 ) ) );
	}

	$credentials['test_api_key']     = $api_key;
	$credentials['test_api_context'] = '';
	update_option( Gateway::CREDENTIALS_OPTION, $credentials, false );
	$state = array();
	WP_CLI::log( 'Created a bunq sandbox user.' );
}

$api     = Plugin::instance()->api_client();
$gateway = new Gateway( $api );

// Loading a stored context does not call bunq, so call it: the gateway (re)creates the context when there is none or
// bunq refuses it from this orb's IP address.
delete_transient( Gateway::CONTEXT_FAILED_TRANSIENT );
$gateway->call_bunq(
	function () use ( $api ) {
		return $api->request( 'GET', 'user/' . $api->user_id() );
	}
);
WP_CLI::log( 'The bunq sandbox API context works from this orb.' );

$settings_url = Gateway::settings_url();

// A real sandbox OAuth client, with this shop's settings page as redirect URL.
if ( empty( $state['oauth_client_id'] ) ) {
	$client_id = ApiClient::id( $api->request( 'POST', 'user/' . $api->user_id() . '/oauth-client', array() ) );
	$client    = ApiClient::item( $api->request( 'GET', 'user/' . $api->user_id() . '/oauth-client/' . $client_id ), 'OauthClient' );

	$state['oauth_client_id']    = $client_id;
	$state['redirect_urls']      = array();
	$settings['test_oauth_client_id']     = $client['client_id'];
	$settings['test_oauth_client_secret'] = $client['secret'];
	WP_CLI::log( 'Created a bunq sandbox OAuth client.' );
}

if ( ! in_array( $settings_url, $state['redirect_urls'], true ) && ! Url::is_local( $settings_url ) ) {
	$api->request( 'POST', 'user/' . $api->user_id() . '/oauth-client/' . (int) $state['oauth_client_id'] . '/callback-url', array( 'url' => $settings_url ) );
	$state['redirect_urls'][] = $settings_url;
	WP_CLI::log( 'Registered the OAuth redirect URL ' . $settings_url );
}

// The first bank account of the sandbox user, unless one of its accounts is selected already.
$accounts = array_map( 'strval', array_filter( array_keys( $api->get_bank_accounts( true ) ) ) );
if ( ! in_array( (string) ( isset( $settings['monetary_account_bank_id'] ) ? $settings['monetary_account_bank_id'] : '' ), $accounts, true ) ) {
	$settings['monetary_account_bank_id'] = (string) reset( $accounts );
	unset( $state['callback_url'] );
}

update_option( 'woocommerce_bunq_settings', $settings );

// bunq calls the shop back on its public URL; without one the plugin's background checks confirm payments.
$callback_url = CallbackHandler::url();

if ( ! Url::is_local( $callback_url ) && ( empty( $state['callback_url'] ) || $state['callback_url'] !== $callback_url ) ) {
	$monetary_account_id = (int) $settings['monetary_account_bank_id'];
	$api->create_notification_filters( $monetary_account_id ? $monetary_account_id : null );
	$state['callback_url'] = $callback_url;
	WP_CLI::log( 'Registered the bunq callback URL ' . $callback_url );
}

$state['user_id'] = $api->user_id();
update_option( BUNQ_DEMO_STATE, $state, false );
delete_transient( Gateway::BANK_ACCOUNTS_TRANSIENT );
delete_transient( Gateway::CONTEXT_FAILED_TRANSIENT );

WP_CLI::success( 'The demo shop is connected to the bunq sandbox (user ' . $state['user_id'] . ').' );
