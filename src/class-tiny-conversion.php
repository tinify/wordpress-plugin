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
 * class managing conversion delivery method
 */
class Tiny_Conversion extends Tiny_WP_Base {

	/**
	 * @var Tiny_Settings plug-in settings
	 */
	private $settings;

	/**
	 * @param Tiny_Settings $settings
	 */
	public function __construct( $settings ) {
		parent::__construct();
		$this->settings = $settings;
	}

	/**
	 * will check if conversion is enabled,
	 * if true:
	 * - will enable the delivery method
	 * - will add hook to toggle rules
	 *
	 * hooked into `init`
	 */
	public function init() {
		if ( ! $this->settings->get_conversion_enabled() ) {
			return;
		}

		add_action( 'update_option_tinypng_convert_format', 'Tiny_Apache_Rewrite::toggle_rules', 20, 3 );
		$delivery_method = $this->settings->get_conversion_delivery_method();

		$this->init_image_delivery( $delivery_method );
	}

	/**
	 * Initializes the method of delivery for optimised images
	 *
	 * @param string $delivery_method 'picture' or 'htaccess'
	 * @return void
	 */
	private function init_image_delivery( $delivery_method ) {
		/**
		 * Controls wether the page should replace <img> with <picture> elements
		 * converted sources.
		 *
		 * @since 3.7.0
		 */
		if ( $delivery_method === 'htaccess' && Tiny_Server_Capabilities::is_apache() ) {
			new Tiny_Apache_Rewrite();
			return;
		}

		if ( apply_filters( 'tiny_replace_with_picture', $delivery_method === 'picture' ) ) {
			new Tiny_Picture( ABSPATH, array( get_site_url() ) );
			return;
		}
	}
}
