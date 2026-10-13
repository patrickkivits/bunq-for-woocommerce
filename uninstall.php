<?php
/**
 * Runs when the plugin is deleted from the WordPress admin: removes the saved gateway settings (including the API
 * key and API context) and cached data.
 *
 * @package PatrickKivits\BunqForWooCommerce
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'woocommerce_bunq_settings' );
delete_option( 'bunq_for_woocommerce_credentials' );
delete_option( 'wc_bunq_gateway.requirements' );

delete_transient( 'wc_bunq_gateway.bunq_get_bank_accounts' );
delete_transient( 'wc_bunq_gateway.last_error' );
delete_transient( 'wc_bunq_gateway.last_success' );
delete_transient( 'wc_bunq_gateway.context_failed' );

// Pending background payment checks (Action Scheduler is available while WooCommerce is active).
if ( function_exists( 'as_unschedule_all_actions' ) ) {
	as_unschedule_all_actions( 'wc_bunq_check_payment' );
	as_unschedule_all_actions( 'wc_bunq_cancel_payment_request' );
	as_unschedule_all_actions( 'bunq_for_woocommerce_follow_up_check' );
}

// The OAuth state per user, the payment check lock per order and the callback throttle per payment request.
global $wpdb;
// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Transients with variable names cannot be deleted otherwise.
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_wc_bunq_gateway.oauth2state.%' OR option_name LIKE '_transient_timeout_wc_bunq_gateway.oauth2state.%' OR option_name LIKE '_transient_wc_bunq_gateway.lock.%' OR option_name LIKE '_transient_timeout_wc_bunq_gateway.lock.%' OR option_name LIKE '_transient_bunq_for_woocommerce_callback_%' OR option_name LIKE '_transient_timeout_bunq_for_woocommerce_callback_%'" );
