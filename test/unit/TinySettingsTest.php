<?php

require_once(dirname(__FILE__) . "/TinyTestCase.php");

class Tiny_Settings_Test extends TinyTestCase {

    public function setUp() {
        parent::setUp();
        $this->subject = new Tiny_Settings();
        $this->subject->admin_init();
    }

    public function testAdminInitShouldRegisterKeys() {
        $this->assertEquals(array(
            array('media', 'tinypng_api_key'),
            array('media', 'tinypng_sizes'),
            array('media', 'tinypng_status')
        ), $this->wp->getCalls('register_setting'));
    }

    public function testAdminInitShouldAddSettingsSection() {
        $this->assertEquals(array(
            array('tinypng_settings', 'PNG and JPEG compression', array($this->subject, 'render_section'), 'media'),
        ), $this->wp->getCalls('add_settings_section'));
    }

    public function testAdminInitShouldAddSettingsField() {
        $this->assertEquals(array(
            array('tinypng_api_key', 'TinyPNG API key', array($this->subject, 'render_api_key'), 'media', 'tinypng_settings', array('label_for' => 'tinypng_api_key')),
            array('tinypng_sizes', 'File compression', array($this->subject, 'render_sizes'), 'media', 'tinypng_settings'),
            array('tinypng_status', 'Connection status', array($this->subject, 'render_pending_status'), 'media', 'tinypng_settings')
        ), $this->wp->getCalls('add_settings_field'));
    }

    public function testShouldRetrieveSizesWithSettings() {
        $this->wp->addOption("tinypng_sizes[0]", "on");
        $this->wp->addOption("tinypng_sizes[medium]", "on");
        $this->wp->addOption("tinypng_sizes[post-thumbnail]", "on");
        $this->wp->addImageSize('post-thumbnail', array('width' => 825, 'height' => 510));

        global $_wp_additional_image_sizes;
        $_wp_additional_image_sizes = array('post-thumbnail' => array('width' => 825, 'height' => 510));

        $this->subject->get_sizes();
        $this->assertEquals(array(
            0 => array('width' => null, 'height' => null, 'tinify' => true),
            'thumbnail' => array('width' => 150, 'height' => 150, 'tinify' => false),
            'medium' => array('width' => 300, 'height' => 300, 'tinify' => true),
            'large' => array('width' => 1024, 'height' => 1024, 'tinify' => false),
            'post-thumbnail' => array('width' => 825, 'height' => 510, 'tinify' => true)
        ), $this->subject->get_sizes());
    }

    public function testShouldSkipDummySize() {
        $this->wp->addOption("tinypng_sizes[tiny_dummy]", "on");

        $this->subject->get_sizes();
        $this->assertEquals(array(
            0 => array('width' => null, 'height' => null, 'tinify' => false),
            'thumbnail' => array('width' => 150, 'height' => 150, 'tinify' => false),
            'medium' => array('width' => 300, 'height' => 300, 'tinify' => false),
            'large' => array('width' => 1024, 'height' => 1024, 'tinify' => false),
        ), $this->subject->get_sizes());
    }

    public function testShouldSetAllSizesOnWithoutConfiguration() {
        $this->subject->get_sizes();
        $this->assertEquals(array(
            0 => array('width' => null, 'height' => null, 'tinify' => true),
            'thumbnail' => array('width' => 150, 'height' => 150, 'tinify' => true),
            'medium' => array('width' => 300, 'height' => 300, 'tinify' => true),
            'large' => array('width' => 1024, 'height' => 1024, 'tinify' => true),
        ), $this->subject->get_sizes());
    }

    public function testShouldShowAdditionalSize() {
        $this->wp->addImageSize('additional_size_1', array('width' => 666, 'height' => 333));
        $this->subject->get_sizes();
        $sizes = $this->subject->get_sizes();
        $this->assertEquals(
            array('width' => 666, 'height' => 333, 'tinify' => true),
            $sizes["additional_size_1"]);
    }

    public function testShouldShowAdditionalSizeWithoutHeight() {
        $this->wp->addImageSize('additional_size_no_height', array('width' => 777));
        $this->subject->get_sizes();
        $sizes = $this->subject->get_sizes();
        $this->assertEquals(
            array('width' => 777, 'height' => 0, 'tinify' => true),
            $sizes["additional_size_no_height"]);
    }

    public function testShouldShowAdditionalSizeWithoutWidth() {
        $this->wp->addImageSize('additional_size_no_width', array('height' => 888));
        $this->subject->get_sizes();
        $sizes = $this->subject->get_sizes();
        $this->assertEquals(
            array('width' => 0, 'height' => 888, 'tinify' => true),
            $sizes["additional_size_no_width"]);
    }
}
