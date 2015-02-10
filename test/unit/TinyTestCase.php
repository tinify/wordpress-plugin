<?php

require(dirname(__FILE__) . '/../helpers/compress.php');
require(dirname(__FILE__) . '/../helpers/wordpress.php');
require(dirname(__FILE__) . '/../../src/class-tiny-exception.php');
require(dirname(__FILE__) . '/../../src/class-tiny-compress.php');
require(dirname(__FILE__) . '/../../src/class-tiny-compress-curl.php');
require(dirname(__FILE__) . '/../../src/class-tiny-compress-fopen.php');
require(dirname(__FILE__) . '/../../src/class-tiny-metadata.php');
require(dirname(__FILE__) . '/../../src/class-tiny-wp-base.php');
require(dirname(__FILE__) . '/../../src/class-tiny-settings.php');
require(dirname(__FILE__) . '/../../src/class-tiny-plugin.php');

abstract class TinyTestCase extends PHPUnit_Framework_TestCase {

    protected $wp;

    protected function setUp() {
        $this->wp = $GLOBALS['wp'];
    }

    protected function tearDown() {
        $this->wp->clear();
    }
}
