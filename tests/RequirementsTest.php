<?php

namespace BunqTest;

final class RequirementsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        update_option(BUNQ_REQUIREMENTS_OPTION, $this->fingerprint());
    }

    public function testPassesInASupportedEnvironment()
    {
        $this->assertTrue(bunq_requirements_check());
    }

    public function testFailsOnAnOldWordPress()
    {
        Environment::$blogInfo['version'] = '3.7.1';

        $this->assertFalse(bunq_requirements_check());
    }

    public function testFailsWithoutWooCommerce()
    {
        Environment::$options['active_plugins'] = array('akismet/akismet.php');

        $this->assertFalse(bunq_requirements_check());
    }

    public function testActivePluginsCanBeFiltered()
    {
        Environment::$options['active_plugins'] = array();
        add_filter('active_plugins', function ($plugins) {
            return array_merge($plugins, array('woocommerce/woocommerce.php'));
        });

        $this->assertTrue(bunq_requirements_check());
    }

    public function testProvesOpenSslWorksOnceAndRemembersTheOutcome()
    {
        delete_option(BUNQ_REQUIREMENTS_OPTION);

        $this->assertTrue(bunq_requirements_check());
        $this->assertSame($this->fingerprint(), get_option(BUNQ_REQUIREMENTS_OPTION));
    }

    public function testTheRememberedOutcomeIsTiedToThePhpAndOpenSslBuild()
    {
        update_option(BUNQ_REQUIREMENTS_OPTION, md5('another build'));

        $this->assertTrue(bunq_requirements_check());
        $this->assertSame($this->fingerprint(), get_option(BUNQ_REQUIREMENTS_OPTION), 'Checked again and stored for this build');
    }

    public function testDisablingThePluginDeactivatesItAndHidesTheActivationNotice()
    {
        $_GET['activate'] = 'true';

        bunq_requirements_disable_plugin();

        $this->assertSame(array('bunq-for-woocommerce/bunq-for-woocommerce.php'), Environment::$deactivatedPlugins);
        $this->assertArrayNotHasKey('activate', $_GET);
    }

    public function testDisablingThePluginNeedsTheCapability()
    {
        Environment::$currentUserCan = false;

        bunq_requirements_disable_plugin();

        $this->assertSame(array(), Environment::$deactivatedPlugins);
    }

    public function testDisablingAnInactivePluginDoesNothing()
    {
        Environment::$pluginActive = false;

        bunq_requirements_disable_plugin();

        $this->assertSame(array(), Environment::$deactivatedPlugins);
    }

    public function testTheNoticeExplainsTheEnvironmentIsIncompatible()
    {
        ob_start();
        bunq_requirements_show_notice();
        $notice = ob_get_clean();

        $this->assertStringContainsString('class="error"', $notice);
        $this->assertStringContainsString('bunq for WooCommerce', $notice);
        $this->assertStringContainsString('cannot be activated due to incompatible environment.', $notice);
    }

    private function fingerprint()
    {
        return md5(PHP_VERSION . '|' . (defined('OPENSSL_VERSION_TEXT') ? OPENSSL_VERSION_TEXT : ''));
    }
}
