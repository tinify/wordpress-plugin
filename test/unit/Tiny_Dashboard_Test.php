<?php

require_once dirname(__FILE__) . '/TinyTestCase.php';

class Tiny_Dashboard_Test extends Tiny_TestCase
{
	/**
	 * @var Tiny_Settings|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $settings;

	public function set_up()
	{
		parent::set_up();
		$this->settings = $this->createMock(Tiny_Settings::class);
	}

	/**
	 * helper to build optimization stats
	 *
	 * @param int $uploaded number of uploaded images
	 * @param int $remaining number of images remaining
	 * @param integer $unoptimized_size
	 * @param integer $optimized_size
	 * @return void
	 */
	private function stats($uploaded, $remaining, $unoptimized_size = 0, $optimized_size = 0)
	{
		return array(
			'uploaded-images' => $uploaded,
			'optimized-image-sizes' => 0,
			'available-unoptimized-sizes' => 0,
			'optimized-library-size' => $optimized_size,
			'unoptimized-library-size' => $unoptimized_size,
			'estimated_credit_use' => 0,
			'available-for-optimization' => array_fill(0, $remaining, array('ID' => 1, 'post_title' => 'image')),
			'display-percentage' => 0,
		);
	}

	public function test_is_empty_when_there_are_no_images()
	{
		$this->settings->method('is_on_free_plan')->willReturn(true);

		$dashboard = new Tiny_Dashboard($this->settings);
		$data = $dashboard->get_widget_data(
			$this->stats(0, 0),
			0,
			false
		);

		$this->assertSame('empty', $data['status']);
		$this->assertSame(0, $data['percentage']);
		$this->assertSame(0, $data['images_remaining']);
		$this->assertSame('panda-waiting.png', $data['panda']);
	}

	public function test_is_in_progress_when_images_remain()
	{
		$this->settings->method('is_on_free_plan')->willReturn(true);

		$dashboard = new Tiny_Dashboard($this->settings);
		$data = $dashboard->get_widget_data(
			$this->stats(1250, 330),
			0,
			false
		);

		$this->assertSame('in_progress', $data['status']);
		$this->assertSame(920, $data['images_optimized']);
		$this->assertSame(1250, $data['images_total']);
		$this->assertSame(330, $data['images_remaining']);
		$this->assertSame(73, $data['percentage']);
		$this->assertSame('panda-waiting.png', $data['panda']);
	}

	public function test_is_done_when_no_images_remain()
	{
		$this->settings->method('is_on_free_plan')->willReturn(true);

		$dashboard = new Tiny_Dashboard($this->settings);
		$data = $dashboard->get_widget_data(
			$this->stats(1250, 0),
			0,
			false
		);

		$this->assertSame('done', $data['status']);
		$this->assertSame(1250, $data['images_optimized']);
		$this->assertSame(100, $data['percentage']);
		$this->assertSame('optimized', $data['label']);
		$this->assertSame('panda-laying.png', $data['panda']);
	}

	public function test_labels_up_to_half_as_getting_started()
	{
		$this->settings->method('is_on_free_plan')->willReturn(true);

		$dashboard = new Tiny_Dashboard($this->settings);

		$one_percent = $dashboard->get_widget_data(
			$this->stats(100, 99),
			0,
			false
		);
		$fifty_percent = $dashboard->get_widget_data(
			$this->stats(100, 50),
			0,
			false
		);

		$this->assertSame('getting started', $one_percent['label']);
		$this->assertSame('getting started', $fifty_percent['label']);
	}

	public function test_labels_above_half_as_halfway_there()
	{
		$this->settings->method('is_on_free_plan')->willReturn(true);

		$dashboard = new Tiny_Dashboard($this->settings);

		$fifty_one_percent = $dashboard->get_widget_data(
			$this->stats(100, 49),
			0,
			false
		);
		$seventy_five_percent = $dashboard->get_widget_data(
			$this->stats(100, 25),
			0,
			false
		);

		$this->assertSame('halfway there', $fifty_one_percent['label']);
		$this->assertSame('halfway there', $seventy_five_percent['label']);
	}

	public function test_labels_above_three_quarters_as_almost_there()
	{
		$this->settings->method('is_on_free_plan')->willReturn(true);

		$dashboard = new Tiny_Dashboard($this->settings);

		$seventy_six_percent = $dashboard->get_widget_data(
			$this->stats(100, 24),
			0,
			false
		);
		$ninety_nine_percent = $dashboard->get_widget_data(
			$this->stats(1000, 1),
			0,
			false
		);

		$this->assertSame('almost there', $seventy_six_percent['label']);
		$this->assertSame('almost there', $ninety_nine_percent['label']);
	}

