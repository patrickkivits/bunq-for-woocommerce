<?php
/**
 * Minimal stand-ins for the WordPress and WooCommerce functions and classes the plugin calls, backed by
 * BunqTest\Environment so tests can seed state up front and inspect it afterwards. Only the behaviour the
 * plugin relies on is mimicked.
 */

use BunqTest\Environment;

const MINUTE_IN_SECONDS = 60;
const HOUR_IN_SECONDS = 3600;
const DAY_IN_SECONDS = 86400;

// --- Translation, escaping and sanitising -------------------------------------------------------------------

function __($text, $domain = 'default') { return $text; }
function esc_html__($text, $domain = 'default') { return $text; }
function esc_html($text) { return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8'); }
function esc_url($url) { return $url; }
function wp_kses_post($text) { return $text; }
function wpautop($text) { return '<p>' . $text . '</p>' . "\n"; }
function sanitize_text_field($value) { return trim(strip_tags((string) $value)); }
function sanitize_key($key) { return preg_replace('/[^a-z0-9_\-]/', '', strtolower((string) $key)); }
function wp_unslash($value) { return is_string($value) ? stripslashes($value) : $value; }
function wc_clean($value) { return is_string($value) ? sanitize_text_field($value) : $value; }
function absint($value) { return abs((int) $value); }

// --- Hooks --------------------------------------------------------------------------------------------------

function add_action($hook, $callback, $priority = 10, $accepted_args = 1)
{
    Environment::$hooks[$hook][] = array('callback' => $callback, 'priority' => $priority, 'accepted_args' => $accepted_args);

    return true;
}

function add_filter($hook, $callback, $priority = 10, $accepted_args = 1)
{
    return add_action($hook, $callback, $priority, $accepted_args);
}

function has_action($hook, $callback = false)
{
    if (empty(Environment::$hooks[$hook])) {
        return false;
    }

    if ($callback === false) {
        return true;
    }

    foreach (Environment::$hooks[$hook] as $registered) {
        if ($registered['callback'] === $callback) {
            return $registered['priority'];
        }
    }

    return false;
}

function bunqtest_hook_callbacks($hook)
{
    $callbacks = isset(Environment::$hooks[$hook]) ? Environment::$hooks[$hook] : array();

    // Stable sort by priority: registration order is kept within a priority.
    foreach ($callbacks as $index => $callback) {
        $callbacks[$index]['index'] = $index;
    }
    usort($callbacks, function ($a, $b) {
        return ($a['priority'] - $b['priority']) ?: ($a['index'] - $b['index']);
    });

    return $callbacks;
}

function do_action($hook, ...$args)
{
    foreach (bunqtest_hook_callbacks($hook) as $registered) {
        call_user_func_array($registered['callback'], array_slice($args, 0, $registered['accepted_args']));
    }
}

function apply_filters($hook, $value, ...$args)
{
    foreach (bunqtest_hook_callbacks($hook) as $registered) {
        $value = call_user_func_array($registered['callback'], array_slice(array_merge(array($value), $args), 0, $registered['accepted_args']));
    }

    return $value;
}

// --- Options and transients ---------------------------------------------------------------------------------

function get_option($name, $default = false)
{
    return array_key_exists($name, Environment::$options) ? Environment::$options[$name] : $default;
}

function update_option($name, $value, $autoload = null)
{
    Environment::$options[$name] = $value;

    return true;
}

function delete_option($name)
{
    unset(Environment::$options[$name]);

    return true;
}

function get_transient($key)
{
    return array_key_exists($key, Environment::$transients) ? Environment::$transients[$key] : false;
}

function set_transient($key, $value, $expiration = 0)
{
    Environment::$transients[$key] = $value;
    Environment::$transientTtls[$key] = $expiration;

    return true;
}

function delete_transient($key)
{
    unset(Environment::$transients[$key], Environment::$transientTtls[$key]);

    return true;
}

// --- Request, user and site ---------------------------------------------------------------------------------

function is_admin() { return Environment::$isAdmin; }
function is_ssl() { return Environment::$isSsl; }
function current_user_can($capability) { return Environment::$currentUserCan; }
function get_current_user_id() { return Environment::$currentUserId; }
function wp_verify_nonce($nonce, $action = -1) { return Environment::$validNonce ? 1 : false; }
function wp_create_nonce($action = -1) { return 'nonce-' . $action; }
function wp_nonce_url($url, $action = -1, $name = '_wpnonce') { return add_query_arg($name, wp_create_nonce($action), $url); }
function get_bloginfo($show = '') { return isset(Environment::$blogInfo[$show]) ? Environment::$blogInfo[$show] : ''; }
function admin_url($path = '') { return 'https://shop.example/wp-admin/' . ltrim($path, '/'); }
function wp_generate_uuid4() { return '00000000-0000-4000-8000-000000000000'; }
function wp_parse_url($url, $component = -1) { return parse_url($url, $component); }
function trailingslashit($value) { return rtrim($value, '/\\') . '/'; }

function wp_safe_redirect($location, $status = 302)
{
    Environment::$redirects[] = $location;

    return true;
}

function wp_register_script($handle, $src, $deps = array(), $ver = false, $in_footer = false)
{
    Environment::$registeredScripts[$handle] = compact('src', 'deps', 'ver', 'in_footer');

    return true;
}

// --- Plugins ------------------------------------------------------------------------------------------------

function plugin_basename($file) { return basename(dirname($file)) . '/' . basename($file); }
function plugin_dir_path($file) { return trailingslashit(dirname($file)); }

function plugin_dir_url($file)
{
    $plugin_root = dirname(BUNQ_FOR_WOOCOMMERCE_PLUGIN_FILE);

    return 'https://shop.example/wp-content/plugins/bunq-for-woocommerce' . substr(dirname($file), strlen($plugin_root)) . '/';
}

function is_plugin_active($plugin) { return Environment::$pluginActive; }

function deactivate_plugins($plugins, $silent = false, $network_wide = null)
{
    Environment::$deactivatedPlugins[] = $plugins;
}

function load_plugin_textdomain($domain, $deprecated = false, $plugin_rel_path = false)
{
    Environment::$loadedTextdomains[$domain] = $plugin_rel_path;

    return true;
}

// --- URLs ---------------------------------------------------------------------------------------------------

function bunqtest_build_url(array $parts, array $query)
{
    $url = '';

    if (isset($parts['scheme'])) {
        $url .= $parts['scheme'] . '://';
    }
    if (isset($parts['host'])) {
        $url .= $parts['host'];
    }
    if (isset($parts['port'])) {
        $url .= ':' . $parts['port'];
    }
    $url .= isset($parts['path']) ? $parts['path'] : '';
    if ($query) {
        $url .= '?' . http_build_query($query);
    }
    if (isset($parts['fragment'])) {
        $url .= '#' . $parts['fragment'];
    }

    return $url;
}

/**
 * add_query_arg('key', 'value', $url) or add_query_arg(array('key' => 'value'), $url), as in WordPress.
 */
function add_query_arg(...$args)
{
    if (is_array($args[0])) {
        $params = $args[0];
        $url = isset($args[1]) ? $args[1] : '';
    } else {
        $params = array($args[0] => $args[1]);
        $url = isset($args[2]) ? $args[2] : '';
    }

    $parts = parse_url($url);
    parse_str(isset($parts['query']) ? $parts['query'] : '', $query);

    foreach ($params as $key => $value) {
        if ($value === false) {
            unset($query[$key]);
        } else {
            $query[$key] = $value;
        }
    }

    return bunqtest_build_url($parts, $query);
}

function remove_query_arg($key, $url = false)
{
    $parts = parse_url($url);
    parse_str(isset($parts['query']) ? $parts['query'] : '', $query);

    foreach ((array) $key as $name) {
        unset($query[$name]);
    }

    return bunqtest_build_url($parts, $query);
}

// --- WooCommerce --------------------------------------------------------------------------------------------

function WC() { return Environment::$woocommerce; }

function wc_get_order($order = false)
{
    $id = is_object($order) ? $order->get_id() : (int) $order;

    return isset(Environment::$orders[$id]) ? Environment::$orders[$id] : false;
}

function wc_get_orders($args)
{
    $orders = array();

    foreach (Environment::$orders as $order) {
        if (isset($args['meta_key']) && (string) $order->get_meta($args['meta_key']) !== (string) $args['meta_value']) {
            continue;
        }
        $orders[] = $order;
    }

    return isset($args['limit']) && $args['limit'] > 0 ? array_slice($orders, 0, $args['limit']) : $orders;
}

function wc_add_notice($message, $notice_type = 'success')
{
    Environment::$notices[] = array('type' => $notice_type, 'message' => $message);
}

function wc_price($price, $args = array())
{
    return (isset($args['currency']) ? $args['currency'] . ' ' : '') . number_format((float) $price, 2, '.', '');
}

function wc_get_logger() { return new WC_Logger(); }

function is_wc_endpoint_url($endpoint = false)
{
    return $endpoint === false ? Environment::$wcEndpoint !== null : Environment::$wcEndpoint === $endpoint;
}

function get_query_var($var, $default = '')
{
    return isset(Environment::$queryVars[$var]) ? Environment::$queryVars[$var] : $default;
}

function woocommerce_form_field($key, $args, $value = null)
{
    echo '<select name="' . $key . '">';
    foreach ($args['options'] as $option_value => $label) {
        echo '<option value="' . $option_value . '">' . $label . '</option>';
    }
    echo '</select>';
}

// --- Action Scheduler ---------------------------------------------------------------------------------------

function as_schedule_single_action($timestamp, $hook, $args = array(), $group = '')
{
    Environment::$scheduledActions[] = compact('timestamp', 'hook', 'args', 'group');

    return count(Environment::$scheduledActions);
}

function as_enqueue_async_action($hook, $args = array(), $group = '')
{
    Environment::$asyncActions[] = compact('hook', 'args', 'group');

    return count(Environment::$asyncActions);
}

function as_unschedule_all_actions($hook, $args = array(), $group = '')
{
    Environment::$unscheduledHooks[] = $hook;
}

// --- Classes ------------------------------------------------------------------------------------------------

class WP_Error
{
    private $code;
    private $message;

    public function __construct($code = '', $message = '', $data = '')
    {
        $this->code = $code;
        $this->message = $message;
    }

    public function get_error_code() { return $this->code; }
    public function get_error_message() { return $this->message; }
}

class WC_Logger
{
    public function log($level, $message, $context = array())
    {
        Environment::$logs[] = array(
            'level' => $level,
            'message' => $message,
            'source' => isset($context['source']) ? $context['source'] : null,
        );
    }
}

class WC_Payment_Gateways
{
    public function payment_gateways() { return Environment::$gateways; }
}

class WooCommerce
{
    /** @var object|null Cart with a "total" property, null when there is none (admin, empty cart). */
    public $cart = null;
    private $payment_gateways;

    public function payment_gateways()
    {
        return $this->payment_gateways ?: ($this->payment_gateways = new WC_Payment_Gateways());
    }

    public function api_request_url($request)
    {
        return 'https://shop.example/wc-api/' . $request . '/';
    }
}

class WC_Order
{
    private static $next_id = 100;

    private $id;
    private $status;
    private $payment_method;
    private $total;
    private $currency;
    private $order_key;
    private $billing_name;
    private $meta;
    private $transaction_id = '';

    /** @var string[] Order notes in the order they were added. */
    public $notes = array();
    /** @var int How often save() was called. */
    public $save_count = 0;
    /** @var array[] Every status change as array(from, to). */
    public $status_changes = array();

    /**
     * @param array $data Keys: id, status, payment_method, total, currency, order_key, billing_name, meta.
     */
    public function __construct(array $data = array())
    {
        $this->id = isset($data['id']) ? (int) $data['id'] : self::$next_id++;
        $this->status = isset($data['status']) ? $data['status'] : 'pending';
        $this->payment_method = isset($data['payment_method']) ? $data['payment_method'] : 'bunq';
        $this->total = isset($data['total']) ? $data['total'] : '10.00';
        $this->currency = isset($data['currency']) ? $data['currency'] : 'EUR';
        $this->order_key = isset($data['order_key']) ? $data['order_key'] : 'wc_order_key' . $this->id;
        $this->billing_name = isset($data['billing_name']) ? $data['billing_name'] : 'Jane Doe';
        $this->meta = isset($data['meta']) ? $data['meta'] : array();
    }

    public function get_id() { return $this->id; }
    public function get_order_number() { return (string) $this->id; }
    public function get_status() { return $this->status; }
    public function get_payment_method() { return $this->payment_method; }
    public function get_total() { return $this->total; }
    public function get_currency() { return $this->currency; }
    public function get_order_key() { return $this->order_key; }
    public function get_transaction_id() { return $this->transaction_id; }
    public function get_formatted_billing_full_name() { return $this->billing_name; }

    public function get_meta($key = '', $single = true, $context = 'view')
    {
        return isset($this->meta[$key]) ? $this->meta[$key] : '';
    }

    public function update_meta_data($key, $value, $meta_id = 0)
    {
        $this->meta[$key] = $value;
    }

    public function save()
    {
        $this->save_count++;

        return $this->id;
    }

    public function add_order_note($note, $is_customer_note = 0, $added_by_user = false)
    {
        $this->notes[] = $note;

        return count($this->notes);
    }

    public function needs_payment()
    {
        return in_array($this->status, array('pending', 'failed'), true) && (float) $this->total > 0;
    }

    public function payment_complete($transaction_id = '')
    {
        $this->transaction_id = (string) $transaction_id;
        $this->set_status('processing');

        return true;
    }

    public function update_status($new_status, $note = '', $manual = false)
    {
        if ($note !== '') {
            $this->notes[] = $note;
        }
        $this->set_status($new_status);

        return true;
    }

    private function set_status($status)
    {
        $this->status_changes[] = array($this->status, $status);
        $this->status = $status;

        // WooCommerce fires this after the transition is saved; the plugin hooks the cancelled one.
        do_action('woocommerce_order_status_' . $status, $this->id, $this);
    }
}

/**
 * The parts of WooCommerce's settings API and payment gateway base class the plugin builds on.
 */
class WC_Payment_Gateway
{
    public $id;
    public $icon;
    public $has_fields = false;
    public $method_title = '';
    public $method_description = '';
    public $title;
    public $description;
    public $enabled = 'yes';
    public $supports = array('products');
    public $form_fields = array();
    public $settings = array();
    public $plugin_id = 'woocommerce_';

    public function get_option_key() { return $this->plugin_id . $this->id . '_settings'; }
    public function get_form_fields() { return $this->form_fields; }
    public function get_method_title() { return $this->method_title; }
    public function supports($feature) { return in_array($feature, $this->supports, true); }
    public function admin_options() {}

    public function init_settings()
    {
        $this->settings = get_option($this->get_option_key(), null);

        // Nothing saved yet: fall back to each field's default, as WooCommerce does.
        if (!is_array($this->settings)) {
            $this->settings = array();
            foreach ($this->get_form_fields() as $key => $field) {
                $this->settings[$key] = isset($field['default']) ? $field['default'] : '';
            }
        }
    }

    public function get_option($key, $empty_value = null)
    {
        if (empty($this->settings)) {
            $this->init_settings();
        }

        if (!isset($this->settings[$key])) {
            $form_fields = $this->get_form_fields();
            $this->settings[$key] = isset($form_fields[$key]['default']) ? $form_fields[$key]['default'] : '';
        }

        if ($empty_value !== null && $this->settings[$key] === '') {
            $this->settings[$key] = $empty_value;
        }

        return $this->settings[$key];
    }

    public function update_option($key, $value = '')
    {
        if (empty($this->settings)) {
            $this->init_settings();
        }

        $this->settings[$key] = $value;

        return update_option($this->get_option_key(), $this->settings, 'yes');
    }

    /**
     * Save the posted settings form (fields are posted as woocommerce_<gateway>_<field>).
     */
    public function process_admin_options()
    {
        $this->init_settings();

        foreach ($this->get_form_fields() as $key => $field) {
            $post_key = $this->plugin_id . $this->id . '_' . $key;

            if ($field['type'] === 'checkbox') {
                $this->settings[$key] = isset($_POST[$post_key]) ? 'yes' : 'no';
            } else {
                $this->settings[$key] = isset($_POST[$post_key]) ? $_POST[$post_key] : '';
            }
        }

        return update_option($this->get_option_key(), $this->settings, 'yes');
    }

    public function is_available()
    {
        return 'yes' === $this->enabled;
    }

    public function get_return_url($order = null)
    {
        return 'https://shop.example/checkout/order-received/' . $order->get_id() . '/?key=' . $order->get_order_key();
    }
}
