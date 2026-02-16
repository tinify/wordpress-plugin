<?php

require_once dirname( __FILE__ ) . '/TinyTestCase.php';

class Tiny_Settings_Ajax_Test extends Tiny_TestCase {
	protected $notices;
	
	public function set_up() {
		parent::set_up();
		
	}
	
	public function test_settings_ajax_init() {
		$tiny_settings = new Tiny_Settings();
		$tiny_settings->ajax_init();

		WordPressStubs::assertHook('wp_ajax_tiny_image_sizes_notice', array( $tiny_settings, 'image_sizes_notice' ));
		WordPressStubs::assertHook('wp_ajax_tiny_account_status', array( $tiny_settings, 'account_status' ));
		WordPressStubs::assertHook('wp_ajax_tiny_settings_create_api_key', array( $tiny_settings, 'create_api_key' ));
		WordPressStubs::assertHook('wp_ajax_tiny_settings_update_api_key', array( $tiny_settings, 'update_api_key' ));
	}
	
	public function test_notices_ajax_init() {
		$tiny_notices = new Tiny_Notices();
		$tiny_notices->ajax_init();

		WordPressStubs::assertHook('wp_ajax_tiny_dismiss_notice', array( $tiny_notices, 'dismiss' ));
	}
}