	public function test_has_no_label_when_nothing_is_optimized()
	{
		$this->settings->method('is_on_free_plan')->willReturn(true);

		$dashboard = new Tiny_Dashboard($this->settings);
		$data = $dashboard->get_widget_data(
			$this->stats(10, 10),
			0,
			false
		);

		$this->assertSame('', $data['label']);
	}

	public function test_does_not_show_100_percent_while_images_remain()
	{
		$this->settings->method('is_on_free_plan')->willReturn(true);

		$dashboard = new Tiny_Dashboard($this->settings);
		$data = $dashboard->get_widget_data(
			$this->stats(1000, 1),
			0,
			false
		);

		$this->assertSame('in_progress', $data['status']);
		$this->assertSame(99, $data['percentage']);
	}

	public function test_bytes_saved_is_the_library_size_reduction()
	{
		$this->settings->method('is_on_free_plan')->willReturn(true);

		$dashboard = new Tiny_Dashboard($this->settings);
		$data = $dashboard->get_widget_data(
			$this->stats(10, 3, 5000, 3000),
			0,
			false
		);

		$this->assertSame(2000, $data['bytes_saved']);
	}

	public function test_bytes_saved_is_never_negative()
	{
		$this->settings->method('is_on_free_plan')->willReturn(true);

		$dashboard = new Tiny_Dashboard($this->settings);
		$data = $dashboard->get_widget_data(
			$this->stats(10, 3, 3000, 5000),
			0,
			false
		);

		$this->assertSame(0, $data['bytes_saved']);
	}

	public function test_shows_remaining_credits_on_free_plan()
	{
		$this->settings->method('is_on_free_plan')->willReturn(true);

		$dashboard = new Tiny_Dashboard($this->settings);
		$data = $dashboard->get_widget_data(
			$this->stats(10, 1),
			'210',
			true
		);

		$this->assertSame(210, $data['remaining_credits']);
	}

	public function test_hides_remaining_credits_on_paid_plan()
	{
		$this->settings->method('is_on_free_plan')->willReturn(false);

		$dashboard = new Tiny_Dashboard($this->settings);
		$data = $dashboard->get_widget_data(
			$this->stats(10, 1),
			210,
			true
		);

		$this->assertNull($data['remaining_credits']);
	}

	public function test_hides_remaining_credits_when_unknown()
	{
		$this->settings->method('is_on_free_plan')->willReturn(true);

		$dashboard = new Tiny_Dashboard($this->settings);
		$data = $dashboard->get_widget_data(
			$this->stats(10, 1),
			false,
			true
		);

		$this->assertNull($data['remaining_credits']);
	}

	public function test_has_no_notice_with_enough_credits()
	{
		$this->settings->method('is_on_free_plan')->willReturn(true);

		$dashboard = new Tiny_Dashboard($this->settings);
		$data = $dashboard->get_widget_data(
			$this->stats(10, 1),
			100,
			true
		);

		$this->assertNull($data['notice']);
	}

	public function test_notifies_low_credits_below_100()
	{
		$this->settings->method('is_on_free_plan')->willReturn(true);

		$dashboard = new Tiny_Dashboard($this->settings);
		$just_below = $dashboard->get_widget_data(
			$this->stats(10, 1),
			99,
			true
		);
		$none_left = $dashboard->get_widget_data(
			$this->stats(10, 1),
			0,
			true
		);

		$this->assertSame('low_credits', $just_below['notice']);
		$this->assertSame('low_credits', $none_left['notice']);
	}

	public function test_does_not_notify_low_credits_on_paid_plan()
	{
		$this->settings->method('is_on_free_plan')->willReturn(false);

		$dashboard = new Tiny_Dashboard($this->settings);
		$data = $dashboard->get_widget_data(
			$this->stats(10, 1),
			0,
			true
		);

		$this->assertNull($data['notice']);
	}

	public function test_notifies_missing_api_key()
	{
		$this->settings->method('is_on_free_plan')->willReturn(false);

		$dashboard = new Tiny_Dashboard($this->settings);
		$data = $dashboard->get_widget_data(
			$this->stats(10, 1),
			false,
			false
		);

		$this->assertSame('no_api_key', $data['notice']);
	}

	public function test_missing_api_key_takes_precedence_over_low_credits()
	{
		$this->settings->method('is_on_free_plan')->willReturn(true);

		$dashboard = new Tiny_Dashboard($this->settings);
		$data = $dashboard->get_widget_data(
			$this->stats(10, 1),
			10,
			false
		);

		$this->assertSame('no_api_key', $data['notice']);
	}
}
