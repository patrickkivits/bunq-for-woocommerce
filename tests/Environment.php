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
    public static $deactivatedPlugins = array();
    public static $loadedTextdomains = array();
    public static $redirects = array();
    public static $isAdmin = false;
    public static $isSsl = false;
    public static $currentUserId = 0;
    public static $currentUserCan = true;
    public static $validNonce = true;
    public static $pluginActive = true;
    /** The WooCommerce endpoint the current request is on (is_wc_endpoint_url()), null for none. */
    public static $wcEndpoint = null;
    public static $queryVars = array();
    public static $blogInfo = array();
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
        self::$deactivatedPlugins = array();
        self::$loadedTextdomains = array();
        self::$redirects = array();
        self::$isAdmin = false;
        self::$isSsl = false;
        self::$currentUserId = 0;
        self::$currentUserCan = true;
        self::$validNonce = true;
        self::$pluginActive = true;
        self::$wcEndpoint = null;
        self::$queryVars = array();
        self::$blogInfo = array('version' => '6.8', 'name' => 'Test Shop');
        self::$woocommerce = new \WooCommerce();

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
