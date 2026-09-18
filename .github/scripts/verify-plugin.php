<?php
/**
 * Smoke test for the WordPress/WooCommerce compatibility workflow.
 *
 * Run through WP-CLI once WordPress, WooCommerce and this plugin are installed
 * and activated:
 *
 *   wp eval-file .github/scripts/verify-plugin.php
 *
 * WP-CLI has fully loaded WordPress (including the `init` action) by the time
 * this file runs, so everything the plugin registers at load time is in place.
 */

if ( ! function_exists( 'is_plugin_active' ) ) {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

$failures = array();

$check = function ( $condition, $message ) use ( &$failures ) {
    if ( $condition ) {
        WP_CLI::log( "  ok   $message" );
    } else {
        $failures[] = $message;
        WP_CLI::warning( "FAIL $message" );
    }
};

WP_CLI::log( sprintf(
    'PHP %s / WordPress %s / WooCommerce %s',
    PHP_VERSION,
    get_bloginfo( 'version' ),
    defined( 'WC_VERSION' ) ? WC_VERSION : 'not loaded'
) );

// Environment the workflow is supposed to have set up.
$check( defined( 'WC_VERSION' ), 'WooCommerce is loaded' );
$check( is_plugin_active( 'bunq-for-woocommerce/bunq-for-woocommerce.php' ), 'bunq for WooCommerce is active' );

// The plugin deactivates itself on admin_init when this returns false.
$check( function_exists( 'bunq_requirements_check' ), 'bunq_requirements_check() is defined' );
$check( function_exists( 'bunq_requirements_check' ) && bunq_requirements_check(), 'bunq_requirements_check() passes' );

// The gateway class is declared inside the plugins_loaded hook.
$check( class_exists( 'WC_Bunq_Gateway' ), 'WC_Bunq_Gateway class is declared' );

// The gateway is registered with WooCommerce and can be instantiated.
$gateways = WC()->payment_gateways()->payment_gateways();
$gateway  = isset( $gateways['bunq'] ) ? $gateways['bunq'] : null;

$check( $gateway instanceof WC_Payment_Gateway, 'gateway "bunq" is registered with WooCommerce' );

if ( $gateway ) {
    $check( $gateway instanceof WC_Bunq_Gateway, 'gateway "bunq" is an instance of WC_Bunq_Gateway' );
    $check( 'bunq' === $gateway->get_method_title(), 'gateway method title is "bunq"' );
    $check( in_array( 'products', (array) $gateway->supports, true ), 'gateway supports "products"' );

    // Field definitions must be available outside admin (checkout, WP-CLI) so that
    // get_option() can fall back to each field's default. Only the bank account
    // lookup that fills the select options is gated on is_admin().
    $form_fields = $gateway->get_form_fields();
    $check( is_array( $form_fields ) && ! empty( $form_fields ), 'gateway settings form fields are defined' );
    $check( isset( $form_fields['enabled'] ), 'gateway settings contain the "enabled" field' );
}

// Compatibility declarations (HPOS since WooCommerce 7.1, block checkout since 8.3).
if ( class_exists( 'Automattic\WooCommerce\Utilities\FeaturesUtil' )
    && class_exists( 'Automattic\WooCommerce\Internal\Features\FeaturesController' )
) {
    $plugin_file    = 'bunq-for-woocommerce/bunq-for-woocommerce.php';
    $known_features = wc_get_container()
        ->get( Automattic\WooCommerce\Internal\Features\FeaturesController::class )
        ->get_features( false, true );

    foreach ( array( 'custom_order_tables', 'cart_checkout_blocks' ) as $feature ) {
        if ( ! isset( $known_features[ $feature ] ) ) {
            WP_CLI::log( "  skip feature \"$feature\" does not exist in this WooCommerce version" );
            continue;
        }

        $compatible = Automattic\WooCommerce\Utilities\FeaturesUtil::get_compatible_plugins_for_feature( $feature, true );
        $check(
            in_array( $plugin_file, $compatible['compatible'], true ),
            "plugin declares compatibility with the \"$feature\" feature"
        );
    }
} else {
    WP_CLI::log( '  skip FeaturesUtil not available in this WooCommerce version' );
}

// Block checkout integration (WooCommerce Blocks).
if ( class_exists( 'Automattic\WooCommerce\Blocks\Package' )
    && class_exists( 'Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry' )
) {
    $registry = Automattic\WooCommerce\Blocks\Package::container()->get(
        Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry::class
    );

    if ( did_action( 'woocommerce_blocks_payment_method_type_registration' ) ) {
        $check( $registry->is_registered( 'bunq' ), 'block checkout payment method "bunq" is registered' );
        $check(
            $registry->is_registered( 'bunq' ) && $registry->get_registered( 'bunq' ) instanceof WC_Bunq_WooCommerce_Block_Checkout,
            'block checkout payment method is an instance of WC_Bunq_WooCommerce_Block_Checkout'
        );
    } else {
        WP_CLI::log( '  skip block payment method registration did not run in this context' );
    }
} else {
    WP_CLI::log( '  skip WooCommerce Blocks not available in this WooCommerce version' );
}

if ( $failures ) {
    WP_CLI::error( count( $failures ) . ' check(s) failed:' . PHP_EOL . ' - ' . implode( PHP_EOL . ' - ', $failures ) );
}

WP_CLI::success( 'bunq for WooCommerce loads and registers correctly.' );
