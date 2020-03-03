<?php
/**
 * Plugin Name: bunq for WooCommerce
 * Description: Accept payments in your WooCommerce shop with just your bunq account.
 * Version: 0.0.1
 * Author: Patrick Kivits
 * Author URI: https://www.patrickkivits.nl
 * Requires at least: 3.8
 * Tested up to: 5.3
 * Text Domain: bunq-for-woocommerce
 * License: GPLv2 or later
 * WC requires at least: 2.2.0
 * WC tested up to: 3.8
 */

require_once (__DIR__.'/vendor/autoload.php');
require_once (__DIR__.'/includes/helpers.php');
require_once (__DIR__.'/includes/oauth2.php');
require_once (__DIR__.'/includes/bunq.php');

// Check for active WooCommerce install
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) return;

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

        var $api_key;
        var $testmode;
        var $monetary_account_bank_id;
        var $api_context;
        var $oauth_client_id;
        var $oauth_client_secret;

        public function __construct() {
            $this->id = 'bunq';
            $this->icon = '';
            $this->has_fields = false;
            $this->method_title = 'bunq';
            $this->method_description = 'bunq payment gateway for WooCommerce';

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

            // This action hook saves the settings
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

            // You can also register a webhook here
            add_action( 'woocommerce_api_wc_bunq_gateway', array( $this, 'bunq_callback' ) );

            if(isset($_GET['code']) && $_GET['code'] && isset($_GET['state']) && $_GET['state'])
            {
                $oauth_redirect_uri = bunq_helper_get_current_url();
                $oauth_redirect_uri = bunq_helper_remove_url_parameter('code', $oauth_redirect_uri);
                $oauth_redirect_uri = bunq_helper_remove_url_parameter('state', $oauth_redirect_uri);
                $oauth_redirect_uri = substr($oauth_redirect_uri, 0, -2); // Remove double &&
                
                try {
                    $access_token = bunq_oauth2_get_access_token($this->oauth_client_id, $this->oauth_client_secret, $oauth_redirect_uri, $this->testmode);

                    if($access_token)
                    {
                        $this->update_option(($this->testmode ? 'test_api_key' : 'api_key'), $access_token);
                        self::refresh_api_context();
                    }
                }
                catch (Exception $exception) {}

                header('Location: '.$oauth_redirect_uri);
                exit;
            }
        }

        public function admin_options()
        {
            parent::admin_options();
            self::init_settings();

            $testmode = 'yes' === self::get_option( 'testmode' );
            $oauth_client_id = $testmode ? self::get_option( 'test_oauth_client_id' ) : self::get_option( 'oauth_client_id' );
            $oauth_client_secret = $testmode ? self::get_option( 'test_oauth_client_secret' ) : self::get_option( 'oauth_client_secret' );
            $oauth_redirect_uri = bunq_helper_get_current_url();

            if($oauth_client_id && $oauth_client_secret)
            {
                $url = bunq_oauth2_get_authorization_url(
                    $oauth_client_id,
                    $oauth_client_secret,
                    $oauth_redirect_uri,
                    $testmode
                );
                echo '<a href="'.$url.'" class="button-secondary">OAuth Authorization Request</a>';
            }
        }

        public function refresh_api_context()
        {
            // Get saved testmode and api key
            $testmode = 'yes' === $this->settings['testmode'];
            $api_key = $testmode ? $this->settings['test_api_key'] : $this->settings['api_key'];
            $monetary_account_bank_id = $this->settings['monetary_account_bank_id'] > 0 ? intval($this->settings['monetary_account_bank_id']) : null;

            // Recreate API context after settings are saved
            try {
                if($api_key)
                {
                    $api_context = bunq_create_api_context($api_key, $testmode);
                    $this->update_option(($testmode ? 'test_api_context' : 'api_context'), $api_context->toJson());

                    // Setup callback URL for bunq (only on production)
                    if(!in_array($_SERVER['REMOTE_ADDR'], array('127.0.0.1', '::1')))
                    {
                        bunq_create_notification_filters($monetary_account_bank_id);
                    }
                }
            } catch (Exception $exception) {}
        }

        public function process_admin_options() {
            parent::process_admin_options();

            self::refresh_api_context();
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
         * Plugin options, we deal with it in Step 3 too
         */
        public function init_form_fields(){

            $api_context = $this->get_setting('api_context');

            // Load Bunq API context
            if($api_context)
            {
                bunq_load_api_context($api_context);
            }

            $this->form_fields = array(
                'enabled' => array(
                    'title'       => 'Enable/Disable',
                    'label'       => 'Enable bunq Gateway',
                    'type'        => 'checkbox',
                    'description' => '',
                    'default'     => 'no'
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
                'testmode' => array(
                    'title'       => 'Test mode',
                    'label'       => 'Enable Test Mode',
                    'type'        => 'checkbox',
                    'description' => 'Place the payment gateway in test mode using test API keys.',
                    'default'     => 'yes',
                    'desc_tip'    => true,
                ),
                'test_api_key' => array(
                    'title'       => 'Test API Key',
                    'type'        => 'text',
                ),
                'api_key' => array(
                    'title'       => 'Live API Key',
                    'type'        => 'text',
                ),
                'test_oauth_client_id' => array(
                    'title'       => 'Test OAuth Client ID',
                    'type'        => 'text',
                ),
                'oauth_client_id' => array(
                    'title'       => 'OAuth Client ID',
                    'type'        => 'text'
                ),
                'test_oauth_client_secret' => array(
                    'title'       => 'Test OAuth Client Secret',
                    'type'        => 'text',
                ),
                'oauth_client_secret' => array(
                    'title'       => 'OAuth Client Secret',
                    'type'        => 'text'
                ),
                'monetary_account_bank_id' => array(
                    'title'       => 'Bank account',
                    'type'        => 'select',
                    'options'     => bunq_get_bank_accounts($api_context)
                ),
                'api_context' => array(
                    'title'       => 'Live API Context',
                    'type'        => 'textarea',
                    'css'         => 'height: 150px;'
                ),
                'test_api_context' => array(
                    'title'       => 'Test API Context',
                    'type'        => 'textarea',
                    'css'         => 'height: 150px;'
                ),
            );
        }

        public function process_payment( $order_id ) {

            $api_context = $this->get_setting('api_context');
            bunq_load_api_context($api_context);

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

                return array(
                    'result'   => 'success',
                    'redirect' => $payment_request['url'],
                );
            }

            return array('result' => 'failure');
        }

        public function bunq_callback() {

            // Get input and save raw as text file
            $input = file_get_contents('php://input');

            if(!$input)
            {
                exit;
            }

            $obj = json_decode($input);

            $category = $obj->NotificationUrl->category;
            $eventType = $obj->NotificationUrl->event_type;

            if($category !== 'BUNQME_TAB' || $eventType !== 'BUNQME_TAB_RESULT_INQUIRY_CREATED')
            {
                exit;
            }

            // Retrieve payment request id (bunqmetab)
            $payment_request_id = $obj->NotificationUrl->object->BunqMeTabResultInquiry->bunq_me_tab_id;

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
    }
}