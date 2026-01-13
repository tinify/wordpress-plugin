<?php
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
		assertArrayHasKey('site_info', $info);
		assertArrayHasKey('active_plugins', $info);
		assertArrayHasKey('server_info', $info);
		assertArrayHasKey('tiny_info', $info);
		assertArrayHasKey('image_sizes', $info);
	}

	public function test_will_die_when_nonce_is_invalid() {
		$tiny_settings = new Tiny_Settings();
		$tiny_diagnostics = new Tiny_Diagnostics($tiny_settings);
		
		
		$this->wp->stub('check_ajax_referer', function($action, $query_arg) {
			$this->assertEquals('tiny-compress', $action);
			$this->assertEquals('security', $query_arg);
			// mocking an invalid nonce here, it usually calls wp_die
			throw new Exception('invalid nonce');
		});
		
		try {
			$tiny_diagnostics->download_diagnostics();
		} catch (Exception $e) {
			$this->assertEquals('invalid nonce', $e->getMessage());
		}
	}
}
