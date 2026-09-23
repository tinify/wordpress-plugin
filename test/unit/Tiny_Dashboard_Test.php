<?php

require_once dirname(__FILE__) . '/TinyTestCase.php';

class Tiny_Dashboard_Test extends Tiny_TestCase
{
	/**
	 * Builds the widget data
	 */
	private function widget_data($uploaded, $remaining, $unoptimized_size = 0, $optimized_size = 0)
	{
		return Tiny_Dashboard::get_widget_data(array(
			'uploaded-images' => $uploaded,
			'optimized-image-sizes' => 0,
			'available-unoptimized-sizes' => 0,
			'optimized-library-size' => $optimized_size,
			'unoptimized-library-size' => $unoptimized_size,
			'estimated_credit_use' => 0,
			'available-for-optimization' => array_fill(0, $remaining, array('ID' => 1, 'post_title' => 'image')),
			'display-percentage' => 0,
		));
	}

	public function test_is_empty_when_there_are_no_images()
	{
		$data = $this->widget_data(0, 0);

		$this->assertSame('empty', $data['status']);
		$this->assertSame(0, $data['percentage']);
		$this->assertSame(0, $data['images_remaining']);
		$this->assertSame('panda-waiting.png', $data['panda']);
	}

	public function test_is_in_progress_when_images_remain()
	{
		$data = $this->widget_data(1250, 330);

		$this->assertSame('in_progress', $data['status']);
		$this->assertSame(920, $data['images_optimized']);
		$this->assertSame(1250, $data['images_total']);
		$this->assertSame(330, $data['images_remaining']);
		$this->assertSame(73, $data['percentage']);
		$this->assertSame('panda-waiting.png', $data['panda']);
	}

	public function test_is_done_when_no_images_remain()
	{
		$data = $this->widget_data(1250, 0);

		$this->assertSame('done', $data['status']);
		$this->assertSame(1250, $data['images_optimized']);
		$this->assertSame(100, $data['percentage']);
		$this->assertSame('optimized', $data['label']);
		$this->assertSame('panda-laying.png', $data['panda']);
	}

	public function test_labels_below_half_as_keep_going()
	{
		$this->assertSame('getting started', $this->widget_data(100, 51)['label']);
	}

	public function test_labels_half_and_above_as_almost_there()
	{
		$this->assertSame('getting started', $this->widget_data(100, 50)['label']);
	}

	public function test_has_no_label_when_nothing_is_optimized()
	{
		$this->assertSame('', $this->widget_data(10, 10)['label']);
	}

	public function test_does_not_show_100_percent_while_images_remain()
	{
		$data = $this->widget_data(1000, 1);

		$this->assertSame('in_progress', $data['status']);
		$this->assertSame(99, $data['percentage']);
	}

	public function test_bytes_saved_is_the_library_size_reduction()
	{
		$this->assertSame(2000, $this->widget_data(10, 3, 5000, 3000)['bytes_saved']);
	}

	public function test_bytes_saved_is_never_negative()
	{
		$this->assertSame(0, $this->widget_data(10, 3, 3000, 5000)['bytes_saved']);
	}

	private function account_data($remaining_credits, $paying_state, $has_api_key = true)
	{
		return Tiny_Dashboard::get_widget_data(array(
			'uploaded-images' => 10,
			'optimized-image-sizes' => 0,
			'available-unoptimized-sizes' => 0,
			'optimized-library-size' => 0,
			'unoptimized-library-size' => 0,
			'estimated_credit_use' => 0,
			'available-for-optimization' => array(array('ID' => 1, 'post_title' => 'image')),
			'display-percentage' => 0,
		), $remaining_credits, $paying_state, $has_api_key);
	}

	public function test_shows_remaining_credits_on_free_plan()
	{
		$this->assertSame(210, $this->account_data('210', 'free')['remaining_credits']);
	}

	public function test_hides_remaining_credits_on_paid_plan()
	{
		$this->assertNull($this->account_data(210, 'paid')['remaining_credits']);
	}

	public function test_hides_remaining_credits_when_unknown()
	{
		$this->assertNull($this->account_data(false, 'free')['remaining_credits']);
		$this->assertNull($this->account_data(210, false)['remaining_credits']);
	}

	public function test_has_no_notice_with_enough_credits()
	{
		$this->assertNull($this->account_data(100, 'free')['notice']);
	}

	public function test_notifies_low_credits_below_100()
	{
		$this->assertSame('low_credits', $this->account_data(99, 'free')['notice']);
		$this->assertSame('low_credits', $this->account_data(0, 'free')['notice']);
	}

	public function test_does_not_notify_low_credits_on_paid_plan()
	{
		$this->assertNull($this->account_data(0, 'paid')['notice']);
	}

	public function test_notifies_missing_api_key()
	{
		$this->assertSame('no_api_key', $this->account_data(false, false, false)['notice']);
	}

	public function test_missing_api_key_takes_precedence_over_low_credits()
	{
		$this->assertSame('no_api_key', $this->account_data(10, 'free', false)['notice']);
	}
}
