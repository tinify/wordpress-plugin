<?php

require_once dirname(__FILE__) . '/TinyTestCase.php';

abstract class Tiny_Compress_Shared_TestCase extends Tiny_TestCase
{
	protected $compressor;
	protected $after_compress_called;

	public function set_up()
	{
		parent::set_up();
		$this->after_compress_called = false;
		$after_compress_called = &$this->after_compress_called;
		$callback = function ($compressor) use (&$after_compress_called) {
			$after_compress_called = true;
		};
		$this->compressor = Tiny_Compress::create('api1234', $callback);
	}

	protected abstract function register($method, $url, $details);

	public function test_should_return_client_compressor()
	{
		$this->assertInstanceOf('Tiny_Compress', $this->compressor);
	}

	public function test_get_key_should_return_key()
	{
		$this->assertSame('api1234', $this->compressor->get_key());
	}

	public function test_get_status_should_return_success_status()
	{
		$this->register('GET', '/keys/api1234', array(
			'status' => 200,
			'headers' => array(
				'content-type' => 'application/json',
			),
			'body' => '{}',
		));

		$this->assertEquals(
			(object) array(
				'ok' => true,
				'message' => null,
			),
			$this->compressor->get_status()
		);

		$this->assertEquals(
			false,
			$this->compressor->limit_reached()
		);

		$this->assertEquals(
			true,
			$this->after_compress_called
		);
	}

	public function test_get_status_should_return_limit_reached_status()
	{
		$this->register('GET', '/keys/api1234', array(
			'status' => 200,
			'headers' => array(
				'content-type' => 'application/json',
				'compression-count-remaining' => '0',
			),
			'body' => '{}',
		));

		$this->assertEquals(
			(object) array(
				'ok' => true,
				'message' => null,
			),
			$this->compressor->get_status()
		);

		$this->assertEquals(
			true,
			$this->compressor->limit_reached()
		);

		$this->assertEquals(
			true,
			$this->after_compress_called
		);
	}

	public function test_get_status_should_return_unauthorized_status()
	{
		$this->register('GET', '/keys/api1234', array(
			'status' => 404,
			'headers' => array(
				'content-type' => 'application/json',
			),
			'body' => '{}',
		));

		$this->assertEquals(
			(object) array(
				'ok' => false,
				'message' => 'The key that you have entered is not valid',
			),
			$this->compressor->get_status()
		);

		$this->assertEquals(
			false,
			$this->compressor->limit_reached()
		);

		$this->assertEquals(
			true,
			$this->after_compress_called
		);
	}

	public function test_compress_file_should_save_compressed_file()
	{
		$this->register('POST', '/shrink', array(
			'status' => 201,
			'headers' => array(
				'location' => 'https://api.tinify.com/output/compressed.png',
				'content-type' => 'application/json',
			),
			'body' => '{}',
		));

		$handler = array(
			'status' => 200,
			'headers' => array(
				'content-type' => 'image/png',
				'content-length' => 9,
				'image-width' => 10,
				'image-height' => 15,
			),
			'body' => 'optimized',
		);

		$this->register('GET', '/output/compressed.png', $handler);
		$this->register('POST', '/output/compressed.png', $handler);

		$img = file_get_contents('test/fixtures/input-example.png');
		file_put_contents($this->vfs->url() . '/image.png', $img);

		$this->assertEquals(
			array(
				'input' => array(
					'size' => 15391,
					'type' => 'image/png',
				),
				'output' => array(
					'size' => 9,
					'type' => 'image/png',
					'width' => 10,
					'height' => 15,
					'ratio' => round(9 / 15391, 4),
				),
			),
			$this->compressor->compress_file($this->vfs->url() . '/image.png')
		);

		$this->assertEquals(
			false,
			$this->compressor->limit_reached()
		);

		$this->assertEquals(
			true,
			$this->after_compress_called
		);
	}

	public function test_compress_file_should_save_resized_file()
	{
		$this->register('POST', '/shrink', array(
			'status' => 201,
			'headers' => array(
				'location' => 'https://api.tinify.com/output/compressed.png',
				'content-type' => 'application/json',
			),
			'body' => '{}',
		));

		$handler = array(
			'status' => 200,
			'headers' => array(
				'content-type' => 'image/png',
				'content-length' => 5,
				'image-width' => 6,
				'image-height' => 9,
			),
			'body' => 'small',
		);

		$this->register('GET', '/output/compressed.png', $handler);
		$this->register('POST', '/output/compressed.png', $handler);

		$img = file_get_contents('test/fixtures/input-example.png');
		file_put_contents($this->vfs->url() . '/image.png', $img);

		$resize = array(
			'width' => 9,
			'method' => 'fit',
		);

		$this->assertEquals(
			array(
				'input' => array(
					'size' => 15391,
					'type' => 'image/png',
				),
				'output' => array(
					'size' => 5,
					'type' => 'image/png',
					'width' => 6,
					'height' => 9,
					'ratio' => round(5 / 15391, 4),
					'resized' => true,
				),
			),
			$this->compressor->compress_file($this->vfs->url() . '/image.png', $resize)
		);

		$this->assertEquals(
			false,
			$this->compressor->limit_reached()
		);

		$this->assertEquals(
			true,
			$this->after_compress_called
		);
	}

