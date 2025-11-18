<?php

require_once dirname( __FILE__ ) . '/TinyTestCase.php';

class Tiny_Image_Test extends Tiny_TestCase {
	protected $subject;
	protected $settings;
	
	public function set_up() {
		parent::set_up();

		$this->wp->createImagesFromJSON( $this->json( 'image_filesystem_data' ) );
		$this->wp->setTinyMetadata( 1, $this->json( 'image_database_metadata' ) );

		$this->settings = new Tiny_Settings();
		$this->subject = new Tiny_Image( $this->settings, 1, $this->json( '_wp_attachment_metadata' ) );
	}

	public function test_tiny_post_meta_key_may_never_change() {
		$this->assertEquals( '61b16225f107e6f0a836bf19d47aa0fd912f8925', sha1( Tiny_Config::META_KEY ) );
	}

	public function test_update_wp_metadata_should_not_update_with_no_resized_original() {
		$tiny_image = new Tiny_Image( new Tiny_Settings(), 150, $this->json( '_wp_attachment_metadata' ) );
		$tiny_image_metadata = $tiny_image->get_wp_metadata();
		$this->assertEquals( 1256, $tiny_image_metadata['width'] );
		$this->assertEquals( 1256, $tiny_image_metadata['height'] );
	}

	public function test_update_wp_metadata_should_update_with_resized_original() {
		$tiny_image = new Tiny_Image( new Tiny_Settings(), 150, $this->json( '_wp_attachment_metadata' ) );
		$response = array(
			'output' => array(
				'width' => 200,
				'height' => 100,
				'size' => 100,
			),
		);
		$tiny_image->get_image_size()->add_tiny_meta_start();
		$tiny_image->get_image_size()->add_tiny_meta( $response );
		$tiny_image->add_wp_metadata( Tiny_Image::ORIGINAL, $tiny_image->get_image_size() );
		$tiny_image_metadata = $tiny_image->get_wp_metadata();
		$this->assertEquals( 200, $tiny_image_metadata['width'] );
		$this->assertEquals( 100, $tiny_image_metadata['height'] );
		$this->assertEquals( 100, $tiny_image_metadata['filesize'] );
	}
	
	public function test_parse_wp_metadata_should_ignore_invalid_sizes() {
		$invalid_metadata = array(
			'width' => 1256,
			'height' => 1256,
			'file' => '2015/09/tinypng_gravatar.png',
			'sizes' => array(
				'valid' => array(
					'file' => 'tinypng_gravatar-200x200.png',
					'width' => 200,
					'height' => 200,
					'mime-type' => 'image/png',
				),
				'missing-file' => array(
					'width' => 50,
					'height' => 50,
					'mime-type' => 'image/png',
				),
				'scalar-size' => 'tinypng_gravatar-300x300.png',
				'null-size' => null,
				'valid-second' => array(
					'file' => 'tinypng_gravatar-400x400.png',
					'mime-type' => 'image/png',
				),
			),
			'image_meta' => array(),
		);

		$tiny_image = new Tiny_Image( $this->settings, 999, $invalid_metadata );

		$this->assertEquals(
			array(
				'valid' => array(
					'file' => 'tinypng_gravatar-200x200.png',
					'width' => 200,
					'height' => 200,
					'mime-type' => 'image/png',
				),
				'valid-second' => array(
					'file' => 'tinypng_gravatar-400x400.png',
					'mime-type' => 'image/png',
				),
			),
			$tiny_image->get_wp_metadata()['sizes']
		);
	}

	public function test_get_images_should_return_all_images() {
		$this->assertEquals( array(
			Tiny_Image::ORIGINAL,
			'medium',
			'thumbnail',
			'twentyfourteen-full-width',
			'failed',
			'large',
			'small',
		), array_keys( $this->subject->get_image_sizes() ) );
	}

	public function test_filter_images_should_filter_correctly() {
		$this->assertEquals( array(
			Tiny_Image::ORIGINAL,
			'medium',
			'thumbnail',
		), array_keys( $this->subject->filter_image_sizes( 'compressed' ) ) );
	}

