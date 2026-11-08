<?php

namespace BunqTest;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use PatrickKivits\BunqForWooCommerce\Bunq\ApiException;
use PatrickKivits\BunqForWooCommerce\Gateway\ConnectionHealth;
use PatrickKivits\BunqForWooCommerce\Gateway\Gateway;
use PatrickKivits\BunqForWooCommerce\Plugin;

/**
 * The hourly check of the connection with bunq, the admin notice when it broke and the WooCommerce > Status section.
 */
final class ConnectionHealthTest extends TestCase
{
    /** @var FakeBunqApi */
    private $bunq;

    /** @var ConnectionHealth */
    private $health;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bunq = new FakeBunqApi();
        $this->health = Plugin::instance()->connection_health();
    }

    // --- Scheduling -----------------------------------------------------------------------------------------

    public function testTheCheckIsQueuedOnceToRunEveryHour()
    {
        do_action('admin_init');
        do_action('admin_init');

        $actions = array_values(array_filter(Environment::$scheduledActions, function ($action) {
            return $action['hook'] === ConnectionHealth::CHECK_ACTION;
        }));
        $this->assertCount(1, $actions);
        $this->assertSame(HOUR_IN_SECONDS, $actions[0]['interval']);
        $this->assertSame('bunq', $actions[0]['group']);
        $this->assertEqualsWithDelta(time() + HOUR_IN_SECONDS, $actions[0]['timestamp'], 5);
    }

    public function testTheCheckIsNotQueuedFromAjaxRequests()
    {
        Environment::$doingAjax = true;

        $this->health->schedule();

        $this->assertSame(array(), Environment::$scheduledActions);
    }

    public function testDeactivatingThePluginRemovesTheCheck()
    {
        do_action('deactivate_bunq-for-woocommerce/bunq-for-woocommerce.php');

        $this->assertSame(array(ConnectionHealth::CHECK_ACTION), Environment::$unscheduledHooks);
    }

    // --- The check ------------------------------------------------------------------------------------------

    public function testAWorkingConnectionIsRecorded()
    {
        $this->connectedGateway();

        do_action(ConnectionHealth::CHECK_ACTION);

        $this->assertSame(array(array(123)), $this->bunq->callsTo('check_connection'));
        $status = ConnectionHealth::status('live');
        $this->assertSame('ok', $status['status']);
        $this->assertSame('', $status['error']);
        $this->assertEqualsWithDelta(time(), $status['checked_at'], 5);
        $this->assertSame('', $this->notices());
    }

    public function testNothingIsCheckedWhileTheGatewayIsDisabledOrNotConnected()
    {
        $this->connectedGateway(array('enabled' => 'no'));
        $this->assertNull($this->health->check());

        $this->connectedGateway(array('api_context' => ''));
        $this->assertNull($this->health->check());

        $this->assertSame(array(), $this->bunq->callsTo('check_connection'));
        $this->assertNull(ConnectionHealth::status('live'));
    }

    public function testARevokedAccessIsRecordedAndShownOnEveryAdminPage()
    {
        $this->connectedGateway();
        $this->bunq->exceptions['check_connection'] = new ApiException('Incorrect API key or IP address.', 400, '', true);
        $this->bunq->exceptions['create_api_context'] = new ApiException('User credentials are incorrect.', 400);

        $status = $this->health->check();

        $this->assertSame('broken', $status['status']);
        $this->assertSame('User credentials are incorrect.', $status['error'], 'The error of recreating the API context');
        $this->assertSame(1, $status['failures']);
        $this->assertSame(array(array('live-api-key', false)), $this->bunq->callsTo('create_api_context'), 'The API context was recreated once');
        $this->assertSame(1, get_transient(Gateway::CONTEXT_FAILED_TRANSIENT));

        $notices = $this->notices();
        $this->assertStringContainsString('notice-error', $notices);
        $this->assertStringContainsString('The connection with bunq stopped working, so payments with bunq fail.', $notices);
        $this->assertStringContainsString('<code>User credentials are incorrect.</code>', $notices);
        $this->assertStringContainsString('href="https://shop.example/wp-admin/admin.php?page=wc-settings&tab=checkout&section=bunq"', $notices);
        $this->assertStringContainsString('href="https://shop.example/wp-admin/admin-post.php?action=bunq_check_connection&_wpnonce=nonce-bunq_check_connection"', $notices);
    }

    public function testTheNextCheckTriesAgainDespiteARememberedFailure()
    {
        $this->connectedGateway();
        set_transient(Gateway::CONTEXT_FAILED_TRANSIENT, 1, 5 * MINUTE_IN_SECONDS);

        $this->assertSame('ok', $this->health->check()['status']);
        $this->assertFalse(get_transient(Gateway::CONTEXT_FAILED_TRANSIENT));
    }

    public function testAClosedBankAccountBreaksTheConnection()
    {
        $this->connectedGateway();
        $this->bunq->exceptions['check_connection'] = new \RuntimeException('The bunq bank account that receives the payments is cancelled.');

        $status = $this->health->check();

        $this->assertSame('broken', $status['status']);
        $this->assertSame(array(), $this->bunq->callsTo('create_api_context'), 'Only a refused session recreates the API context');
        $this->assertStringContainsString('is cancelled.', $this->notices());
    }

    public function testBunqBeingUnreachableIsOnlyShownAfterThreeChecksInARow()
    {
        $this->connectedGateway();
        $unreachable = new ConnectException('cURL error 28: Operation timed out', new Request('GET', 'https://api.bunq.com/v1/user'));
        $this->bunq->exceptions['check_connection'] = array($unreachable, $unreachable, $unreachable);

        $this->health->check();
        $this->health->check();
        $this->assertSame('unreachable', ConnectionHealth::status('live')['status']);
        $this->assertSame(2, ConnectionHealth::status('live')['failures']);
        $this->assertSame('', $this->notices());

        $this->health->check();
        $notices = $this->notices();
        $this->assertStringContainsString('bunq could not be reached for 0 hours.', $notices);
        $this->assertStringContainsString('cURL error 28', $notices);
    }

    public function testBunqErrorsOfItsOwnCountAsUnreachable()
    {
        $this->connectedGateway();
        $this->bunq->exceptions['check_connection'] = new ApiException('Service unavailable.', 503);

        $this->assertSame('unreachable', $this->health->check()['status']);
    }

    public function testTheFailureKeepsItsStartWhileItLasts()
    {
        $this->connectedGateway();
        update_option(ConnectionHealth::OPTION, array('live' => array('status' => 'broken', 'checked_at' => 1000, 'error' => 'x', 'failures' => 4, 'failing_since' => 900)));
        $this->bunq->exceptions['check_connection'] = new \RuntimeException('Still broken.');

        $status = $this->health->check();

        $this->assertSame(5, $status['failures']);
        $this->assertSame(900, $status['failing_since']);
        $this->assertSame('Still broken.', $status['error']);
    }

    public function testARestoredConnectionClearsTheNotice()
    {
        $this->connectedGateway();
        $this->recordBroken();

        $this->health->check();

        $this->assertSame('ok', ConnectionHealth::status('live')['status']);
        $this->assertSame('', $this->notices());
        $this->assertLogged('info', 'The connection with bunq works again.');
    }

    public function testAPaymentThatGoesThroughClearsTheNotice()
    {
        $gateway = $this->connectedGateway();
        $this->recordBroken();

        $gateway->process_payment(Environment::addOrder(array('total' => '24.95'))->get_id());

        $this->assertSame('ok', ConnectionHealth::status('live')['status']);
    }

    public function testAPaymentThatGoesThroughWritesNothingWhileTheConnectionWorks()
    {
        $gateway = $this->connectedGateway();

        $gateway->process_payment(Environment::addOrder(array('total' => '24.95'))->get_id());

        $this->assertNull(ConnectionHealth::status('live'));
    }

    public function testConnectingAgainClearsTheNotice()
    {
        $gateway = $this->connectedGateway();
        $this->recordBroken();

        $gateway->refresh_api_context(false);

        $this->assertSame('ok', ConnectionHealth::status('live')['status']);
    }

    public function testDisconnectingForgetsTheOutcome()
    {
        $gateway = $this->connectedGateway();
        $this->recordBroken();
        update_option(ConnectionHealth::OPTION, array_merge(get_option(ConnectionHealth::OPTION), array('sandbox' => array('status' => 'ok', 'checked_at' => 1, 'error' => '', 'failures' => 0, 'failing_since' => 0))));

        $gateway->disconnect('live');

        $this->assertNull(ConnectionHealth::status('live'));
        $this->assertNotNull(ConnectionHealth::status('sandbox'));
    }

    // --- The notice -----------------------------------------------------------------------------------------

    public function testTheNoticeIsNotShownOnTheBunqSettingsPageWhichShowsTheErrorItself()
    {
        $gateway = $this->connectedGateway();
        $this->recordBroken();
        $_GET = array('page' => 'wc-settings', 'tab' => 'checkout', 'section' => 'bunq');

        $this->assertSame('', $this->notices());

        $connection = $gateway->get_connection('live');
        $this->assertTrue($connection['failed']);
        $this->assertSame('Incorrect API key or IP address.', $connection['error']);
        $this->assertFalse($gateway->get_connection('sandbox')['failed']);
    }

    public function testTheNoticeIsOnlyForShopManagers()
    {
        $this->connectedGateway();
        $this->recordBroken();
        Environment::$currentUserCan = false;

        $this->assertSame('', $this->notices());
    }

    public function testTheNoticeIsAboutTheActiveMode()
    {
        $this->connectedGateway(array('testmode' => 'yes', 'test_api_context' => '{"sandbox":1}'));
        $this->recordBroken();

        $this->assertSame('', $this->notices());

        update_option(ConnectionHealth::OPTION, array('sandbox' => get_option(ConnectionHealth::OPTION)['live']));
        $this->assertStringContainsString('The connection with the bunq sandbox stopped working', $this->notices());
    }

    public function testTheNoticeIsNotShownWhileTheGatewayIsDisabled()
    {
        $this->connectedGateway(array('enabled' => 'no'));
        $this->recordBroken();

        $this->assertSame('', $this->notices());
    }

    // --- Check again ----------------------------------------------------------------------------------------

    public function testCheckAgainChecksAndGoesBackToThePageItWasClickedOn()
    {
        $this->connectedGateway();
        $this->recordBroken();
        Environment::$currentUserId = 3;
        Environment::$referer = 'https://shop.example/wp-admin/edit.php?post_type=shop_order';

        $this->assertSame('https://shop.example/wp-admin/edit.php?post_type=shop_order', $this->health->check_now());

        $this->assertSame(array(array(123)), $this->bunq->callsTo('check_connection'));
        $notices = $this->notices();
        $this->assertStringContainsString('notice-success', $notices);
        $this->assertStringContainsString('The connection with bunq works.', $notices);
        $this->assertSame('', $this->notices(), 'The outcome is shown once');
    }

    public function testCheckAgainWithoutAReferrerGoesToTheSettings()
    {
        $this->connectedGateway();

        $this->assertSame(Gateway::settings_url(), $this->health->check_now());
    }

    public function testCheckAgainNeedsAValidNonceAndAShopManager()
    {
        $this->connectedGateway();

        Environment::$validNonce = false;
        $this->assertRefused();

        Environment::$validNonce = true;
        Environment::$currentUserCan = false;
        $this->assertRefused();

        $this->assertSame(array(), $this->bunq->callsTo('check_connection'));
    }

    // --- WooCommerce > Status -------------------------------------------------------------------------------

    public function testTheStatusSectionShowsAWorkingConnection()
    {
        $this->bunq->accountName = 'Kivits Webshop';
        $this->connectedGateway();
        update_option(ConnectionHealth::OPTION, array('live' => array('status' => 'ok', 'checked_at' => 1762628400, 'error' => '', 'failures' => 0, 'failing_since' => 0)));

        $html = ConnectionHealth::system_status_html(Plugin::instance()->gateway(), 1762632000);

        $this->assertStringContainsString('<th colspan="3" data-export-label="bunq for WooCommerce"><h2>bunq for WooCommerce</h2></th>', $html);
        $this->assertRow('Version', Plugin::VERSION, $html);
        $this->assertRow('Mode', 'Live', $html);
        $this->assertRow('Connection', '<mark class="yes"><span class="dashicons dashicons-yes"></span> Connected as Kivits Webshop</mark>', $html);
        $this->assertRow('Connection check', '<mark class="yes"><span class="dashicons dashicons-yes"></span> Works</mark>', $html);
        $this->assertRow('Last check', '2025-11-08 19:00 UTC', $html);
        $this->assertRow('Next check', '2025-11-08 20:00 UTC', $html);
        $this->assertStringContainsString('<code>https://shop.example/wc-api/wc_bunq_gateway/</code>', $html);
        $this->assertStringNotContainsString('Failing since', $html);
    }

    public function testTheStatusSectionShowsABrokenConnection()
    {
        $this->connectedGateway();
        update_option(ConnectionHealth::OPTION, array('live' => array('status' => 'broken', 'checked_at' => 1762628400, 'error' => 'Incorrect API key or IP address.', 'failures' => 2, 'failing_since' => 1762624800)));

        $html = ConnectionHealth::system_status_html(Plugin::instance()->gateway(), null);

        $this->assertStringContainsString('<mark class="error"><span class="dashicons dashicons-warning"></span> Broken: Incorrect API key or IP address.</mark>', $html);
        $this->assertRow('Failing since', '2025-11-08 18:00 UTC', $html);
        $this->assertRow('Next check', 'Not scheduled', $html);
    }

    public function testTheStatusSectionShowsAMissingConnection()
    {
        $this->connectedGateway(array('api_context' => ''));

        $html = ConnectionHealth::system_status_html(Plugin::instance()->gateway(), null);

        $this->assertRow('Connection', '<mark class="error"><span class="dashicons dashicons-warning"></span> Not connected</mark>', $html);
        $this->assertStringNotContainsString('Connection check', $html);
    }

    public function testTheStatusSectionIsAddedToTheSystemReport()
    {
        $this->connectedGateway();

        ob_start();
        do_action('woocommerce_system_status_report');
        $html = ob_get_clean();

        $this->assertStringContainsString('id="status-bunq"', $html);
        $this->assertRow('Connection check', 'Not checked yet', $html);
    }

    // --- Helpers --------------------------------------------------------------------------------------------

    private function connectedGateway(array $settings = array())
    {
        update_option('woocommerce_bunq_settings', $this->liveSettings(array_merge(array('api_context' => '{"live":1}'), $settings)));
        $gateway = new Gateway($this->bunq);
        $this->registerGateway($gateway);

        return $gateway;
    }

    private function recordBroken()
    {
        ConnectionHealth::record('live', new ApiException('Incorrect API key or IP address.', 400, '', true));
    }

    private function notices()
    {
        ob_start();
        do_action('admin_notices');

        return ob_get_clean();
    }

    private function assertRow($label, $value, $html)
    {
        $this->assertMatchesRegularExpression(
            '#<td data-export-label="' . preg_quote($label, '#') . '">[^<]*</td>\s*<td class="help">&nbsp;</td>\s*<td>' . preg_quote($value, '#') . '#',
            $html
        );
    }

    private function assertRefused()
    {
        try {
            $this->health->check_now();
            $this->fail('Expected the request to be refused');
        } catch (\RuntimeException $exception) {
            $this->addToAssertionCount(1);
        }
    }
}
