<?php

require_once dirname( __FILE__ ) . '/TinyTestCase.php';

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class Tiny_Compress_No_Client_Test extends TinyTestCase {
	protected $php_mock;

	public function setUp() {
		parent::setUp();
		$php_mock = \Mockery::mock( 'alias:Tiny_PHP' );
		$php_mock->shouldReceive( 'client_library_supported' )->andReturn( false );
		$php_mock->shouldReceive( 'fopen_available' )->andReturn( false );
	}

	public function testShouldThrowErrorWhenCurlAndFopenUnavailable() {
		$this->setExpectedException( 'Tiny_Exception' );
		Tiny_Compress::create( 'api1234' );
	}
}