	public function test_filter_images_should_filter_correctly_when_sizes_are_given() {
		$this->assertEquals( array(
			Tiny_Image::ORIGINAL
			), array_keys( $this->subject->filter_image_sizes( 'compressed', array( Tiny_Image::ORIGINAL, 'invalid' ) ) )
		);
	}

	public function test_get_count_should_add_count_correctly() {
		$this->assertEquals(array(
			'compressed' => 3,
			'resized' => 1,
			), $this->subject->get_count( array( 'compressed', 'resized' ) )
		);
	}

	public function test_get_count_should_add_count_correctly_when_sizes_are_given() {
		$this->assertEquals(array(
			'compressed' => 1,
			'resized' => 1,
			), $this->subject->get_count( array( 'compressed', 'resized' ), array( Tiny_Image::ORIGINAL, 'invalid' ) )
		);
	}

	public function test_get_latest_error_should_return_message() {
		$this->subject->get_image_size()->add_tiny_meta_start( 'large' );
		$this->subject->get_image_size()->add_tiny_meta_error( new Tiny_Exception( 'Could not download output', 'OutputError' ), 'large' );
		$this->assertEquals( 'Could not download output', $this->subject->get_latest_error() );
	}

	public function test_get_latest_error_should_return_trimmed_message_if_message_is_huge() {
		$this->subject->get_image_size()->add_tiny_meta_start( 'large' );
		$this->subject->get_image_size()->add_tiny_meta_error(
			new Tiny_Exception(
				'Request body has unknown keys DOCTYPE HTML PUBLIC 3.2 Final <html> <head> <title>Index</title> </head> <body> <h1>Index of page</h1> <ul><li> 4m planets super nova by netbaby.jpg</li> <li> 75006 lego planets jedi starfighter planet kamino.jpg</li> </body> </html>',
				'OutputError'
			),
			'large'
		);
		$this->assertEquals(
			'Request body has unknown keys DOCTYPE HTML PUBLIC 3.2 Final <html> <head> <title>Index</title> </head> <body> <h1>Index of page</h1> <ul>...',
			$this->subject->get_latest_error()
		);
	}

	public function test_get_statistics() {
		$active_sizes = $this->settings->get_sizes();
		$active_tinify_sizes = $this->settings->get_active_tinify_sizes();
		$this->assertEquals( array(
			'initial_total_size' => 360542,
			'compressed_total_size' => 328670,
			'image_sizes_compressed' => 3,
			'available_uncompressed_sizes' => 1,
			'image_sizes_converted' => 0,
			'available_unconverted_sizes' => 4
		), $this->subject->get_statistics( $active_sizes, $active_tinify_sizes ) );
	}

	public function test_get_image_sizes_available_for_compression_when_file_modified() {
		$active_sizes = $this->settings->get_sizes();
		$active_tinify_sizes = $this->settings->get_active_tinify_sizes();
		$this->wp->createImage( 37857, '2015/09', 'tinypng_gravatar-150x150.png' );
		$statistics = $this->subject->get_statistics( $active_sizes, $active_tinify_sizes );
		$this->assertEquals( 2, $statistics['available_uncompressed_sizes'] );
	}

	public function test_get_savings() {
		$active_sizes = $this->settings->get_sizes();
		$active_tinify_sizes = $this->settings->get_active_tinify_sizes();
		$this->assertEquals( 8.8, $this->subject->get_savings( $this->subject->get_statistics( $active_sizes, $active_tinify_sizes ) ) );
	}

	public function test_is_retina_for_retina_size() {
		$this->assertEquals( true, Tiny_Image::is_retina( 'small_wr2x' ) );
	}

	public function test_is_retina_for_non_retina_size() {
		$this->assertEquals( false, Tiny_Image::is_retina( 'small' ) );
	}

	public function test_is_retina_for_non_retina_size_with_short_name() {
		$this->assertEquals( false, Tiny_Image::is_retina( 'file' ) );
	}

