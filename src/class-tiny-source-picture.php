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

class Tiny_Source_Picture extends Tiny_Source_Base {
	/**
	 * Adds alternative format sources (e.g., image/webp, image/avif) to an existing
	 * <picture> element based on locally available converted files.
	 *
	 * @return string The augmented <picture> HTML or the original if no additions.
	 */
	public function augment_picture_element() {
		$modified_sources = array();

		foreach ( $this->get_element_by_tag( $this->raw_html, 'source' ) as $source_tag_html ) {
			$type_attr = self::get_attribute_value( $source_tag_html, 'type' );
			$type_attr = null !== $type_attr ? strtolower( trim( $type_attr ) ) : '';

			// Skip if already optimized.
			if ( '' !== $type_attr && in_array( $type_attr, $this->valid_mimetypes, true ) ) {
				continue;
			}

			$alternative_sources = $this->create_alternative_sources( $source_tag_html );
			if ( is_array( $alternative_sources ) && $alternative_sources ) {
				$modified_sources = array_merge( $modified_sources, $alternative_sources );
			}
		}

		// handle inner image
		foreach ( $this->get_element_by_tag( $this->raw_html, 'img' ) as $img_tag_html ) {
			$alt_image_source = $this->create_alternative_sources( $img_tag_html );
			$modified_sources = array_merge( $modified_sources, $alt_image_source );
		}

		$modified_source = implode( '', $modified_sources );

		// Insert newly built <source> elements immediately before the first <img>
		return preg_replace( '#(<img\b)#i', $modified_source . '$1', $this->raw_html, 1 );
	}
}
