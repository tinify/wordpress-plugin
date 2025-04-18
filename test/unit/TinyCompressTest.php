<?php

require_once dirname(__FILE__) . '/TinyTestCase.php';

class Tiny_Compress_Common extends Tiny_TestCase
{
	public function test_estimate_cost_free()
	{
		$this->assertEqualsWithDelta(
			150 * 0,
			Tiny_Compress::estimate_cost(150, 0),
			0.001
		);
	}

	public function test_estimate_cost_normal_and_free()
	{
		$this->assertEqualsWithDelta(
			350 * 0 + 2650 * 0.009,
			Tiny_Compress::estimate_cost(3000, 150),
			0.001
		);
	}

	public function test_estimate_cost_cheap_and_normal_and_free()
	{
		$this->assertEqualsWithDelta(
			500 * 0 + 9500 * 0.009 + 40000 * 0.002,
			Tiny_Compress::estimate_cost(50000, 0),
			0.001
		);
	}
}
