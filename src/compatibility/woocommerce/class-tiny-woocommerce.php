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
 * Handles WooCommerce compatibility
 *
 * @since 3.6.9
 */
class Tiny_WooCommerce {

	public function __construct() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		$this->add_hooks();
	}

	private function add_hooks() {
		add_filter( 'tiny_replace_with_picture', array( $this, 'skip_on_product_pages' ), 10, 1 );
	}

	/**
	 * We are skipping single product pages for now.
	 * Variation images in the product gallery are injected through JavaScript but might never
	 * display because the sourceset takes priority over the root img. The replacement is on the image and not
	 * on the srcset.
	 *
	 * @since 3.6.9
	 *
	 * @param bool $should_replace Whether to replace images with picture elements.
	 * @return bool False on product pages, otherwise unchanged.
	 */
	public function skip_on_product_pages( $should_replace ) {
		if ( is_product() ) {
			return false;
		}

		return $should_replace;
	}
}
