<?php

require_once dirname( __FILE__ ) . '/TinyTestCase.php';

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class Tiny_Compress_Client_Test extends TinyTestCase {
	protected $php_mock;

	public function setUp() {
		parent::setUp();
		$php_mock = \Mockery::mock( 'alias:Tiny_PHP' );
		$php_mock->shouldReceive( 'client_library_supported' )->andReturn( true );
		$this->compressor = Tiny_Compress::create( 'api1234' );
	}

	public function testShouldReturnClientCompressor() {
		$this->assertInstanceOf( 'Tiny_Compress_Client', $this->compressor );
	}

	public function testCanCreateKeyShouldReturnTrue() {
		$this->assertSame( true, $this->compressor->can_create_key() );
	}

	public function testGetKeyShouldReturnKey() {
		$this->assertSame( 'api1234', $this->compressor->get_key() );
	}

	public function testGetStatusShouldReturnSuccessStatus() {
		$client = new TinifyMockClient();
		Tinify\Tinify::setClient( $client );
		$client->register('POST', '/shrink', function() {
			throw new Tinify\ClientException( 'Input missing' );
		});

		$status = $this->compressor->get_status();
		$this->assertEquals(
			(object) array(
				'ok' => true,
				'message' => null,
				'code' => null,
			),
			$status
		);
	}

	public function testGetStatusShouldReturnUnauthorizedStatus() {
		$client = new TinifyMockClient();
		Tinify\Tinify::setClient( $client );
		$client->register('POST', '/shrink', function() {
			throw new Tinify\AccountException(
				'Credentials are invalid',
				'Unauthorized',
				401
			);
		});

		$status = $this->compressor->get_status();
		$this->assertEquals(
			(object) array(
				'ok' => false,
				'message' => 'The key that you have entered is not valid',
				'code' => 401,
			),
			$status
		);
	}
}
