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
	/** @var string */
	private static $base_dir;

	/** @var array */
	private static $allowed_domains = array();

	/**
	 * Initialize the plugin.
	 *
	 * @param string $base_dir       Absolute path (e.g. ABSPATH)
	 * @param array  $domains        List of allowed domain URLs
	 */
	public static function init( $base_dir = ABSPATH, $domains = array() ) {
		// normalize and store as statics
		self::$base_dir        = $base_dir;
		self::$allowed_domains = $domains;

		if ( is_admin() || is_customize_preview() ) {
			return;
		}

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}

		if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
			return;
		}

		add_action( 'template_redirect', function() {
			ob_start( array( __CLASS__, 'replace_img_with_picture_tag' ), 1000 );
		});
	}

	public static function replace_img_with_picture_tag( $content ) {
		$images = Tiny_Picture::filter_images( $content );

		foreach ( $images as $image ) {
			$content = Tiny_Picture::replace_image( $content, $image );
		}
		return $content;
	}

	private static function replace_image( $content, $image ) {
		$content = str_replace( $image->img_element, $image->get_picture_element(), $content );
		return $content;
	}

	/**
	 * Filters out all images from the content and returns them as an array.
	 *
	 * @return Tiny_Image[]
	 */
	private static function filter_images( $content ) {
		if ( preg_match( '/(?=<body).*<\/body>/is', $content, $body ) ) {
			$content = $body[0];
		}

		$content = preg_replace( '/<!--(.*)-->/Uis', '', $content );

		$content = preg_replace( '#<noscript(.*?)>(.*?)</noscript>#is', '', $content );

		if ( ! preg_match_all( '/<img\s.*>/isU', $content, $matches ) ) {
			return array();
		}

		$images = array_map(function ( $img ) {
			return new Tiny_Picture_Element( $img, self::$base_dir, self::$allowed_domains );
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

	private $base_dir;

	private $allowed_domains;

	public function __construct( $img_element, $base_dir, $domains ) {
		$this->img_element = $img_element;
		$dom = new \DOMDocument();
		$dom->loadHTML( $img_element );
		$this->img_element_node = $dom->getElementsByTagName( 'img' )->item( 0 );

		$this->base_dir = $base_dir;
		$this->allowed_domains = $domains;
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

	private function get_local_path( $url ) {
		if ( strpos( $url, 'http' ) === 0 ) {
			$matchedDomain = null;

			foreach ( $this->allowed_domains as $domain ) {
				if ( strpos( $url, $domain ) === 0 ) {
					$matchedDomain = $domain;
					break;
				}
			}

			if ( $matchedDomain === null ) {
				return '';
			}

			$url = substr( $url, strlen( $matchedDomain ) );
		}
		$url = $this->base_dir . $url;

		return $url;
	}

	private function get_formatted_source( $imgsrcs, $mimetype ) {
		$formatted_src_set = array();
		foreach ( $imgsrcs as $imgsrc ) {
			$format_url = Tiny_Helpers::replace_file_extension( $mimetype, $imgsrc['path'] );
			$local_path = $this->get_local_path( $format_url );
			if ( empty( $local_path ) ) {
				continue;
			}
			$exists_local = file_exists( $local_path );
			if ( $exists_local ) {
				$formatted_src_set[] = $format_url . ' ' . $imgsrc['size'];
			}
		}

		if ( empty( $formatted_src_set ) ) {
			// no alternative sources found
			return '';
		}

		$source_set = implode( ', ', $formatted_src_set );
		$trimmed_source_set = trim( $source_set );
		return '<source type="' . $mimetype . '" srcset="' . $trimmed_source_set . '">';
	}

	public function get_picture_element() {
		$srcsets = $this->get_image_srcsets();

		$avif = $this->get_formatted_source( $srcsets, 'image/avif' );
		$webp = $this->get_formatted_source( $srcsets, 'image/webp' );
		if ( empty( $avif ) && empty( $webp ) ) {
			return $this->img_element;
		}

		$picture = '<picture>';
		$picture .= $this->get_formatted_source( $srcsets, 'image/avif' );
		$picture .= $this->get_formatted_source( $srcsets, 'image/webp' );
		$picture .= $this->img_element;

		$picture .= '</picture>';
		return $picture;
	}
}
