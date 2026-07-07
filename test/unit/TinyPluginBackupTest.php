<?php

require_once dirname(__FILE__) . '/TinyTestCase.php';

use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertTrue;

class Tiny_Plugin_Backup_Test extends Tiny_TestCase
{

	public function set_up()
	{
		parent::set_up();
	}

	public function test_init_adds_backup_image_size_action()
	{
		$tiny_plugin = new Tiny_Plugin();
		$tiny_plugin->init();

		// assert that backup is hooked into `tiny_image_before_compression`
		WordPressStubs::assertHook('tiny_image_before_compression', array($tiny_plugin, 'backup_original_image'));
	}

	public function test_will_copy_original_file_on_backup()
	{
		$this->wp->createImage(37857, '2026/04', 'testfile.png');
		$expected_backup = $this->vfs->url() . '/wp-content/uploads/tinify_backup/2026/04/testfile.png';

		$tiny_plugin = new Tiny_Plugin();

		$ref = new \ReflectionClass($tiny_plugin);
		$settings_prop = $ref->getProperty('settings');
		$settings_prop->setAccessible(true);
		$mock_settings = $this->createMock(Tiny_Settings::class);
		$mock_settings->method('get_backup_enabled')->willReturn(true);
		$settings_prop->setValue($tiny_plugin, $mock_settings);

		$this->wp->stub('wp_get_attachment_metadata', function ($i) {
			return array(
				'width' => 1256,
				'height' => 1256,
				'file' => '2026/04/testfile.png',
				'sizes' => array(),
			);
		});

		$backup_made = $tiny_plugin->backup_original_image(1);

		assertTrue($backup_made, 'expected backup to be made');
		assertTrue(file_exists($expected_backup), 'expected backup to be created');
	}

	public function test_no_backup_when_backup_exists()
	{
		$this->wp->createImage(37857, '2026/04', 'testfile.png');
		$expected_backup = $this->vfs->url() . '/wp-content/uploads/tinify_backup/2026/04/testfile.png';

		$tiny_plugin = new Tiny_Plugin();

		$ref = new \ReflectionClass($tiny_plugin);
		$settings_prop = $ref->getProperty('settings');
		$settings_prop->setAccessible(true);
		$mock_settings = $this->createMock(Tiny_Settings::class);
		$mock_settings->method('get_backup_enabled')->willReturn(true);
		$settings_prop->setValue($tiny_plugin, $mock_settings);

		$this->wp->stub('wp_get_attachment_metadata', function ($i) {
			return array(
				'width' => 1256,
				'height' => 1256,
				'file' => '2026/04/testfile.png',
				'sizes' => array(),
			);
		});

		$this->wp->createImage(37857, 'tinify_backup/2026/04', 'testfile.png');

		$backup_made = $tiny_plugin->backup_original_image(1);

		assertFalse($backup_made, 'expected backup not to be made');
		assertTrue(file_exists($expected_backup), 'expected backup to exist');
	}

	/**
	 * when the attachment file path contains the upload directory name as a path 
	 * segment, the relative path must be extracted using only the leading basedir prefix
	 */
	public function test_backup_preserves_upload_dir_name_in_relative_path()
	{
		$this->wp->createImage(37857, 'wp-content/uploads/2026/04', 'testfile.png');

		$tiny_plugin = new Tiny_Plugin();

		$ref = new \ReflectionClass($tiny_plugin);
		$settings_prop = $ref->getProperty('settings');
		$settings_prop->setAccessible(true);
		$mock_settings = $this->createMock(Tiny_Settings::class);
		$mock_settings->method('get_backup_enabled')->willReturn(true);
		$settings_prop->setValue($tiny_plugin, $mock_settings);

		$this->wp->stub('wp_get_attachment_metadata', function ($i) {
			return array(
				'width' => 1256,
				'height' => 1256,
				'file' => 'wp-content/uploads/2026/04/testfile.png',
				'sizes' => array(),
			);
		});

		$backup_made = $tiny_plugin->backup_original_image(1);

		assertTrue($backup_made, 'expected backup to be made');

		$expected_backup = $this->vfs->url() . '/wp-content/uploads/tinify_backup/wp-content/uploads/2026/04/testfile.png';
		assertTrue(file_exists($expected_backup), 'expected backup at path preserving the upload dir segment');
	}

	public function test_will_backup_unscaled_original_when_exists()
	{
		$this->wp->createImage(37857, '2026/04', 'testfile.png');
		$this->wp->createImage(37857, '2026/04', 'testfile-scaled.png');
		$expected_backup = $this->vfs->url() . '/wp-content/uploads/tinify_backup/2026/04/testfile-scaled.png';

		$tiny_plugin = new Tiny_Plugin();

		$ref = new \ReflectionClass($tiny_plugin);
		$settings_prop = $ref->getProperty('settings');
		$settings_prop->setAccessible(true);
		$mock_settings = $this->createMock(Tiny_Settings::class);
		$mock_settings->method('get_backup_enabled')->willReturn(true);
		$settings_prop->setValue($tiny_plugin, $mock_settings);

		$this->wp->stub('wp_get_attachment_metadata', function ($i) {
			return array(
				'width' => 1256,
				'height' => 1256,
				'file' => '2026/04/testfile.png',
				'original_image' => 'testfile-scaled.png',
				'sizes' => array(),
			);
		});

		$backup_made = $tiny_plugin->backup_original_image(1);

		assertTrue($backup_made, 'expected backup of unscaled original to be made');
		assertTrue(file_exists($expected_backup), 'expected backup of unscaled original to be created');
	}
}
