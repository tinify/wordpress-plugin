<?php

require_once dirname(__FILE__) . '/TinyTestCase.php';
require_once dirname(__FILE__) . '/../../src/class-tiny-migrate.php';

class Tiny_Migrate_Test extends Tiny_TestCase
{

	public function set_up()
	{
		parent::set_up();
		$this->wp->stub('update', 1);
	}

	/**
	 * Helper to check if a specific option update occurred.
	 */
	private function assertOptionWasUpdated($option, $value)
	{
		$calls = $this->wp->getCalls('update_option');
		foreach ($calls as $call) {
			if (($call[0] ?? null) === $option && ($call[1] ?? null) === $value) {
				return $this->assertTrue(true);
			}
		}
		$this->fail("Failed asserting that option '$option' was updated to '$value'.");
	}

	public function test_run_skips_migration_when_db_version_is_current()
	{
		$this->wp->addOption(Tiny_Migrate::DB_VERSION_OPTION, Tiny_Migrate::DB_VERSION);

		Tiny_Migrate::run();

		$this->assertCount(0, $this->wp->getCalls('update'), 'Should not touch DB if version matches.');
	}

	public function test_run_performs_migration_and_updates_version()
	{
		Tiny_Migrate::run();

		$update_calls = $this->wp->getCalls('update');
		$this->assertCount(1, $update_calls);

		list($table, $data, $where) = $update_calls[0];

		$this->assertEquals('wp_postmeta', $table);
		$this->assertEquals(['meta_key' => '_tiny_compress_images'], $data);
		$this->assertEquals(['meta_key' => 'tiny_compress_images'], $where);

		$this->assertOptionWasUpdated(Tiny_Migrate::DB_VERSION_OPTION, Tiny_Migrate::DB_VERSION);
	}

	public function test_run_does_not_update_db_version_when_migration_fails()
	{
		$this->wp->stub('update', function() { return false; });

		Tiny_Migrate::run();

		$option_calls = $this->wp->getCalls('update_option');
		$version_updates = array_filter($option_calls, fn($call) => $call[0] === Tiny_Migrate::DB_VERSION_OPTION);

		$this->assertEmpty($version_updates, 'Should not update DB version when migration fails.');
	}

	public function test_run_does_not_update_option_if_unnecessary()
	{
		$this->wp->addOption(Tiny_Migrate::DB_VERSION_OPTION, Tiny_Migrate::DB_VERSION);

		Tiny_Migrate::run();

		$option_calls = $this->wp->getCalls('update_option');
		$version_updates = array_filter($option_calls, fn($call) => $call[0] === Tiny_Migrate::DB_VERSION_OPTION);

		$this->assertEmpty($version_updates, 'Should not re-save the version if already current.');
	}
}
