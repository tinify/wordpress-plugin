<?php

require_once dirname( __FILE__ ) . '/IntegrationTestCase.php';

class CompressIntegrationTest extends IntegrationTestCase {
	public function tear_down() {
		parent::tear_down();
		clear_settings();
		clear_uploads();
	}

	public function test_upload_without_key_should_show_error() {
		$this->upload_media( 'test/fixtures/input-example.png' );
		$this->assertContains(
			'Latest error: Register an account or provide an API key first',
			$this->find( 'td.tiny-compress-images' )->getText()
		);
	}

	public function test_upload_with_invalid_key_should_show_error() {
		$this->set_api_key( '1234' );
		$this->upload_media( 'test/fixtures/input-example.png' );
		$this->assertContains(
			'Latest error: Credentials are invalid',
			$this->find( 'td.tiny-compress-images' )->getText()
		);
	}

	public function test_upload_with_limited_key_should_show_error() {
		$this->set_api_key( 'LIMIT123' );
		$this->upload_media( 'test/fixtures/input-example.png' );
		$this->assertContains(
			'Latest error: Your monthly limit has been exceeded',
			$this->find( 'td.tiny-compress-images' )->getText()
		);
	}

	public function test_upload_with_valid_key_should_show_sizes_compressed() {
		$this->set_api_key( 'PNG123' );
		$this->upload_media( 'test/fixtures/input-example.png' );
		$this->assertContains(
			'sizes compressed',
			$this->find( 'td.tiny-compress-images' )->getText()
		);
	}

	public function test_upload_with_gateway_timeout_should_show_error() {
		$this->set_api_key( 'GATEWAYTIMEOUT' );
		$this->enable_compression_sizes( array( 'medium' ) );
		$this->upload_media( 'test/fixtures/input-example.png' );
		$this->assertContains(
			'Error while parsing response',
			$this->find( 'td.tiny-compress-images' )->getText()
		);
	}

	public function test_upload_with_incorrect_metadata_should_show_error() {
		$this->set_api_key( 'PNG123 INVALID' );
		$this->enable_compression_sizes( array( '0', 'medium' ) );
		$this->enable_preserve( array( 'copyright' ) );
		$this->upload_media( 'test/fixtures/input-example.png' );
		$this->assertContains(
			"Metadata key 'author' not supported",
			$this->find( 'td.tiny-compress-images' )->getText()
		);
	}

	public function test_compress_button_should_compress_uncompressed_sizes() {
		$this->set_api_key( 'PNG123' );

		$this->enable_compression_sizes( array( 'medium' ) );
		$this->upload_media( 'test/fixtures/input-example.png' );
		$this->enable_compression_sizes( array( 'medium', 'thumbnail' ) );

		$this->visit( '/wp-admin/upload.php' );
		$this->assertContains(
			'1 size compressed',
			$this->find( 'td.tiny-compress-images' )->getText()
		);

		$this->assertContains(
			'1 size to be compressed',
			$this->find( 'td.tiny-compress-images' )->getText()
		);

		$this->find( 'td.tiny-compress-images button' )->click();

		$this->wait_for_text(
			'td.tiny-compress-images',
			'2 sizes compressed'
		);
	}

	public function test_compress_button_should_show_error_for_incorrect_json() {
		$this->enable_compression_sizes( array() );
		$this->upload_media( 'test/fixtures/input-example.png' );
		$this->enable_compression_sizes( array( 'medium', 'large') );

		$this->set_api_key( 'JSON1234' );
		$this->visit( '/wp-admin/upload.php' );

		$this->find( 'td.tiny-compress-images button' )->click();
		$this->wait_for_text(
			'td.tiny-compress-images',
			'Error while parsing response'
		);
	}

	public function test_limit_reached_dismiss_button_should_remove_error() {
		$this->set_api_key( 'LIMIT123' );
		$this->upload_media( 'test/fixtures/input-example.png' );

		$this->find( '.tiny-notice button, .tiny-notice a.tiny-dismiss' )->click();
		$this->wait_for_text_disappearance( 'a.tiny-dismiss', 'Dismiss' );

		$this->visit( '/wp-admin/options-media.php' );
		$this->assertEquals( 0, count( $this->find_all( 'div.error p' ) ) );
	}

