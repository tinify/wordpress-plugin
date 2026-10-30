<?php

require_once dirname(__FILE__) . '/TinyTestCase.php';

class Tiny_Dashboard_Test extends Tiny_TestCase
{
	/**
	 * @var Tiny_Settings|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $settings;

	/**
	 * @var Tiny_Background_Optimize|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $background_optimizer;

	public function set_up()
	{
		parent::set_up();
		$this->settings = $this->createMock(Tiny_Settings::class);
		$this->background_optimizer = $this->createMock(Tiny_Background_Optimize::class);
	}

	/**
	 * helper to build optimization stats
	 *
	 * @param int $uploaded number of uploaded images
	 * @param int $remaining number of images remaining
	 * @param integer $unoptimized_size
	 * @param integer $optimized_size
	 * @param int $credit_use estimated credits the remaining images need
	 * @return array optimization stats
	 */
	private function stats($uploaded, $remaining, $unoptimized_size = 0, $optimized_size = 0, $credit_use = 0)
	{
		return array(
			'uploaded-images' => $uploaded,
			'optimized-image-sizes' => 0,
			'available-unoptimized-sizes' => 0,
			'optimized-library-size' => $optimized_size,
			'unoptimized-library-size' => $unoptimized_size,
			'estimated_credit_use' => $credit_use,
			'available-for-optimization' => array_fill(0, $remaining, array('ID' => 1, 'post_title' => 'image')),
			'display-percentage' => 0,
		);
	}

	/**
	 * helper to connect an account
	 *
	 * @param int|false $remaining_credits credits left, false when unknown
	 * @param bool $free whether the account is on the free plan
	 */
	private function connect($remaining_credits = false, $free = true)
	{
		$this->settings->method('has_api_key')->willReturn(true);
		$this->settings->method('is_on_free_plan')->willReturn($free);
		$this->settings->method('get_remaining_credits')->willReturn($remaining_credits);
	}

	private function widget_data($stats)
	{
		$dashboard = new Tiny_Dashboard($this->settings, $this->background_optimizer);
		return $dashboard->get_widget_data($stats);
	}

	public function test_is_not_connected_without_api_key()
	{
		$this->settings->method('has_api_key')->willReturn(false);

		$data = $this->widget_data($this->stats(1250, 330));

		$this->assertSame('not_connected', $data['state']);
		$this->assertSame(920, $data['images_optimized']);
		$this->assertSame(1250, $data['images_total']);
		$this->assertSame(330, $data['images_remaining']);
		$this->assertSame(73, $data['percentage']);
	}

	public function test_is_pending_while_api_key_waits_for_activation()
	{
		$this->connect();
		$this->settings->method('refresh_api_key_pending')->willReturn(true);
		$this->settings->method('get_email_address')->willReturn('test@example.com');

		$data = $this->widget_data($this->stats(10, 10));

		$this->assertSame('pending', $data['state']);
		$this->assertSame('test@example.com', $data['email_address']);
	}

	public function test_is_empty_when_there_are_no_images()
	{
		$this->connect();

		$data = $this->widget_data($this->stats(0, 0));

		$this->assertSame('empty', $data['state']);
		$this->assertSame(0, $data['percentage']);
	}

	public function test_is_compressing_while_a_run_is_going()
	{
		$this->connect();
		$this->background_optimizer->method('is_running')->willReturn(true);

		$data = $this->widget_data($this->stats(10, 4));

		$this->assertSame('compressing', $data['state']);
	}

	public function test_is_done_when_no_images_remain()
	{
		$this->connect();

		$data = $this->widget_data($this->stats(1250, 0, 5000, 3000));

		$this->assertSame('done', $data['state']);
		$this->assertSame(100, $data['percentage']);
		$this->assertSame(2000, $data['bytes_saved']);
	}

	public function test_auto_compress_is_on_when_the_setting_is()
	{
		$this->connect();
		$this->settings->method('auto_compress_enabled')->willReturn(true);

		$data = $this->widget_data($this->stats(10, 0));

		$this->assertTrue($data['auto_compress']);
	}

	public function test_auto_compress_is_off_when_the_setting_is()
	{
		$this->connect();
		$this->settings->method('auto_compress_enabled')->willReturn(false);

		$data = $this->widget_data($this->stats(10, 0));

		$this->assertFalse($data['auto_compress']);
	}

	public function test_is_out_of_credits_when_none_are_left()
	{
		$this->connect(0);

		$data = $this->widget_data($this->stats(10, 4, 0, 0, 12));

		$this->assertSame('out_of_credits', $data['state']);
	}

	public function test_is_short_of_credits_when_they_do_not_cover_the_remaining_images()
	{
		$this->connect(10);

		$data = $this->widget_data($this->stats(10, 4, 0, 0, 12));

		$this->assertSame('credits_short', $data['state']);
		$this->assertSame(12, $data['estimated_credit_use']);
	}

	public function test_is_short_of_credits_when_they_do_not_cover_a_single_image()
	{
		$this->connect(1);

		// One image with two sizes needs 2 credits.
		$data = $this->widget_data($this->stats(1, 1, 0, 0, 2));

		$this->assertSame('credits_short', $data['state']);
		$this->assertSame(0, $data['images_affordable']);
	}

	public function test_is_ready_when_credits_cover_the_remaining_images()
	{
		$this->connect(12);

		$data = $this->widget_data($this->stats(10, 4, 0, 0, 12));

		$this->assertSame('ready', $data['state']);
	}

