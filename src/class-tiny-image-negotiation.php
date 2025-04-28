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
class Tiny_Image_Negotiation extends Tiny_WP_Base {

	/** @var string */
	private $base_dir;

	/** @var array */
	private $allowed_domains = array();

	/**
	 * Initialize the plugin.
	 *
	 * @param string $base_dir       Absolute path (e.g. ABSPATH)
	 * @param array  $domains        List of allowed domain URLs
	 */
	function __construct( $base_dir = ABSPATH, $domains = array() ) {
		$this->base_dir        = $base_dir;
		$this->allowed_domains = $domains;

		if ( is_admin() || is_customize_preview() ) {
			return;
		}

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}

		if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
			return;
		}

		add_filter( 'wp_get_attachment_url', array( $this, 'filter_attachment_url' ), 10, 2 );
		add_filter( 'wp_get_attachment_image_src', array( $this, 'filter_attachment_image_src' ), 10, 4 );
		add_filter( 'wp_calculate_image_srcset', array( $this, 'filter_image_srcset' ), 10, 5 );

		add_action( 'template_redirect', function() {
			ob_start( array( $this, 'replace_img_sources' ), 1000 );
		});
	}

	public function replace_img_sources( $content ) {

		$images = $this->filter_images( $content );

		foreach ( $images as $image ) {
			$content = Tiny_Image_Negotiation::replace_image( $content, $image );
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
			return new Tiny_Picture_Element( $img, $this->base_dir, $this->allowed_domains, array( 'image/avif', 'image/webp' ) );
		}, $matches[0]);
		$images = array_filter( $images );

		if ( ! $images || ! is_array( $images ) ) {
			return array();
		}

		return $images;
	}


	/**
	 * Checks the HTTP_ACCEPT header what file formats
	 * are supported.
	 *
	 * @return array|null [ 'ext' => string, 'mime' => string ].
	 */
	private function get_support_formats() {
		$accept = $_SERVER['HTTP_ACCEPT'] ?? '';
		$accepted_formats = array();
		if ( stripos( $accept, 'image/avif' ) !== false ) {
			$accepted_formats[] = array(
				'ext' => '.avif',
				'mime' => 'image/avif',
			);
		}
		if ( stripos( $accept, 'image/webp' ) !== false ) {
			$accepted_formats[] = array(
				'ext' => '.webp',
				'mime' => 'image/webp',
			);
		}
		return $accepted_formats;
	}

	/**
	 * Replace attachment URL with .avif/.webp variant if supported and exists.
	*
	* @see https://developer.wordpress.org/reference/hooks/wp_get_attachment_url/
	*
	* @param string $url     Original attachment URL.
	* @param int    $post_id Attachment post ID.
	* @return string New or original URL.
	*/
	public function filter_attachment_url( $url, $post_id ) {
		header( 'Vary: Accept' );
		$supported_formats = $this->get_support_formats();
		if ( empty( $supported_formats ) ) {
			return $url;
		}

		$uploads = wp_upload_dir();
		$basedir = trailingslashit( $uploads['basedir'] );
		$baseurl = trailingslashit( $uploads['baseurl'] );

		$relative = str_replace( $baseurl, '', $url );
		$path     = $basedir . $relative;

		foreach ( $supported_formats as $format ) {
			$candidate = preg_replace( '/\.(jpe?g|png|webp)$/i', $format['ext'], $path );
			if ( file_exists( $candidate ) ) {
				return $baseurl . ltrim( str_replace( $basedir, '', $candidate ), '/' );
			}
		}
		return $url;
	}

	/**
	 * Swap URL in <img> tags.
	 *
	 * @param array|false $image         [0]=URL, [1]=width, [2]=height, [3]=is_intermediate
	 * @param int         $attachment_id Attachment ID.
	 * @param string|int[] $size         Size name or [width,height].
	 * @param bool        $icon          Whether icon fallback.
	 * @return array|false Modified image array.
	 */
	public function filter_attachment_image_src( $image, $attachment_id, $size, $icon ) {
		if ( ! $image ) {
			return $image;
		}
		list( $url, $w, $h, $inter ) = $image;
		$new_url = $this->filter_attachment_url( $url, $attachment_id );
		return [ $new_url, $w, $h, $inter ];
	}

	/**
	 * Swap URLs in responsive srcset.
	 *
	 * @param array   $sources       Array of [ 'url'=>..., 'descriptor'=>..., 'value'=>... ]
	 * @param int[]   $size_array    [width, height]
	 * @param string  $image_src     Original src
	 * @param array   $image_meta    Attachment metadata
	 * @param int     $attachment_id Attachment ID
	 * @return array Modified sources array.
	 */
	public function filter_image_srcset( $sources, $size_array, $image_src, $image_meta, $attachment_id ) {
		foreach ( $sources as &$src ) {
			$src['url'] = $this->filter_attachment_url( $src['url'], $attachment_id );
		}
		return $sources;
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

	private $valid_mimetypes;

	private $dom;

	public function __construct( $img_element, $base_dir, $domains, $valid_mimetypes ) {
		$this->img_element = $img_element;
		$this->dom = new \DOMDocument();
		$this->dom->loadHTML( $img_element );
		$this->img_element_node = $this->dom->getElementsByTagName( 'img' )->item( 0 );

		$this->base_dir = $base_dir;
		$this->allowed_domains = $domains;
		$this->$valid_mimetypes = $valid_mimetypes;
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
		}

		if ( $this->img_element_node->hasAttribute( 'src' ) ) {
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
			$matched_domain = null;

			foreach ( $this->allowed_domains as $domain ) {
				if ( strpos( $url, $domain ) === 0 ) {
					$matched_domain = $domain;
					break;
				}
			}

			if ( null === $matched_domain ) {
				return '';
			}

			$url = substr( $url, strlen( $matched_domain ) );
		}
		$url = $this->base_dir . $url;

		return $url;
	}

	private function get_formatted_source( $imgsrc, $mimetype ) {
		$format_url = Tiny_Helpers::replace_file_extension( $mimetype, $imgsrc['path'] );
		$local_path = $this->get_local_path( $format_url );
		if ( empty( $local_path ) ) {
			return null;
		}

		$exists_local = file_exists( $local_path );
		if ( $exists_local ) {
			return $format_url . ' ' . $imgsrc['size'];
		}
		return null;
	}

	public function get_picture_element() {
		$srcsets = $this->get_image_srcsets();

		foreach ( $srcsets as $srcset ) {
			foreach ( $this->valid_mimetypes as $mimetype ) {
				$new_srcset = $this->get_formatted_source( $srcset, $mimetype );
				if ( $new_srcset ) {
					$srcset['path'] = $new_srcset;
					break;
				}
			}
		}
		$srcset_parts = [];
		foreach ( $srcsets as $srcset ) {
			if ( empty( $srcset['size'] ) ) {
				$this->img_element_node->setAttribute( 'src', $srcset['path'] );
			} else {
				$srcset_parts[] = $srcset['path'] . ' ' . $srcset['size'];
			}
		}
		$new_srcsets = implode( ', ', $srcset_parts );
		$this->img_element_node->setAttribute( 'srcset', $new_srcsets );

		return $this->dom->saveHTML( $this->img_element_node );
	}
}
