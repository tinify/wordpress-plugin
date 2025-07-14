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

	public function test_will_register_command_on_cli_init_hook()
	{
		$this->wp->defaults();

		if (!defined('WP_CLI')) {
			define('WP_CLI', true);
		}

		$tiny_cli = new Tiny_Cli(null);

		$add_action_calls = $this->wp->getCalls('add_action');
		$cli_init_found = false;

		foreach ($add_action_calls as $call) {
			if ($call[0] === 'cli_init' && $call[1] === array($tiny_cli, 'register_command')) {
				$cli_init_found = true;
				break;
			}
		}

		$this->assertTrue($cli_init_found, 'register_command should be hooked to cli_init');
	}

	public function test_will_not_hook_if_cli_is_unavailable()
	{
		$this->wp->defaults();

		$add_action_calls = $this->wp->getCalls('add_action');
		$cli_init_found = false;

		foreach ($add_action_calls as $call) {
			if ($call[0] === 'cli_init') {
				$cli_init_found = true;
				break;
			}
		}

		$this->assertFalse($cli_init_found, 'No cli_init hooks should be registered when WP_CLI is not available');
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
			'convert_opts' => array(
				'convert' => false,
				'convert_to' => array(
					'image/avif',
					'image/webp'
				)
			),
		);
		$mockCompressor->expects($this->once())
			->method('compress_file')
			->with(
				$expected['file'],
				$expected['resize'],
				$expected['preserve'],
				$expected['convert_opts']
			);
		$settings->set_compressor($mockCompressor);

		$command = new Tiny_Command($settings);

		$command->optimize(array(), array(
			"attachments" => '4030',
		));
	}

	public function test_will_compress_all_uncompressed_attachments_if_none_given()
	{
		// Define WordPress constants needed for wpdb operations
		if (!defined('ARRAY_A')) {
			define('ARRAY_A', 'ARRAY_A');
		}

		$this->wp->stub('get_post_mime_type', function ($i) {
			return 'image/png';
		});

		// Mock the global wpdb object
		global $wpdb;

		// Create a mock class that has the methods we need
		$wpdb = $this->getMockBuilder(stdClass::class)
			->addMethods(['get_results'])
			->getMock();

		$wpdb->posts = 'wp_posts';
		$wpdb->postmeta = 'wp_postmeta';

		$this->wp->stub('wp_get_attachment_metadata', function ($i) {
			return array(
				'width' => 1256,
				'height' => 1256,
				'file' => '2025/07/test.png',
				'sizes' => array(),
			);
		});

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

		$wpdb->method('get_results')
			->willReturn($mock_results);

		$settings = new Tiny_Settings();
		$mockCompressor = $this->createMock(Tiny_Compress::class);

		$mockCompressor->expects($this->once())
			->method('compress_file')
			->willReturn(array('output' => array('size' => 1000)));

		$settings->set_compressor($mockCompressor);

		$command = new Tiny_Command($settings);

		$command->optimize(array(), array());
	}
}
