<?php

require 'vendor/autoload.php';
require(dirname(__FILE__) . '/../helpers/compress.php');
require(dirname(__FILE__) . '/../helpers/wordpress.php');

function plugin_autoloader($class) {
    $file = dirname(__FILE__) . '/../../src/class-' . str_replace('_', '-', strtolower($class)) . '.php';
    if (file_exists($file)) {
        include $file;
    } else {
        spl_autoload($class);
    }
}

spl_autoload_register('plugin_autoloader');

function tiny_is_network_activated() {
    return false;
}

abstract class TinyTestCase extends PHPUnit_Framework_TestCase {
    protected $wp;

    protected function setUp() {
        $this->wp = $GLOBALS['wp'];
    }

    protected function tearDown() {
        $this->wp->clear();
    }
}
