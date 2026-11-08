<?php

namespace BunqTest;

use PatrickKivits\BunqForWooCommerce\Bunq\ApiException;
use PatrickKivits\BunqForWooCommerce\Gateway\PaymentMethods;
use PatrickKivits\BunqForWooCommerce\Orders\PaymentDetails;
use PatrickKivits\BunqForWooCommerce\Plugin;
use WC_Bunq_Gateway;

/**
 * The bunq payment details on the order screen: what is stored when a payment comes in, the box that shows it, the
 * lookup for orders paid before, and the personal data export and erasure.
 */
final class PaymentDetailsTest extends TestCase
{
    /** @var FakeBunqApi */
    private $bunq;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bunq = new FakeBunqApi();
    }

    public function testTheHooksAreRegistered()
    {
        $details = Plugin::instance()->payment_details();

        $this->assertSame(10, has_action('add_meta_boxes', array($details, 'add_meta_box')));
        $this->assertSame(10, has_action('woocommerce_privacy_export_order_personal_data_props', array($details, 'add_export_props')));
        $this->assertSame(10, has_action('woocommerce_privacy_export_order_personal_data_prop', array($details, 'export_prop')));
        $this->assertSame(10, has_action('woocommerce_privacy_remove_order_personal_data_meta', array($details, 'add_erased_meta')));
    }

    // --- Storing the details --------------------------------------------------------------------------------

    public function testStartingAPaymentStoresTheLinkToThePaymentRequest()
    {
        $gateway = $this->authorizedGateway();
        $order = Environment::addOrder();

        $gateway->process_payment($order->get_id());

        $this->assertSame('https://bunq.me/shop/24.95/1', $order->get_meta('bunq_payment_request_url'));
    }

    public function testAPaymentStoresThePayerAndMethod()
    {
        $gateway = $this->authorizedGateway();
        $order = $this->unpaidOrder('24.95');
        $this->bunqReports(array(FakeBunqApi::payment(777, '24.95', 'EUR', 456, 'NL12BUNQ0123456789', 'J. Doe', 'IDEAL')));

        $this->assertSame('paid', $gateway->check_payment_status($order));

        $this->assertSame('J. Doe', $order->get_meta('bunq_payer_name'));
        $this->assertSame('NL12BUNQ0123456789', $order->get_meta('bunq_payer_iban'));
        $this->assertSame('IDEAL', $order->get_meta('bunq_payment_type'));
        $this->assertSame('https://bunq.me/t/55', $order->get_meta('bunq_payment_request_url'));
    }

    public function testAPaymentThatDoesNotMatchStoresThePayerToo()
    {
        $gateway = $this->authorizedGateway();
        $order = $this->unpaidOrder('10.00');
        $this->bunqReports(array(FakeBunqApi::payment(777, '9.99', 'EUR', 456, 'NL12BUNQ0123456789', 'J. Doe', 'BUNQ')));

        $this->assertSame('on-hold', $gateway->check_payment_status($order));

        $this->assertSame('J. Doe', $order->get_meta('bunq_payer_name'));
        $this->assertSame('BUNQ', $order->get_meta('bunq_payment_type'));
    }

    public function testAPaymentWithoutIbanOrTypeIsStoredAsKnown()
    {
        $gateway = $this->authorizedGateway();
        $order = $this->unpaidOrder('24.95');
        $this->bunqReports(array(FakeBunqApi::payment(777, '24.95', 'EUR', 456, null, 'J. Doe', null)));

        $gateway->check_payment_status($order);

        $this->assertSame('', $order->get_meta('bunq_payer_iban'));
        $this->assertSame('UNKNOWN', $order->get_meta('bunq_payment_type'), 'So the order screen does not ask bunq again');
    }

    // --- The box --------------------------------------------------------------------------------------------

    /**
     * @dataProvider orderScreens
     */
    public function testTheBoxIsAddedToTheScreenOfABunqOrder($screen_id, $by_post)
    {
        Environment::$orderScreenId = $screen_id;
        $order = Environment::addOrder(array('payment_method' => 'bunq_ideal'));
        $post = (object) array('ID' => $order->get_id());

        Plugin::instance()->payment_details()->add_meta_box($screen_id, $by_post ? $post : $order);

        $this->assertArrayHasKey('bunq-payment-details', Environment::$metaBoxes);
        $this->assertSame($screen_id, Environment::$metaBoxes['bunq-payment-details']['screen']);
        $this->assertSame('side', Environment::$metaBoxes['bunq-payment-details']['context']);
    }

    public function orderScreens()
    {
        return array(
            'posts' => array('shop_order', true),
            'HPOS' => array('woocommerce_page_wc-orders', false),
        );
    }

    public function testTheBoxIsNotAddedToOtherOrdersOrScreens()
    {
        $other = Environment::addOrder(array('payment_method' => 'cod'));
        $bunq = Environment::addOrder();

        Plugin::instance()->payment_details()->add_meta_box('shop_order', $other);
        Plugin::instance()->payment_details()->add_meta_box('product', $bunq);

        $this->assertSame(array(), Environment::$metaBoxes);
    }

    public function testTheBoxShowsThePayerTheMethodAndTheLink()
    {
        $order = $this->paidOrder(array(
            'bunq_payer_name' => 'J. <b>Doe</b>',
            'bunq_payer_iban' => 'NL12BUNQ0123456789',
            'bunq_payment_type' => 'IDEAL',
            'bunq_payment_request_url' => 'https://bunq.me/t/55',
        ));

        $html = PaymentDetails::html($order);

        $this->assertStringContainsString('J. &lt;b&gt;Doe&lt;/b&gt;', $html, 'Escaped');
        $this->assertStringContainsString('NL12 BUNQ 0123 4567 89', $html);
        $this->assertStringContainsString('iDEAL | Wero', $html);
        $this->assertStringContainsString('777', $html);
        $this->assertStringContainsString('<a href="https://bunq.me/t/55" target="_blank" rel="noopener noreferrer">View the payment request on bunq.me</a>', $html);
    }

    public function testTheBoxLeavesOutWhatBunqDidNotReport()
    {
        $order = $this->paidOrder(array('bunq_payer_name' => 'J. Doe', 'bunq_payment_type' => 'UNKNOWN'));

        $html = PaymentDetails::html($order);

        $this->assertStringContainsString('J. Doe', $html);
        $this->assertStringNotContainsString('IBAN', $html);
        $this->assertStringNotContainsString('Method', $html);
        $this->assertStringNotContainsString('<a ', $html);
    }

    public function testTheBoxOfAnUnpaidOrderSaysSoAndLinksToTheRequest()
    {
        $order = Environment::addOrder(array('meta' => array('bunq_payment_request_id' => 55, 'bunq_payment_request_url' => 'https://bunq.me/t/55')));

        $html = PaymentDetails::html($order);

        $this->assertStringContainsString('No bunq payment has been received for this order yet.', $html);
        $this->assertStringContainsString('https://bunq.me/t/55', $html);
    }

    /**
     * @dataProvider paymentTypes
     */
    public function testPaymentTypesGetReadableNames($type, $expected)
    {
        $this->assertSame($expected, PaymentMethods::payment_type_label($type));
    }

    public function paymentTypes()
    {
        return array(
            'iDEAL' => array('IDEAL', 'iDEAL | Wero'),
            'bunq' => array('BUNQ', 'From a bunq account'),
            'Bancontact' => array('BANCONTACT', 'Bancontact'),
            'card' => array('MASTERCARD', 'Credit or Debit Card'),
            'SEPA' => array('EBA_SCT', 'Bank transfer'),
            'unknown to the plugin' => array('SOFORT', 'SOFORT'),
            'unknown to bunq' => array('UNKNOWN', ''),
            'none' => array('', ''),
        );
    }

    // --- Orders paid before the details were stored ---------------------------------------------------------

    public function testAnOlderOrderGetsItsDetailsFromBunqOnce()
    {
        $this->registerGateway($this->authorizedGateway());
        $order = $this->paidOrder(array('bunq_monetary_account_id' => 456));
        $this->bunqReports(array(FakeBunqApi::payment(776, '5.00'), FakeBunqApi::payment(777, '24.95', 'EUR', 456, 'NL12BUNQ0123456789', 'J. Doe', 'BUNQ')));

        Plugin::instance()->payment_details()->fetch_missing_details($order);
        Plugin::instance()->payment_details()->fetch_missing_details($order);

        $this->assertSame(array(array(55, 456)), $this->bunq->callsTo('get_payment_request'));
        $this->assertSame('J. Doe', $order->get_meta('bunq_payer_name'));
        $this->assertSame('BUNQ', $order->get_meta('bunq_payment_type'));
        $this->assertSame('https://bunq.me/t/55', $order->get_meta('bunq_payment_request_url'));
        $this->assertSame(1, $order->save_count);
    }

    public function testAFailedLookupIsLoggedAndNotRetriedForAWhile()
    {
        $this->registerGateway($this->authorizedGateway());
        $order = $this->paidOrder();
        $this->bunq->exceptions['get_payment_request'] = new ApiException('bunq is down', 500);

        Plugin::instance()->payment_details()->fetch_missing_details($order);
        Plugin::instance()->payment_details()->fetch_missing_details($order);

        $this->assertCount(1, $this->bunq->callsTo('get_payment_request'));
        $this->assertSame('', $order->get_meta('bunq_payment_type'));
        $this->assertSame(15 * MINUTE_IN_SECONDS, Environment::$transientTtls['bunq_for_woocommerce_details_failed_' . $order->get_id()]);
        $this->assertNotEmpty(Environment::$logs);
    }

    public function testNothingIsFetchedForUnpaidOrdersOrWithoutAConnection()
    {
        $this->registerGateway($this->authorizedGateway());
        Plugin::instance()->payment_details()->fetch_missing_details($this->unpaidOrder('10.00'));

        Environment::$gateways = array();
        Plugin::instance()->payment_details()->fetch_missing_details($this->paidOrder());

        $this->assertSame(array(), $this->bunq->callsTo('get_payment_request'));
    }

    // --- Personal data ---------------------------------------------------------------------------------------

    public function testThePayerIsPartOfThePersonalDataExportAndErasure()
    {
        $details = Plugin::instance()->payment_details();
        $order = $this->paidOrder(array('bunq_payer_name' => 'J. Doe', 'bunq_payer_iban' => 'NL12BUNQ0123456789'));

        $props = $details->add_export_props(array('billing_email' => 'Email'), $order);
        $this->assertSame(array('billing_email', 'bunq_payer_name', 'bunq_payer_iban'), array_keys($props));
        $this->assertSame('NL12BUNQ0123456789', $details->export_prop('', 'bunq_payer_iban', $order));
        $this->assertSame('x', $details->export_prop('x', 'billing_email', $order));
        $this->assertSame(array(), $details->add_export_props(array(), Environment::addOrder(array('payment_method' => 'cod'))));

        $this->assertSame(array('Transaction ID' => 'numeric_id', 'bunq_payer_name' => 'text', 'bunq_payer_iban' => 'text'), $details->add_erased_meta(array('Transaction ID' => 'numeric_id')));
    }

    private function authorizedGateway()
    {
        update_option('woocommerce_bunq_settings', $this->liveSettings(array('api_context' => '{"live":1}')));

        return new WC_Bunq_Gateway($this->bunq);
    }

    private function unpaidOrder($total)
    {
        return Environment::addOrder(array('total' => $total, 'meta' => array('bunq_payment_request_id' => 55)));
    }

    private function paidOrder(array $meta = array())
    {
        return Environment::addOrder(array(
            'status' => 'processing',
            'total' => '24.95',
            'meta' => array_merge(array('bunq_payment_request_id' => 55, 'bunq_payment_id' => 777), $meta),
        ));
    }

    private function bunqReports(array $payments)
    {
        $this->bunq->paymentRequests[55] = FakeBunqApi::paymentRequest(55, 'ACTIVE', $payments);
    }
}