	public function test_resize_fit_should_display_resized_text_in_library() {
		$this->set_api_key( 'PNG123' );
		$this->enable_resize(array(
			'method' => 'fit',
			'width' => 300,
			'height' => 200,
		));

		$this->upload_media( 'test/fixtures/input-example.png' );
		$this->find( 'td.tiny-compress-images a.thickbox' )->click();

		$this->assertContains(
			'resized to 300x200',
			$this->find( 'div.tiny-compression-details' )->getText()
		);
	}

	public function test_resize_fit_should_display_resized_text_in_edit_screen() {
		if ( ! $this->has_postbox_container() ) { return; }
		$this->set_api_key( 'PNG123' );
		$this->enable_resize(array(
			'method' => 'fit',
			'width' => 300,
			'height' => 200,
		));

		$this->upload_media( 'test/fixtures/input-example.png' );
		$this->find_link( 'input-example' )->click();

		$this->assertContains(
			'Dimensions: 300 × 200',
			$this->find( $this->postbox_dimension_selector() )->getText()
		);
	}

	public function test_resize_scale_should_display_resized_text_in_library() {
		$this->set_api_key( 'PNG123' );
		$this->enable_resize(array(
			'method' => 'scale',
			'height' => 200,
		));

		$this->upload_media( 'test/fixtures/input-example.png' );
		$this->find( 'td.tiny-compress-images a.thickbox' )->click();

		$this->assertContains(
			'resized to 300x200',
			$this->find( 'div.tiny-compression-details' )->getText()
		);
	}

	public function test_resize_scale_should_display_resized_text_in_edit_screen() {
		if ( ! $this->has_postbox_container() ) { return; }
		$this->set_api_key( 'PNG123' );
		$this->enable_resize(array(
			'method' => 'scale',
			'height' => 200,
		));

		$this->upload_media( 'test/fixtures/input-example.png' );
		$this->find_link( 'input-example' )->click();

		$this->assertContains(
			'Dimensions: 300 × 200',
			$this->find( $this->postbox_dimension_selector() )->getText()
		);
	}

	public function test_superfluous_resize_should_not_display_resized_text_in_library() {
		$this->set_api_key( 'PNG123' );
		$this->enable_resize(array(
			'method' => 'fit',
			'width' => 15000,
			'height' => 15000,
		));

		$this->upload_media( 'test/fixtures/input-example.png' );
		$this->find( 'td.tiny-compress-images a.thickbox' )->click();

		$this->assertNotContains(
			'resized',
			$this->find( 'div.tiny-compression-details' )->getText()
		);
	}

	public function test_superfluous_resize_should_display_original_dimensions_in_edit_screen() {
		if ( ! $this->has_postbox_container() ) { return; }
		$this->set_api_key( 'PNG123' );
		$this->enable_resize(array(
			'method' => 'fit',
			'width' => 15000,
			'height' => 15000,
		));

		$this->upload_media( 'test/fixtures/input-example.png' );
		$this->find_link( 'input-example' )->click();

		$this->assertContains(
			'Dimensions: 1080 × 720',
			$this->find( $this->postbox_dimension_selector() )->getText()
		);
	}

	public function test_resize_disabled_should_not_display_resized_text_in_library() {
		$this->set_api_key( 'PNG123' );
		$this->disable_resize();

		$this->upload_media( 'test/fixtures/input-example.png' );
		$this->find( 'td.tiny-compress-images a.thickbox' )->click();

		$this->assertNotContains(
			'resized',
			$this->find( 'div.tiny-compression-details' )->getText()
		);
	}

	public function test_resize_disabled_should_display_original_dimensions_in_edit_screen() {
		if ( ! $this->has_postbox_container() ) { return; }
		$this->set_api_key( 'PNG123' );
		$this->disable_resize();

		$this->upload_media( 'test/fixtures/input-example.png' );
		$this->find_link( 'input-example' )->click();

		$this->assertContains(
			'Dimensions: 1080 × 720',
			$this->find( $this->postbox_dimension_selector() )->getText()
		);
	}

