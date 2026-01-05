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

	public function test_get_log_enabled_memoizes_log_enabled() {
		$this->wp->addOption('tinypng_logging_enabled', 'on');
		$logger = Tiny_Logger::get_instance();
		assertTrue($logger->get_log_enabled(), 'log should be enabled when tinypng_logging_enabled is on');
	}

	public function test_sets_log_path_on_construct() {
		$logger = Tiny_Logger::get_instance();
		assertEquals($logger->get_log_file_path(), 'vfs://root/wp-content/uploads/tiny-compress-logs/tiny-compress.log');
	}
}
