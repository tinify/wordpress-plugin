<?php

use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertTrue;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\content\LargeFileContent;

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
		$logger->clear_logs();
		$logger->reset();
	}

	public function test_logger_always_has_one_instance()
	{
		$instance1 = Tiny_Logger::get_instance();
		$instance2 = Tiny_Logger::get_instance();
		assertEquals($instance1, $instance2, 'logger should be a singleton');
	}

	public function test_get_log_enabled_memoizes_log_enabled()
	{
		$this->wp->addOption('tinypng_logging_enabled', 'on');
		$logger = Tiny_Logger::get_instance();
		assertTrue($logger->get_log_enabled(), 'log should be enabled when tinypng_logging_enabled is on');
	}

	public function test_sets_log_path_on_construct()
	{
		$logger = Tiny_Logger::get_instance();
		assertEquals($logger->get_log_file_path(), 'vfs://root/wp-content/uploads/tiny-compress-logs/tiny-compress.log');
	}

	public function test_registers_save_update_when_log_enabled()
	{
		$logger = Tiny_Logger::get_instance();
		$logger->init();
		WordPressStubs::assertHook('pre_update_option_tinypng_logging_enabled', 'Tiny_Logger::on_save_log_enabled');
	}

	public function test_option_hook_updates_log_enabled()
	{
		$this->wp->addOption('tinypng_logging_enabled', false);
		Tiny_Logger::init();
		$logger = Tiny_Logger::get_instance();

		assertFalse($logger->get_log_enabled(), 'option is not set so should be false');

		apply_filters('pre_update_option_tinypng_logging_enabled', 'on', null, '');

		assertTrue($logger->get_log_enabled(), 'when option is updated, should be true');
	}

	public function test_will_not_log_if_disabled()
	{
		$this->wp->addOption('tinypng_logging_enabled', false);
		$logger = Tiny_Logger::get_instance();

		Tiny_Logger::error('This should not be logged');
		Tiny_Logger::debug('This should also not be logged');

		$log_path = $logger->get_log_file_path();
		$log_exists = file_exists($log_path);
		assertFalse($log_exists, 'log file should not exist when logging is disabled');
	}

	public function test_creates_log_when_log_is_enabled()
	{
		$this->wp->addOption('tinypng_logging_enabled', 'on');

		$logger = Tiny_Logger::get_instance();
		$log_path = $logger->get_log_file_path();
		$log_exists = file_exists($log_path);
		assertFalse($log_exists, 'log file should not exist initially');

		Tiny_Logger::error('This should be logged');
		Tiny_Logger::debug('This should also be logged');

		$log_path = $logger->get_log_file_path();
		$log_exists = file_exists($log_path);
		assertTrue($log_exists, 'log file is created when logging is enabled');
	}

	public function test_removes_full_log_and_creates_new()
	{
		$this->wp->addOption('tinypng_logging_enabled', 'on');
		
		$log_dir_path = 'wp-content/uploads/tiny-compress-logs';
		vfsStream::newDirectory($log_dir_path)->at($this->vfs);
		$log_dir = $this->vfs->getChild($log_dir_path);
		
		vfsStream::newFile('tiny-compress.log')
			->withContent(LargeFileContent::withMegabytes(5.1))
			->at($log_dir);
	
		$logger = Tiny_Logger::get_instance();

		assertTrue(filesize($logger->get_log_file_path()) > 5242880, 'log file should be larger than 5MB');

		Tiny_Logger::error('This should be logged');

		assertTrue(filesize($logger->get_log_file_path()) < 1048576, 'log file rotated and less than 1MB');
	}
}
