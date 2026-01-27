<?php

require_once dirname(__FILE__) . '/TinyTestCase.php';
require_once dirname(__FILE__) . '/../../src/class-tiny-cli.php';

class Tiny_Cli_Test extends Tiny_TestCase
{
	protected $subject;
	protected $compressor;

	public function set_up()
	{
		parent::set_up();
	}

	public function test_will_compress_attachments_given_in_params()
	{
		$this->wp->stub('get_post_mime_type', function ($i) {
			return 'image/png';
		});
		$this->wp->stub('wp_get_attachment_metadata', function ($i) {
			return array(
				'width' => 1256,
				'height' => 1256,
				'file' => '2025/07/test.png',
				'sizes' => array(
					'thumbnail' => array(
						'file' => 'test-150x150.png',
						'width' => 150,
						'height' => 150,
						'mime-type' => 'image/png'
					)
				),
			);
		});

		$virtual_test_image = array(
			'path' => '2025/07',
			'images' => array(
				array(
					'size' => 137856,
					'file' => 'test.png',
				),
				array(
					'size' => 37856,
					'file' => 'test-150x150.jpg',
				),
			)
		);
		$this->wp->createImagesFromJSON($virtual_test_image);

		$settings = new Tiny_Settings();
		$mockCompressor = $this->createMock(Tiny_Compress::class);

		$expected = array(
			'file' => "vfs://root/wp-content/uploads/2025/07/test.png",
			'resize' => false,
			'preserve' => array(),
			'convert_to' => array(),
		);
		$mockCompressor->expects($this->once())
			->method('compress_file')
			->with(
				$expected['file'],
				$expected['resize'],
				$expected['preserve'],
				$expected['convert_to']
			);
		$settings->set_compressor($mockCompressor);

		$command = new Tiny_Cli($settings);

		$command->optimize(array(), array(
			"attachments" => '4030',
		));
	}

	public function test_will_compress_all_uncompressed_attachments_if_none_given()
	{
		// mock db
		if (!defined('ARRAY_A')) {
			define('ARRAY_A', 'ARRAY_A');
		}
		global $wpdb;
		$wpdb = $this->getMockBuilder(stdClass::class)
			->addMethods(['get_results'])
			->getMock();

		$wpdb->posts = 'wp_posts';
		$wpdb->postmeta = 'wp_postmeta';

		$mock_results = array(
			array(
				'ID' => 1,
				'post_title' => 'Test Image',
				'meta_value' => serialize(array(
					'width' => 1200,
					'height' => 800,
					'file' => '2025/07/test.png',
					'sizes' => array()
				)),
				'unique_attachment_name' => '2025/07/test.png',
				'tiny_meta_value' => ''
			)
		);

		$wpdb->method('get_results')
			->willReturn($mock_results);

		// create mock image
		$virtual_test_image = array(
			'path' => '2025/07',
			'images' => array(
				array(
					'size' => 137856,
					'file' => 'test.png',
				),
			)
		);
		$this->wp->createImagesFromJSON($virtual_test_image);

		// mock wp_get_attachment_metadata
		$this->wp->stub('wp_get_attachment_metadata', function ($i) {
			return array(
				'width' => 1256,
				'height' => 1256,
				'file' => '2025/07/test.png',
				'sizes' => array(),
			);
		});

		// mock get_post_mime_type
		$this->wp->stub('get_post_mime_type', function ($i) {
			return 'image/png';
		});

		// mock compressor 
		$settings = new Tiny_Settings();
		$mockCompressor = $this->createMock(Tiny_Compress::class);
		$settings->set_compressor($mockCompressor);

		// create assertion
		$mockCompressor->expects($this->once())
			->method('compress_file');

		// invoke test
		$command = new Tiny_Cli($settings);
		$command->optimize(array(), array());
	}
}
