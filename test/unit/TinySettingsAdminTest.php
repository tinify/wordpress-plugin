<?php

require_once dirname( __FILE__ ) . '/TinyTestCase.php';

class Tiny_Settings_Admin_Test extends Tiny_TestCase {
	protected $subject;

	public function set_up() {
		parent::set_up();
		$this->subject = new Tiny_Settings();
		$this->subject->admin_init();
	}

	public function test_admin_init_should_register_keys() {
		$this->assertEquals(array(
			array( 'tinify', 'tinypng_api_key' ),
			array( 'tinify', 'tinypng_api_key_pending' ),
			array( 'tinify', 'tinypng_compression_timing' ),
			array( 'tinify', 'tinypng_sizes' ),
			array( 'tinify', 'tinypng_resize_original' ),
			array( 'tinify', 'tinypng_preserve_data' ),
			array( 'tinify', 'tinypng_convert_format' ),
		), $this->wp->getCalls( 'register_setting' ));
	}

	public function test_should_retrieve_sizes_with_settings() {
		$this->wp->addOption( 'tinypng_sizes[0]', 'on' );
		$this->wp->addOption( 'tinypng_sizes[medium]', 'on' );
		$this->wp->addOption( 'tinypng_sizes[post-thumbnail]', 'on' );
		$this->wp->addImageSize( 'post-thumbnail', array(
			'width' => 825,
			'height' => 510,
		) );

		$this->subject->get_sizes();
		$this->assertEquals(array(
			0 => array(
				'width' => null,
				'height' => null,
				'tinify' => true,
			),
			'thumbnail' => array(
				'width' => 150,
				'height' => 150,
				'tinify' => false,
			),
			'medium' => array(
				'width' => 300,
				'height' => 300,
				'tinify' => true,
			),
			'medium_large' => array(
				'width' => 768,
				'height' => 0,
				'tinify' => false,
			),
			'large' => array(
				'width' => 1024,
				'height' => 1024,
				'tinify' => false,
			),
			'post-thumbnail' => array(
				'width' => 825,
				'height' => 510,
				'tinify' => true,
			),
		), $this->subject->get_sizes());
	}

	public function test_should_not_retrieve_sizes_with_zero_width_and_height_values() {
		$this->wp->addOption( 'tinypng_sizes[0]', 'on' );
		$this->wp->addOption( 'tinypng_sizes[medium]', 'on' );
		$this->wp->addOption( 'tinypng_sizes[post-thumbnail]', 'on' );
		$this->wp->addOption( 'tinypng_sizes[zero-width]', 'off' );
		$this->wp->addOption( 'tinypng_sizes[zero-height]', 'off' );
		$this->wp->addOption( 'tinypng_sizes[zero-width-height]', 'off' );

		$this->wp->addImageSize( 'zero-width', array(
			'width' => 0,
			'height' => 510,
		) );
		$this->wp->addImageSize( 'zero-height', array(
			'width' => 825,
			'height' => 0,
		) );
		$this->wp->addImageSize( 'zero-width-height', array(
			'width' => 0,
			'height' => 0,
		) );

		$this->subject->get_sizes();
		$this->assertEquals(array(
			0 => array(
				'width' => null,
				'height' => null,
				'tinify' => true,
			),
			'thumbnail' => array(
				'width' => 150,
				'height' => 150,
				'tinify' => false,
			),
			'medium' => array(
				'width' => 300,
				'height' => 300,
				'tinify' => true,
			),
			'medium_large' => array(
				'width' => 768,
				'height' => 0,
				'tinify' => false,
			),
			'large' => array(
				'width' => 1024,
				'height' => 1024,
				'tinify' => false,
			),
			'zero-width' => array(
				'width' => 0,
				'height' => 510,
				'tinify' => false,
			),
			'zero-height' => array(
				'width' => 825,
				'height' => 0,
				'tinify' => false,
			),
		), $this->subject->get_sizes());
	}

	public function test_should_skip_dummy_size() {
		$this->wp->addOption( 'tinypng_sizes[tiny_dummy]', 'on' );

		$this->subject->get_sizes();
		$this->assertEquals(array(
			0 => array(
				'width' => null,
				'height' => null,
				'tinify' => false,
			),
			'thumbnail' => array(
				'width' => 150,
				'height' => 150,
				'tinify' => false,
			),
			'medium' => array(
				'width' => 300,
				'height' => 300,
				'tinify' => false,
			),
			'medium_large' => array(
				'width' => 768,
				'height' => 0,
				'tinify' => false,
			),
			'large' => array(
				'width' => 1024,
				'height' => 1024,
				'tinify' => false,
			),
		), $this->subject->get_sizes());
	}

	public function test_should_set_all_sizes_on_without_configuration() {
		$this->subject->get_sizes();
		$this->assertEquals(array(
			0 => array(
				'width' => null,
				'height' => null,
				'tinify' => true,
			),
			'thumbnail' => array(
				'width' => 150,
				'height' => 150,
				'tinify' => true,
			),
			'medium' => array(
				'width' => 300,
				'height' => 300,
				'tinify' => true,
			),
			'medium_large' => array(
				'width' => 768,
				'height' => 0,
				'tinify' => true,
			),
			'large' => array(
				'width' => 1024,
				'height' => 1024,
				'tinify' => true,
			),
		), $this->subject->get_sizes());
	}

	public function test_should_show_additional_size() {
		$this->wp->addImageSize( 'additional_size_1', array(
			'width' => 666,
			'height' => 333,
		) );
		$this->subject->get_sizes();
		$sizes = $this->subject->get_sizes();
		$this->assertEquals(
			array(
				'width' => 666,
				'height' => 333,
				'tinify' => true,
			),
			$sizes['additional_size_1']
		);
	}

