<?php

require_once dirname(__FILE__) . '/TinyTestCase.php';

class Tiny_Notices_Test extends Tiny_TestCase
{
	protected $subject;
	protected $settings;

	public function set_up()
	{
		parent::set_up();

		$this->wp->addMethod( 'get_user_meta' );
		$this->wp->addMethod( 'update_user_meta' );
		$this->wp->addMethod( 'get_current_user_id' );

		$this->settings = $this->createMock( Tiny_Settings::class );
		$this->settings->method( 'get_compression_count' )->willReturn( 0 );

		$this->wp->stub( 'current_user_can', function () {
			return true;
		} );

		$this->subject = new Tiny_Notices( $this->settings );
	}

	/**
	 * Verifies that when the current user has the manage_options capability,
	 * calling admin_init() registers at least one admin_notices action.
	 */
	public function test_registers_notices_when_user_can_manage_options() {
		$this->subject->add( 'test', 'Test notice message' );

		$this->subject->admin_init();

		WordPressStubs::assertHook( 'admin_notices' );
	}

	/**
	 * Verifies that feedback_notice_show is hooked to admin_notices when the
	 * feedback notice has not been dismissed
	 */
	public function test_registers_feedback_notice_when_not_dismissed() {
		$this->settings = $this->createMock( Tiny_Settings::class );
		$this->settings->method( 'get_compression_count' )->willReturn( 20 );
		$this->subject = new Tiny_Notices( $this->settings );

		$this->subject->show_notices();

		WordPressStubs::assertHook( 'admin_notices', array( $this->subject, 'feedback_notice_show' ) );
	}

	/**
	 * Verifies that feedback_notice_show is hooked to admin_notices when the
	 * compression count is just above compressions_for_feedback
	 */
	public function test_registers_feedback_notice_when_compressioncount_reached() {
		$this->settings = $this->createMock( Tiny_Settings::class );
		$this->settings->method( 'get_compression_count' )->willReturn( 11 );
		$this->subject = new Tiny_Notices( $this->settings );

		$this->subject->show_notices();

		WordPressStubs::assertHook( 'admin_notices', array( $this->subject, 'feedback_notice_show' ) );
	}


	/**
	 * Verifies that feedback_notice_show is NOT hooked to admin_notices when
	 * the feedback notice has been dismissed by the user.
	 */
	public function test_will_not_show_feedback_notice_when_dismissed() {
		$this->settings = $this->createMock( Tiny_Settings::class );
		$this->subject = new Tiny_Notices( $this->settings );

		$this->wp->stub( 'get_user_meta', function () {
			return array( 'feedback' => true );
		} );
		
		$this->subject->admin_init();

		WordPressStubs::assertNotHook( 'admin_notices', array( $this->subject, 'feedback_notice_show' ) );
	}

}
