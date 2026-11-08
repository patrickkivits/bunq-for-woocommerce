<?php

namespace BunqTest;

/**
 * In-memory stand-in for the WordPress and WooCommerce state the plugin touches (options, transients, hooks,
 * orders, the Action Scheduler queue, the WooCommerce log). The stub functions in stubs/wordpress.php read and
 * write it, tests seed and inspect it, and reset() gives every test a clean slate.
 */
final class Environment
{
    public static $options = array();
    public static $transients = array();
    public static $transientTtls = array();
    public static $hooks = array();
    /** Hooks the plugin registered while it was loaded in bootstrap.php; restored by reset(). */
    public static $bootHooks = array();
    /** @var \WC_Order[] keyed by order id */
    public static $orders = array();
    /** @var \WC_Payment_Gateway[] keyed by gateway id, what WC()->payment_gateways()->payment_gateways() returns */
    public static $gateways = array();
    public static $logs = array();
    public static $notices = array();
    public static $scheduledActions = array();
    public static $asyncActions = array();
    public static $unscheduledHooks = array();
    public static $registeredScripts = array();
    /** Data passed to wp_localize_script(), by script handle and object name. */
    public static $localizedScripts = array();
    public static $deactivatedPlugins = array();
    public static $loadedTextdomains = array();
    public static $redirects = array();
    /** @var array[] Meta boxes added through add_meta_box(). */
    public static $metaBoxes = array();
    /** @var string What wc_get_page_screen_id('shop-order') returns: "woocommerce_page_wc-orders" with HPOS. */
    public static $orderScreenId = 'shop_order';
    /** What wp_get_referer() returns. */
    public static $referer = false;
    public static $isAdmin = false;
    public static $doingAjax = false;
    public static $isSsl = false;
    public static $currentUserId = 0;
    public static $currentUserCan = true;
    public static $validNonce = true;
    public static $pluginActive = true;
    /** The WooCommerce endpoint the current request is on (is_wc_endpoint_url()), null for none. */
    public static $wcEndpoint = null;
    public static $queryVars = array();
    public static $blogInfo = array();
    /** Emails sent through WC_Email::send(), as array(to, subject, message). */
    public static $sentEmails = array();
    /** Whether WC_Email::send() succeeds. */
    public static $mailWorks = true;
    /** Errors shown on the edit order screen (WC_Admin_Meta_Boxes::add_error()). */
    public static $adminErrors = array();
    /** @var \WC_Emails */
    public static $mailer = null;
    /** @var \WooCommerce|null */
    public static $woocommerce = null;

    public static function reset()
    {
        self::$options = array('active_plugins' => array('woocommerce/woocommerce.php'));
        self::$transients = array();
        self::$transientTtls = array();
        self::$hooks = self::$bootHooks;
        self::$orders = array();
        self::$gateways = array();
        self::$logs = array();
        self::$notices = array();
        self::$scheduledActions = array();
        self::$asyncActions = array();
        self::$unscheduledHooks = array();
        self::$registeredScripts = array();
        self::$localizedScripts = array();
        self::$deactivatedPlugins = array();
        self::$loadedTextdomains = array();
        self::$redirects = array();
        self::$metaBoxes = array();
        self::$orderScreenId = 'shop_order';
        self::$referer = false;
        self::$isAdmin = false;
        self::$doingAjax = false;
        $GLOBALS['pagenow'] = 'index.php';

        // Secrets the logger learned in an earlier test (the plugin is not loaded yet on the first reset).
        if (class_exists(\PatrickKivits\BunqForWooCommerce\Logger::class)) {
            $secrets = new \ReflectionProperty(\PatrickKivits\BunqForWooCommerce\Logger::class, 'secrets');
            $secrets->setValue(null, array());
        }
        self::$isSsl = false;
        self::$currentUserId = 0;
        self::$currentUserCan = true;
        self::$validNonce = true;
        self::$pluginActive = true;
        self::$wcEndpoint = null;
        self::$queryVars = array();
        self::$blogInfo = array('version' => '6.8', 'name' => 'Test Shop');
        self::$woocommerce = new \WooCommerce();
        self::$sentEmails = array();
        self::$mailWorks = true;
        self::$adminErrors = array();
        self::$mailer = new \WC_Emails();

        \Automattic\WooCommerce\Utilities\FeaturesUtil::$declared = array();

        $_GET = array();
        $_POST = array();
        $_SERVER['HTTP_HOST'] = 'shop.example';
        $_SERVER['REQUEST_URI'] = '/wp-admin/admin.php?page=wc-settings&tab=checkout&section=bunq';
        unset($_SERVER['HTTPS'], $GLOBALS['wp']);
    }

    /**
     * Create an order and make it retrievable through wc_get_order().
     *
     * @param array $data See WC_Order::__construct() for the keys.
     * @return \WC_Order
     */
    public static function addOrder(array $data = array())
    {
        $order = new \WC_Order($data);
        self::$orders[$order->get_id()] = $order;

        return $order;
    }

    /**
     * @param string|null $level Only messages logged at this level, or all of them.
     * @return string[]
     */
    public static function logMessages($level = null)
    {
        $messages = array();

        foreach (self::$logs as $entry) {
            if ($level === null || $entry['level'] === $level) {
                $messages[] = $entry['message'];
            }
        }

        return $messages;
    }
}
