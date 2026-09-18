<?php
/**
 * Plugin Name: bunq for WooCommerce
 * Description: Accept payments in your WooCommerce shop with just your bunq account.
 * Version: 1.5.6
 * Author: Patrick Kivits
 * Author URI: https://www.patrickkivits.nl
 * Requires at least: 3.8
 * Tested up to: 6.8
 * Text Domain: bunq-for-woocommerce
 * License: GPLv2 or later
 * WC requires at least: 2.2.0
 * WC tested up to: 9.8
 */

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

if ( ! bunq_requirements_check() ) {
    add_action( 'admin_init', 'bunq_requirements_disable_plugin' );
    add_action( 'admin_notices', 'bunq_requirements_show_notice' );
    return;
}

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

        var $api_key;
        var $testmode;
        var $monetary_account_bank_id;
        var $api_context;
        var $oauth_client_id;
        var $oauth_client_secret;
        var $direct_gateway;
        var $enabled_payment_methods;
        var $payment_methods;

        public function __construct() {
            $this->id = 'bunq';
            $this->icon = '';
            $this->method_title = 'bunq';
            $this->method_description = 'bunq payment gateway for WooCommerce';
            $this->payment_methods = [
                [
                    'id' => 'card',
                    'description' => 'Credit or Debit Card',
                    'min' => 1,
                    'max' => 500,
                ],
                [
                    'id' => 'ideal',
                    'description' => 'iDEAL',
                    'min' => 0.01,
                    'max' => null,
                ],
                [
                    'id' => 'bancontact',
                    'description' => 'Bancontact',
                    'min' => 5,
                    'max' => 10000,
                ],
                [
                    'id' => 'bunq-transfer',
                    'description' => 'From a bunq account',
                    'min' => 0.01,
                    'max' => null,
                ],
            ];

            $this->supports = array(
                'products'
            );

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
            $this->enabled_payment_methods = $this->get_option( 'enabled_payment_methods' ) || $this->payment_methods;

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

                delete_transient('wc_bunq_gateway.bunq_get_bank_accounts');
                $this->update_option(($this->testmode ? 'test_api_key' : 'api_key'), $access_token);
                $this->refresh_api_context();

                set_transient(self::LAST_SUCCESS_TRANSIENT, 'bunq authorization completed. Select your bank account and save the settings.', 5 * MINUTE_IN_SECONDS);
            }
            catch (Throwable $exception) {
                bunq_helper_log($exception);
                set_transient(self::LAST_ERROR_TRANSIENT, bunq_helper_format_error($exception), 5 * MINUTE_IN_SECONDS);
            }

            ob_end_clean();

            wp_safe_redirect($oauth_redirect_uri);
            exit;
        }

        public function payment_fields()
        {
            global $woocommerce;

            $enabled_payment_methods_setting = $this->get_setting('enabled_payment_methods');
            if(is_array($enabled_payment_methods_setting) && !empty($enabled_payment_methods_setting)) {
                $enabled_payment_methods = array_filter($this->payment_methods, function($payment_method) use ($enabled_payment_methods_setting) {
                    return in_array($payment_method['id'], $enabled_payment_methods_setting);
                });
            } else {
                $enabled_payment_methods = $this->payment_methods;
            }

            $total = $woocommerce->cart->total;
            $allowed_payment_methods = array_filter($enabled_payment_methods, function($payment_method) use ($total) {
                return $payment_method['min'] <= $total && ($payment_method['max'] === null || $payment_method['max'] >= $total);
            });

            if($this->has_fields) {
                echo woocommerce_form_field(
                    'wc_bunq_gateway_payment_method',
                    [
                        'type'        => 'select',
                        'class'       => ['form-row-wide'],
                        'required'    => true,
                        'options'     =>  array_column($allowed_payment_methods, 'description', 'id')
                    ]
                );
            }
        }

        public function validate_fields()
        {
            if (!empty($_POST['wc_bunq_gateway_payment_method']) && !in_array($_POST['wc_bunq_gateway_payment_method'], array_column($this->payment_methods, 'id'))) {
                wc_add_notice('Payment method invalid', 'error');
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
                    .'<p>Details are logged in <a href="'.esc_url(admin_url('admin.php?page=wc-status&tab=logs')).'">WooCommerce &gt; Status &gt; Logs</a> (source: <code>bunq</code>).</p></div>';
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

            if($oauth_client_id && $oauth_client_secret)
            {
                $oauth_redirect_uri = bunq_helper_get_current_url();
                $url = bunq_oauth2_get_authorization_url(
                    $oauth_client_id,
                    $oauth_client_secret,
                    $oauth_redirect_uri,
                    $testmode
                );
                echo '<a href="'.$url.'" class="button-secondary">OAuth Authorization Request</a>';
            }
        }

        /**
         * (Re)create the bunq API context from the saved API key.
         *
         * @throws Throwable When the API context could not be created (e.g. bunq rejected the key).
         */
        public function refresh_api_context()
        {
            // Get saved testmode and api key
            $testmode = 'yes' === $this->get_setting('testmode');
            $api_key = $testmode ? $this->get_setting('test_api_key') : $this->get_setting('api_key');
            $monetary_account_bank_id = $this->get_setting('monetary_account_bank_id') > 0 ? intval($this->get_setting('monetary_account_bank_id')) : null;

            if(!$api_key)
            {
                return;
            }

            // Creating the context talks to bunq (installation, device-server, session-server). Let errors bubble up
            // so the caller can show them to the admin instead of silently leaving the API context empty.
            $api_context = bunq_create_api_context($api_key, $testmode);
            $this->update_option(($testmode ? 'test_api_context' : 'api_context'), $api_context->toJson());
            bunq_helper_log('bunq API context created for '.($testmode ? 'sandbox' : 'production'), 'info');

            // Setup callback URL for bunq (not for local environment)
            if(!in_array($_SERVER['REMOTE_ADDR'], array('127.0.0.1', '::1')))
            {
                try {
                    bunq_create_notification_filters($monetary_account_bank_id);
                } catch (Throwable $exception) {
                    // Not fatal for the setup: payments still work, only the automatic payment confirmation is affected.
                    bunq_helper_log($exception, 'warning');
                }
            }
        }

        public function process_admin_options() {
            parent::process_admin_options();

            // Reset readonly options based on OAuth Client ID and OAuth Client Secret
            if(!$this->settings['test_oauth_client_id'] || !$this->settings['test_oauth_client_secret'])
            {
                $this->update_option('test_api_context', '');
                $this->update_option('test_api_key', '');
                delete_transient('wc_bunq_gateway.bunq_get_bank_accounts');
            }

            if(!$this->settings['oauth_client_id'] || !$this->settings['oauth_client_secret'])
            {
                $this->update_option('api_context', '');
                $this->update_option('api_key', '');
                delete_transient('wc_bunq_gateway.bunq_get_bank_accounts');
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

        public function init_form_fields()
        {
	        if(is_admin()) {
                $transient = 'wc_bunq_gateway.bunq_get_bank_accounts';
                $bank_accounts = get_transient($transient);

                if(! $bank_accounts) {
                    $api_context = $this->load_api_context();
                    $bank_accounts = bunq_get_bank_accounts($api_context);
                    set_transient($transient, $bank_accounts);
                }

		        $this->form_fields = array(
			        'enabled' => array(
				        'title'       => 'Enable/Disable',
				        'label'       => 'Enable bunq Gateway',
				        'type'        => 'checkbox',
				        'description' => '',
				        'default'     => 'no'
			        ),
                    'testmode' => array(
                        'title'       => 'Test mode',
                        'label'       => 'Enable Test Mode',
                        'type'        => 'checkbox',
                        'description' => 'Place the payment gateway in test mode using test API keys.',
                        'default'     => 'no',
                        'desc_tip'    => true,
                    ),
			        'title' => array(
				        'title'       => 'Title',
				        'type'        => 'text',
				        'description' => 'This controls the title which the user sees during checkout.',
				        'default'     => 'iDEAL, Credit Card or Sofort',
				        'desc_tip'    => true,
			        ),
			        'description' => array(
				        'title'       => 'Description',
				        'type'        => 'textarea',
				        'description' => 'This controls the description which the user sees during checkout.',
				        'default'     => 'Pay with iDEAL, Credit Card or Sofort',
			        ),
			        'monetary_account_bank_id' => array(
				        'title'       => 'Bank account',
				        'type'        => 'select',
				        'options'     =>  $bank_accounts
			        ),
                    'direct_gateway' => array(
                        'title'       => 'Direct Gateway',
                        'label'       => 'Enable direct gateway',
                        'type'        => 'checkbox',
                        'default'     => 'no',
                        'description' => 'Allow your customers to directly select a payment method from the checkout page.',
                        'desc_tip'    => true,
                    ),
                );

                $direct_gateway = 'yes' === $this->get_option( 'direct_gateway' );

                if($direct_gateway) {
                    $this->form_fields = array_merge($this->form_fields, array(
                        'enabled_payment_methods' => array(
                            'title'       => 'Payment methods',
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
                            'title'       => 'Test OAuth Client ID',
                            'type'        => 'text',
                        ),
                        'test_oauth_client_secret' => array(
                            'title'       => 'Test OAuth Client Secret',
                            'type'        => 'text',
                        ),
                        'test_api_key' => array(
                            'title'       => 'Test API Key',
                            'type'        => 'text',
                            'custom_attributes' => array('readonly' => 'readonly')
                        ),
                        'test_api_context' => array(
                            'title'       => 'Test API Context',
                            'type'        => 'textarea',
                            'css'         => 'height: 150px;',
                            'custom_attributes' => array('readonly' => 'readonly')
                        ),
                    ));
                } else {
                    $this->form_fields = array_merge($this->form_fields, array(
                        'oauth_client_id' => array(
                            'title'       => 'OAuth Client ID',
                            'type'        => 'text'
                        ),
                        'oauth_client_secret' => array(
                            'title'       => 'OAuth Client Secret',
                            'type'        => 'text'
                        ),
                        'api_key' => array(
                            'title'       => 'Live API Key',
                            'type'        => 'text',
                            'custom_attributes' => array('readonly' => 'readonly')
                        ),
                        'api_context' => array(
                            'title'       => 'Live API Context',
                            'type'        => 'textarea',
                            'css'         => 'height: 150px;',
                            'custom_attributes' => array('readonly' => 'readonly')
                        ),
                    ));
                }
	        }
        }

        public function process_payment( $order_id ) {

            $this->load_api_context();

            $order = wc_get_order( $order_id );
            $monetary_account_bank_id = $this->get_setting('monetary_account_bank_id') > 0 ? intval($this->get_setting('monetary_account_bank_id')) : null;

            $payment_request = bunq_create_payment_request(
                $order->get_total(),
                $order->get_currency(),
                '#'.$order->get_order_number(),
                $this->get_return_url($order),
                $monetary_account_bank_id
            );

            if($payment_request && isset($payment_request['id'])) {
                $order->add_order_note('bunq payment_request created '.$payment_request['id']);
                $order->update_meta_data( 'bunq_payment_request_id', $payment_request['id']);
                $order->save();

                $payment_method = '';
                if($_POST['wc_bunq_gateway_payment_method']) {
                    $payment_method = '/'.$_POST['wc_bunq_gateway_payment_method'];
                }

                return array(
                    'result'   => 'success',
                    'redirect' => $payment_request['url'].$payment_method,
                );
            }

            return array('result' => 'failure');
        }

        public function bunq_callback() {

	        $this->load_api_context();

            // Get input and save raw as text file
            $input = file_get_contents('php://input');

            if(!$input)
            {
                exit;
            }

            $obj = json_decode($input);

            $category = $obj->NotificationUrl->category;

            if($category !== 'BUNQME_TAB')
            {
                exit;
            }

            // Retrieve payment request id (bunqmetab)
            $payment_request_id = $obj->NotificationUrl->object->BunqMeTab->id;

            // Retrieve order by bunq payment request id
            $orders = wc_get_orders( array(
                'limit'        => 1, // Query all orders
                'orderby'      => 'date',
                'order'        => 'DESC',
                'meta_key'     => 'bunq_payment_request_id',
                'meta_compare' => $payment_request_id
            ));

            // Only continue is we have exactly 1 order
            if(count($orders) !== 1)
            {
                exit;
            }

            // Set order
            $order = $orders[0];

            if(!$order->needs_payment())
            {
                exit;
            }

            // Check payment
            $monetary_account_bank_id = $this->get_setting('monetary_account_bank_id') > 0 ? intval($this->get_setting('monetary_account_bank_id')) : null;
            $payment_request = bunq_get_payment_request($payment_request_id, $monetary_account_bank_id);

            foreach($payment_request->getResultInquiries() as $resultInquiry)
            {
                $payment = $resultInquiry->getPayment();

                // Process payment
                if($payment && $payment->getAmount()->getCurrency() === $order->get_currency() && $payment->getAmount()->getValue() === $order->get_total())
                {
                    // Update order with payment id
                    $order->add_order_note('bunq payment received '.$payment->getId());
                    $order->update_meta_data( 'bunq_payment_id', $payment->getId());
                    $order->save();

                    // Complete order
                    global $woocommerce;
                    $woocommerce->cart->empty_cart();
                    $order->payment_complete();

                    break;
                }
            }

            exit;
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

		        return true;
	        }

	        return false;
        }
    }
}
