<?php

require_once dirname(__FILE__) . '/../TinyTestCase.php';
require_once dirname(__FILE__) . '/../../../src/compatibility/as3cf/class-tiny-as3cf.php';

class Amazon_S3_And_CloudFront_Pro {}
class Amazon_S3_And_CloudFront {}

class Tiny_AC3SF_Test extends Tiny_TestCase
{
    public function set_up()
    {
        parent::set_up();
    }


    /**
     * Will check if AS3CF exists (pro version)
     */
    public function test_is_active_when_pro_is_available()
    {
        $this->assertTrue(Tiny_AS3CF::pro_is_active());
        $this->assertTrue(Tiny_AS3CF::is_active());
    }

    /**
     * Will check if AS3CF exists (lite version)
     * @see https://github.com/deliciousbrains/wp-amazon-s3-and-cloudfront/blob/master/wordpress-s3.php
     */
    public function test_is_active_when_lite_is_enabled()
    {
        // Amazon_S3_And_CloudFront stub class is defined at top of file
        $this->assertTrue(Tiny_AS3CF::lite_is_active());
        $this->assertTrue(Tiny_AS3CF::is_active());
    }

    public function test_remove_local_enabled_is_false_when_plugin_inactive()
    {
        $this->assertFalse(Tiny_AS3CF::remove_local_files_setting_enabled());
    }

    public function test_remove_local_false_when_option_not_exists()
    {
        $this->assertFalse(Tiny_AS3CF::remove_local_files_setting_enabled());
    }

    public function test_remove_local_false_when_option_is_false()
    {
        $this->wp->addOption('tantan_wordpress_s3', array(
            'remove-local-file' => false,
        ));

        $this->assertFalse(Tiny_AS3CF::remove_local_files_setting_enabled());
    }

    public function test_remove_local_true_when_option_is_true()
    {
        $this->wp->addOption('tantan_wordpress_s3', array(
            'remove-local-file' => true,
        ));

        $this->assertTrue(Tiny_AS3CF::remove_local_files_setting_enabled());
    }
}