	public function test_is_ready_on_paid_plan_whatever_the_credits()
	{
		$this->connect(0, false);

		$data = $this->widget_data($this->stats(10, 4, 0, 0, 12));

		$this->assertSame('ready', $data['state']);
	}

	public function test_pending_beats_empty()
	{
		$this->connect();
		$this->settings->method('refresh_api_key_pending')->willReturn(true);

		$data = $this->widget_data($this->stats(0, 0));

		$this->assertSame('pending', $data['state']);
	}

	public function test_compressing_beats_done()
	{
		$this->connect();
		$this->background_optimizer->method('is_running')->willReturn(true);

		$data = $this->widget_data($this->stats(10, 0));

		$this->assertSame('compressing', $data['state']);
	}

	public function test_compressing_beats_out_of_credits()
	{
		$this->connect(0);
		$this->background_optimizer->method('is_running')->willReturn(true);

		$data = $this->widget_data($this->stats(10, 4, 0, 0, 12));

		$this->assertSame('compressing', $data['state']);
	}

	public function test_done_beats_out_of_credits()
	{
		$this->connect(0);

		$data = $this->widget_data($this->stats(10, 0));

		$this->assertSame('done', $data['state']);
	}

	public function test_does_not_show_100_percent_while_images_remain()
	{
		$this->connect();

		$data = $this->widget_data($this->stats(1000, 1));

		$this->assertSame(99, $data['percentage']);
	}

	public function test_bytes_saved_is_never_negative()
	{
		$this->connect();

		$data = $this->widget_data($this->stats(10, 3, 3000, 5000));

		$this->assertSame(0, $data['bytes_saved']);
	}

	public function test_shows_remaining_credits_on_free_plan()
	{
		$this->connect('210');

		$data = $this->widget_data($this->stats(10, 1));

		$this->assertSame(210, $data['remaining_credits']);
	}

	public function test_shows_remaining_credits_above_the_monthly_free_credits()
	{
		$this->connect(1200);

		$data = $this->widget_data($this->stats(10, 1));

		$this->assertSame(1200, $data['remaining_credits']);
	}

	public function test_shows_zero_remaining_credits()
	{
		$this->connect(0);

		$data = $this->widget_data($this->stats(10, 1));

		$this->assertSame(0, $data['remaining_credits']);
	}

	public function test_hides_remaining_credits_on_paid_plan()
	{
		$this->connect(210, false);

		$data = $this->widget_data($this->stats(10, 1));

		$this->assertNull($data['remaining_credits']);
	}

	public function test_hides_remaining_credits_when_unknown()
	{
		$this->connect(false);

		$data = $this->widget_data($this->stats(10, 1));

		$this->assertNull($data['remaining_credits']);
	}

	public function test_images_affordable_is_what_the_credits_cover_at_the_average_cost()
	{
		$this->connect(10);

		// 15 images need 30 credits, 2 each, so 10 credits cover 5 images.
		$data = $this->widget_data($this->stats(20, 15, 0, 0, 30));

		$this->assertSame(5, $data['images_affordable']);
	}

	public function test_images_affordable_is_zero_without_estimated_credit_use()
	{
		$this->connect(10);

		$data = $this->widget_data($this->stats(20, 15, 0, 0, 0));

		$this->assertSame(0, $data['images_affordable']);
	}

	public function test_images_affordable_is_zero_without_remaining_images()
	{
		$this->connect(10);

		$data = $this->widget_data($this->stats(20, 0, 0, 0, 0));

		$this->assertSame(0, $data['images_affordable']);
	}

	public function test_images_affordable_is_zero_without_limited_credits()
	{
		$this->connect(10, false);

		$data = $this->widget_data($this->stats(20, 15, 0, 0, 30));

		$this->assertSame(0, $data['images_affordable']);
	}

	public function test_seconds_left_spreads_compressions_over_the_workers()
	{
		$this->connect();
		$this->background_optimizer->method('is_running')->willReturn(true);

		$data = $this->widget_data($this->stats(30, 20, 0, 0, 1200));

		$this->assertSame(240, $data['seconds_left']);
	}

	public function test_widget_request_refuses_an_invalid_nonce_with_a_403()
	{
		$this->wp->stub('check_ajax_referer', function () {
			return false;
		});
		$this->wp->stub('current_user_can', function () {
			return true;
		});

		$this->assert_widget_request_refused_with_a_403();
	}

	public function test_widget_request_refuses_a_user_without_permission_with_a_403()
	{
		$this->wp->stub('current_user_can', function () {
			return false;
		});

		$this->assert_widget_request_refused_with_a_403();
	}

	private function assert_widget_request_refused_with_a_403()
	{
		$this->wp->addMethod('status_header');
		$this->wp->addMethod('wp_die');
		$this->wp->stub('wp_die', function () {
			throw new Exception('wp_die');
		});

		$dashboard = new Tiny_Dashboard($this->settings, $this->background_optimizer);
		try {
			$dashboard->ajax_dashboard_widget();
		} catch (Exception $e) {
			// wp_die() ends the request.
		}

		$this->assertEquals(array(array(403)), $this->wp->getCalls('status_header'));
		$this->assertEquals(array(array(-1, 403)), $this->wp->getCalls('wp_die'));
	}
}
