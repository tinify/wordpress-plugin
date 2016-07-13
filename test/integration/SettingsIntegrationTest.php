<?php

require_once dirname( __FILE__ ) . '/IntegrationTestCase.php';

class SettingsIntegrationTest extends IntegrationTestCase {
	public function tear_down() {
		parent::tear_down();
		clear_settings();
		clear_uploads();
	}

	protected function get_enabled_sizes() {
		return array_map( function($checkbox) {
			return $checkbox->getAttribute( 'name' );
		}, $this->find_all( 'input[type=checkbox][checked][name^=tinypng_sizes]' ) );
	}

	public function test_settings_should_contain_title() {
		$this->visit( '/wp-admin/options-media.php' );

		$headings = array_map( function($heading) {
			return $heading->getText();
		}, $this->find_all( 'h2, h3' ) );

		$this->assertContains( 'JPEG and PNG optimization', $headings );
	}

	public function test_settings_should_show_notice_if_key_is_missing() {
		$this->visit( '/wp-admin/options-media.php' );

		$this->assertStringEndsWith(
			'options-media.php#tiny-compress-images',
			$this->find( '.error a' )->getAttribute( 'href' )
		);
	}

	public function test_settings_should_not_show_notice_if_key_is_set() {
		$this->set_api_key( 'PNG123' );
		$this->visit( '/wp-admin/options-media.php' );

		$this->assertEquals( 0, count( $this->find_all( '.error a' ) ) );
	}

	public function test_settings_should_enable_all_sizes_by_default() {
		$enabled_sizes = $this->get_enabled_sizes();

		$this->assertContains( 'tinypng_sizes[0]', $enabled_sizes );
		$this->assertContains( 'tinypng_sizes[thumbnail]', $enabled_sizes );
		$this->assertContains( 'tinypng_sizes[medium]', $enabled_sizes );
		$this->assertContains( 'tinypng_sizes[large]', $enabled_sizes );
	}

	public function test_settings_should_persist_enabled_sizes() {
		$this->find( '#tinypng_sizes_medium' )->click();
		$this->find( '#tinypng_sizes_0' )->click();
		$this->find( 'form' )->submit();

		$enabled_sizes = $this->get_enabled_sizes();

		$this->assertNotContains( 'tinypng_sizes[0]', $enabled_sizes );
		$this->assertContains( 'tinypng_sizes[thumbnail]', $enabled_sizes );
		$this->assertNotContains( 'tinypng_sizes[medium]', $enabled_sizes );
		$this->assertContains( 'tinypng_sizes[large]', $enabled_sizes );
	}

