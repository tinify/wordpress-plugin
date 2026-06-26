<?php

require_once dirname(__FILE__) . '/TinyTestCase.php';
require_once dirname(__FILE__) . '/../../src/class-tiny-migrate.php';

class Tiny_Migrate_Test extends Tiny_TestCase
{

	public function set_up()
	{
		parent::set_up();
		$this->wp->stub('query', function() {
			return 1;
		});
	}

	/**
	 * Stubs $wpdb->query to return the given row counts on successive calls,
	 * simulating the batched UPDATE draining the table.
	 */
	private function queueQueryResults(array $results)
	{
		$index = 0;
		$this->wp->stub('query', function() use (&$index, $results) {
			$value = isset($results[$index]) ? $results[$index] : 0;
			$index++;
			return $value;
		});
	}

	/**
	 * Helper to check if a specific option update occurred.
	 */
	private function assertOptionWasUpdated($option, $value)
	{
		$calls = $this->wp->getCalls('update_option');
		foreach ($calls as $call) {
			if (isset($call[0], $call[1]) && $call[0] === $option && $call[1] === $value) {
				return $this->assertTrue(true);
			}
		}
		$this->fail("Failed asserting that option '$option' was updated to '$value'.");
	}

	public function test_run_skips_migration_when_db_version_is_current()
	{
		$this->wp->addOption(Tiny_Migrate::DB_VERSION_OPTION, Tiny_Migrate::DB_VERSION);

		Tiny_Migrate::run();

		$this->assertCount(0, $this->wp->getCalls('query'), 'Should not touch DB if version matches.');
	}

	public function test_run_performs_migration_and_updates_version()
	{
		Tiny_Migrate::run();

		$query_calls = $this->wp->getCalls('query');
		$this->assertCount(1, $query_calls);

		$sql = $query_calls[0][0];

		$this->assertStringContainsString('UPDATE wp_postmeta', $sql);
		$this->assertStringContainsString("SET meta_key = '_tiny_compress_images'", $sql);
		$this->assertStringContainsString("WHERE meta_key = 'tiny_compress_images'", $sql);
		$this->assertStringContainsString('LIMIT ' . 2500, $sql);

		$this->assertOptionWasUpdated(Tiny_Migrate::DB_VERSION_OPTION, Tiny_Migrate::DB_VERSION);
	}

	public function test_run_renames_meta_key_in_batches()
	{
		// Two full batches (2500 rows) followed by a partial batch end the loop.
		$this->queueQueryResults(array(2500, 2500, 42));

		Tiny_Migrate::run();

		$this->assertCount(3, $this->wp->getCalls('query'), 'Should keep batching until a partial batch is returned.');
		$this->assertOptionWasUpdated(Tiny_Migrate::DB_VERSION_OPTION, Tiny_Migrate::DB_VERSION);
	}

	public function test_get_tiny_metadata_reads_the_current_private_key()
	{
		$this->wp->updateMetadata(1, Tiny_Config::META_KEY, array('size' => 'current'));

		$this->assertEquals(array('size' => 'current'), Tiny_Image::get_tiny_metadata(1));
	}

	public function test_get_tiny_metadata_falls_back_to_legacy_key_before_migration()
	{
		// Data still lives under the old public key because the migration has
		// not run yet (or is still in flight / backed off).
		$this->wp->updateMetadata(1, Tiny_Config::LEGACY_META_KEY, array('size' => 'legacy'));

		$this->assertEquals(array('size' => 'legacy'), Tiny_Image::get_tiny_metadata(1));
	}

	public function test_get_tiny_metadata_prefers_current_key_over_legacy()
	{
		$this->wp->updateMetadata(1, Tiny_Config::LEGACY_META_KEY, array('size' => 'legacy'));
		$this->wp->updateMetadata(1, Tiny_Config::META_KEY, array('size' => 'current'));

		$this->assertEquals(array('size' => 'current'), Tiny_Image::get_tiny_metadata(1));
	}

	public function test_get_tiny_metadata_returns_empty_when_no_metadata_exists()
	{
		$this->assertEmpty(Tiny_Image::get_tiny_metadata(1));
	}

	public function test_run_flushes_object_cache_after_migrating()
	{
		Tiny_Migrate::run();

		$this->assertCount(1, $this->wp->getCalls('wp_cache_flush'), 'Object cache must be flushed so reads see the renamed key.');
	}

	public function test_run_does_not_flush_cache_when_migration_fails()
	{
		$this->wp->stub('query', function() { return false; });

		Tiny_Migrate::run();

		$this->assertCount(0, $this->wp->getCalls('wp_cache_flush'));
	}

	public function test_run_does_not_update_db_version_when_migration_fails()
	{
		$this->wp->stub('query', function() { return false; });

		Tiny_Migrate::run();

		$option_calls = $this->wp->getCalls('update_option');
		$version_updates = array_filter($option_calls, function($call) { return $call[0] === Tiny_Migrate::DB_VERSION_OPTION; });

		$this->assertEmpty($version_updates, 'Should not update DB version when migration fails.');
	}

	public function test_run_does_not_update_option_if_unnecessary()
	{
		$this->wp->addOption(Tiny_Migrate::DB_VERSION_OPTION, Tiny_Migrate::DB_VERSION);

		Tiny_Migrate::run();

		$this->assertEmpty($this->wp->getCalls('update_option'), 'Should not call update_option at all when version is already current.');
	}

	public function test_run_sets_backoff_transient_when_migration_fails()
	{
		$this->wp->stub('query', function() { return false; });

		Tiny_Migrate::run();

		$set_transient_calls = $this->wp->getCalls('set_transient');
		$this->assertCount(1, $set_transient_calls, 'A backoff transient should be set after a failed migration.');
		$this->assertEquals(Tiny_Migrate::MIGRATION_BACKOFF_KEY, $set_transient_calls[0][0]);
		$this->assertEquals(HOUR_IN_SECONDS, $set_transient_calls[0][2]);
	}

	public function test_run_skips_migration_when_backoff_transient_is_set()
	{
		$this->wp->stub('get_transient', function($key) {
			return Tiny_Migrate::MIGRATION_BACKOFF_KEY === $key ? 1 : false;
		});

		Tiny_Migrate::run();

		$this->assertCount(0, $this->wp->getCalls('query'), 'DB update should not be attempted during the backoff period.');
	}
}
