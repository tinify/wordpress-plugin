<?php

require_once dirname( __FILE__ ) . '/TinyTestCase.php';

class Tiny_Settings_Ajax_Test extends Tiny_TestCase {
	public function set_up() {
		parent::set_up();
		$this->subject = new Tiny_Settings();
		$this->notices = new Tiny_Notices();
		
		$this->subject->ajax_init();
	}

	public function test_ajax_init_should_add_actions() {
		$this->assertEquals(array(
				array( 'init', array( $this->subject, 'init' ) ),
				array( 'rest_api_init', array( $this->subject, 'rest_init' ) ),
				array( 'admin_init', array( $this->subject, 'admin_init' ) ),
				array( 'admin_menu', array( $this->subject, 'admin_menu' ) ),
				array( 'init', array( $this->notices, 'init' ) ),
				array( 'rest_api_init', array( $this->notices, 'rest_init' ) ),
				array( 'admin_init', array( $this->notices, 'admin_init' ) ),
				array( 'admin_menu', array( $this->notices, 'admin_menu' ) ),
				array( 'init', array( $this->notices, 'init' ) ),
				array( 'rest_api_init', array( $this->notices, 'rest_init' ) ),
				array( 'admin_init', array( $this->notices, 'admin_init' ) ),
				array( 'admin_menu', array( $this->notices, 'admin_menu' ) ),
				array( 'wp_ajax_tiny_image_sizes_notice', array( $this->subject, 'image_sizes_notice' ) ),
				array( 'wp_ajax_tiny_account_status', array( $this->subject, 'account_status' ) ),
				array( 'wp_ajax_tiny_settings_create_api_key', array( $this->subject, 'create_api_key' ) ),
				array( 'wp_ajax_tiny_settings_update_api_key', array( $this->subject, 'update_api_key' ) ),
			),
			$this->wp->getCalls( 'add_action' )
		);
	}
}
