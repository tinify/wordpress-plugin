<?php

require_once dirname( __FILE__ ) . '/IntegrationTestCase.php';

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;

class SettingsIntegrationTest extends IntegrationTestCase {
	public function set_up() {
		parent::set_up();
		self::$driver->get( wordpress( '/wp-admin/options-media.php' ) );
	}

	public function tear_down() {
		parent::tear_down();
		clear_settings();
	}

	public function test_title_presence()
	{
		$headings = self::$driver->findElements( WebDriverBy::cssSelector( 'h1, h2, h3, h4' ) );
		$texts = array_map( 'innerText', $headings );
		$this->assertContains( 'PNG and JPEG optimization', $texts );
	}

	public function test_api_key_input_presence() {
		$elements = self::$driver->findElements( WebDriverBy::name( 'tinypng_api_key' ) );
		$this->assertEquals( 1, count( $elements ) );
	}

	public function test_should_show_notice_if_no_api_key_is_set() {
		$element = self::$driver->findElement( WebDriverBy::cssSelector( '.error a' ) );
		$this->assertStringEndsWith( 'options-media.php#tiny-compress-images', $element->getAttribute( 'href' ) );
	}

	public function test_should_show_no_notice_if_api_key_is_set() {
		$this->set_api_key( 'PNG123' );
		self::$driver->navigate()->refresh(); /* Reload first. */
		$elements = self::$driver->findElements( WebDriverBy::cssSelector( '.error a' ) );
		$this->assertEquals( 0, count( $elements ) );
	}

	public function test_no_api_key_notice_should_link_to_settings() {
		self::$driver->findElement( WebDriverBy::cssSelector( '.error a' ) )->click();
		$this->assertStringEndsWith( 'options-media.php#tiny-compress-images', self::$driver->getCurrentURL() );
	}

	public function test_default_sizes_being_compressed() {
		$elements = self::$driver->findElements(
		WebDriverBy::xpath( '//input[@type="checkbox" and starts-with(@name, "tinypng_sizes") and @checked="checked"]' ));
		$size_ids = array_map( 'elementName', $elements );
		$this->assertContains( 'tinypng_sizes[0]', $size_ids );
		$this->assertContains( 'tinypng_sizes[thumbnail]', $size_ids );
		$this->assertContains( 'tinypng_sizes[medium]', $size_ids );
		$this->assertContains( 'tinypng_sizes[large]', $size_ids );
	}

	public function test_should_persist_sizes() {
		$element = self::$driver->findElement( WebDriverBy::id( 'tinypng_sizes_medium' ) );
		$element->click();
		$element = self::$driver->findElement( WebDriverBy::id( 'tinypng_sizes_0' ) );
		$element->click();
		self::$driver->findElement( WebDriverBy::tagName( 'form' ) )->submit();

		$elements = self::$driver->findElements(
		WebDriverBy::xpath( '//input[@type="checkbox" and starts-with(@name, "tinypng_sizes") and @checked="checked"]' ));
		$size_ids = array_map( 'elementName', $elements );
		$this->assertNotContains( 'tinypng_sizes[0]', $size_ids );
		$this->assertContains( 'tinypng_sizes[thumbnail]', $size_ids );
		$this->assertNotContains( 'tinypng_sizes[medium]', $size_ids );
		$this->assertContains( 'tinypng_sizes[large]', $size_ids );
	}

	public function test_should_persist_no_sizes() {
		$elements = self::$driver->findElements(
		WebDriverBy::xpath( '//input[@type="checkbox" and starts-with(@name, "tinypng_sizes") and @checked="checked"]' ));
		foreach ( $elements as $element ) {
			$element->click();
		}
		self::$driver->findElement( WebDriverBy::tagName( 'form' ) )->submit();

		$elements = self::$driver->findElements(
		WebDriverBy::xpath( '//input[@type="checkbox" and starts-with(@name, "tinypng_sizes") and @checked="checked"]' ));
		$this->assertEquals( 0, count( array_map( 'elementName', $elements ) ) );
	}

	public function test_should_show_total_images_info() {
		$this->enable_compression_sizes( array( '0', 'thumbnail', 'medium', 'large') );
		$element = self::$driver->findElement( WebDriverBy::id( 'tiny-image-sizes-notice' ) );
		$this->assertContains( 'With these settings you can compress at least 125 images for free each month.', $element->getText() );
	}

