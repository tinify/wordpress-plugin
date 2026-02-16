<?php
/*
* Tiny Compress Images - WordPress plugin.
* Copyright (C) 2015-2026 Tinify B.V.
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
 * Handles logging of plugin events to file.
 *
 *
 * @since 3.7.0
 */
class Tiny_Logger {


	const LOG_LEVEL_ERROR = 'error';
	const LOG_LEVEL_DEBUG = 'debug';

	const MAX_LOG_SIZE = 2 * 1024 * 1024; // 2MB

	private static $instance = null;

	private $log_enabled   = null;
	private $log_file_path = null;

	/**
	 * To log on various places easily, we create a singleton
	 * to prevent passing around the instance.
	 *
	 * @return Tiny_Logger The logger instance.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 * sets log_file path and log_enabled
	 */
	private function __construct() {
		$this->log_file_path = $this->resolve_log_file_path();
		$this->log_enabled   = 'on' === get_option( 'tinypng_logging_enabled', false );
	}

	/**
	 * Initializes the logger by registering WordPress hooks.
	 *
	 * This method hooks into 'pre_update_option_tinypng_logging_enabled' to
	 * intercept and process logging settings before they are saved to the database.
	 *
	 * @return void
	 */
	public static function init() {
		add_filter(
			'pre_update_option_tinypng_logging_enabled',
			'Tiny_Logger::on_save_log_enabled',
			10,
			3
		);
	}

	/**
	 * Resets the singleton instance.
	 * Used primarily for unit testing.
	 *
	 * @return void
	 */
	public static function reset() {
		self::$instance = null;
	}

	/**
	 * Retrieves whether logging is currently enabled.
	 *
	 * @return bool True if logging is enabled, false otherwise.
	 */
	public function get_log_enabled() {
		return $this->log_enabled;
	}

	/**
	 * Retrieves the absolute filesystem path to the log file.
	 *
	 * @return string The full filesystem path to the tiny-compress.log file.
	 */
	public function get_log_file_path() {
		return $this->log_file_path;
	}

	/**
	 * Triggered when log_enabled is saved
	 * - set the setting on the instance
	 * - if turn on, clear the old logs
	 */
	public static function on_save_log_enabled( $log_enabled, $old, $option ) {
		$instance              = self::get_instance();
		$instance->log_enabled = 'on' === $log_enabled;
		if ( $instance->get_log_enabled() ) {
			self::clear_logs();
		}

		return $log_enabled;
	}

	/**
	 * Retrieves the log path using wp_upload_dir. This operation
	 * should only be used internally. Use the getter to get the
	 * memoized function.
	 *
	 * @return string The log file path.
	 */
	private function resolve_log_file_path() {
		$upload_dir = wp_upload_dir();
		$log_dir    = trailingslashit( $upload_dir['basedir'] ) . 'tiny-compress-logs';
		return trailingslashit( $log_dir ) . 'tiny-compress.log';
	}

	/**
	 * Logs an error message.
	 *
	 * @param string $message The message to log.
	 * @param array  $context Optional. Additional context data. Default empty array.
	 */
	public static function error( $message, $context = array() ) {
		$instance = self::get_instance();
		$instance->log( self::LOG_LEVEL_ERROR, $message, $context );
	}

	/**
	 * Logs a debug message.
	 *
	 * @param string $message The message to log.
	 * @param array  $context Optional. Additional context data. Default empty array.
	 */
	public static function debug( $message, $context = array() ) {
		$instance = self::get_instance();
		$instance->log( self::LOG_LEVEL_DEBUG, $message, $context );
	}

	/**
	 * Logs a message.
	 *
	 * @param string $level The log level.
	 * @param string $message The message to log.
	 * @param array  $context Optional. Additional context data. Default empty array.
	 * @return void
	 */
	private function log( $level, $message, $context = array() ) {
		if ( ! $this->log_enabled ) {
			return;
		}

		$this->rotate_logs();

		// Ensure log directory exists.
		$log_dir       = dirname( $this->log_file_path );
		$wp_filesystem = Tiny_Helpers::get_wp_filesystem();
		if ( ! $wp_filesystem->exists( $log_dir ) ) {
			wp_mkdir_p( $log_dir );
			self::create_blocking_files( $log_dir );
		}

		$timestamp   = current_time( 'Y-m-d H:i:s' );
		$level_str   = strtoupper( $level );
		$context_str = ! empty( $context ) ? ' ' . wp_json_encode( $context ) : '';
		$log_entry   = "[{$timestamp}] [{$level_str}] {$message}{$context_str}" . PHP_EOL;

		error_log( $log_entry, 3, $this->log_file_path );
	}

	/**
	 * Deletes log file and creates a new one when the
	 * MAX_LOG_SIZE is met.
	 *
	 * @return void
	 */
	private function rotate_logs() {
		$wp_filesystem = Tiny_Helpers::get_wp_filesystem();
		if ( ! $wp_filesystem->exists( $this->log_file_path ) ) {
			return;
		}

		$file_size = $wp_filesystem->size( $this->log_file_path );
		if ( $file_size < self::MAX_LOG_SIZE ) {
			return;
		}

		$wp_filesystem->delete( $this->log_file_path );
	}

	/**
	 * Clears log file
	 *
	 * @return bool True if logs were cleared successfully.
	 */
	public static function clear_logs() {
		$instance      = self::get_instance();
		$log_path      = $instance->get_log_file_path();
		$wp_filesystem = Tiny_Helpers::get_wp_filesystem();
		$file_exits    = $wp_filesystem->exists( $log_path );
		if ( $file_exits ) {
			return $wp_filesystem->delete( $log_path );
		}

		return true;
	}

	/**
	 * Creates defensive files to prevent direct access to log directory.
	 * Adds index.html to prevent directory listing and .htaccess to block access.
	 *
	 * @param string $log_dir The path to the log directory.
	 * @return void
	 */
	private static function create_blocking_files( $log_dir ) {
		$wp_filesystem = Tiny_Helpers::get_wp_filesystem();

		$index_file = trailingslashit( $log_dir ) . 'index.html';
		if ( ! $wp_filesystem->exists( $index_file ) ) {
			$index_content = '<!-- Silence is golden -->';
			$wp_filesystem->put_contents( $index_file, $index_content, FS_CHMOD_FILE );
		}

		$htaccess_file = trailingslashit( $log_dir ) . '.htaccess';
		if ( ! $wp_filesystem->exists( $htaccess_file ) ) {
			$htaccess_content = 'deny from all';
			$wp_filesystem->put_contents( $htaccess_file, $htaccess_content, FS_CHMOD_FILE );
		}
	}

	/**
	 * Gets all log file paths.
	 *
	 * @return array Array of log file paths.
	 */
	public function get_log_files() {
		$files = array();

		if ( file_exists( $this->log_file_path ) ) {
			$files[] = $this->log_file_path;
		}

		return $files;
	}
}
