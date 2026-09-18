<?php
/**
 * Bootstrap for the integration tests: the WordPress test suite (wp-phpunit) with WooCommerce and this plugin
 * loaded, against a real database. Everything the tests touch is the real WordPress and WooCommerce code.
 *
 * Database credentials come from the WP_TESTS_DB_* environment variables, see ../wp-tests-config.php.
 */

// wp-phpunit exports WP_PHPUNIT__DIR from the composer autoloader.
require dirname( __DIR__ ) . '/vendor/autoload.php';

$wp_phpunit_dir = getenv( 'WP_PHPUNIT__DIR' );
$plugins_dir    = dirname( __DIR__ ) . '/wp-content/plugins';

if ( ! $wp_phpunit_dir || ! file_exists( $wp_phpunit_dir . '/includes/functions.php' ) || ! file_exists( $plugins_dir . '/woocommerce/woocommerce.php' ) ) {
    fwrite( STDERR, "The WordPress test suite or WooCommerce is missing. Run: composer install --working-dir=tests\n" );
    exit( 1 );
}

if ( ! getenv( 'WP_PHPUNIT__TESTS_CONFIG' ) ) {
    putenv( 'WP_PHPUNIT__TESTS_CONFIG=' . dirname( __DIR__ ) . '/wp-tests-config.php' );
}

// The plugin is loaded from the plugins directory like any other plugin, so that everything keyed on the plugin
// basename (feature compatibility, is_plugin_active) sees "bunq-for-woocommerce/bunq-for-woocommerce.php".
if ( ! file_exists( $plugins_dir . '/bunq-for-woocommerce' ) ) {
    symlink( '../../..', $plugins_dir . '/bunq-for-woocommerce' );
}

// WordPress registers its theme directory only when it exists; the "no content" core package ships none.
if ( ! is_dir( dirname( __DIR__ ) . '/wp-content/themes' ) ) {
    mkdir( dirname( __DIR__ ) . '/wp-content/themes', 0777, true );
}

// Both plugins are active, in the order WordPress loads them on a real site (alphabetical).
$GLOBALS['wp_tests_options'] = array(
    'active_plugins' => array(
        'bunq-for-woocommerce/bunq-for-woocommerce.php',
        'woocommerce/woocommerce.php',
    ),
);

require_once $wp_phpunit_dir . '/includes/functions.php';

// WooCommerce's bundled Jetpack Connection package schedules a cron event on plugins_loaded, which makes
// WooCommerce translate its cron schedule names before init; WordPress 6.7+ reports that as incorrect usage.
// It is WooCommerce's, not the plugin's, so it is not shown while loading. Inside the tests the WordPress test
// case still turns every incorrect usage into a failure.
tests_add_filter( 'doing_it_wrong_trigger_error', function ( $trigger, $function_name ) {
    return $function_name === '_load_textdomain_just_in_time' ? false : $trigger;
}, 10, 2 );

// A clean WooCommerce installation (tables, roles, pages) for every run, as WooCommerce's own test suite does it.
// It runs first thing on init: earlier and WordPress objects to the translations and theme lookups the installer
// makes; later and WooCommerce's own init hooks (sessions, Action Scheduler) would find the tables missing.
tests_add_filter( 'init', function () {
    define( 'WP_UNINSTALL_PLUGIN', true );
    define( 'WC_REMOVE_ALL_DATA', true );
    include WC_ABSPATH . 'uninstall.php';

    WC_Install::install();

    // Reload the capabilities after the install, see https://core.trac.wordpress.org/ticket/28374.
    $GLOBALS['wp_roles'] = null;
    wp_roles();
}, -1 );

// Under WP_DEBUG the plugin mirrors its WooCommerce log to error_log(); keep that out of the test output. Errors
// PHP displays (display_errors) still reach the console.
ini_set( 'error_log', dirname( __DIR__ ) . '/.integration-error.log' );

require $wp_phpunit_dir . '/includes/bootstrap.php';

// The bunq SDK still declares implicitly nullable parameters, which PHP 8.4+ reports as deprecated when its
// classes are compiled. That is the SDK's concern, not the plugin's, so deprecations are left out here just as
// they are in the unit suite and the compatibility workflows.
error_reporting( E_ALL & ~E_DEPRECATED );

require __DIR__ . '/TestCase.php';
