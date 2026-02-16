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

class Tiny_Source_Image extends Tiny_Source_Base {

	/**
	 * Generates a formatted image source array if the corresponding local file exists.
	 *
	 * Attempts to replace the file extension of the provided image path with the
	 * specified MIME type, resolves the local path of the resulting file, and returns
	 * the `srcset` and `type` if the file exists.
	 *
	 * @return string a <picture> element contain additional sources
	 */
	public function create_picture_elements() {
		$sources = $this->create_alternative_sources( $this->raw_html );
		if ( empty( $sources ) ) {
			return $this->raw_html;
		}
		$picture_element   = array( '<picture>' );
		$picture_element[] = implode( '', $sources );
		$picture_element[] = $this->raw_html;
		$picture_element[] = '</picture>';

		return implode( '', $picture_element );
	}
}
