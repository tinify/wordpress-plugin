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

	public function test_bulk_queue_handlers_refuse_an_invalid_nonce_with_a_403() {
		$this->wp->stub( 'check_ajax_referer', function () {
			return false;
		} );
		$this->wp->stub( 'current_user_can', function () {
			return true;
		} );

		$this->assert_bulk_queue_handlers_refuse_with_a_403();
	}

	public function test_bulk_queue_handlers_refuse_a_user_without_permission_with_a_403() {
		$this->wp->stub( 'current_user_can', function () {
			return false;
		} );

		$this->assert_bulk_queue_handlers_refuse_with_a_403();
	}

	private function assert_bulk_queue_handlers_refuse_with_a_403() {
		$this->wp->addMethod( 'status_header' );
		$this->wp->addMethod( 'wp_die' );
		$this->wp->stub( 'wp_die', function () {
			throw new Exception( 'wp_die' );
		} );

		$handlers = array(
			'ajax_bulk_queue_start',
			'ajax_bulk_queue_optimize_next',
			'ajax_bulk_queue_status',
			'ajax_bulk_queue_cancel',
		);
		foreach ( $handlers as $handler ) {
			try {
				$this->subject->$handler();
			} catch ( Exception $e ) {
				// wp_die() ends the request.
			}
		}

		// Before WP 5.1 wp_die() leaves an Ajax response at 200, so the status is set first.
		$this->assertEquals(
			array_fill( 0, count( $handlers ), array( 403 ) ),
			$this->wp->getCalls( 'status_header' )
		);
		$this->assertEquals(
			array_fill( 0, count( $handlers ), array( -1, 403 ) ),
			$this->wp->getCalls( 'wp_die' )
		);
	}
}