	public function test_should_show_additional_size_without_height() {
		$this->wp->addImageSize( 'additional_size_no_height', array(
			'width' => 777,
		) );
		$this->subject->get_sizes();
		$sizes = $this->subject->get_sizes();
		$this->assertEquals(
			array(
				'width' => 777,
				'height' => 0,
				'tinify' => true,
			),
			$sizes['additional_size_no_height']
		);
	}

	public function test_should_show_additional_size_without_width() {
		$this->wp->addImageSize( 'additional_size_no_width', array(
			'height' => 888,
		) );
		$this->subject->get_sizes();
		$sizes = $this->subject->get_sizes();
		$this->assertEquals(
			array(
				'width' => 0,
				'height' => 888,
				'tinify' => true,
			),
			$sizes['additional_size_no_width']
		);
	}

	public function test_get_resize_enabled_should_return_true_if_enabled() {
		$this->wp->addOption( 'tinypng_resize_original', array(
			'enabled' => 'on',
		) );
		$this->assertEquals( true, $this->subject->get_resize_enabled() );
	}

	public function test_get_resize_enabled_should_return_false_without_configuration() {
		$this->wp->addOption( 'tinypng_resize_original', array() );
		$this->assertEquals( false, $this->subject->get_resize_enabled() );
	}

	public function test_get_resize_enabled_should_return_false_if_original_is_not_compressed() {
		$this->wp->addOption( 'tinypng_sizes[0]', 'off' );
		$this->wp->addOption( 'tinypng_resize_original', array(
			'enabled' => 'on',
		) );
		$this->assertEquals( false, $this->subject->get_resize_enabled() );
	}

	public function test_should_return_resize_options_with_width_and_height() {
		$this->wp->addOption(
			'tinypng_resize_original',
			array(
				'enabled' => 'on',
				'width' => '800',
				'height' => '600',
			)
		);

		$this->assertEquals(
			array(
				'method' => 'fit',
				'width' => 800,
				'height' => 600,
			),
			$this->subject->get_resize_options( Tiny_Image::ORIGINAL )
		);
	}

	public function test_should_return_resize_options_without_width() {
		$this->wp->addOption(
			'tinypng_resize_original',
			array(
				'enabled' => 'on',
				'width' => '',
				'height' => '600',
			)
		);

		$this->assertEquals(
			array(
				'method' => 'scale',
				'height' => 600,
			),
			$this->subject->get_resize_options( Tiny_Image::ORIGINAL )
		);
	}

	public function test_should_return_resize_options_without_height() {
		$this->wp->addOption(
			'tinypng_resize_original',
			array(
				'enabled' => 'on',
				'width' => '800',
				'height' => '',
			)
		);

		$this->assertEquals(
			array(
				'method' => 'scale',
				'width' => 800,
			),
			$this->subject->get_resize_options( Tiny_Image::ORIGINAL )
		);
	}

	public function test_should_return_resize_options_with_invalid_width() {
		$this->wp->addOption(
			'tinypng_resize_original',
			array(
				'enabled' => 'on',
				'width' => '-1',
				'height' => '600',
			)
		);

		$this->assertEquals(
			array(
				'method' => 'scale',
				'height' => 600,
			),
			$this->subject->get_resize_options( Tiny_Image::ORIGINAL )
		);
	}

	public function test_should_return_resize_options_with_invalid_height() {
		$this->wp->addOption(
			'tinypng_resize_original',
			array(
				'enabled' => 'on',
				'width' => '800',
				'height' => '-1',
			)
		);

		$this->assertEquals(
			array(
				'method' => 'scale',
				'width' => 800,
			),
			$this->subject->get_resize_options( Tiny_Image::ORIGINAL )
		);
	}

	public function test_should_not_return_resize_options_without_with_and_height() {
		$this->wp->addOption(
			'tinypng_resize_original',
			array(
				'enabled' => 'on',
				'width' => '',
				'height' => '',
			)
		);

		$this->assertEquals( false, $this->subject->get_resize_options( Tiny_Image::ORIGINAL ) );
	}

	public function test_should_not_return_resize_options_when_not_enabled() {
		$this->wp->addOption(
			'tinypng_resize_original',
			array(
				'width' => '800',
				'height' => '600',
			)
		);

		$this->assertEquals( false, $this->subject->get_resize_options( Tiny_Image::ORIGINAL ) );
	}

	public function test_should_return_include_metadata_enabled() {
		$this->wp->addOption( 'tinypng_preserve_data', array(
			'copyright' => 'on',
		) );
		$this->assertEquals( true, $this->subject->get_preserve_enabled( 'copyright' ) );
	}

	public function test_should_return_include_metadata_not_enabled_without_configuration() {
		$this->wp->addOption( 'tinypng_include_metadata', array() );
		$this->assertEquals( false, $this->subject->get_preserve_enabled( 'copyright' ) );
	}

	public function test_should_return_preserve_options_when_enabled() {
		$this->wp->addOption( 'tinypng_preserve_data', array(
			'copyright' => 'on',
		) );

		$this->assertEquals(
			array(
				'0' => 'copyright',
			),
			$this->subject->get_preserve_options( Tiny_Image::ORIGINAL )
		);
	}

	public function test_should_not_return_preserve_options_when_disabled() {
		$this->wp->addOption( 'tinypng_include_metadata', array() );

		$this->assertEquals(
			array(),
			$this->subject->get_preserve_options( Tiny_Image::ORIGINAL )
		);
	}
}
