<?php

require_once dirname(__FILE__) . '/TinyTestCase.php';

class Tiny_Diagnostics_Test extends Tiny_TestCase
{
	public function set_up()
	{
		parent::set_up();
	}

	public function test_adds_ajax_action_to_download_diagnostics() {
		$tiny_settings = new Tiny_Settings();
		$tiny_diagnostics = new Tiny_Diagnostics($tiny_settings);

		WordPressStubs::assertHook('wp_ajax_tiny_download_diagnostics', array($tiny_diagnostics, 'download_diagnostics'));
	}
}