	public function test_settings_should_persist_all_disabled_sizes() {
		$checkboxes = $this->find_all(
			'input[type=checkbox][checked][name^=tinypng_sizes]'
		);

		foreach ( $checkboxes as $checkbox ) {
			$checkbox->click();
		}

		$this->find( 'form' )->submit();

		$enabled_sizes = $this->get_enabled_sizes();
		$this->assertEquals( 0, count( $enabled_sizes ) );
	}

// 	public function test_api_key_input_presence() {
// 		$elements = self::$driver->findElements( WebDriverBy::name( 'tinypng_api_key' ) );
// 		$this->assertEquals( 1, count( $elements ) );
// 	}

// 	public function test_should_show_total_images_info() {
// 		$this->enable_compression_sizes( array( '0', 'thumbnail', 'medium', 'large') );
// 		$element = self::$driver->findElement( WebDriverBy::id( 'tiny-image-sizes-notice' ) );
// 		$this->assertContains( 'With these settings you can compress at least 125 images for free each month.', $element->getText() );
// 	}
//
// 	public function test_should_update_total_images_info() {
// 		$this->enable_compression_sizes( array( '0', 'thumbnail', 'medium', 'large') );
// 		$element = self::$driver->findElement(
// 		WebDriverBy::xpath( '//input[@type="checkbox" and @name="tinypng_sizes[0]" and @checked="checked"]' ));
// 		$element->click();
// 		self::$driver->wait( 2 )->until(WebDriverExpectedCondition::textToBePresentInElement(
// 			WebDriverBy::cssSelector( '#tiny-image-sizes-notice' ),
// 		'With these settings you can compress at least 166 images for free each month.'));
// 	}
//
// 	public function test_should_show_correct_no_image_sizes_info() {
// 		$elements = self::$driver->findElements(
// 		WebDriverBy::xpath( '//input[@type="checkbox" and starts-with(@name, "tinypng_sizes") and @checked="checked"]' ));
// 		foreach ( $elements as $element ) {
// 			$element->click();
// 		}
// 		self::$driver->wait( 2 )->until(WebDriverExpectedCondition::textToBePresentInElement(
// 		WebDriverBy::cssSelector( '#tiny-image-sizes-notice' ), 'With these settings no images will be compressed.'));
// 		// Not really necessary anymore to assert this.
// 		$elements = self::$driver->findElement( WebDriverBy::id( 'tiny-image-sizes-notice' ) )->findElements( WebDriverBy::tagName( 'p' ) );
// 		$statuses = array_map( 'innerText', $elements );
// 		$this->assertContains( 'With these settings no images will be compressed.', $statuses );
// 	}
//
// 	public function test_should_show_resizing_when_original_enabled() {
// 		$element = self::$driver->findElement( WebDriverBy::id( 'tinypng_sizes_0' ) );
// 		if ( ! $element->getAttribute( 'checked' ) ) {
// 			$element->click();
// 		}
// 		$labels = self::$driver->findElements( WebDriverBy::tagName( 'label' ) );
// 		$texts = array_map( 'innerText', $labels );
// 		$this->assertContains( 'Resize and compress the original image', $texts );
// 		$paragraphs = self::$driver->findElements( WebDriverBy::tagName( 'p' ) );
// 		$texts = array_map( 'innerText', $paragraphs );
// 		$this->assertNotContains( 'Enable compression of the original image size for more options.', $texts );
// 	}
//
// 	public function test_should_not_show_resizing_when_original_disabled() {
// 		$element = self::$driver->findElement( WebDriverBy::id( 'tinypng_sizes_0' ) );
// 		if ( $element->getAttribute( 'checked' ) ) {
// 			$element->click();
// 		}
// 		self::$driver->wait( 1 )->until(WebDriverExpectedCondition::textToBePresentInElement(
// 		WebDriverBy::cssSelector( 'p.tiny-resize-unavailable' ), 'Enable compression of the original image size for more options.'));
// 		$labels = self::$driver->findElements( WebDriverBy::tagName( 'label' ) );
// 		$texts = array_map( 'innerText', $labels );
// 		$this->assertNotContains( 'Resize and compress orginal images to fit within:', $texts );
// 	}
//
// 	public function test_should_not_show_resizing_when_original_disabled_when_shown_first() {
// 		$this->enable_compression_sizes( array( 'original') );
// 		self::$driver->navigate()->refresh();
// 		$this->assertEquals('Enable compression of the original image size for more options.',
// 		self::$driver->findElement( WebDriverBy::cssSelector( '.tiny-resize-unavailable' ) )->getText());
// 	}
//
// 	public function test_should_persist_resizing_settings() {
// 		$this->enable_resize( 123, 456 );
// 		$this->assertEquals( '123', self::$driver->findElement( WebDriverBy::id( 'tinypng_resize_original_width' ) )->getAttribute( 'value' ) );
// 		$this->assertEquals( '456', self::$driver->findElement( WebDriverBy::id( 'tinypng_resize_original_height' ) )->getAttribute( 'value' ) );
// 	}
//
// 	public function test_status_presence_ok() {
// 		reset_webservice();
// 		$this->set_api_key( 'PNG123' );
// 		self::$driver->wait( 2 )->until(WebDriverExpectedCondition::textToBePresentInElement(
// 			WebDriverBy::cssSelector( '#tiny-compress-status p.tiny-account-status' ),
// 		'Your account is connected'));
// 	}
//
// 	public function test_status_presense_fail() {
// 		$this->set_api_key( 'INVALID123', false );
// 		self::$driver->wait( 2 )->until(WebDriverExpectedCondition::textToBePresentInElement(
// 			WebDriverBy::cssSelector( '#tiny-compress-status p.tiny-update-account-message' ),
// 		'The key that you have entered is not valid'));
// 	}
}
