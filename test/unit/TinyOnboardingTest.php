<?php

require_once dirname( __FILE__ ) . '/TinyTestCase.php';

class Tiny_Onboarding_Test extends Tiny_TestCase {
	public function test_on_activate_should_start_onboarding_for_new_site() {
		Tiny_Onboarding::on_activate();

		$this->assertEquals(
			array( array( 'tinypng_onboarding_status', 0 ) ),
			$this->wp->getCalls( 'update_option' )
		);
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_on_activate_should_not_start_onboarding_when_api_key_constant_is_defined() {
		define( 'TINY_API_KEY', 'key-from-wp-config' );

		Tiny_Onboarding::on_activate();

		$this->assertContains(
			array( 'tinypng_onboarding_status', 1 ),
			$this->wp->getCalls( 'update_option' )
		);
	}
}
