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

	public function test_throws_error_when_user_lacks_permission() {
		$tiny_settings = new Tiny_Settings();
		$tiny_diagnostics = new Tiny_Diagnostics($tiny_settings);

		$this->wp->stub('current_user_can', function($capability) {
			$this->assertEquals('edit_posts', $capability);
			return false;
		});

		$this->wp->stub('wp_send_json_error', function($message, $status_code) use (&$json_error_called) {
			$this->assertStringContainsString('Not allowed', $message);
			$this->assertEquals(403, $status_code);
			throw new Exception('wp_send_json_error');
		});

		try {
			$tiny_diagnostics->download_diagnostics();
		} catch (Exception $e) {
			$this->assertEquals('wp_send_json_error', $e->getMessage());
		}
	}

	public function test_can_download_zip() {
		$tiny_settings = new Tiny_Settings();
		$tiny_diagnostics = new Tiny_Diagnostics($tiny_settings);

		$this->wp->stub('current_user_can', function($capability) {
			$this->assertEquals('edit_posts', $capability);
			return true;
		});

		$zip_path = $tiny_diagnostics->create_diagnostic_zip();
		$this->assertStringContainsString('tiny-compress-diagnostics', $zip_path);
		$this->assertTrue(file_exists($zip_path), 'zip should exist at the returned path');
		$file_size = filesize($zip_path);
		$this->assertGreaterThan(0, $file_size, 'Zip file should have content');

		// Clean up
		if (file_exists($zip_path)) {
			unlink($zip_path);
		}
	}
}
