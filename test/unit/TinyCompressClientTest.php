<?php

require_once dirname( __FILE__ ) . '/TinyCompressSharedTestCase.php';

class Tiny_Compress_Client_Test extends Tiny_Compress_Shared_TestCase {
	public static function setUpBeforeClass() {
		Tiny_PHP::$client_library_supported = true;
	}

	public function setUp() {
		parent::setUp();
		$this->client = new MockTinifyClient();
		Tinify\Tinify::setClient( $this->client );
	}

	protected function register($method, $url, $details) {
		$this->client->register( $method, $url, $details );
	}

	public function testShouldReturnClientCompressor() {
		$this->assertInstanceOf( 'Tiny_Compress_Client', $this->compressor );
	}

	public function testCanCreateKeyShouldReturnTrue() {
		$this->assertSame( true, $this->compressor->can_create_key() );
	}

	public function testCreateKeyShouldSetApiKey() {
		$this->register( 'POST', '/keys', array(
			'status' => 202,
			'headers' => array(
				'content-type' => 'application/json',
			),
			'body' => json_encode(array(
				'key' => 'newkey123',
			)),
		));

		$this->compressor->create_key( 'john@example.com', array(
			'name' => 'John Doe',
		));

		$this->assertEquals(
			'newkey123',
			$this->compressor->get_key()
		);
	}
}
