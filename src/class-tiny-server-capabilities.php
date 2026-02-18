<?php
/*
* Tiny Compress Images - WordPress plugin.
* Copyright (C) 2015-2018 Tinify B.V.
*
* This program is free software; you can redistribute it and/or modify it
* under the terms of the GNU General Public License as published by the Free
* Software Foundation; either version 2 of the License, or (at your option)
* any later version.
*
* This program is distributed in the hope that it will be useful, but WITHOUT
* ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
* FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for
* more details.
*
* You should have received a copy of the GNU General Public License along
* with this program; if not, write to the Free Software Foundation, Inc., 51
* Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
*/

/**
 * class responsible for checking and managing server capabilities
 */
class Tiny_Server_Capabilities {

	/**
	 * Detect the web server software using WordPress globals.
	 * WordPress sets $is_apache, $is_IIS, $is_iis7, and $is_nginx in wp-includes/vars.php
	 *
	 * @return string The server type: 'apache', 'nginx', 'iis', or 'unknown'
	 */
	private static function get_server_type() {
		global $is_apache, $is_iis7, $is_nginx;

		if ( $is_apache ) {
			return 'apache';
		}

		if ( $is_nginx ) {
			return 'nginx';
		}

		if ( $is_iis7 ) {
			return 'iis';
		}

		return 'unknown';
	}

	/**
	 * Check if the server is Apache.
	 *
	 * @return bool True if running on Apache
	 */
	public static function is_apache() {
		return self::get_server_type() === 'apache';
	}

	/**
	 * Check if mod_rewrite is available on Apache.
	 *
	 * @return bool True if mod_rewrite is available
	 */
	public static function has_mod_rewrite() {
		if ( ! self::is_apache() ) {
			return false;
		}

		if ( function_exists( 'apache_mod_loaded' ) ) {
			return apache_mod_loaded( 'mod_rewrite' );
		}

		if ( function_exists( 'apache_get_modules' ) ) {
			$modules = apache_get_modules();
			return in_array( 'mod_rewrite', $modules, true );
		}

		if ( function_exists( 'insert_with_markers' ) ) {
			$htaccess = wp_upload_dir() . '.htaccess';
			return is_writable( dirname( $htaccess ) );
		}

		return false;
	}

	/**
	 * Check if the /uploads directory is writable for .htaccess.
	 *
	 * @return bool True if uploads directory is writable, false otherwise
	 */
	public static function uploads_htaccess_writable() {
		$upload_dir = wp_upload_dir();
		if ( isset( $upload_dir['basedir'] ) ) {
			return is_writable( $upload_dir['basedir'] );
		}
		return false;
	}

	/**
	 * Get a detailed capabilities object.
	 *
	 * @return array Array with properties: server, is_apache, has_mod_rewrite,
	 * uploads_writable, htaccess_available
	 */
	public static function get_capabilities() {
		return array(
			'server'             => self::get_server_type(),
			'is_apache'          => self::is_apache(),
			'has_mod_rewrite'    => self::has_mod_rewrite(),
			'uploads_writable'   => self::uploads_htaccess_writable(),
			'htaccess_available' => self::is_apache() &&
				self::has_mod_rewrite() &&
				self::uploads_htaccess_writable(),
		);
	}
}
