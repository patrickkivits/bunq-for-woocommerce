<?php

const BUNQ_REQUIREMENTS_OPTION = 'wc_bunq_gateway.requirements';

function bunq_requirements_check() {
    $min_wp  = '3.8';
    $min_php = '7.3.0';
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

    // Generating a key pair proves OpenSSL can do what the bunq SDK needs, but it costs tens of milliseconds,
    // so the outcome is remembered per PHP/OpenSSL build in an autoloaded option (a transient would cost two
    // queries per request without an object cache).
    $fingerprint = md5( PHP_VERSION . '|' . ( defined( 'OPENSSL_VERSION_TEXT' ) ? OPENSSL_VERSION_TEXT : '' ) );

    if ( get_option( BUNQ_REQUIREMENTS_OPTION ) === $fingerprint ) {
        return true;
    }

    try {
        \bunq\Security\KeyPair::generate();
    } catch (Exception $exception) {
	    if(defined( 'WP_DEBUG' ) && WP_DEBUG) {
		    error_log($exception->getMessage());
	    }
        return false;
    }

    update_option( BUNQ_REQUIREMENTS_OPTION, $fingerprint, true );

    return true;
}

function bunq_requirements_disable_plugin() {
    // __FILE__ would point at this include, not at the plugin, so use the main plugin file.
    $plugin = plugin_basename( BUNQ_FOR_WOOCOMMERCE_PLUGIN_FILE );

    if ( current_user_can('activate_plugins') && is_plugin_active( $plugin ) ) {
        deactivate_plugins( $plugin );

        // Hide the default "Plugin activated" notice
        if ( isset( $_GET['activate'] ) ) {
            unset( $_GET['activate'] );
        }
    }
}

function bunq_requirements_show_notice() {
    echo '<div class="error"><p><strong>bunq for WooCommerce</strong> '.esc_html__('cannot be activated due to incompatible environment.', 'bunq-for-woocommerce').'</p></div>';
}