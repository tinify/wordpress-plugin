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

		$this->wp->stub( 'wp_create_image_subsizes', function ( $id, $file ) {
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
		$this->wp->createImage( 65400, '2026/04', 'testfile-150x150.png' );
		$this->wp->createImage( 123000, 'tinify_backup/2026/04', 'testfile.png' );

		$this->wp->stub( 'wp_create_image_subsizes', function ( $id, $file ) {
			return array(
				'file'  => '2026/04/testfile.png',
				'sizes' => array(
					'thumbnail' => array(
						'file' => '2026/04/testfile-150x150.png',
					)
				),
			);
		} );

		$wp_metadata = array(
			'file'  => '2026/04/testfile.png',
			'sizes' => array(
				'thumbnail' => array( 'file' => 'testfile-150x150.png', 'width' => 150, 'height' => 150 ),
			),
		);
		$mock_settings = $this->createMock( Tiny_Settings::class );
		$mock_settings->method( 'get_sizes' )->willReturn( array(
			Tiny_Image::ORIGINAL,
			'thumbnail',
		) );
		$mock_settings->method( 'get_active_tinify_sizes' )->willReturn( array(
			Tiny_Image::ORIGINAL,
			'thumbnail',
		) );
		$tiny_metadata = array(
			Tiny_Image::ORIGINAL => array( 'input' => array( 'size' => 37857 ), 'output' => array( 'size' => 30000 ) ),
			'thumbnail'          => array( 'input' => array( 'size' => 5000 ), 'output' => array( 'size' => 4000 ) ),
		);
		$tiny_image    = new Tiny_Image( $mock_settings, 1, $wp_metadata, $tiny_metadata );

		assertEquals($tiny_image->get_image_size( 'thumbnail' )->filesize(), 65400, 'thumbnail before restoring'); 
		
		// wp_generate_attachment_metadata will regenerate new thumbnails, mock this by creating
		// a new thumbnail on vfs
		// https://developer.wordpress.org/reference/functions/wp_generate_attachment_metadata/
		$this->wp->createImage( 123654, '2026/04', 'testfile-150x150.png' );

		$tiny_image->restore_backup();

		assertEquals($tiny_image->get_image_size( 'thumbnail' )->filesize(), 123654, 'thumbnail before restoring'); 
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

	public function test_clean_attachment_keeps_backup_file_when_another_attachment_uses_the_file()
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

		/* Attachment 2 uses the same file, like a WPML or Polylang translation. */
		$this->wp->updateMetadata( 1, '_wp_attached_file', '2026/04/testfile.png' );
		$this->wp->stub( 'get_posts', function ( $args ) {
			$same_file = '2026/04/testfile.png' === $args['meta_value'];
			$excluded  = array( 1 ) === $args['post__not_in'];
			return $same_file && $excluded ? array( 2 ) : array();
		} );

		$tiny_plugin   = new Tiny_Plugin();
		$ref           = new \ReflectionClass( $tiny_plugin );
		$settings_prop = $ref->getProperty( 'settings' );
		$settings_prop->setAccessible( true );
		$mock_settings = $this->createMock( Tiny_Settings::class );
		$settings_prop->setValue( $tiny_plugin, $mock_settings );

		assertTrue( file_exists( $backup_path ), 'expected backup to exist before clean_attachment' );
		$tiny_plugin->clean_attachment( 1 );
		assertTrue( file_exists( $backup_path ), 'expected backup to be kept for the other attachment' );
	}

	public function test_clean_attachment_keeps_converted_file_when_another_attachment_uses_the_file()
	{
		/* realpath() does not work on vfs, so the converted file is on disk. */
		$upload_dir = sys_get_temp_dir() . '/tiny-test-' . uniqid();
		$webp_path  = $upload_dir . '/2026/04/testfile.webp';
		mkdir( dirname( $webp_path ), 0755, true );
		file_put_contents( $webp_path, 'webp content' );

		$this->wp->stub( 'wp_upload_dir', function () use ( $upload_dir ) {
			return array( 'basedir' => $upload_dir );
		} );
		$this->wp->stub( 'wp_get_attachment_metadata', function ( $i ) {
			return array(
				'file'  => '2026/04/testfile.png',
				'sizes' => array(),
			);
		} );
		$this->wp->updateMetadata( 1, Tiny_Config::META_KEY, array(
			Tiny_Image::ORIGINAL => array(
				'convert' => array(
					'path' => $webp_path,
					'type' => 'image/webp',
					'size' => 100,
				),
			),
		) );

		/* Attachment 2 uses the same file, like a WPML or Polylang translation. */
		$this->wp->updateMetadata( 1, '_wp_attached_file', '2026/04/testfile.png' );
		$this->wp->stub( 'get_posts', function ( $args ) {
			$same_file = '2026/04/testfile.png' === $args['meta_value'];
			$excluded  = array( 1 ) === $args['post__not_in'];
			return $same_file && $excluded ? array( 2 ) : array();
		} );

		$tiny_plugin   = new Tiny_Plugin();
		$ref           = new \ReflectionClass( $tiny_plugin );
		$settings_prop = $ref->getProperty( 'settings' );
		$settings_prop->setAccessible( true );
		$mock_settings = $this->createMock( Tiny_Settings::class );
		$settings_prop->setValue( $tiny_plugin, $mock_settings );

		$tiny_plugin->clean_attachment( 1 );
		$kept = file_exists( $webp_path );

		if ( $kept ) {
			unlink( $webp_path );
		}
		rmdir( $upload_dir . '/2026/04' );
		rmdir( $upload_dir . '/2026' );
		rmdir( $upload_dir );

		assertTrue( $kept, 'expected converted file to be kept for the other attachment' );
	}

	public function test_clean_attachment_deletes_backup_file_when_attachment_has_no_attached_file()
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

		/* WordPress ignores an empty meta_value, so the query would match any attachment. */
		$this->wp->stub( 'get_posts', function ( $args ) {
			return array( 2 );
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
