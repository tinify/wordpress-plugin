<?php

require_once dirname( __FILE__ ) . '/IntegrationTestCase.php';

class SettingsIntegrationTest extends IntegrationTestCase {
	protected function get_enabled_sizes() {
		return array_map( function( $checkbox ) {
			return $checkbox->getAttribute( 'name' );
		}, $this->find_all( 'input[type=checkbox][checked][name^=tinypng_sizes]' ) );
	}

	public function test_settings_should_allow_key_reset() {
		$this->find( '#tinypng_api_key_name' )->clear()->sendKeys( 'John' );
		$this->find( '#tinypng_api_key_email' )->clear()->sendKeys( 'john@example.com' );
		$this->find( 'button[data-tiny-action=create-key]' )->click();

		$this->wait_for_text(
			'div.tiny-account-status p.status a',
			'Change API key'
		);

		$this->refresh();

		$this->wait_for_text(
			'div.tiny-account-status p.status a',
			'Change API key'
		);
	}

	public function test_settings_should_store_compression_timing() {
		$this->find( '#tinypng_compression_timing_auto' )->click();
		$this->find( 'form' )->submit();

		$this->assertEquals(
			'true',
			$this->find( '#tinypng_compression_timing_auto' )->getAttribute( 'checked' )
		);
	}

	public function test_settings_should_enable_all_sizes_by_default() {
		$enabled_sizes = $this->get_enabled_sizes();

		$this->assertContains( 'tinypng_sizes[0]', $enabled_sizes );
		$this->assertContains( 'tinypng_sizes[thumbnail]', $enabled_sizes );
		$this->assertContains( 'tinypng_sizes[medium]', $enabled_sizes );
		if ( $this->has_medium_large_size() ) {
			$this->assertContains( 'tinypng_sizes[medium_large]', $enabled_sizes );
		}
		$this->assertContains( 'tinypng_sizes[large]', $enabled_sizes );
	}

	public function test_settings_should_store_enabled_sizes() {
		$this->find( '#tinypng_sizes_medium' )->click();
		$this->find( '#tinypng_sizes_0' )->click();
		$this->find( 'form' )->submit();

		$enabled_sizes = $this->get_enabled_sizes();

		$this->assertNotContains( 'tinypng_sizes[0]', $enabled_sizes );
		$this->assertContains( 'tinypng_sizes[thumbnail]', $enabled_sizes );
		$this->assertNotContains( 'tinypng_sizes[medium]', $enabled_sizes );
		$this->assertContains( 'tinypng_sizes[large]', $enabled_sizes );
	}

	public function test_settings_should_store_all_disabled_sizes() {
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

	public function test_settings_should_show_free_compressions() {
		$this->enable_compression_sizes(
			array( '0', 'thumbnail', 'medium', 'large' )
		);

		$this->refresh();

		$this->assertContains(
			'With these settings you can compress at least 125 images for free each month.',
			$this->find( '#tiny-image-sizes-notice' )->getText()
		);
	}

	public function test_settings_should_update_free_compressions() {
		$this->enable_compression_sizes(
			array( '0', 'thumbnail', 'medium', 'large' )
		);

		$this->refresh();
		$this->find( '#tinypng_sizes_medium' )->click();

		$this->assertContains(
			'With these settings you can compress at least 166 images for free each month.',
			$this->find( '#tiny-image-sizes-notice' )->getText()
		);
	}

	public function test_settings_should_show_no_compressions() {
		$checkboxes = $this->find_all(
			'input[type=checkbox][checked][name^=tinypng_sizes]'
		);

		foreach ( $checkboxes as $checkbox ) {
			$checkbox->click();
		}

		$this->assertContains(
			'With these settings no images will be compressed.',
			$this->find( '#tiny-image-sizes-notice' )->getText()
		);
	}

	public function test_settings_should_show_resizing_when_original_enabled() {
		$elements = $this->find_all( 'label[for=tinypng_resize_original_enabled]' );
		$this->assertEquals(
			'Resize the original image',
			$elements[0]->getText()
		);

		$elements = $this->find_all( 'div.tiny-resize-unavailable' );
		$this->assertEquals(
			'',
			$elements[0]->getText()
		);
	}

	public function test_settings_should_not_show_resizing_when_original_disabled() {
		$this->find( '#tinypng_sizes_0' )->click(); /* Enabled by default */

		$elements = $this->find_all( 'label[for=tinypng_resize_original_enabled]' );
		$this->assertEquals(
			'',
			$elements[0]->getText()
		);

		$elements = $this->find_all( 'div.tiny-resize-unavailable' );
		$this->assertEquals(
			'Enable compression of the original image size for more options.',
			$elements[0]->getText()
		);
	}

	public function test_settings_should_store_resizing_settings() {
		$this->find( '#tinypng_resize_original_enabled' )->click();
		$this->find( '#tinypng_resize_original_width' )->clear()->sendKeys( '234' );
		$this->find( '#tinypng_resize_original_height' )->clear()->sendKeys( '345' );
		$this->find( 'form' )->submit();

		$this->assertEquals(
			'234',
			$this->find( '#tinypng_resize_original_width' )->getAttribute( 'value' )
		);

		$this->assertEquals(
			'345',
			$this->find( '#tinypng_resize_original_height' )->getAttribute( 'value' )
		);
	}
}
