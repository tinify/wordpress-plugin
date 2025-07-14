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
class Tiny_Picture extends Tiny_WP_Base {

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

		add_action( 'template_redirect', function() {
			ob_start( array( $this, 'replace_img_sources' ), 1000 );
		});
	}

	public function replace_img_sources( $content ) {
		$image_sources = $this->filter_images( $content );
		foreach ( $image_sources as $image_source ) {
			$content = Tiny_Picture::replace_image( $content, $image_source );
		}
		return $content;
	}

	private static function replace_image( $content, $source ) {
		$content = str_replace( $source->image, $source->create_picture_elements(), $content );
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

		$images = array();
		foreach ( $matches[0] as $img ) {
			$images[] = new Tiny_Image_Source(
				$img,
				$this->base_dir,
				$this->allowed_domains
			);
		}

		return $images;
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
	public function filter_image_srcset(
		$sources,
		$size_array,
		$image_src,
		$image_meta,
		$attachment_id
	) {
		foreach ( $sources as &$src ) {
			$src['url'] = $this->filter_attachment_url( $src['url'], $attachment_id );
		}
		return $sources;
	}
}


class Tiny_Image_Source {
	/**
	 * The raw HTML img element as a string
	 * @var string
	 */
	public $image;

	private $base_dir;

	private $allowed_domains;

	private $valid_mimetypes;


	public function __construct( $img_element, $base_dir, $domains ) {
		$this->image = $img_element;
		$this->base_dir = $base_dir;
		$this->allowed_domains = $domains;
		$this->valid_mimetypes = array( 'image/webp', 'image/avif' );
	}

	/**
	 * Attempts to get an HTML attribute from the given string
	 *
	 * @param string $html The string to parse
	 * @param string $attribute_name The name of the attribute to search for.
	 * @return string The value of the attribute, or an empty string if not found.
	 */
	private static function get_attribute_value( $element, $name ) {
		$regex = '#\b' . preg_quote( $name, '#' ) . '\s*=\s*(["\'])(.*?)\1#is';
		if ( preg_match( $regex, $element, $attr_matches ) ) {
			return $attr_matches[2];
		}
		return null;
	}

	/**
	 * Retrieves the image sources from the img element
	 *
	 * @return array{path: string, size: string}[] The image sources
	 */
	private function get_image_srcsets() {
		$result = array();
		$srcset = $this::get_attribute_value( $this->image, 'srcset' );

		if ( $srcset ) {
			// Split the srcset to get individual entries
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

		$source = $this::get_attribute_value( $this->image, 'src' );
		if ( ! empty( $source ) ) {
			// No srcset, but we have a src attribute
			$result[] = array(
				'path' => $source,
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

	/**
	 * Generates a formatted image source array if the corresponding local file exists.
	 *
	 * Attempts to replace the file extension of the provided image path with the
	 * specified MIME type, resolves the local path of the resulting file, and returns
	 * the `srcset` and `type` if the file exists.
	 *
	 * @param array  $imgsrc   An associative array containing at least the keys 'path'
	 * 						   (string) and 'size' (string).
	 * @param string $mimetype The target MIME type (e.g., 'image/webp', 'image/avif').
	 *
	 * @return array|null An array with 'srcset' and 'type' if the file exists locally,
	 * 					  or null otherwise.
	 */
	private function get_formatted_source( $imgsrc, $mimetype ) {
		$format_url = Tiny_Helpers::replace_file_extension( $mimetype, $imgsrc['path'] );
		$local_path = $this->get_local_path( $format_url );
		if ( empty( $local_path ) ) {
			return null;
		}

		$exists_local = file_exists( $local_path );
		if ( $exists_local ) {
			return array(
				'src' => $format_url,
				'size' => $imgsrc['size'],
				'type' => $mimetype,
			);
		}
		return null;
	}

	public function create_picture_elements() {
		$srcsets = $this->get_image_srcsets();

		$srcset_parts = array();
		foreach ( $srcsets as $srcset ) {
			foreach ( $this->valid_mimetypes as $mimetype ) {
				$new_srcset = $this->get_formatted_source( $srcset, $mimetype );

				if ( $new_srcset ) {
					$srcset_parts[] = $new_srcset;
					break;
				}
			}
		}

		if ( empty( $srcset_parts ) ) {
			return $this->image;
		}

		$picture_element = array( '<picture>' );
		foreach ( $srcset_parts as $source ) {
			$srcset = trim( $source['src'] . ' ' . $source['size'] );
			$picture_element[] =
				'<source srcset="' . $srcset . '" type="' . $source['type'] . '" />';
		}
		$picture_element[] = $this->image;
		$picture_element[] = '</picture>';

		return implode( '', $picture_element );
	}
}
