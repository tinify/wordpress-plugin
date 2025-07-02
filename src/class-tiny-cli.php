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
	/**
	 * Tiny_Plugin $settings
	 *
	 * @var Tiny_Settings
	 */
	private $tiny_settings;

	public function __construct( $settings ) {
		$this->tiny_settings = $settings;

		$this->add_hooks();
	}

	/**
	 * Registers hooks to set up CLI support
	 */
	public function add_hooks() {
		// Only add CLI hooks when WP-CLI is available
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			add_action( 'cli_init', array( $this, 'register' ) );
		}
	}

	public function register() {
		\WP_CLI::add_command( 'tiny', $this );
	}
}