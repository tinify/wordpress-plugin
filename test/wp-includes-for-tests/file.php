<?php
/**
 * Mock WordPress filesystem classes for tests.
 */

// Base filesystem class for testing.
class WP_Filesystem_Base {
	protected $is_writable = true;

	public function exists( $path ) {
		return file_exists( $path );
	}

	public function get_contents( $path ) {
		return file_get_contents( $path );
	}

	public function put_contents( $path, $contents, $chmod = false ) {
		$dir = dirname( $path );
		if ( ! is_dir( $dir ) ) {
			mkdir( $dir, 0755, true );
		}
		return file_put_contents( $path, $contents ) !== false;
	}

	public function delete( $path ) {
		if ( is_dir( $path ) ) {
			return rmdir( $path );
		}
		return unlink( $path );
	}

	public function size( $path ) {
		return filesize( $path );
	}
}

// Direct filesystem implementation (default for tests).
class WP_Filesystem_Direct extends WP_Filesystem_Base {
}

/**
 * Initialize the WP_Filesystem object.
 */
function WP_Filesystem( $args = array(), $context = false ) {
	global $wp_filesystem;

	if ( null === $wp_filesystem ) {
		$wp_filesystem = new WP_Filesystem_Direct();
	}

	return true;
}

// Define constants if not already defined.
if ( ! defined( 'FS_CHMOD_DIR' ) ) {
	define( 'FS_CHMOD_DIR', 0755 );
}

if ( ! defined( 'FS_CHMOD_FILE' ) ) {
	define( 'FS_CHMOD_FILE', 0644 );
}
