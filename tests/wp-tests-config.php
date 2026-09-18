<?php
/**
 * Configuration for the WordPress test suite (wp-phpunit) used by the integration tests.
 *
 * WordPress core and WooCommerce are installed next to this file by composer (tests/wordpress and
 * tests/wp-content/plugins/woocommerce). The database credentials come from the environment so the same file
 * serves a local database and the CI service. The test suite empties the database, so use a dedicated one.
 */

define( 'ABSPATH', __DIR__ . '/wordpress/' );
define( 'WP_CONTENT_DIR', __DIR__ . '/wp-content' );

define( 'DB_NAME', getenv( 'WP_TESTS_DB_NAME' ) ?: 'wordpress_tests' );
define( 'DB_USER', getenv( 'WP_TESTS_DB_USER' ) ?: 'root' );
define( 'DB_PASSWORD', getenv( 'WP_TESTS_DB_PASSWORD' ) !== false ? getenv( 'WP_TESTS_DB_PASSWORD' ) : 'root' );
define( 'DB_HOST', getenv( 'WP_TESTS_DB_HOST' ) ?: '127.0.0.1' );
define( 'DB_CHARSET', 'utf8' );
define( 'DB_COLLATE', '' );

$table_prefix = 'wptests_';

define( 'WP_DEBUG', true );

define( 'WP_TESTS_DOMAIN', 'shop.example' );
define( 'WP_TESTS_EMAIL', 'admin@shop.example' );
define( 'WP_TESTS_TITLE', 'bunq for WooCommerce tests' );
define( 'WP_PHP_BINARY', 'php' );
define( 'WPLANG', '' );
