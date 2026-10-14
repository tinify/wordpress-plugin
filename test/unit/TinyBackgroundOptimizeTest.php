<?php

require_once dirname( __FILE__ ) . '/TinyTestCase.php';

class Tiny_Background_Optimize_Test extends Tiny_TestCase {
	private $subject;
	private $settings;

	/* Attachment ID => queue status, standing in for the postmeta table. */
	private $statuses = array();

	/* Arguments of every request sent, plus how many workers were active then. */
	private $requests = array();

	public function set_up() {
		parent::set_up();
		$functions = array(
			'add_post_meta',
			'delete_post_meta_by_key',
			'wp_create_nonce',
			'admin_url',
			'wp_unslash',
			'wp_remote_post',
			'is_wp_error',
			'wp_die',
		);
		foreach ( $functions as $function ) {
			$this->wp->addMethod( $function );
		}

		/* The query for the next queued attachment. */
		$this->wp->stub( 'get_var', function () {
			$queued = array_keys( $this->statuses, Tiny_Background_Optimize::STATUS_QUEUED, true );
			return $queued ? max( $queued ) : null;
		} );
		$this->wp->stub( 'update_post_meta', function ( $id, $key, $value, $prev_value = '' ) {
			if ( Tiny_Background_Optimize::META_KEY_STATUS !== $key ) {
				return true;
			}
			if ( '' !== $prev_value && $this->statuses[ $id ] !== $prev_value ) {
				return false;
			}
			$this->statuses[ $id ] = $value;
			return true;
		} );
		$this->wp->stub( 'admin_url', function ( $path ) {
			return 'http://localhost/wp-admin/' . $path;
		} );
		$this->wp->stub( 'wp_unslash', function ( $value ) {
			return array_map( 'stripslashes', $value );
		} );
		$this->wp->stub( 'wp_remote_post', function ( $url, $args ) {
			$this->requests[] = $args + array(
				'url'            => $url,
				'active_workers' => $this->count_active_workers(),
			);
			return array();
		} );

		$this->settings = $this->createMock( 'Tiny_Settings' );
		$this->subject  = new Tiny_Background_Optimize( $this->settings );
	}

	public function tear_down() {
		unset( $_POST['worker'] );
		$_COOKIE = array();
		parent::tear_down();
	}

	private function activate_workers( array $workers ) {
		foreach ( $workers as $worker ) {
			$this->wp->addTransient( Tiny_Background_Optimize::WORKER_TRANSIENT . $worker, time() );
		}
	}

	private function count_active_workers() {
		$active = 0;
		for ( $worker = 1; $worker <= Tiny_Background_Optimize::WORKERS; $worker++ ) {
			if ( get_transient( Tiny_Background_Optimize::WORKER_TRANSIENT . $worker ) ) {
				$active++;
			}
		}
		return $active;
	}

	private function sent_to_workers() {
		return array_column( array_column( $this->requests, 'body' ), 'worker' );
	}

	public function test_registers_worker_action() {
		$this->subject->ajax_init();

		$this->assertContains(
			array( 'wp_ajax_' . Tiny_Background_Optimize::WORKER_ACTION, array( $this->subject, 'work' ) ),
			$this->wp->getCalls( 'add_action' )
		);
	}

	public function test_registers_ping_action() {
		$this->subject->ajax_init();

		$this->assertContains(
			array( 'wp_ajax_' . Tiny_Background_Optimize::PING_ACTION, array( $this->subject, 'ping' ) ),
			$this->wp->getCalls( 'add_action' )
		);
	}

	public function test_worker_action_queues_and_starts_workers() {
		$this->subject->start( array( 7, 8 ) );

		$this->assertEquals( array( 7, 8 ), array_column( $this->wp->getCalls( 'add_post_meta' ), 0 ), 'meta data should be added when queue starts');
		$this->assertCount( Tiny_Background_Optimize::WORKERS, $this->requests, 'should have made 5 remote calls on start queue');
	}

	public function test_queue_clears_previous() {
		$this->subject->queue( array( 7 ) );

		$this->assertEquals(
			array(
				array( Tiny_Background_Optimize::META_KEY_STATUS ),
				array( Tiny_Background_Optimize::META_KEY_RESULT ),
			),
			$this->wp->getCalls( 'delete_post_meta_by_key' )
		);
	}

