<?php

use function PHPUnit\Framework\assertEquals;

require_once dirname(__FILE__) . '/TinyTestCase.php';

class Tiny_Logger_Test extends Tiny_TestCase
{
	public function set_up()
	{
		parent::set_up();
	}

	public function test_logger_always_has_one_instance()
	{
		$instance1 = Tiny_Logger::get_instance();
		$instance2 = Tiny_Logger::get_instance();
		assertEquals($instance1, $instance2, 'logger should be a singleton');
	}
}
