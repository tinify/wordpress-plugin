<?php

require_once dirname(__FILE__) . '/TinyTestCase.php';

use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertTrue;
use function PHPUnit\Framework\assertEquals;

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

	public function test_restore_backup_returns_false_when_no_backup_exists()
	{
		$this->wp->createImage( 37857, '2026/04', 'testfile.png' );

		$wp_metadata   = array(
			'file'  => '2026/04/testfile.png',
			'sizes' => array(),
		);
		$mock_settings = $this->createMock( Tiny_Settings::class );
		$tiny_image    = new Tiny_Image( $mock_settings, 1, $wp_metadata, null, array(), array() );

		$result = $tiny_image->restore_backup();

		assertFalse( $result, 'expected restore to return false when no backup exists' );
	}

	public function test_restore_backup_restores_file_from_backup()
	{
		// Create only the directory (via a placeholder) so the restore can write testfile.png
		// without having to overwrite a LargeFileContent vfsStream file, which is read-only.
		$this->wp->createImage( 1, '2026/04', '_placeholder.png' );
		$this->wp->createImage( 100000, 'tinify_backup/2026/04', 'testfile.png' );

		$this->wp->stub( 'wp_generate_attachment_metadata', function ( $id, $file ) {
			return array(
				'file'  => '2026/04/testfile.png',
				'sizes' => array(),
			);
		} );

		$wp_metadata   = array(
			'file'  => '2026/04/testfile.png',
			'sizes' => array(),
		);
		$mock_settings = $this->createMock( Tiny_Settings::class );
		$tiny_image    = new Tiny_Image( $mock_settings, 1, $wp_metadata, null, array(), array() );

		$result = $tiny_image->restore_backup();

		$original_path = $this->vfs->url() . '/wp-content/uploads/2026/04/testfile.png';
		assertTrue( $result, 'expected restore to return true' );
		assertEquals( 100000, filesize( $original_path ), 'expected original file to be overwritten with backup content' );
	}

	public function test_restore_backup_clears_all_sizes_metadata()
	{
		// Create only thumbnail file (not testfile.png) so the restore writes testfile.png fresh.
		$this->wp->createImage( 5000, '2026/04', 'testfile-150x150.png' );
		$this->wp->createImage( 100000, 'tinify_backup/2026/04', 'testfile.png' );

		$this->wp->stub( 'wp_generate_attachment_metadata', function ( $id, $file ) {
			return array(
				'file'  => '2026/04/testfile.png',
				'sizes' => array(),
			);
		} );

		$wp_metadata = array(
			'file'  => '2026/04/testfile.png',
			'sizes' => array(
				'thumbnail' => array( 'file' => 'testfile-150x150.png', 'width' => 150, 'height' => 150 ),
			),
		);
		$tiny_metadata = array(
			Tiny_Image::ORIGINAL => array( 'input' => array( 'size' => 37857 ), 'output' => array( 'size' => 30000 ) ),
			'thumbnail'          => array( 'input' => array( 'size' => 5000 ), 'output' => array( 'size' => 4000 ) ),
		);
		$mock_settings = $this->createMock( Tiny_Settings::class );
		$mock_settings->method( 'get_sizes' )->willReturn( array() );
		$mock_settings->method( 'get_active_tinify_sizes' )->willReturn( array() );
		$tiny_image    = new Tiny_Image( $mock_settings, 1, $wp_metadata, $tiny_metadata );

		$tiny_image->restore_backup();

		assertEquals( array(), $tiny_image->get_image_size( Tiny_Image::ORIGINAL )->meta, 'expected original size metadata to be cleared' );
		assertEquals( array(), $tiny_image->get_image_size( 'thumbnail' )->meta, 'expected thumbnail size metadata to be cleared' );
	}

	public function test_clean_attachment_deletes_backup_file()
	{
		$this->wp->createImage( 37857, '2026/04', 'testfile.png' );
		$this->wp->createImage( 100000, 'tinify_backup/2026/04', 'testfile.png' );
		$backup_path = $this->vfs->url() . '/wp-content/uploads/tinify_backup/2026/04/testfile.png';

		$this->wp->stub( 'wp_get_attachment_metadata', function ( $i ) {
			return array(
				'file'  => '2026/04/testfile.png',
				'sizes' => array(),
			);
		} );

		$tiny_plugin   = new Tiny_Plugin();
		$ref           = new \ReflectionClass( $tiny_plugin );
		$settings_prop = $ref->getProperty( 'settings' );
		$settings_prop->setAccessible( true );
		$mock_settings = $this->createMock( Tiny_Settings::class );
		$settings_prop->setValue( $tiny_plugin, $mock_settings );

		assertTrue( file_exists( $backup_path ), 'expected backup to exist before clean_attachment' );
		$tiny_plugin->clean_attachment( 1 );
		assertFalse( file_exists( $backup_path ), 'expected backup to be deleted after clean_attachment' );
	}

	public function test_ajax_init_adds_restore_backup_action()
	{
		$tiny_plugin = new Tiny_Plugin();
		$tiny_plugin->ajax_init();

		WordPressStubs::assertHook( 'wp_ajax_tiny_restore_backup', array( $tiny_plugin, 'restore_backup_image' ) );
	}
}
