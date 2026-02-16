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
 * Collects diagnostic information and generates downloadable zip files.
 *
 * @since 3.7.0
 */
class Tiny_Diagnostics {

	/**
	 * Tiny settings
	 *
	 * @var Tiny_Settings
	 */
	private $settings;

	/**
	 * @param Tiny_Settings $settings
	 */
	public function __construct( $settings ) {
		$this->settings = $settings;

		add_action(
			'wp_ajax_tiny_download_diagnostics',
			array( $this, 'download_diagnostics' )
		);
	}

	/**
	 * Collects all diagnostic information.
	 *
	 * File contains:
	 * - timestamp of export
	 * - server information
	 * - site information
	 * - plugin list
	 * - tinify settings
	 * - image settings
	 * - logs
	 *
	 * @since 3.7.0
	 *
	 * @return array Array of diagnostic information.
	 */
	public function collect_info() {
		$info = array(
			'timestamp'      => current_time( 'Y-m-d H:i:s' ),
			'site_info'      => self::get_site_info(),
			'server_info'    => self::get_server_info(),
			'active_plugins' => self::get_active_plugins(),
			'tiny_info'      => $this->get_tiny_info(),
			'image_sizes'    => $this->settings->get_active_tinify_sizes(),
		);

		return $info;
	}

	/**
	 * Gets server information.
	 * We have considered phpinfo but this would be a security concern
	 * as it contains a lot of information we probably do not need.
	 * Whenever support needs more server information, we can manually
	 * add it here.
	 *
	 * @since 3.7.0
	 *
	 * @return array Server information.
	 */
	private static function get_server_info() {
		global $wpdb;

		return array(
			'php_version'         => phpversion(),
			'server_software'     => isset( $_SERVER['SERVER_SOFTWARE'] ) ?
				sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) :
				'Unknown',
			'mysql_version'       => $wpdb->db_version(),
			'max_execution_time'  => ini_get( 'max_execution_time' ),
			'memory_limit'        => ini_get( 'memory_limit' ),
			'post_max_size'       => ini_get( 'post_max_size' ),
			'upload_max_filesize' => ini_get( 'upload_max_filesize' ),
			'max_input_vars'      => ini_get( 'max_input_vars' ),
			'curl_version'        => function_exists( 'curl_version' ) ?
				curl_version()['version'] :
				'Not available',
			'disabled_functions'  => ini_get( 'disable_functions' ),
		);
	}

	/**
	 * Gets site information.
	 *
	 * @since 3.7.0
	 *
	 * @return array Site information.
	 */
	private static function get_site_info() {
		global $wp_version;
		$theme = wp_get_theme();

		return array(
			'wp_version'    => $wp_version,
			'site_url'      => get_site_url(),
			'home_url'      => get_home_url(),
			'is_multisite'  => is_multisite(),
			'site_language' => get_locale(),
			'timezone'      => wp_timezone_string(),
			'theme_name'    => $theme->get( 'Name' ),
			'theme_version' => $theme->get( 'Version' ),
			'theme_uri'     => $theme->get( 'ThemeURI' ),
		);
	}

	/**
	 * Gets list of active plugins.
	 *
	 * @since 3.7.0
	 *
	 * @return array List of active plugins.
	 */
	private static function get_active_plugins() {
		$active_plugins = get_option( 'active_plugins', array() );
		$plugins        = array();

		foreach ( $active_plugins as $plugin ) {
			$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );
			$plugins[]   = array(
				'name'    => $plugin_data['Name'],
				'version' => $plugin_data['Version'],
				'author'  => $plugin_data['Author'],
				'file'    => $plugin,
			);
		}

		return $plugins;
	}

	/**
	 * Gets TinyPNG plugin info & settings.
	 *
	 * @since 3.7.0
	 *
	 * @return array Plugin settings
	 */
	private function get_tiny_info() {
		return array(
			'version'              => Tiny_Plugin::version(),
			'status'               => $this->settings->get_status(),
			'php_client_supported' => Tiny_PHP::client_supported(),

			'compression_count'    => $this->settings->get_compression_count(),
			'compression_timing'   => $this->settings->get_compression_timing(),
			'conversion'           => $this->settings->get_conversion_options(),
			'paying_state'         => $this->settings->get_paying_state(),
		);
	}

	public function download_diagnostics() {
		check_ajax_referer( 'tiny-compress', 'security' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				esc_html__( 'Not allowed to download diagnostics.', 'tiny-compress-images' ),
				403
			);
		}

		$zippath = $this->create_diagnostic_zip();
		return $this->download_zip( $zippath );
	}

	/**
	 * Creates a diagnostic zip file.
	 *
	 * @since 3.7.0
	 *
	 * @return string|WP_Error Path to the created zip file or WP_Error on failure.
	 */
	public function create_diagnostic_zip() {
		if ( ! class_exists( 'ZipArchive' ) ) {
			return new WP_Error(
				'zip_not_available',
				__(
					'ZipArchive class is not available on this server.',
					'tiny-compress-images'
				)
			);
		}

		$wp_filesystem = Tiny_Helpers::get_wp_filesystem();
		$temp_dir      = trailingslashit( get_temp_dir() ) . 'tiny-compress-temp';
		if ( ! $wp_filesystem->exists( $temp_dir ) ) {
			wp_mkdir_p( $temp_dir );
		}

		$temp_path = tempnam( $temp_dir, 'tiny-compress-diagnostics-' . gmdate( 'Y-m-d-His' ) );

		$zip = new ZipArchive();
		if ( true !== $zip->open( $temp_path, ZipArchive::CREATE | ZipArchive::OVERWRITE ) ) {
			return new WP_Error(
				'zip_create_failed',
				__(
					'Failed to create zip file.',
					'tiny-compress-images'
				)
			);
		}

		$info = self::collect_info();
		$zip->addFromString( 'tiny-diagnostics.json', wp_json_encode( $info, JSON_PRETTY_PRINT ) );

		$logger    = Tiny_Logger::get_instance();
		$log_files = $logger->get_log_files();

		foreach ( $log_files as $log_file ) {
			if ( $wp_filesystem->exists( $log_file ) ) {
				$zip->addFile( $log_file, 'logs/' . basename( $log_file ) );
			}
		}

		$zip->close();
		return $temp_path;
	}

	/**
	 * Downloads and removes the zip
	 *
	 * @since 3.7.0
	 *
	 * @param string $zip_path Path to the zip file.
	 */
	public static function download_zip( $zip_path ) {
		$wp_filesystem = Tiny_Helpers::get_wp_filesystem();
		if ( ! $wp_filesystem->exists( $zip_path ) ) {
			wp_die( esc_html__( 'Diagnostic file not found.', 'tiny-compress-images' ) );
		}

		header( 'Content-Type: application/zip' );
		header( 'Content-Disposition: attachment; filename="tiny-compress-diagnostics.zip"' );
		header( 'Content-Length: ' . $wp_filesystem->size( $zip_path ) );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
		readfile( $zip_path );

		// Clean up.
		$wp_filesystem->delete( $zip_path );

		exit;
	}
}
