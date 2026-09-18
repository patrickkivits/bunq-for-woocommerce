<?php
/**
 * Runs when the plugin is deleted from the WordPress admin.
 * Removes the saved gateway settings (including API key and API context) and cached data.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

delete_option( 'woocommerce_bunq_settings' );

delete_transient( 'wc_bunq_gateway.bunq_get_bank_accounts' );
delete_transient( 'wc_bunq_gateway.last_error' );
delete_transient( 'wc_bunq_gateway.last_success' );
delete_transient( 'wc_bunq_gateway.requirements' );

// Pending background payment checks (Action Scheduler is available while WooCommerce is active).
if ( function_exists( 'as_unschedule_all_actions' ) ) {
    as_unschedule_all_actions( 'wc_bunq_check_payment' );
}

global $wpdb;
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_wc_bunq_gateway.oauth2state.%' OR option_name LIKE '_transient_timeout_wc_bunq_gateway.oauth2state.%' OR option_name LIKE '_transient_wc_bunq_gateway.lock.%' OR option_name LIKE '_transient_timeout_wc_bunq_gateway.lock.%'" );
