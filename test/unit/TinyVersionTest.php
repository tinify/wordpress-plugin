<?php

require_once dirname(__FILE__) . '/TinyTestCase.php';

/**
 * Tests for version consistency across plugin files.
 */
class TinyVersionTest extends Tiny_TestCase
{

	/**
	 * Plugin root directory path.
	 * @var string
	 */
	private $plugin_root;

	/**
	 * Set up test environment.
	 */
	public function set_up()
	{
		parent::set_up();
		$this->plugin_root = dirname(dirname(__DIR__));
	}

	/**
	 * Test that the version in readme.txt matches the version in tiny-compress-images.php.
	 */
	public function test_readme_version_matches_plugin_version()
	{
		$plugin_file = $this->plugin_root . '/tiny-compress-images.php';
		$readme_file = $this->plugin_root . '/readme.txt';

		$plugin_version = $this->get_version($plugin_file, '/Version:\s*(.+)/');
		$readme_version = $this->get_version($readme_file, '/Stable tag:\s*(.+)/');

		$this->assertEquals(
			$readme_version,
			$plugin_version,
			sprintf(
				'readme.txt has %s but tiny-compress-images.php has version %s',
				$readme_version,
				$plugin_version
			)
		);
	}


	/**
	 * Extract version number from a plugin file.
	 *
	 * @param string $file_path Path to the file
	 * @return string|null The version number or null if not found.
	 */
	private function get_version($file_path, $regex)
	{
		$content = file_get_contents($file_path);

		if (preg_match($regex, $content, $matches)) {
			return trim($matches[1]);
		}

		return null;
	}
}
