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

class Tiny_Cli {

	public function __construct( $settings ) {

		// Only add CLI hooks when WP-CLI is available
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			add_action( 'cli_init', Tiny_Cli_Commands::register( $settings ) );
		}
	}
}

class Tiny_Cli_Commands {
	/**
	 * Tiny_Plugin $settings
	 *
	 * @var Tiny_Settings
	 */
	private $tiny_settings;

	public static function register( $settings ) {
		$tiny_settings = $settings;

		WP_CLI::add_command( 'tiny', self::class );
	}

	/**
	 * Optimize will process images
	 *
	 * [--attachments=<strings>]
	 * : A comma separated list of attachment IDs to process. If omitted
	 * will optimize all uncompressed attachments
	 *
	 *
	 * ## EXAMPLES
	 *
	 *      optimize specific attachments
	 *      wp tiny optimize --attachments=532,603,705
	 *
	 *      optimize all unprocessed images
	 *      wp tiny optimize
	 *
	 *
	 * @param array $args
	 * @param array $assoc_args
	 * @return void
	 */
	public function optimize( $args, $assoc_args ) {

	}

	private function compress_attachment( $id ) {

	}
}