	// public function test_preserve_copyright_should_display_correct_image_size_in_library() {
	// 	$this->set_api_key( 'PRESERVEJPG123' );
	// 	$this->enable_preserve( array( 'copyright') );
	// 	$this->upload_media( 'test/fixtures/input-copyright.jpg' );
	// 	$this->assertNotContains('files modified after compression',
	// 	self::$driver->findElement( WebDriverBy::cssSelector( 'div#tiny-compress-details' ) )->getText());
	// }
	//
	// public function test_should_show_details_in_edit_screen() {
	// 	if ( ! $this->has_postbox_container() ) { return; }
	// 	$this->set_api_key( 'PNG123' );
	// 	$this->enable_compression_sizes( array() );
	// 	$this->upload_media( 'test/fixtures/input-example.png' );
	// 	$this->enable_compression_sizes( array( 'medium', 'large') );
	// 	$this->view_edit_image();
	// 	$this->assertContains("Compress JPEG & PNG Images\n2 sizes not compressed\nDetails\nCompress",
	// 	self::$driver->findElement( WebDriverBy::cssSelector( 'div.postbox-container div.tiny-compress-images' ) )->getText());
	// }
	//
	// public function test_button_in_edit_screen_should_compress_images() {
	// 	if ( ! $this->has_postbox_container() ) { return; }
	// 	$this->upload_media( 'test/fixtures/input-example.png' );
	// 	$this->set_api_key( 'PNG123' );
	// 	$this->enable_compression_sizes( array( 'medium', 'large') );
	// 	$this->view_edit_image();
	// 	self::$driver->findElement( WebDriverBy::cssSelector( 'div.tiny-compress-images button.tiny-compress' ) )->click();
	// 	self::$driver->wait( 2 )->until(WebDriverExpectedCondition::textToBePresentInElement(
	// 	WebDriverBy::cssSelector( 'div.tiny-compress-images' ), '2 sizes compressed'));
	// }
	//
	// public function test_edit_screen_should_show_details_popup() {
	// 	if ( ! $this->has_postbox_container() ) { return; }
	// 	$this->set_api_key( 'PNG123' );
	// 	$this->enable_compression_sizes( array( 'medium', 'large') );
	// 	$this->upload_media( 'test/fixtures/input-example.png' );
	// 	$this->view_edit_image();
	// 	self::$driver->findElement( WebDriverBy::cssSelector( 'div.tiny-compress-images a.thickbox' ) )->click();
	// 	$this->assertContains('Compression details for input-example.jpg',
	// 	self::$driver->findElement( WebDriverBy::cssSelector( 'div.tiny-compression-details' ) )->getText());
	// }
	//
	// public function test_edit_screen_should_show_correct_details_in_popup() {
	// 	if ( ! $this->has_postbox_container() ) { return; }
	// 	$this->set_api_key( 'PNG123' );
	// 	$this->enable_compression_sizes( array( 'medium', 'large') );
	// 	$this->upload_media( 'test/fixtures/input-example.png' );
	// 	$this->view_edit_image();
	//
	// 	self::$driver->findElement( WebDriverBy::cssSelector( 'div.tiny-compress-images a.thickbox' ) )->click();
	// 	$cells = self::$driver->findElements( WebDriverBy::cssSelector( 'div.tiny-compression-details td' ) );
	// 	$texts = array_map( 'innerText', $cells );
	// 	// Remove sizes that are version specific
	// 	for ( $i = 0; $i < count( $texts ); ) {
	// 		$row_size = (substr( $texts[ $i + 2 ], 0, 3 ) == 'Not') ? 3 : 4;
	// 		if ( in_array( $texts[ $i ], array( 'original', 'thumbnail', 'medium', 'large', 'Combined') )  ) {
	// 			$i += $row_size;
	// 		} else {
	// 			$texts = array_merge( array_slice( $texts, 0, $i ), array_slice( $texts, $i + $row_size ) );
	// 		}
	// 	}
	//
	// 	$this->assertEquals(
	// 		array(
	// 			'original',  '158.1 kB', 'Not configured to be compressed',
	// 			'large',     '158.1 kB', '147.5 kB', '1 min ago',
	// 			'medium',    '158.1 kB', '147.5 kB', '1 min ago',
	// 			'thumbnail', '11.8 kB',  'Not configured to be compressed',
	// 			'Combined',  '316.2 kB', '295.0 kB', '',
	// 		),
	// 		$texts
	// 	);
	// }

	public function test_unsupported_format_should_not_show_compress_info_in_library() {
		$this->upload_media( 'test/fixtures/input-example.gif' );
		$this->assertEquals( '', $this->find( 'div#tiny-compress-details' )->getText() );
	}

	public function test_non_image_file_should_not_show_compress_info_in_library() {
		$this->upload_media( 'test/fixtures/input-example.pdf' );
		$this->assertEquals( '', $this->find( 'div#tiny-compress-details' )->getText() );
	}
}
