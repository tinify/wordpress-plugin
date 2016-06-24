<?php

require_once dirname( __FILE__ ) . '/../helpers/mock-http-stream-wrapper.php';
require_once dirname( __FILE__ ) . '/../helpers/mock-tinify-client.php';
require_once dirname( __FILE__ ) . '/../helpers/wordpress.php';
require_once dirname( __FILE__ ) . '/../../src/config/tiny-config.php';
require_once 'vendor/autoload.php';

use org\bovigo\vfs\vfsStream;

function plugin_autoloader($class) {
	$file = dirname( __FILE__ ) . '/../../src/class-' . str_replace( '_', '-', strtolower( $class ) ) . '.php';
	if ( file_exists( $file ) ) {
		include $file;
	} else {
		spl_autoload( $class );
	}
}

spl_autoload_register( 'plugin_autoloader' );

class Tiny_PHP {
	public static $fopen_available = true;
	public static $client_library_supported = true;

	public static function fopen_available() {
		return self::$fopen_available;
	}

	public static function client_library_supported() {
		return self::$client_library_supported;
	}
}

abstract class Tiny_TestCase extends PHPUnit_Framework_TestCase {
	protected $wp;
	protected $vfs;

	public static function tearDownAfterClass() {
		Tiny_PHP::$client_library_supported = true;
		Tiny_PHP::$fopen_available = true;
	}

	protected function setUp() {
		$this->vfs = vfsStream::setup();
		$this->wp = new WordPressStubs( $this->vfs );
	}

	protected function tearDown() {
	}

	protected function assertBetween($lower_bound, $upper_bound, $actual, $message = '') {
		$this->assertGreaterThanOrEqual( $lower_bound, $actual, $message );
		$this->assertLessThanOrEqual( $upper_bound, $actual, $message );
	}

	protected function assertEqualWithinDelta($expected, $actual, $delta, $message = '') {
		$this->assertGreaterThanOrEqual( $expected - $delta, $actual, $message );
		$this->assertLessThanOrEqual( $expected + $delta, $actual, $message );
	}

	protected function json($file_name) {
		return json_decode( file_get_contents( dirname( __FILE__ ) . '/../fixtures/json/' . $file_name . '.json' ), true );
	}
}