	public function test_update_tiny_post_data_should_call_do_action() {
		$this->wp->addOption( 'tinypng_api_key', 'test123' );
		$this->wp->addOption( 'tinypng_sizes[0]', 'on' );
		$this->wp->addOption( 'tinypng_sizes[large]', 'on' );
		$this->wp->addOption( 'tinypng_sizes[post-thumbnail]', 'on' );

		$this->wp->addImageSize( 'post-thumbnail', array( 'width' => 825, 'height' => 510 ) );
		$this->wp->createImages();
		$this->wp->stub( 'get_post_mime_type', function( $i ) { return "image/png"; } );

		$testmeta = $this->wp->getTestMetadata();
		$tiny_image = new Tiny_Image( new Tiny_Settings(), 1, $testmeta );
		$tiny_image->get_image_size()->add_tiny_meta_start();
		$tiny_image->update_tiny_post_meta();

		$do_action_calls = array();
		foreach ($this->wp->getCalls( 'do_action' ) as $action) {
			array_push($do_action_calls, $action[0]);
		}

		$this->assertEquals(array('updated_tiny_postmeta'), $do_action_calls);
	}

	/**
	 * When an image is already compressed, we still need to be able to convert it
	 * In case a customer has already compressed a couple of images and then turns
	 * on the conversion feature.
	 */
	public function test_compressed_images_can_be_converted() {
		// Enable conversion and all image sizes
		$this->wp->addOption('tinypng_conversion_enabled', true);
		$this->wp->addOption('tinypng_convert_to', 'smallest');
		
		$settings = new Tiny_Settings();
		$this->subject = new Tiny_Image($settings, 1, $this->json('_wp_attachment_metadata'));

		$active_tinify_sizes = $settings->get_active_tinify_sizes();
		
		$uncompressed_sizes = $this->subject->filter_image_sizes('uncompressed', $active_tinify_sizes);
		$unconverted_sizes = $this->subject->filter_image_sizes('unconverted', $active_tinify_sizes);
		$unprocessed_sizes = $uncompressed_sizes + $unconverted_sizes;

		$this->assertCount(1, $uncompressed_sizes, 'should be 1 size compressed');
		$this->assertCount(4, $unconverted_sizes, 'All 4 sizes should be converted');
		$this->assertCount(4, $unprocessed_sizes, 'All sizes should be processed');
	}

	/**
	 * Test conversion to see if follow-up conversion will be done with the same mimetype
	 */
	public function test_conversion_same_mimetype()
	{
		$this->wp->addOption('tinypng_convert_format', array(
			'convert' => 'on',
			'convert_to' => 'smallest',
		));
		$this->wp->addOption('tinypng_sizes', array(
			Tiny_Image::ORIGINAL => 'on',
			'thumbnail' => 'on',
		));
		$this->wp->createImages(array(
			'thumbnail' => 1000,
		));
		$this->wp->stub('get_post_mime_type', function () {
			return 'image/png';
		});

		$metadata = $this->wp->getTestMetadata();
		$settings = new Tiny_Settings();

		// create a mock compressor to spy on calls
		$mock_compressor = $this->createMock(Tiny_Compress::class);

		// we expect for all sizes a webp
		$converted_type = 'image/webp';
		$responses = array(
			array(
				'input' => array('size' => 1000),
				'output' => array('size' => 800, 'type' => 'image/png'),
				'convert' => array('type' => $converted_type, 'size' => 750, 'path' => 'vfs://root/converted-1.webp'),
			),
			array(
				'input' => array('size' => 1000),
				'output' => array('size' => 780, 'type' => 'image/png'),
				'convert' => array('type' => $converted_type, 'size' => 720, 'path' => 'vfs://root/converted-2.webp'),
			),
		);

		$compress_calls = array();
		$mock_compressor->expects($this->exactly(2))
			->method('compress_file')
			->willReturnCallback(function ($file, $resize, $preserve, $convert_to) use (&$compress_calls, &$responses) {
				$compress_calls[] = array(
					'file' => $file,
					'convert_to' => $convert_to,
				);
				return array_shift($responses);
			});
		$settings->set_compressor($mock_compressor);

		$tinyimg = new Tiny_Image($settings, 999, $metadata);
		$tinyimg->compress();

		// should have been 2 calls to our mock 'compress_file'
		$this->assertCount(2, $compress_calls);

		// first call would have been width all mimetypes
		$this->assertEquals(array('image/avif', 'image/webp'), $compress_calls[0]['convert_to']);

		// second call should be only with image/webp because first call was a image/webp
		$this->assertEquals(array('image/webp'), $compress_calls[1]['convert_to']);
	}
}
