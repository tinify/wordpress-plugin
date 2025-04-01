<?php

require_once dirname( __FILE__ ) . '/TinyTestCase.php';

class Tiny_Plugin_Ajax_Test extends Tiny_TestCase {
	protected $subject;
	
	public function set_up() {
		parent::set_up();
		$this->subject = new Tiny_Plugin();
		$this->subject->ajax_init();
	}

	public function test_init_should_add_filters() {
		$this->assertEquals(array(
			array( 'wp_ajax_tiny_async_optimize_upload_new_media', array( $this->subject, 'compress_on_upload' ) ),
			array( 'wp_ajax_nopriv_tiny_rpc', array( $this->subject, 'process_rpc_request' ) ),
		), $this->wp->getCalls( 'add_filter' ));
	}
}
