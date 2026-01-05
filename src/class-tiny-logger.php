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
 * @since 3.7.0
 */
class Tiny_Logger {


	const LOG_LEVEL_ERROR = 'error';
	const LOG_LEVEL_DEBUG = 'debug';

	const MAX_LOG_SIZE = 5242880; // 5MB
	const MAX_LOG_FILES = 3;

	private static $instance = null;
	private $log_enabled = false;
	private $log_file_path = null;

	/**
	 * To log on various places easily, we create a singleton
	 * to prevent passing around the instance.
	 *
	 * @since 3.7.0
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
	 *
	 * @since 3.7.0
	 */
	private function __construct() {
		$this->log_enabled = 'on' === get_option( 'tinypng_logging_enabled', false );
		$this->log_file_path = $this->get_log_file_path();
	}

	/**
	 * Initializes the logger by registering WordPress hooks.
	 *
	 * This method hooks into 'pre_update_option_tinypng_logging_enabled' to
	 * intercept and process logging settings before they are saved to the database.
	 *
	 * @since 3.7.0
	 *
	 * @return void
	 */
	public static function init() {
		add_filter( 'pre_update_option_tinypng_logging_enabled', 'Tiny_Logger::on_save_log_enabled', 10, 3 );
	}

	/**
	 * Resets the singleton instance.
	 * Used primarily for unit testing.
	 */
	public static function reset() {
		self::$instance = null;
	}

	/**
	 * Retrieves whether logging is currently enabled.
	 *
	 * @since 3.7.0
	 *
	 * @return bool True if logging is enabled, false otherwise.
	 */
	public function get_log_enabled() {
		return $this->log_enabled;
	}

	/**
	 * Triggered when log_enabled is saved
	 * - set the setting on the instance
	 * - if turn off, clear the logs
	 * - if turned on, check if we can create the log file
	 *
	 * @since 3.7.0
	 */
	public static function on_save_log_enabled( $log_enabled, $old, $option ) {
		if ( $log_enabled !== 'on' ) {
			$instance = self::get_instance();
			$instance->clear_logs();
		}

		return $log_enabled;
	}

	/**
	 * Gets the log file path.
	 *
	 * @since 3.7.0
	 *
	 * @return string The log file path.
	 */
	private function get_log_file_path() {
		$upload_dir = wp_upload_dir();
		$log_dir = trailingslashit( $upload_dir['basedir'] ) . 'tiny-compress-logs';

		return trailingslashit( $log_dir ) . 'tiny-compress.log';
	}

	/**
	 * Gets the log directory path.
	 *
	 * @since 3.7.0
	 *
	 * @return string The log directory path.
	 */
	public function get_log_dir() {
		return dirname( $this->log_file_path );
	}

	/**
	 * Checks if logging is enabled.
	 *
	 * @since 3.7.0
	 *
	 * @return bool True if logging is enabled.
	 */
	public function is_enabled() {
		return $this->log_enabled;
	}

	/**
	 * Logs an error message.
	 *
	 * @since 3.7.0
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
	 * @since 3.7.0
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
	 * @since 3.7.0
	 *
	 * @param string $level The log level.
	 * @param string $message The message to log.
	 * @param array  $context Optional. Additional context data. Default empty array.
	 */
	private function log( $level, $message, $context = array() ) {
		if ( ! $this->log_enabled ) {
			return;
		}

		$this->rotate_logs();

		$timestamp = current_time( 'Y-m-d H:i:s' );
		$level_str = strtoupper( $level );
		$context_str = ! empty( $context ) ? ' ' . wp_json_encode( $context ) : '';
		$log_entry = "[{$timestamp}] [{$level_str}] {$message}{$context_str}\n";

		$file = fopen( $this->log_file_path, 'a' );
		if ( $file ) {
			fwrite( $file, $log_entry );
			fclose( $file );
		}
	}

	/**
	 * Rotates log files when they exceed the max size.
	 *
	 * @since 3.7.0
	 */
	private function rotate_logs() {
		if ( ! file_exists( $this->log_file_path ) ) {
			return;
		}

		$file_size = filesize( $this->log_file_path );
		if ( $file_size < self::MAX_LOG_SIZE ) {
			return;
		}

		for ( $i = self::MAX_LOG_FILES - 1; $i > 0; $i-- ) {
			$old_file = $this->log_file_path . '.' . $i;
			$new_file = $this->log_file_path . '.' . ($i + 1);

			if ( file_exists( $old_file ) ) {
				if ( $i === self::MAX_LOG_FILES - 1 ) {
					unlink( $old_file );
				} else {
					rename( $old_file, $new_file );
				}
			}
		}

		rename( $this->log_file_path, $this->log_file_path . '.1' );
	}

	/**
	 * Clears all log files.
	 *
	 * @since 3.7.0
	 *
	 * @return bool True if logs were cleared successfully.
	 */
	public function clear_logs() {
		$cleared = true;

		// Remove main log file.
		if ( file_exists( $this->log_file_path ) ) {
			$cleared = unlink( $this->log_file_path ) && $cleared;
		}

		// Remove rotated log files.
		for ( $i = 1; $i <= self::MAX_LOG_FILES; $i++ ) {
			$log_file = $this->log_file_path . '.' . $i;
			if ( file_exists( $log_file ) ) {
				$cleared = unlink( $log_file ) && $cleared;
			}
		}

		return $cleared;
	}

	/**
	 * Gets all log file paths.
	 *
	 * @since 3.7.0
	 *
	 * @return array Array of log file paths.
	 */
	public function get_log_files() {
		$files = array();

		if ( file_exists( $this->log_file_path ) ) {
			$files[] = $this->log_file_path;
		}

		for ( $i = 1; $i <= self::MAX_LOG_FILES; $i++ ) {
			$log_file = $this->log_file_path . '.' . $i;
			if ( file_exists( $log_file ) ) {
				$files[] = $log_file;
			}
		}

		return $files;
	}
}
