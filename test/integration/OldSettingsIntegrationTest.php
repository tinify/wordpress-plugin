<?php

require_once dirname( __FILE__ ) . '/IntegrationTestCase.php';

class OldSettingsIntegrationTest extends IntegrationTestCase {
	public function set_up() {
		parent::set_up();
		$this->visit( '/wp-admin/options-media.php' );
	}

	public function tear_down() {
		parent::tear_down();
		clear_settings();
		clear_uploads();
	}

	public function test_old_settings_should_contain_heading() {
		$headings = array_map( function( $heading ) {
			return $heading->getText();
		}, $this->find_all( 'h3' ) );

		$this->assertContains( 'Compress JPEG & PNG images', $headings );
	}

	public function test_old_settings_should_contain_link() {
		$this->assertStringEndsWith(
			'options-general.php?page=tinify',
			$this->find( '.tinify-settings a' )->getAttribute( 'href' )
		);
	}
}
