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
class Tiny_WooCommerce
{

	/**
	 * Constructor.
	 */
	public function __construct()
	{
		$this->add_hooks();
	}

	private function add_hooks()
	{
		add_filter('tiny_skip_picture_wrap', array($this, 'skip_product_gallery_images'), 10, 2);
	}

	/**
	 * Skip picture wrapping for WooCommerce product gallery images.
	 *
	 * Product gallery images need to remain as direct children of their parent
	 * elements for WooCommerce's variation switcher to work correctly.
	 *
	 * @param bool   $should_skip Whether to skip this image. Default false.
	 * @param string $img         The img tag HTML.
	 * @return bool True if the image should be skipped, false otherwise.
	 */
	public function skip_product_gallery_images($should_skip, $img)
	{
		if ($should_skip) {
			return $should_skip;
		}

		return stripos($img, 'woocommerce-product-gallery__image') !== false;
	}
}
