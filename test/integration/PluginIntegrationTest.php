<?php

require_once dirname( __FILE__ ) . '/IntegrationTestCase.php';

use Facebook\WebDriver\WebDriverBy;

class PluginIntegrationTest extends IntegrationTestCase {
	public function set_up() {
		parent::set_up();
		self::$driver->get( wordpress( '/wp-admin/plugins.php' ) );
	}

	public function tear_down() {
		parent::tear_down();
		clear_settings();
	}

	public function test_title_presence() {
		$element = self::$driver->findElements(
		WebDriverBy::xpath( '//*[@id="compress-jpeg-png-images"]//a[text()="Settings"]' ));

		$this->assertStringEndsWith('options-media.php#tiny-compress-images',
		$element[0]->getAttribute( 'href' ));
	}
}
