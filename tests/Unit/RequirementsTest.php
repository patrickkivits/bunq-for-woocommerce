<?php

namespace BunqTest;

use PatrickKivits\BunqForWooCommerce\Requirements;

final class RequirementsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        update_option(Requirements::OPTION, $this->fingerprint());
    }

    public function testPassesInASupportedEnvironment()
    {
        $this->assertTrue($this->requirements()->check());
    }

    public function testFailsOnAnOldWordPress()
    {
        Environment::$blogInfo['version'] = '4.6.1';
        $this->assertFalse($this->requirements()->check());

        Environment::$blogInfo['version'] = '4.7';
        $this->assertTrue($this->requirements()->check(), 'wp_generate_uuid4() and wp_parse_url() with a component exist since 4.7');
    }

    public function testFailsWithoutWooCommerce()
    {
        Environment::$options['active_plugins'] = array('akismet/akismet.php');

        $this->assertFalse($this->requirements()->check());
    }

    public function testActivePluginsCanBeFiltered()
    {
        Environment::$options['active_plugins'] = array();
        add_filter('active_plugins', function ($plugins) {
            return array_merge($plugins, array('woocommerce/woocommerce.php'));
        });

        $this->assertTrue($this->requirements()->check());
    }

    public function testProvesOpenSslWorksOnceAndRemembersTheOutcome()
    {
        delete_option(Requirements::OPTION);

        $this->assertTrue($this->requirements()->check());
        $this->assertSame($this->fingerprint(), get_option(Requirements::OPTION));
    }

    public function testTheRememberedOutcomeIsTiedToThePhpAndOpenSslBuild()
    {
        update_option(Requirements::OPTION, md5('another build'));

        $this->assertTrue($this->requirements()->check());
        $this->assertSame($this->fingerprint(), get_option(Requirements::OPTION), 'Checked again and stored for this build');
    }

    public function testDisablingThePluginDeactivatesItAndHidesTheActivationNotice()
    {
        $_GET['activate'] = 'true';

        $this->requirements()->disable_plugin();

        $this->assertSame(array('bunq-for-woocommerce/bunq-for-woocommerce.php'), Environment::$deactivatedPlugins);
        $this->assertArrayNotHasKey('activate', $_GET);
    }

    public function testDisablingThePluginNeedsTheCapability()
    {
        Environment::$currentUserCan = false;

        $this->requirements()->disable_plugin();

        $this->assertSame(array(), Environment::$deactivatedPlugins);
    }

    public function testDisablingAnInactivePluginDoesNothing()
    {
        Environment::$pluginActive = false;

        $this->requirements()->disable_plugin();

        $this->assertSame(array(), Environment::$deactivatedPlugins);
    }

    public function testTheNoticeExplainsTheEnvironmentIsIncompatible()
    {
        ob_start();
        $this->requirements()->show_notice();
        $notice = ob_get_clean();

        $this->assertStringContainsString('class="error"', $notice);
        $this->assertStringContainsString('bunq for WooCommerce', $notice);
        $this->assertStringContainsString('cannot be activated due to incompatible environment.', $notice);
    }

    private function requirements()
    {
        return new Requirements(BUNQ_FOR_WOOCOMMERCE_PLUGIN_FILE);
    }

    private function fingerprint()
    {
        return md5(PHP_VERSION . '|' . (defined('OPENSSL_VERSION_TEXT') ? OPENSSL_VERSION_TEXT : ''));
    }
}
