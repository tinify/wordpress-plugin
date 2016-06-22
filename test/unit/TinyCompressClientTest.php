<?php

require_once dirname( __FILE__ ) . '/TinyTestCase.php';

/**
 * @runTestsInSeparateProcesses
 */
class Tiny_Compress_Client_Test extends TinyTestCase {
	protected $php_mock;

	public function setUp() {
		parent::setUp();
		$this->php_mock = \Mockery::mock( 'alias:Tiny_PHP' );
		$this->php_mock->shouldReceive( 'client_library_supported' )->andReturn( true );
		$this->compressor = Tiny_Compress::create( 'api1234' );
	}

	public function testShouldReturnCompressor() {
		$this->assertInstanceOf( 'Tiny_Compress', $this->compressor );
	}

	public function testShouldReturnClientCompressor() {
		$this->assertInstanceOf( 'Tiny_Compress_Client', $this->compressor );
	}

	public function testShouldAllowKeyCreation() {
		$this->assertSame( true, $this->compressor->can_create_key() );
	}
}
