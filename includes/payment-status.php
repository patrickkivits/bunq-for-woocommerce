<?php
/**
 * Payment confirmation outside the bunq callback: when the customer returns to the shop and, as a fallback for a
 * callback that never arrives, from Action Scheduler. Also cancels the bunq payment request when the order is
 * cancelled in WooCommerce.
 */

const BUNQ_PAYMENT_CHECK_ACTION = 'wc_bunq_check_payment';
const BUNQ_ACTION_GROUP = 'bunq';

/**
 * @return WC_Bunq_Gateway|null The gateway instance WooCommerce registered.
 */
function bunq_get_gateway()
{
    if (!function_exists('WC') || !WC()->payment_gateways()) {
        return null;
    }

    $gateways = WC()->payment_gateways()->payment_gateways();

    return isset($gateways['bunq']) && $gateways['bunq'] instanceof WC_Bunq_Gateway ? $gateways['bunq'] : null;
}

/**
 * @param mixed $order
 * @return bool Whether this is an unpaid bunq order with a payment request to check.
 */
function bunq_order_awaits_payment($order)
{
    return $order instanceof WC_Order
        && $order->get_payment_method() === 'bunq'
        && $order->needs_payment()
        && intval($order->get_meta('bunq_payment_request_id')) > 0;
}

/**
 * Delays between the scheduled checks of one order. After the last one the order is left to WooCommerce's own
 * unpaid-order handling.
 *
 * @return int[]
 */
function bunq_payment_check_delays()
{
    return array(
        10 * MINUTE_IN_SECONDS,
        30 * MINUTE_IN_SECONDS,
        2 * HOUR_IN_SECONDS,
        6 * HOUR_IN_SECONDS,
        DAY_IN_SECONDS,
        2 * DAY_IN_SECONDS,
    );
}

/**
 * Queue a background check of the order's payment request (Action Scheduler ships with WooCommerce).
 *
 * @param int $order_id
 * @param int $attempt
 */
function bunq_schedule_payment_check($order_id, $attempt = 0)
{
    if (!function_exists('as_schedule_single_action')) {
        return;
    }

    $delays = bunq_payment_check_delays();

    if (!isset($delays[$attempt])) {
        return;
    }

    as_schedule_single_action(time() + $delays[$attempt], BUNQ_PAYMENT_CHECK_ACTION, array(intval($order_id), intval($attempt)), BUNQ_ACTION_GROUP);
}

add_action(BUNQ_PAYMENT_CHECK_ACTION, 'bunq_scheduled_payment_check', 10, 2);
function bunq_scheduled_payment_check($order_id, $attempt = 0)
{
    $order = wc_get_order($order_id);

    if (!bunq_order_awaits_payment($order)) {
        return;
    }

    $gateway = bunq_get_gateway();

    if (!$gateway) {
        return;
    }

    $outcome = 'pending';

    try {
        $outcome = $gateway->check_payment_status($order);
    } catch (Throwable $exception) {
        bunq_helper_log($exception);
    }

    bunq_helper_log('Scheduled check '.($attempt + 1).' for order #'.$order->get_order_number().': '.$outcome, 'debug');

    if ($outcome === 'pending') {
        bunq_schedule_payment_check($order_id, $attempt + 1);
    }
}

/**
 * When the customer lands on the order-received page, ask bunq for the payment right away instead of waiting for
 * the callback. Runs before WooCommerce clears the cart on the same hook (wc_clear_cart_after_payment, priority 20).
 */
add_action('template_redirect', 'bunq_check_payment_on_return', 5);
function bunq_check_payment_on_return()
{
    if (!function_exists('is_wc_endpoint_url') || !is_wc_endpoint_url('order-received')) {
        return;
    }

    global $wp;
    $order_id = absint($wp->query_vars['order-received'] ?? 0);
    $order = $order_id ? wc_get_order($order_id) : null;

    if (!bunq_order_awaits_payment($order)) {
        return;
    }

    $order_key = isset($_GET['key']) ? wc_clean(wp_unslash($_GET['key'])) : '';

    if (!is_string($order_key) || $order_key === '' || !hash_equals((string) $order->get_order_key(), $order_key)) {
        return;
    }

    $gateway = bunq_get_gateway();

    if (!$gateway) {
        return;
    }

    try {
        $outcome = $gateway->check_payment_status($order);
        bunq_helper_log('Check on return for order #'.$order->get_order_number().': '.$outcome, 'debug');
    } catch (Throwable $exception) {
        bunq_helper_log($exception);
    }
}

/**
 * A cancelled order must not be payable any more, so cancel its payment request at bunq as well.
 */
add_action('woocommerce_order_status_cancelled', 'bunq_cancel_payment_request_for_order', 10, 2);
function bunq_cancel_payment_request_for_order($order_id, $order = null)
{
    $order = $order instanceof WC_Order ? $order : wc_get_order($order_id);

    if (!$order || $order->get_payment_method() !== 'bunq') {
        return;
    }

    $payment_request_id = intval($order->get_meta('bunq_payment_request_id'));

    // Nothing to cancel, or bunq already reported the request as cancelled or expired.
    if (!$payment_request_id || $order->get_meta('bunq_payment_request_status')) {
        return;
    }

    $gateway = bunq_get_gateway();

    if (!$gateway) {
        return;
    }

    try {
        $gateway->cancel_payment_request($payment_request_id);
        $order->update_meta_data('bunq_payment_request_status', 'CANCELLED');
        $order->save();
        $order->add_order_note(sprintf(
            /* translators: %s: bunq payment request id */
            __('bunq payment request %s cancelled', 'bunq-for-woocommerce'),
            $payment_request_id
        ));
        bunq_helper_log('bunqme-tab '.$payment_request_id.' cancelled for order #'.$order->get_order_number(), 'info');
    } catch (Throwable $exception) {
        bunq_helper_log($exception, 'warning');
    }
}
