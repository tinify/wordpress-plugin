<?php

use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertTrue;

require_once dirname(__FILE__) . '/TinyTestCase.php';

class Tiny_Logger_Test extends Tiny_TestCase
{
	public function set_up()
	{
		parent::set_up();
	}

	public function tear_down()
	{
		// ensure we clear the logger on each test
		$logger = Tiny_Logger::get_instance();
		$logger->reset();
	}

	public function test_logger_always_has_one_instance()
	{
		$instance1 = Tiny_Logger::get_instance();
		$instance2 = Tiny_Logger::get_instance();
		assertEquals($instance1, $instance2, 'logger should be a singleton');
	}

	public function test_log_enabled_when_option_is_on() {
		$this->wp->addOption('tinypng_logging_enabled', 'on');
		$logger = Tiny_Logger::get_instance();
		assertTrue($logger->get_log_enabled(), 'log should be enabled when tinypng_logging_enabled is on');
	}
}