	public function test_compress_file_should_return_unauthorized_status()
	{
		$this->register('POST', '/shrink', array(
			'status' => 401,
			'headers' => array(
				'content-type' => 'application/json',
			),
			'body' => json_encode(array(
				'error' => 'Unauthorized',
				'message' => 'Credentials are invalid',
			)),
		));

		file_put_contents($this->vfs->url() . '/image.png', 'unoptimized');

		$exception = null;
		try {
			$this->compressor->compress_file($this->vfs->url() . '/image.png');
		} catch (Exception $err) {
			$exception = $err;
		}

		$this->assertEquals(
			false,
			$this->compressor->limit_reached()
		);

		$this->assertEquals(
			true,
			$this->after_compress_called
		);

		$this->expectException('Tiny_Exception');
		throw $exception;
	}

	public function test_get_compression_count_should_return_null_before_compresion()
	{
		$this->assertSame(null, $this->compressor->get_compression_count());
	}

	public function test_get_compression_count_should_return_count()
	{
		$this->register('POST', '/shrink', array(
			'status' => 201,
			'headers' => array(
				'location' => 'https://api.tinify.com/output/compressed.png',
				'content-type' => 'application/json',
				'compression-count' => 12,
			),
			'body' => '{}',
		));

		$handler = array(
			'status' => 200,
			'headers' => array(
				'content-type' => 'image/png',
				'content-length' => 9,
				'image-width' => 10,
				'image-height' => 15,
				'compression-count' => 12,
			),
			'body' => 'optimized',
		);

		$this->register('GET', '/output/compressed.png', $handler);
		$this->register('POST', '/output/compressed.png', $handler);

		file_put_contents($this->vfs->url() . '/image.png', 'unoptimized');
		$this->compressor->compress_file($this->vfs->url() . '/image.png');

		$this->assertSame(12, $this->compressor->get_compression_count());
	}

	public function test_should_compress_and_convert_when_convert_is_true()
	{
		$uncompressed_img = file_get_contents('test/fixtures/input-example.jpg');
		file_put_contents($this->vfs->url() . '/image.jpg', $uncompressed_img);
		$this->register('POST', '/shrink', array(
			'status' => 201,
			'headers' => array(
				'location' => 'https://api.tinify.com/output/compressed.jpg',
				'content-type' => 'application/json',
				'compression-count' => 12,
			),
			'body' => '{
				"input": {
					"type": "image/jpeg"
				},
				"output": {
					"type": "image/jpeg"
				}
			}',
		));
		$this->register('POST', '/shrink', array(
			'status' => 201,
			'headers' => array(
				'location' => 'https://api.tinify.com/output/compressed.avif',
				'content-type' => 'application/json',
				'compression-count' => 12,
			),
			'body' => '{
				"input": {
					"type": "image/jpeg"
				},
				"output": {
					"type": "image/avif"
				}
			}',
		));

		$compressed_jpg = file_get_contents('test/mock-tinypng-webservice/output-example.jpg');
		file_put_contents($this->vfs->url() . '/compressed.jpg', $compressed_jpg);
		$this->register('GET', '/output/compressed.jpg', array(
			'status' => 200,
			'headers' => array(
				'content-type' => 'image/jpeg',
				'content-length' => 151021,
				'image-width' => 10,
				'image-height' => 15,
				'compression-count' => 12,
			),
			'body' => $compressed_jpg,
		));

		$compressed_avif = file_get_contents('test/mock-tinypng-webservice/output-example.avif');
		$this->register('GET', '/output/compressed.avif', array(
			'status' => 200,
			'headers' => array(
				'content-type' => 'image/avif',
				'content-length' => 11618,
				'image-width' => 10,
				'image-height' => 15,
				'compression-count' => 12,
			),
			'body' => $compressed_avif,
		));

		$test_output = $this->compressor->compress_file($this->vfs->url() . '/image.jpg', array(), array(), array('convert' => true, 'replace' => false));

		$expected_output = array(
			'input' => array(
				'size' => 641206,
				'type' => 'image/jpeg',
			),
			'output' => array(
				'type' => 'image/jpeg',
				'size' => strlen($compressed_jpg),
				'width' => 10,
				'height' => 15,
				'ratio' => 0.2355,
				'convert' => array(
					'type' => 'image/avif',
					'size' => strlen($compressed_avif),
					'path' => 'vfs://root/image.avif',
				),
			),
		);
		// Should do one request where input is a png and the output is an avif
		$this->assertEquals($expected_output, $test_output);
	}
}
