<?php

function bunq_requirements_check() {
    $min_wp  = '3.8';
    $min_php = '7.0.13';
    $exts = ['openssl', 'curl', 'json', 'mbstring'];

    // Check for WordPress version
    if ( version_compare( get_bloginfo('version'), $min_wp, '<' ) ) {
        return false;
    }

    // Check WooCommerce installation
    if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
        return false;
    }

    // Check the PHP version
    if ( version_compare( PHP_VERSION, $min_php, '<' ) ) {
        return false;
    }

    // Check PHP loaded extensions
    foreach ( $exts as $ext ) {
        if ( ! extension_loaded( $ext ) ) {
            return false;
        }
    }

    // Test bunq creating key pair
    try {
        \bunq\Security\KeyPair::generate();
    } catch (Exception $exception) {
	    error_log($exception->getMessage());
        return false;
    }

    return true;
}

function bunq_requirements_disable_plugin() {
    if ( current_user_can('activate_plugins') && is_plugin_active( plugin_basename( __FILE__ ) ) ) {
        deactivate_plugins( plugin_basename( __FILE__ ) );

        // Hide the default "Plugin activated" notice
        if ( isset( $_GET['activate'] ) ) {
            unset( $_GET['activate'] );
        }
    }
}

function bunq_requirements_show_notice() {
    echo '<div class="error"><p><strong>bunq for WooCommerce</strong> cannot be activated due to incompatible environment.</p></div>';
}