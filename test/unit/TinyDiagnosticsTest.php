<?php

use Mockery\Matcher\Any;

use function PHPUnit\Framework\assertIsString;
use function PHPUnit\Framework\assertIsArray;
use function PHPUnit\Framework\assertArrayHasKey;

require_once dirname(__FILE__) . '/TinyTestCase.php';

class Tiny_Diagnostics_Test extends Tiny_TestCase
{
	public function set_up()
	{
		parent::set_up();
	}

	public function test_construct_adds_action_to_download_diagnostics()
	{
		$tiny_settings = new Tiny_Settings();
		$tiny_diagnostics = new Tiny_Diagnostics($tiny_settings);

		WordPressStubs::assertHook('wp_ajax_tiny_download_diagnostics', array($tiny_diagnostics, 'download_diagnostics'));
	}

	public function test_collect_info_returns_info()
	{
		$tiny_settings = new Tiny_Settings();
		$tiny_diagnostics = new Tiny_Diagnostics($tiny_settings);

		$info = $tiny_diagnostics->collect_info();

		// were just verifying the main structure
		assertArrayHasKey('timestamp', $info);
		assertArrayHasKey('server_info', $info);
		assertArrayHasKey('site_info', $info);
		assertArrayHasKey('active_plugins', $info);
		assertArrayHasKey('tiny_info', $info);
		assertArrayHasKey('image_sizes', $info);
	}
}
