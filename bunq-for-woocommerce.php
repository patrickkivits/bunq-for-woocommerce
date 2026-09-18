<?php
/**
 * Plugin Name: bunq for WooCommerce
 * Description: Accept payments in your WooCommerce shop with just your bunq account.
 * Version: 1.6.4
 * Author: Patrick Kivits
 * Author URI: https://www.patrickkivits.nl
 * Requires at least: 3.8
 * Requires PHP: 7.3
 * Tested up to: 6.8
 * Text Domain: bunq-for-woocommerce
 * Domain Path: /languages
 * License: GPLv2 or later
 * WC requires at least: 2.2.0
 * WC tested up to: 9.8
 */

define('BUNQ_FOR_WOOCOMMERCE_PLUGIN_FILE', __FILE__);

require_once (__DIR__.'/vendor/autoload.php');
require_once (__DIR__.'/includes/helpers.php');
require_once (__DIR__.'/includes/oauth2.php');
require_once (__DIR__.'/includes/bunq.php');
require_once (__DIR__.'/includes/requirements.php');

// Declare compatibility with High-Performance Order Storage (HPOS)
add_action( 'before_woocommerce_init', function() {
    if ( class_exists( 'Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
} );

// Declare compatibility for 'cart_checkout_blocks'
function declare_cart_checkout_blocks_compatibility() {
    if ( class_exists( 'Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
    }
}

// Hook the custom function to the 'woocommerce_blocks_loaded' action
add_action( 'woocommerce_blocks_loaded', 'bunq_register_blocks_payment_method_type' );

function bunq_register_blocks_payment_method_type() {
    // Check if the required class exists
    if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
        return;
    }

    // Include the custom Blocks Checkout class
    require_once plugin_dir_path(__FILE__) . 'includes/class-wc-bunq-woocommerce-block-checkout.php';

    // Hook the registration function to the 'woocommerce_blocks_payment_method_type_registration' action
    add_action(
        'woocommerce_blocks_payment_method_type_registration',
        function( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
            // Register an instance of WC_Bunq_WooCommerce_Block_Checkout
            $payment_method_registry->register( new WC_Bunq_WooCommerce_Block_Checkout );
        }
    );
}

// Hook the custom function to the 'before_woocommerce_init' action
add_action('before_woocommerce_init', 'declare_cart_checkout_blocks_compatibility');

// Translations live in /languages (the plugin is not distributed through wordpress.org).
add_action( 'init', function() {
    load_plugin_textdomain( 'bunq-for-woocommerce', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}, 1 );

if ( ! bunq_requirements_check() ) {
    add_action( 'admin_init', 'bunq_requirements_disable_plugin' );
    add_action( 'admin_notices', 'bunq_requirements_show_notice' );
    return;
}

require_once (__DIR__.'/includes/payment-status.php');

/*
 * This action hook registers our PHP class as a WooCommerce payment gateway
 */
add_filter( 'woocommerce_payment_gateways', 'bunq_add_gateway_class' );
function bunq_add_gateway_class( $gateways ) {
    $gateways[] = 'WC_Bunq_Gateway'; // your class name is here
    return $gateways;
}

/*
 * The class itself, please note that it is inside plugins_loaded action hook
 */
add_action( 'plugins_loaded', 'bunq_init_gateway_class' );
function bunq_init_gateway_class() {

    class WC_Bunq_Gateway extends WC_Payment_Gateway {

        const LAST_ERROR_TRANSIENT = 'wc_bunq_gateway.last_error';
        const LAST_SUCCESS_TRANSIENT = 'wc_bunq_gateway.last_success';
        const BANK_ACCOUNTS_TRANSIENT = 'wc_bunq_gateway.bunq_get_bank_accounts';
        const REFRESH_ACCOUNTS_ACTION = 'bunq_refresh_accounts';
        const CONTEXT_FAILED_TRANSIENT = 'wc_bunq_gateway.context_failed';

        var $api_key;
        var $testmode;
        var $monetary_account_bank_id;
        var $api_context;
        var $oauth_client_id;
        var $oauth_client_secret;
        var $direct_gateway;
        var $payment_methods;

        public function __construct() {
            $this->id = 'bunq';
            $this->icon = '';
            $this->method_title = 'bunq';
            $this->method_description = __('bunq payment gateway for WooCommerce', 'bunq-for-woocommerce');
            // 'id' is what the setting and the checkout form store (keep stable); 'bunqme' is the value bunq.me
            // expects in its paymentMethod query parameter to open that method directly.
            $this->payment_methods = [
                [
                    'id' => 'card',
                    'bunqme' => 'CARD',
                    'description' => __('Credit or Debit Card', 'bunq-for-woocommerce'),
                    'min' => 1,
                    'max' => 500,
                ],
                [
                    'id' => 'ideal',
                    'bunqme' => 'IDEAL',
                    'description' => __('iDEAL', 'bunq-for-woocommerce'),
                    'min' => 0.01,
                    'max' => null,
                ],
                [
                    'id' => 'bancontact',
                    'bunqme' => 'BANCONTACT',
                    'description' => __('Bancontact', 'bunq-for-woocommerce'),
                    'min' => 5,
                    'max' => 10000,
                ],
                [
                    'id' => 'bunq-transfer',
                    'bunqme' => 'BUNQ_TRANSFER',
                    'description' => __('From a bunq account', 'bunq-for-woocommerce'),
                    'min' => 0.01,
                    'max' => null,
                ],
            ];

            $this->supports = array(
                'products',
                'refunds',
            );

            // "Refresh bank accounts" button on the settings page: drop the cached list before the fields are built.
            if(is_admin() && isset($_GET[self::REFRESH_ACCOUNTS_ACTION]) && current_user_can('manage_woocommerce')
                && wp_verify_nonce(sanitize_key(wp_unslash($_GET['_wpnonce'] ?? '')), self::REFRESH_ACCOUNTS_ACTION))
            {
                delete_transient(self::BANK_ACCOUNTS_TRANSIENT);
            }

            $this->init_form_fields();

            // Load the settings.
            $this->init_settings();
            $this->title = $this->get_option( 'title' );
            $this->description = $this->get_option( 'description' );
            $this->enabled = $this->get_option( 'enabled' );
            $this->testmode = 'yes' === $this->get_option( 'testmode' );
            $this->api_key = $this->testmode ? $this->get_option( 'test_api_key' ) : $this->get_option( 'api_key' );
            $this->oauth_client_id = $this->testmode ? $this->get_option( 'test_oauth_client_id' ) : $this->get_option( 'oauth_client_id' );
            $this->oauth_client_secret = $this->testmode ? $this->get_option( 'test_oauth_client_secret' ) : $this->get_option( 'oauth_client_secret' );
            $this->api_context = $this->testmode ? $this->get_option( 'test_api_context' ) : $this->get_option( 'api_context' );
            $this->monetary_account_bank_id = $this->get_option( 'monetary_account_bank_id' );
            $this->direct_gateway = 'yes' === $this->get_option( 'direct_gateway' );
            $this->has_fields = $this->direct_gateway;

            // This action hook saves the settings
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

            // You can also register a webhook here
            add_action( 'woocommerce_api_wc_bunq_gateway', array( $this, 'bunq_callback' ) );

            if(is_admin() && isset($_GET['code']) && $_GET['code'] && isset($_GET['state']) && $_GET['state'] && isset($_GET['section']) && $_GET['section'] === $this->id)
            {
                $this->handle_oauth_callback();
            }
        }

        /**
         * Handle the redirect back from bunq after the OAuth authorization request.
         * Exchanges the code for an access token and creates the bunq API context.
         */
        public function handle_oauth_callback()
        {
            // Strip the OAuth parameters so the redirect URI matches the one registered at bunq.
            $oauth_redirect_uri = remove_query_arg(array('code', 'state'), bunq_helper_get_current_url());

            // Buffer any output (e.g. PHP deprecation notices from the SDK) so the redirect header can still be sent.
            ob_start();

            delete_transient(self::LAST_ERROR_TRANSIENT);
            delete_transient(self::LAST_SUCCESS_TRANSIENT);

            try {
                $access_token = bunq_oauth2_get_access_token($this->oauth_client_id, $this->oauth_client_secret, $oauth_redirect_uri, $this->testmode);

                delete_transient(self::BANK_ACCOUNTS_TRANSIENT);
                $this->update_option(($this->testmode ? 'test_api_key' : 'api_key'), $access_token);
                $this->refresh_api_context();

                set_transient(self::LAST_SUCCESS_TRANSIENT, __('bunq authorization completed. Select your bank account and save the settings.', 'bunq-for-woocommerce'), 5 * MINUTE_IN_SECONDS);
            }
            catch (Throwable $exception) {
                bunq_helper_log($exception);
                set_transient(self::LAST_ERROR_TRANSIENT, bunq_helper_format_error($exception), 5 * MINUTE_IN_SECONDS);
            }

            ob_end_clean();

            wp_safe_redirect($oauth_redirect_uri);
            exit;
        }

        /**
         * The payment methods the customer may pick, limited to the enabled ones and to those whose bunq limits fit the total.
         *
         * @param float|null $total Cart or order total; null allows every enabled method.
         * @return array
         */
        public function get_allowed_payment_methods($total = null)
        {
            $enabled_payment_methods_setting = $this->get_setting('enabled_payment_methods');
            if(is_array($enabled_payment_methods_setting) && !empty($enabled_payment_methods_setting)) {
                $enabled_payment_methods = array_filter($this->payment_methods, function($payment_method) use ($enabled_payment_methods_setting) {
                    return in_array($payment_method['id'], $enabled_payment_methods_setting);
                });
            } else {
                $enabled_payment_methods = $this->payment_methods;
            }

            if($total === null) {
                return $enabled_payment_methods;
            }

            return array_filter($enabled_payment_methods, function($payment_method) use ($total) {
                return $payment_method['min'] <= $total && ($payment_method['max'] === null || $payment_method['max'] >= $total);
            });
        }

        /**
         * The amount the customer is about to pay: the order total on the pay-for-order page, otherwise the cart
         * total. Null when there is no amount to filter on (empty cart, block editor).
         *
         * @return float|null
         */
        public function get_checkout_total()
        {
            if(function_exists('is_wc_endpoint_url') && is_wc_endpoint_url('order-pay')) {
                $order = wc_get_order(absint(get_query_var('order-pay')));
                $total = $order ? (float) $order->get_total() : 0;
            } else {
                $total = function_exists('WC') && WC()->cart ? (float) WC()->cart->total : 0;
            }

            return $total > 0 ? $total : null;
        }

        public function payment_fields()
        {
            if($this->description) {
                echo wpautop(wp_kses_post($this->description));
            }

            if($this->has_fields) {
                $total = $this->get_checkout_total();

                woocommerce_form_field(
                    'wc_bunq_gateway_payment_method',
                    [
                        'type'        => 'select',
                        'class'       => ['form-row-wide'],
                        'required'    => true,
                        'options'     =>  array_column($this->get_allowed_payment_methods($total), 'description', 'id')
                    ]
                );
            }
        }

        public function validate_fields()
        {
            if (!empty($_POST['wc_bunq_gateway_payment_method']) && !in_array($_POST['wc_bunq_gateway_payment_method'], array_column($this->payment_methods, 'id'))) {
                wc_add_notice(__('Payment method invalid', 'bunq-for-woocommerce'), 'error');
                return false;
            }

            return true;
        }

        public function admin_options()
        {
            $last_error = get_transient(self::LAST_ERROR_TRANSIENT);
            if($last_error) {
                delete_transient(self::LAST_ERROR_TRANSIENT);
                echo '<div class="notice notice-error"><p><strong>bunq for WooCommerce:</strong> '.esc_html($last_error).'</p>'
                    .'<p>'.sprintf(
                        /* translators: %s: link to the WooCommerce log page */
                        esc_html__('Details are logged in %s (source: bunq).', 'bunq-for-woocommerce'),
                        '<a href="'.esc_url(admin_url('admin.php?page=wc-status&tab=logs')).'">'.esc_html__('WooCommerce > Status > Logs', 'bunq-for-woocommerce').'</a>'
                    ).'</p></div>';
            }

            $last_success = get_transient(self::LAST_SUCCESS_TRANSIENT);
            if($last_success) {
                delete_transient(self::LAST_SUCCESS_TRANSIENT);
                echo '<div class="notice notice-success"><p><strong>bunq for WooCommerce:</strong> '.esc_html($last_success).'</p></div>';
            }

            parent::admin_options();
            $this->init_settings();

            $testmode = 'yes' === $this->get_option( 'testmode' );
            $oauth_client_id = $testmode ? $this->get_option( 'test_oauth_client_id' ) : $this->get_option( 'oauth_client_id' );
            $oauth_client_secret = $testmode ? $this->get_option( 'test_oauth_client_secret' ) : $this->get_option( 'oauth_client_secret' );

            $settings_url = remove_query_arg(array('code', 'state', self::REFRESH_ACCOUNTS_ACTION, '_wpnonce'), bunq_helper_get_current_url());

            if($oauth_client_id && $oauth_client_secret)
            {
                $url = bunq_oauth2_get_authorization_url(
                    $oauth_client_id,
                    $oauth_client_secret,
                    $settings_url,
                    $testmode
                );
                echo '<a href="'.esc_url($url).'" class="button-secondary">'.esc_html__('OAuth Authorization Request', 'bunq-for-woocommerce').'</a> ';
            }

            if($this->get_setting('api_context'))
            {
                $refresh_url = wp_nonce_url(add_query_arg(self::REFRESH_ACCOUNTS_ACTION, '1', $settings_url), self::REFRESH_ACCOUNTS_ACTION);
                echo '<a href="'.esc_url($refresh_url).'" class="button-secondary">'.esc_html__('Refresh bank accounts', 'bunq-for-woocommerce').'</a>';
            }
        }

        /**
         * (Re)create the bunq API context from the saved API key.
         *
         * @throws Throwable When the API context could not be created (e.g. bunq rejected the key).
         */
        public function refresh_api_context($register_callback = true)
        {
            // Get saved testmode and api key
            $testmode = 'yes' === $this->get_setting('testmode');
            $api_key = $testmode ? $this->get_setting('test_api_key') : $this->get_setting('api_key');

            if(!$api_key)
            {
                return;
            }

            // Creating the context talks to bunq (installation, device-server, session-server). Let errors bubble up
            // so the caller can show them to the admin instead of silently leaving the API context empty.
            $api_context = bunq_create_api_context($api_key, $testmode);
            $this->update_option(($testmode ? 'test_api_context' : 'api_context'), $api_context->toJson());
            delete_transient(self::CONTEXT_FAILED_TRANSIENT);
            bunq_helper_log('bunq API context created for '.($testmode ? 'sandbox' : 'production'), 'info');

            if(!$register_callback)
            {
                return;
            }

            // Register the callback URL at bunq so payments are confirmed automatically. A callback URL that only
            // resolves locally (development sites) cannot be reached by bunq, so skip it there.
            $callback_url = WC()->api_request_url('wc_bunq_gateway');

            if(bunq_helper_is_local_url($callback_url))
            {
                bunq_helper_log('Skipped registering the bunq callback URL '.$callback_url.' because bunq cannot reach it.', 'info');
            }
            else
            {
                try {
                    bunq_create_notification_filters($this->get_monetary_account_bank_id());
                    bunq_helper_log('bunq callback URL registered: '.$callback_url, 'info');
                } catch (Throwable $exception) {
                    // Not fatal for the setup: payments still work, only the automatic payment confirmation is affected.
                    bunq_helper_log($exception, 'warning');
                }
            }
        }

        public function process_admin_options() {
            parent::process_admin_options();

            // Reset readonly options based on OAuth Client ID and OAuth Client Secret.
            // Only the fields of the active mode are in the form, so the other mode's keys can be absent
            // until they have been saved once: a missing key counts as "not set".
            if(empty($this->settings['test_oauth_client_id']) || empty($this->settings['test_oauth_client_secret']))
            {
                $this->update_option('test_api_context', '');
                $this->update_option('test_api_key', '');
                delete_transient(self::BANK_ACCOUNTS_TRANSIENT);
            }

            if(empty($this->settings['oauth_client_id']) || empty($this->settings['oauth_client_secret']))
            {
                $this->update_option('api_context', '');
                $this->update_option('api_key', '');
                delete_transient(self::BANK_ACCOUNTS_TRANSIENT);
            }
        }

        public function get_setting($key)
        {
            $this->init_settings();

            if(!isset($this->settings['testmode']))
            {
                return null;
            }

            $testmode = 'yes' === $this->settings['testmode'];

            if($key === 'api_context')
            {
                $key = $testmode ? 'test_api_context' : 'api_context';
            }

            if($key === 'oauth_client_id')
            {
                $key = $testmode ? 'test_oauth_client_id' : 'oauth_client_id';
            }

            if($key === 'oauth_client_secret')
            {
                $key = $testmode ? 'test_oauth_client_secret' : 'oauth_client_secret';
            }

            return isset($this->settings[$key]) ? $this->settings[$key] : null;
        }

        /**
         * @return int|null The selected bunq monetary account, or null for the account's default.
         */
        public function get_monetary_account_bank_id()
        {
            $monetary_account_bank_id = intval($this->get_setting('monetary_account_bank_id'));

            return $monetary_account_bank_id > 0 ? $monetary_account_bank_id : null;
        }

        public function init_form_fields()
        {
            // The bank account list is only needed to render the settings page, and
            // fetching it costs a bunq API call. Outside admin the select simply has
            // no options; the field definitions themselves are always set so that
            // get_option() can fall back to each field's default on the frontend and
            // under WP-CLI.
            $bank_accounts = array();

            if(is_admin()) {
                $bank_accounts = get_transient(self::BANK_ACCOUNTS_TRANSIENT);

                if(! $bank_accounts) {
                    $api_context = $this->load_api_context();
                    $bank_accounts = bunq_get_bank_accounts($api_context);

                    // A real account list is kept for an hour; an error or "not set up yet" result only briefly, so
                    // it does not stick around after the merchant fixed the cause.
                    set_transient(self::BANK_ACCOUNTS_TRANSIENT, $bank_accounts, count($bank_accounts) > 1 ? HOUR_IN_SECONDS : 5 * MINUTE_IN_SECONDS);
                }
            }

            $this->form_fields = array(
                'enabled' => array(
                    'title'       => __('Enable/Disable', 'bunq-for-woocommerce'),
                    'label'       => __('Enable bunq Gateway', 'bunq-for-woocommerce'),
                    'type'        => 'checkbox',
                    'description' => '',
                    'default'     => 'no'
                ),
                'testmode' => array(
                    'title'       => __('Test mode', 'bunq-for-woocommerce'),
                    'label'       => __('Enable Test Mode', 'bunq-for-woocommerce'),
                    'type'        => 'checkbox',
                    'description' => __('Place the payment gateway in test mode using test API keys.', 'bunq-for-woocommerce'),
                    'default'     => 'no',
                    'desc_tip'    => true,
                ),
                'title' => array(
                    'title'       => __('Title', 'bunq-for-woocommerce'),
                    'type'        => 'text',
                    'description' => __('This controls the title which the user sees during checkout.', 'bunq-for-woocommerce'),
                    'default'     => __('iDEAL, Credit Card or Bancontact', 'bunq-for-woocommerce'),
                    'desc_tip'    => true,
                ),
                'description' => array(
                    'title'       => __('Description', 'bunq-for-woocommerce'),
                    'type'        => 'textarea',
                    'description' => __('This controls the description which the user sees during checkout.', 'bunq-for-woocommerce'),
                    'default'     => __('Pay with iDEAL, Credit Card or Bancontact', 'bunq-for-woocommerce'),
                ),
                'monetary_account_bank_id' => array(
                    'title'       => __('Bank account', 'bunq-for-woocommerce'),
                    'type'        => 'select',
                    'options'     =>  $bank_accounts
                ),
                'direct_gateway' => array(
                    'title'       => __('Direct Gateway', 'bunq-for-woocommerce'),
                    'label'       => __('Enable direct gateway', 'bunq-for-woocommerce'),
                    'type'        => 'checkbox',
                    'default'     => 'no',
                    'description' => __('Allow your customers to directly select a payment method from the checkout page.', 'bunq-for-woocommerce'),
                    'desc_tip'    => true,
                ),
            );

            $direct_gateway = 'yes' === $this->get_option( 'direct_gateway' );

            if($direct_gateway) {
                $this->form_fields = array_merge($this->form_fields, array(
                    'enabled_payment_methods' => array(
                        'title'       => __('Payment methods', 'bunq-for-woocommerce'),
                        'type'        => 'multiselect',
                        'custom_attributes' => ['multiple' => 'multiple'],
                        'options'     =>  array_column($this->payment_methods, 'description', 'id')
                    )
                ));
            }

            $testmode = 'yes' === $this->get_option( 'testmode' );

            if($testmode) {
                $this->form_fields = array_merge($this->form_fields, array(
                    'test_oauth_client_id' => array(
                        'title'       => __('Test OAuth Client ID', 'bunq-for-woocommerce'),
                        'type'        => 'text',
                    ),
                    'test_oauth_client_secret' => array(
                        'title'       => __('Test OAuth Client Secret', 'bunq-for-woocommerce'),
                        'type'        => 'password',
                    ),
                    'test_api_key' => array(
                        'title'       => __('Test API Key', 'bunq-for-woocommerce'),
                        'type'        => 'password',
                        'custom_attributes' => array('readonly' => 'readonly')
                    ),
                    'test_api_context' => array(
                        'title'       => __('Test API Context', 'bunq-for-woocommerce'),
                        'type'        => 'textarea',
                        'css'         => 'height: 150px;',
                        'custom_attributes' => array('readonly' => 'readonly')
                    ),
                ));
            } else {
                $this->form_fields = array_merge($this->form_fields, array(
                    'oauth_client_id' => array(
                        'title'       => __('OAuth Client ID', 'bunq-for-woocommerce'),
                        'type'        => 'text'
                    ),
                    'oauth_client_secret' => array(
                        'title'       => __('OAuth Client Secret', 'bunq-for-woocommerce'),
                        'type'        => 'password'
                    ),
                    'api_key' => array(
                        'title'       => __('Live API Key', 'bunq-for-woocommerce'),
                        'type'        => 'password',
                        'custom_attributes' => array('readonly' => 'readonly')
                    ),
                    'api_context' => array(
                        'title'       => __('Live API Context', 'bunq-for-woocommerce'),
                        'type'        => 'textarea',
                        'css'         => 'height: 150px;',
                        'custom_attributes' => array('readonly' => 'readonly')
                    ),
                ));
            }
        }

        /**
         * Only offer the gateway at checkout when the bunq authorization has been completed; without an API
         * context no payment request can be created.
         */
        public function is_available()
        {
            if(!parent::is_available()) {
                return false;
            }

            return (bool) $this->get_setting('api_context') && !get_transient(self::CONTEXT_FAILED_TRANSIENT);
        }

        public function process_payment( $order_id ) {
            $order = wc_get_order( $order_id );

            try {
                $this->ensure_api_context_loaded();

                // The block checkout does not call validate_fields(), so check the posted method here as well.
                $bunqme_payment_method = null;
                if(!empty($_POST['wc_bunq_gateway_payment_method'])) {
                    $requested_payment_method = sanitize_text_field(wp_unslash($_POST['wc_bunq_gateway_payment_method']));
                    $bunqme_payment_method = array_column($this->payment_methods, 'bunqme', 'id')[$requested_payment_method] ?? null;
                }

                $payment_request = bunq_create_payment_request(
                    $order->get_total(),
                    $order->get_currency(),
                    '#'.$order->get_order_number(),
                    $this->get_return_url($order),
                    $this->get_monetary_account_bank_id()
                );

                if(!$payment_request || empty($payment_request['id']) || empty($payment_request['url'])) {
                    throw new Exception('bunq did not return a payment request for order #'.$order->get_order_number());
                }

                $order->add_order_note(sprintf(
                    /* translators: %s: bunq payment request id */
                    __('bunq payment request %s created', 'bunq-for-woocommerce'),
                    $payment_request['id']
                ));
                $order->update_meta_data( 'bunq_payment_request_id', $payment_request['id']);
                $order->save();

                // Fallback for a callback that never arrives: re-check the payment request in the background.
                bunq_schedule_payment_check($order->get_id());

                // Direct gateway: bunq.me opens the chosen method straight away when it is passed as ?paymentMethod=
                // (it used to be a path segment, which bunq.me now ignores).
                $redirect = $bunqme_payment_method
                    ? add_query_arg('paymentMethod', $bunqme_payment_method, $payment_request['url'])
                    : $payment_request['url'];

                return array(
                    'result'   => 'success',
                    'redirect' => $redirect,
                );
            }
            catch (Throwable $exception) {
                // Log the real cause for the merchant; the customer only gets a generic message.
                bunq_helper_log($exception);
                wc_add_notice(__('The payment could not be started with bunq. Please try again or choose another payment method.', 'bunq-for-woocommerce'), 'error');

                return array('result' => 'failure');
            }
        }

        /**
         * Refund (part of) the order by sending the money back to the account the bunq payment came from.
         *
         * @param int $order_id
         * @param float|null $amount
         * @param string $reason
         * @return bool|WP_Error
         */
        public function process_refund( $order_id, $amount = null, $reason = '' ) {
            $order = wc_get_order( $order_id );
            $payment_id = $order ? intval($order->get_meta('bunq_payment_id')) : 0;

            if(!$payment_id) {
                return new WP_Error('bunq_refund', __('This order has no bunq payment that can be refunded.', 'bunq-for-woocommerce'));
            }

            if(!$amount || (float) $amount <= 0) {
                return new WP_Error('bunq_refund', __('The refund amount must be greater than zero.', 'bunq-for-woocommerce'));
            }

            try {
                $this->ensure_api_context_loaded();

                $monetary_account_id = intval($order->get_meta('bunq_monetary_account_id')) ?: $this->get_monetary_account_bank_id();
                $payment = bunq_get_payment($payment_id, $monetary_account_id);
                $counterparty = $payment->getCounterpartyAlias();
                $iban = $counterparty ? $counterparty->getIban() : null;

                if(!$iban) {
                    return new WP_Error('bunq_refund', __('The bunq payment for this order has no IBAN to refund to (for example a card payment). Refund it from the bunq app instead.', 'bunq-for-woocommerce'));
                }

                $description = sprintf(
                    /* translators: 1: order number, 2: shop name */
                    __('Refund order %1$s %2$s', 'bunq-for-woocommerce'),
                    $order->get_order_number(),
                    get_bloginfo('name')
                );
                if($reason) {
                    $description .= ': '.$reason;
                }

                $refund_payment_id = bunq_create_refund(
                    (float) $amount,
                    $order->get_currency(),
                    $iban,
                    $counterparty->getDisplayName() ?: $order->get_formatted_billing_full_name(),
                    $description,
                    $monetary_account_id
                );

                $order->add_order_note(sprintf(
                    /* translators: 1: refunded amount, 2: bunq draft payment id */
                    __('bunq refund of %1$s created as draft payment %2$s. Approve it in the bunq app to send the money.', 'bunq-for-woocommerce'),
                    wc_price($amount, array('currency' => $order->get_currency())),
                    $refund_payment_id
                ));
                bunq_helper_log('bunq draft payment '.$refund_payment_id.' of '.$order->get_currency().' '.$amount.' created for the refund of order #'.$order->get_order_number().', waiting for approval in the bunq app', 'info');

                return true;
            }
            catch (Throwable $exception) {
                bunq_helper_log($exception);

                return new WP_Error('bunq_refund', sprintf(
                    /* translators: %s: error message from bunq */
                    __('bunq refund failed: %s', 'bunq-for-woocommerce'),
                    bunq_helper_format_error($exception)
                ));
            }
        }

        /**
         * Callback from bunq (notification filter BUNQME_TAB). Confirms the order after fetching the payment
         * request from bunq; the notification body itself is not trusted.
         */
        public function bunq_callback() {
            $input = file_get_contents('php://input');
            $notification = $input ? json_decode($input) : null;

            $category = $notification->NotificationUrl->category ?? null;
            $payment_request_id = $notification->NotificationUrl->object->BunqMeTab->id ?? null;

            if($category !== 'BUNQME_TAB' || !is_numeric($payment_request_id))
            {
                bunq_helper_log('bunq callback ignored: category '.var_export($category, true).', bunqme-tab id '.var_export($payment_request_id, true), 'debug');
                exit;
            }

            $payment_request_id = (int) $payment_request_id;

            // Retrieve the order that belongs to this bunq payment request. Two results means the meta data is
            // ambiguous, so stop rather than guess.
            $orders = wc_get_orders( array(
                'limit'        => 2,
                'meta_key'     => 'bunq_payment_request_id',
                'meta_value'   => $payment_request_id,
                'meta_compare' => '=',
            ));

            if(count($orders) !== 1)
            {
                bunq_helper_log('bunq callback for bunqme-tab '.$payment_request_id.': found '.count($orders).' orders, expected exactly one', 'warning');
                exit;
            }

            $order = $orders[0];

            if(!$order->needs_payment())
            {
                bunq_helper_log('bunq callback for bunqme-tab '.$payment_request_id.': order #'.$order->get_order_number().' no longer needs payment (status '.$order->get_status().')', 'debug');
                exit;
            }

            try {
                $outcome = $this->check_payment_status($order);
                bunq_helper_log('bunq callback for bunqme-tab '.$payment_request_id.': order #'.$order->get_order_number().' is '.$outcome, 'debug');
            }
            catch (Throwable $exception) {
                bunq_helper_log($exception);
            }

            exit;
        }

        /**
         * Fetch the bunq payment request of an order and bring the order in line with it: complete the order when
         * a matching payment came in, cancel it when bunq cancelled or expired the request.
         *
         * @param WC_Order $order
         * @return string One of "paid", "on-hold" (a payment with another amount came in), "cancelled", "pending"
         *                or "settled" (the order did not need payment).
         * @throws Throwable When bunq could not be reached.
         */
        public function check_payment_status( $order ) {
            if(!$order->needs_payment()) {
                return 'settled';
            }

            $payment_request_id = intval($order->get_meta('bunq_payment_request_id'));

            if(!$payment_request_id) {
                throw new Exception('Order #'.$order->get_order_number().' has no bunq payment request');
            }

            // The callback, the customer's return and the scheduled check can run at the same time; let one of
            // them do the work.
            $lock = 'wc_bunq_gateway.lock.'.$order->get_id();
            if(get_transient($lock)) {
                return 'pending';
            }
            set_transient($lock, 1, 30);

            try {
                $this->ensure_api_context_loaded();
                $payment_request = bunq_get_payment_request($payment_request_id, $this->get_monetary_account_bank_id());

                $unmatched_payment = null;

                foreach((array) $payment_request->getResultInquiries() as $result_inquiry)
                {
                    $payment = $result_inquiry->getPayment();

                    if(!$payment)
                    {
                        continue;
                    }

                    // bunq returns the amount as a string; compare it as a decimal so the number of decimals in the shop does not matter.
                    $paid_amount = $payment->getAmount();

                    if($paid_amount->getCurrency() !== $order->get_currency() || !bunq_helper_amounts_match($paid_amount->getValue(), $order->get_total()))
                    {
                        bunq_helper_log('bunqme-tab '.$payment_request_id.': payment '.$payment->getId().' of '.$paid_amount->getCurrency().' '.$paid_amount->getValue().' does not match order #'.$order->get_order_number().' total '.$order->get_currency().' '.$order->get_total(), 'warning');
                        $unmatched_payment = $payment;
                        continue;
                    }

                    bunq_helper_log('bunqme-tab '.$payment_request_id.': payment '.$payment->getId().' received for order #'.$order->get_order_number(), 'info');

                    $order->add_order_note(sprintf(
                        /* translators: %s: bunq payment id */
                        __('bunq payment %s received', 'bunq-for-woocommerce'),
                        $payment->getId()
                    ));
                    $order->update_meta_data( 'bunq_payment_id', $payment->getId());
                    // The account the money landed on, so a refund still works after the merchant changes the setting.
                    $order->update_meta_data( 'bunq_monetary_account_id', $payment->getMonetaryAccountId());
                    $order->save();
                    $order->payment_complete((string) $payment->getId());

                    return 'paid';
                }

                // Money arrived but not the right amount: never cancel such an order, let the merchant decide.
                if($unmatched_payment)
                {
                    $unmatched_amount = $unmatched_payment->getAmount();
                    $order->update_meta_data( 'bunq_payment_id', $unmatched_payment->getId());
                    $order->update_meta_data( 'bunq_monetary_account_id', $unmatched_payment->getMonetaryAccountId());
                    $order->save();
                    $order->update_status('on-hold', sprintf(
                        /* translators: 1: bunq payment id, 2: amount received, 3: order total */
                        __('bunq payment %1$s of %2$s received, but the order total is %3$s. Check the payment before processing the order.', 'bunq-for-woocommerce'),
                        $unmatched_payment->getId(),
                        $unmatched_amount->getCurrency().' '.$unmatched_amount->getValue(),
                        $order->get_currency().' '.$order->get_total()
                    ));

                    return 'on-hold';
                }

                $status = (string) $payment_request->getStatus();

                if(in_array($status, array('CANCELLED', 'EXPIRED'), true))
                {
                    bunq_helper_log('bunqme-tab '.$payment_request_id.' is '.$status.', cancelling order #'.$order->get_order_number(), 'info');

                    // Remember bunq's final state so the order-cancelled hook does not try to cancel the request again.
                    $order->update_meta_data('bunq_payment_request_status', $status);
                    $order->save();
                    $order->update_status('cancelled', sprintf(
                        /* translators: 1: bunq payment request id, 2: status reported by bunq */
                        __('bunq payment request %1$s is %2$s', 'bunq-for-woocommerce'),
                        $payment_request_id,
                        strtolower($status)
                    ));

                    return 'cancelled';
                }

                return 'pending';
            }
            finally {
                delete_transient($lock);
            }
        }

        /**
         * Cancel the bunq payment request of an order at bunq so it can no longer be paid.
         *
         * @param int $payment_request_id
         * @throws Throwable
         */
        public function cancel_payment_request( $payment_request_id ) {
            $this->ensure_api_context_loaded();
            bunq_cancel_payment_request($payment_request_id, $this->get_monetary_account_bank_id());
        }

        /**
         * Make sure the bunq API context is loaded for this request. When the stored context is missing or can no
         * longer be used (expired or revoked session), rebuild it from the saved API key before giving up.
         *
         * @throws Throwable When no usable API context is available.
         */
        public function ensure_api_context_loaded() {
            if($this->load_api_context()) {
                return;
            }

            // A rebuild costs several bunq calls; do not repeat it for every request while the key stays unusable.
            if(get_transient(self::CONTEXT_FAILED_TRANSIENT)) {
                throw new Exception('bunq API context is not available and a recent attempt to recreate it failed. Complete the OAuth authorization in WooCommerce > Settings > Payments > bunq.');
            }

            bunq_helper_log('bunq API context could not be loaded, recreating it from the saved API key', 'warning');

            try {
                $this->refresh_api_context(false);
            } catch (Throwable $exception) {
                set_transient(self::CONTEXT_FAILED_TRANSIENT, 1, 5 * MINUTE_IN_SECONDS);
                throw $exception;
            }

            if(!$this->load_api_context()) {
                set_transient(self::CONTEXT_FAILED_TRANSIENT, 1, 5 * MINUTE_IN_SECONDS);
                throw new Exception('bunq API context is not available. Complete the OAuth authorization in WooCommerce > Settings > Payments > bunq.');
            }
        }

        function load_api_context() {
	        $api_context_json = $this->get_setting('api_context');
	        $testmode = 'yes' === $this->get_setting('testmode');

	        // Load Bunq API context from JSON
	        if($api_context_json)
	        {
		        $new_api_context_json = bunq_load_api_context_from_json($api_context_json);

		        if($new_api_context_json && $new_api_context_json != $api_context_json)
		        {
			        $this->update_option(($testmode ? 'test_api_context' : 'api_context'), $new_api_context_json);
		        }

		        return (bool) $new_api_context_json;
	        }

	        return false;
        }
    }
}
