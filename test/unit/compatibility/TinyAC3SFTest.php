<?php

require_once dirname(__FILE__) . '/../TinyTestCase.php';
require_once dirname(__FILE__) . '/../../../src/compatibility/as3cf/class-tiny-as3cf.php';

class Tiny_AC3SF_Test extends Tiny_TestCase
{
    public function set_up()
    {
        parent::set_up();
    }

    /**
     * Stub is_plugin_active to return true for the pro plugin
     * is_plugin_active will only work in admin area (after admin_init);
     */
    public function test_is_active_when_pro_is_enabled()
    {
        $this->wp->stub(
            'is_plugin_active',
            function ($plugin_name) {
                return $plugin_name === 'amazon-s3-and-cloudfront-pro/amazon-s3-and-cloudfront-pro.php';
            }
        );

        $tiny_settings = new Tiny_Settings();
        $tiny_ac3sf = new Tiny_AS3CF($tiny_settings);

        $this->assertTrue(Tiny_AS3CF::is_active());
    }

    public function test_is_active_when_lite_is_enabled()
    {
        $this->wp->stub(
            'is_plugin_active',
            function ($plugin_name) {
                return $plugin_name === 'amazon-s3-and-cloudfront/wordpress-s3.php';
            }
        );


        $this->assertTrue(Tiny_AS3CF::is_active());
    }

    public function test_is_not_active()
    {
        $this->wp->stub(
            'is_plugin_active',
            function ($plugin_name) {
                return false;
            }
        );


        $this->assertFalse(Tiny_AS3CF::is_active());
    }

    public function test_remove_local_enabled_is_false_when_plugin_inactive()
    {
        $this->wp->stub(
            'is_plugin_active',
            function ($plugin_name) {
                return false;
            }
        );

        $this->assertFalse(Tiny_AS3CF::remove_local_files_setting_enabled());
    }

    public function test_remove_local_false_when_option_not_exists()
    {
        $this->wp->stub(
            'is_plugin_active',
            function ($plugin_name) {
                return true;
            }
        );

        $this->assertFalse(Tiny_AS3CF::remove_local_files_setting_enabled());
    }

    public function test_remove_local_false_when_option_is_false()
    {
        $this->wp->stub(
            'is_plugin_active',
            function ($plugin_name) {
                return true;
            }
        );

        $this->wp->addOption('tantan_wordpress_s3', array(
            'remove-local-file' => false,
        ));

        $this->assertFalse(Tiny_AS3CF::remove_local_files_setting_enabled());
    }

    public function test_remove_local_true_when_option_is_true()
    {
        $this->wp->stub(
            'is_plugin_active',
            function ($plugin_name) {
                return true;
            }
        );

        $this->wp->addOption('tantan_wordpress_s3', array(
            'remove-local-file' => true,
        ));

        $this->assertTrue(Tiny_AS3CF::remove_local_files_setting_enabled());
    }
}
