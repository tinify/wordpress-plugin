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
class Tiny_Picture {


	public function __construct() {
		$this->init();
	}

	private function init() {
		if ( is_admin() || is_customize_preview() ) {
			return;
		}

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}

		if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
			return;
		}

		add_action('template_redirect', function () {
			ob_start( [ $this, 'replace_img_with_picture_tag' ] );
		}, 1000);
	}

	public function replace_img_with_picture_tag( $content ) {
		$images = $this->filter_images( $content );

		foreach ( $images as $image ) {
			$content = $this->replace_image( $content, $image );
		}
		return $content;
	}

	private function replace_image( $content, $image ) {
		$content = str_replace( $image->img_element, $image->get_picture_element(), $content );
		return $content;
	}

	/**
	 * Filters out all images from the content and returns them as an array.
	 *
	 * @return Tiny_Image[]
	 */
	private function filter_images( $content ) {
		if ( preg_match( '/(?=<body).*<\/body>/is', $content, $body ) ) {
			$content = $body[0];
		}

		$content = preg_replace( '/<!--(.*)-->/Uis', '', $content );

		$content = preg_replace( '#<noscript(.*?)>(.*?)</noscript>#is', '', $content );

		if ( ! preg_match_all( '/<img\s.*>/isU', $content, $matches ) ) {
			return array();
		}

		$images = array_map(function ( $img ) {
			return new Tiny_Picture_Element( $img );
		}, $matches[0]);
		$images = array_filter( $images );

		if ( ! $images || ! is_array( $images ) ) {
			return array();
		}

		return $images;
	}
}

class Tiny_Picture_Element {

	/**
	 * The raw HTML img element as a string
	 * @var string
	 */
	public $img_element;

	/**
	 * The DOMElement of the img element
	 * @var DOMNodeList::item
	 */
	private $img_element_node;

	public function __construct( $img_element ) {
		$this->img_element = $img_element;
		$dom = new \DOMDocument();
		$dom->loadHTML( $img_element );
		$this->img_element_node = $dom->getElementsByTagName( 'img' )->item( 0 );
	}

	/**
	 * Retrieves the image sources from the img element
	 *
	 * @return array{path: string, size: string}[] The image sources
	 */
	private function get_image_srcsets() {
		$result = array();
		$srcset = $this->img_element_node->getAttribute( 'srcset' );

		if ( $srcset ) {
			// Split the srcset by commas to get individual entries
			$srcset_entries = explode( ',', $srcset );

			foreach ( $srcset_entries as $entry ) {
				// Trim whitespace
				$entry = trim( $entry );

				// Split by whitespace to separate path and size descriptor
				$parts = preg_split( '/\s+/', $entry, 2 );

				if ( count( $parts ) === 2 ) {
					// We have both path and size
					$result[] = array(
						'path' => $parts[0],
						'size' => $parts[1],
					);
				} elseif ( count( $parts ) === 1 ) {
					// We only have a path (unusual in srcset)
					$result[] = array(
						'path' => $parts[0],
						'size' => '',
					);
				}
			}
		} elseif ( $this->img_element_node->hasAttribute( 'src' ) ) {
			// No srcset, but we have a src attribute
			$result[] = array(
				'path' => $this->img_element_node->getAttribute( 'src' ),
				'size' => '',
			);
		}
		return $result;
	}

	private function get_formatted_source( $imgsrcs, $format ) {
		$upload_dir = wp_upload_dir();

			$formatted_src_set = array();
		foreach ( $imgsrcs as $imgsrc ) {
			$format_url = Tiny_Helpers::replace_file_extension( $format, $imgsrc['path'] );
			$format_path = str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $format_url );
			if ( file_exists( $format_path ) ) {
				$formatted_src_set[] = $format_url . ' ' . $imgsrc['size'];
			}
		}

		if ( empty( $formatted_src_set ) ) {
			// no avif sources found
			return '';
		}

		$source_set = implode( ', ', $formatted_src_set );
		return '<source type="' . $format . '" srcset="' . $source_set . '">';
	}

	public function get_picture_element() {
		$srcsets = $this->get_image_srcsets();

		$picture = '<picture>';
		$picture .= $this->get_formatted_source( $srcsets, 'image/avif' );
		$picture .= $this->get_formatted_source( $srcsets, 'image/webp' );
		$picture .= $this->img_element;

		$picture .= '</picture>';
		return $picture;
	}
}