	public function test_queue_sets_status_queued_on_attachments() {
		$this->subject->queue( array( '7', '8', '7' ) );

		$this->assertEquals(
			array(
				array( 7, Tiny_Background_Optimize::META_KEY_STATUS, 'queued', true ),
				array( 8, Tiny_Background_Optimize::META_KEY_STATUS, 'queued', true ),
			),
			$this->wp->getCalls( 'add_post_meta' )
		);
	}

	public function test_queue_clears_workers_state() {
		$this->activate_workers( array( 1, 2, 3, 4, 5 ) );

		$this->subject->queue( array( 7 ) );

		$this->assertEquals( 0, $this->count_active_workers() );
	}

	public function test_start_marks_all_workers_active_before_sending_requests() {
		$this->subject->start( array( 7 ) );

		// A worker that finishes before the last request goes out must not see that
		// worker as dead and start it a second time.
		$this->assertEquals( Tiny_Background_Optimize::WORKERS, $this->requests[0]['active_workers'] );
	}

	public function test_start_workers_starts_available_workers() {
		// worker 4 failed to call its next request, so its transient expired
		$this->statuses = array( 7 => 'queued' );
		$this->settings->method( 'get_sizes' )->willThrowException( new Exception( 'Network error' ) );
		$this->activate_workers( array( 1, 2, 3, 5 ) );
		$_POST['worker'] = '2';

		$this->subject->work();

		// worker 2 sends its own next request and restarts worker 4, the live ones are left alone
		$this->assertEquals( array( 2, 4 ), $this->sent_to_workers() );
	}

	public function test_worker_makes_ajax_request_to_start_work() {
		// worker makes an ajax request to WORKER_ACTION which is hooked to work()
		$_COOKIE = array( 'wordpress_logged_in' => 'admin' );

		$this->subject->start( array( 7 ) );

		$request = $this->requests[0];
		$this->assertEquals( 'http://localhost/wp-admin/admin-ajax.php', $request['url'] );
		$this->assertEquals( Tiny_Background_Optimize::WORKER_ACTION, $request['body']['action'] );
		$this->assertEquals( 1, $request['body']['worker'] );
		$this->assertFalse( $request['blocking'] );
		// worker runs as the user who started it
		$this->assertEquals( $_COOKIE, $request['cookies'] );
	}

	public function test_worker_request_sends_cookies_without_the_slashes_wordpress_adds() {
		$_COOKIE = array( 'preferences' => '{\"theme\":\"dark\"}' );

		$this->subject->start( array( 7 ) );

		$this->assertEquals( array( 'preferences' => '{"theme":"dark"}' ), $this->requests[0]['cookies'] );
	}

	public function test_clear_deletes_queue_results_and_workers_state() {
		$this->activate_workers( array( 1, 2, 3, 4, 5 ) );

		Tiny_Background_Optimize::clear();

		$this->assertEquals(
			array(
				array( Tiny_Background_Optimize::META_KEY_STATUS ),
				array( Tiny_Background_Optimize::META_KEY_RESULT ),
			),
			$this->wp->getCalls( 'delete_post_meta_by_key' )
		);
		$this->assertEquals( 0, $this->count_active_workers() );
	}

	public function test_work_optimizes_attachment_and_starts_next_worker() {
		$this->statuses = array( 7 => 'queued' );
		// Compressing throws, which keeps Tiny_Image out of this test.
		$this->settings->expects( $this->once() )
			->method( 'get_sizes' )
			->willThrowException( new Exception( 'Network error' ) );
		$this->activate_workers( array( 1, 2, 3, 4, 5 ) );
		$_POST['worker'] = '2';

		$this->subject->work();

		$this->assertEquals( array( 7 => 'failed' ), $this->statuses );
		$this->assertEquals( array( 2 ), $this->sent_to_workers() );
	}

	public function test_work_starts_available_workers() {
		// when other worker has failed to call the next worker, start_workers will restart that worker
		$this->statuses = array( 7 => 'queued' );
		$this->settings->method( 'get_sizes' )->willThrowException( new Exception( 'Network error' ) );
		$this->activate_workers( array( 1, 2, 3 ) );
		$_POST['worker'] = '2';

		$this->subject->work();

		$this->assertEquals( array( 2, 4, 5 ), $this->sent_to_workers() );
	}

	public function test_when_no_attachment_available_clears_worker_transient() {
		$this->activate_workers( array( 2 ) );
		$_POST['worker'] = '2';

		$this->subject->work();

		$this->assertEquals( 0, $this->count_active_workers() );
		$this->assertEmpty( $this->requests );
	}
}