	public function test_should_update_total_images_info() {
		$this->enable_compression_sizes( array( '0', 'thumbnail', 'medium', 'large') );
		$element = self::$driver->findElement(
		WebDriverBy::xpath( '//input[@type="checkbox" and @name="tinypng_sizes[0]" and @checked="checked"]' ));
		$element->click();
		self::$driver->wait( 2 )->until(WebDriverExpectedCondition::textToBePresentInElement(
			WebDriverBy::cssSelector( '#tiny-image-sizes-notice' ),
		'With these settings you can compress at least 166 images for free each month.'));
	}

	public function test_should_show_correct_no_image_sizes_info() {
		$elements = self::$driver->findElements(
		WebDriverBy::xpath( '//input[@type="checkbox" and starts-with(@name, "tinypng_sizes") and @checked="checked"]' ));
		foreach ( $elements as $element ) {
			$element->click();
		}
		self::$driver->wait( 2 )->until(WebDriverExpectedCondition::textToBePresentInElement(
		WebDriverBy::cssSelector( '#tiny-image-sizes-notice' ), 'With these settings no images will be compressed.'));
		// Not really necessary anymore to assert this.
		$elements = self::$driver->findElement( WebDriverBy::id( 'tiny-image-sizes-notice' ) )->findElements( WebDriverBy::tagName( 'p' ) );
		$statuses = array_map( 'innerText', $elements );
		$this->assertContains( 'With these settings no images will be compressed.', $statuses );
	}

	public function test_should_show_resizing_when_original_enabled() {
		$element = self::$driver->findElement( WebDriverBy::id( 'tinypng_sizes_0' ) );
		if ( ! $element->getAttribute( 'checked' ) ) {
			$element->click();
		}
		$labels = self::$driver->findElements( WebDriverBy::tagName( 'label' ) );
		$texts = array_map( 'innerText', $labels );
		$this->assertContains( 'Resize and compress the original image', $texts );
		$paragraphs = self::$driver->findElements( WebDriverBy::tagName( 'p' ) );
		$texts = array_map( 'innerText', $paragraphs );
		$this->assertNotContains( 'Enable compression of the original image size for more options.', $texts );
	}

	public function test_should_not_show_resizing_when_original_disabled() {
		$element = self::$driver->findElement( WebDriverBy::id( 'tinypng_sizes_0' ) );
		if ( $element->getAttribute( 'checked' ) ) {
			$element->click();
		}
		self::$driver->wait( 1 )->until(WebDriverExpectedCondition::textToBePresentInElement(
		WebDriverBy::cssSelector( 'p.tiny-resize-unavailable' ), 'Enable compression of the original image size for more options.'));
		$labels = self::$driver->findElements( WebDriverBy::tagName( 'label' ) );
		$texts = array_map( 'innerText', $labels );
		$this->assertNotContains( 'Resize and compress orginal images to fit within:', $texts );
	}

	public function test_should_not_show_resizing_when_original_disabled_when_shown_first() {
		$this->enable_compression_sizes( array( 'original') );
		self::$driver->navigate()->refresh();
		$this->assertEquals('Enable compression of the original image size for more options.',
		self::$driver->findElement( WebDriverBy::cssSelector( '.tiny-resize-unavailable' ) )->getText());
	}

	public function test_should_persist_resizing_settings() {
		$this->enable_resize( 123, 456 );
		$this->assertEquals( '123', self::$driver->findElement( WebDriverBy::id( 'tinypng_resize_original_width' ) )->getAttribute( 'value' ) );
		$this->assertEquals( '456', self::$driver->findElement( WebDriverBy::id( 'tinypng_resize_original_height' ) )->getAttribute( 'value' ) );
	}

	public function test_status_presence_ok() {
		reset_webservice();
		$this->set_api_key( 'PNG123' );
		self::$driver->wait( 2 )->until(WebDriverExpectedCondition::textToBePresentInElement(
			WebDriverBy::cssSelector( '#tiny-compress-status p.tiny-account-status' ),
		'Your account is connected'));
	}

	public function test_status_presense_fail() {
		$this->set_api_key( 'INVALID123', false );
		self::$driver->wait( 2 )->until(WebDriverExpectedCondition::textToBePresentInElement(
			WebDriverBy::cssSelector( '#tiny-compress-status p.tiny-update-account-message' ),
		'The key that you have entered is not valid'));
	}
}
