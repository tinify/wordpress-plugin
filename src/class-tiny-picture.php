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
 * Class responsible for parsing and modifying html to insert picture elements.
 *
 * 1) searches for <picture> elements or <img> elements
 * 2) checks wether existing source has a modern alternative
 * 3) augments or creates a picture element
 * 4) replaces the original source with the source which includes the modern format
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

		add_action('template_redirect', function () {
			ob_start( array( $this, 'replace_sources' ), 1000 );
		});
	}

	public function replace_sources( $content ) {
		$content = $this->replace_picture_sources( $content );
		$content = $this->replace_img_sources( $content );

		return $content;
	}

	/**
	 * Will extend existing picture elements with additional sourcesets
	 *
	 * @param string $content
	 * @return string the new source html
	 */
	private function replace_picture_sources( $content ) {
		$picture_sources = $this->filter_pictures( $content );
		foreach ( $picture_sources as $picture_source ) {
			$content = $this->replace_picture( $content, $picture_source );
		}
		return $content;
	}

	private function replace_img_sources( $content ) {
		$image_sources = $this->filter_images( $content );
		foreach ( $image_sources as $image_source ) {
			$content = Tiny_Picture::replace_image( $content, $image_source );
		}
		return $content;
	}

	/**
	 * Will search for all picture elements within the given source html
	 *
	 * @param string $content
	 * @return array<Tiny_Picture_Source> an array of picture element sources
	 */
	private function filter_pictures( $content ) {
		$matches = array();
		if ( ! preg_match_all(
			'#<picture\b[^>]*>.*?<\/picture>#is',
			$content,
			$matches
		) ) {
			return array();
		}

		$pictures = array();
		foreach ( $matches[0] as $raw_picture ) {
			$pictures[] = new Tiny_Picture_Source(
				$raw_picture,
				$this->base_dir,
				$this->allowed_domains
			);
		}

		return $pictures;
	}

	/**
	 * Will add additional sourcesets to picture elements.
	 *
	 * @param string $content the full page content
	 * @param Tiny_Picture_Source $source the picture element
	 *
	 * @return string the updated content including augmented picture elements
	 */
	private function replace_picture( $content, $source ) {
		$content = str_replace( $source->raw_html, $source->augment_picture_element(), $content );
		return $content;
	}


	/**
	 * Will replace img elements with picture elements that (possibly) have additional formats.
	 *
	 * @param string $content the full page content
	 * @param Tiny_Image_Source $source the picture element
	 *
	 * @return string the updated content including augmented picture elements
	 */
	private static function replace_image( $content, $source ) {
		$content = str_replace( $source->raw_html, $source->create_picture_elements(), $content );
		return $content;
	}

	/**
	 * Filters out all images from the content and returns them as an array.
	 *
	 * @return Tiny_Image[]
	 */
	private function filter_images( $content ) {
		// Extract only the <body>...</body> section.
		if ( preg_match( '/(?=<body).*<\/body>/is', $content, $body ) ) {
			$content = $body[0];
		}

		// strip HTML comments.
		$content = preg_replace( '/<!--(.*)-->/Uis', '', $content );

		// strip existing <picture> blocks to avoid double-processing.
		$content = preg_replace( '/<picture\b.*?>.*?<\/picture>/is', '', $content );

		// Strip <noscript> blocks to avoid altering their contents.
		$content = preg_replace( '/<noscript\b.*?>.*?<\/noscript>/is', '', $content );

		// Find all <img> tags with any attributes.
		if ( ! preg_match_all( '/<img\b[^>]*>/is', $content, $matches ) ) {
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
}

abstract class Tiny_Source_Base {


	public $raw_html;
	protected $base_dir;
	protected $allowed_domains;
	protected $valid_mimetypes;

	public function __construct( $html, $base_dir, $domains ) {
		$this->raw_html 	   = $html;
		$this->base_dir        = $base_dir;
		$this->allowed_domains = $domains;
		$this->valid_mimetypes = array( 'image/webp', 'image/avif' );
	}

	protected static function get_attribute_value( $element, $name ) {
		// Match the exact attribute name (not part of data-media, mediaType, etc.)
		// and capture a single- or double-quoted value.
		$delim = '~';
		$attr  = preg_quote( $name, $delim );
		$regex = $delim . '(?<![\w:-])' . $attr . '\s*=\s*(["\'])(.*?)\1' . $delim . 'is';

		if ( preg_match( $regex, $element, $m ) ) {
			return $m[2];
		}
		return null;
	}

	/**
	 * Extract elements by tag name from an HTML string (regex-based).
	 *
	 * @param string $html     The HTML string to search in.
	 * @param string $tagname  The tag name (e.g., 'div', 'source', 'img').
	 * @return array           Array of matched elements as strings.
	 */
	protected function get_element_by_tag( $html, $tagname ) {
		$results = [];

		// Self-closing / void tag (e.g. <source />, <img />, <br />)
		if ( preg_match_all(
			'~<' . preg_quote( $tagname, '~' ) . '\b(?:[^>"\']+|"[^"]*"|\'[^\']*\')*/?>~i',
			$html,
			$matches
		) ) {
			$results = array_merge( $results, $matches[0] );
		}

		// Normal paired tags (e.g. <div>…</div>)
		$regex_tag = preg_quote( $tagname, '~' );
		if ( preg_match_all(
			'~<' . $regex_tag .
			'\b(?:[^>"\']+|"[^"]*"|\'[^\']*\')*>.*?</' .
			$regex_tag .
			'>~is',
			$html,
			$matches
		) ) {
			$results = array_merge( $results, $matches[0] );
		}

		return $results;
	}

	protected function get_local_path( $url ) {
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

	protected function get_formatted_source( $image_source_data, $mimetype ) {
		$format_url = Tiny_Helpers::replace_file_extension( $mimetype, $image_source_data['path'] );
		$local_path = $this->get_local_path( $format_url );
		if ( empty( $local_path ) ) {
			return null;
		}

		$exists_local = file_exists( $local_path );
		if ( $exists_local ) {
			return array(
				'src' => $format_url,
				'size' => $image_source_data['size'],
				'type' => $mimetype,
			);
		}
		return null;
	}

	/**
	 * Retrieves the sources from the <img> or <source> element
	 *
	 * @return array{path: string, size: string}[] The image sources
	 */
	protected function get_image_srcsets( $html ) {
		$result = array();
		$srcset = $this::get_attribute_value( $html, 'srcset' );

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

		$source = $this::get_attribute_value( $html, 'src' );
		if ( ! empty( $source ) ) {
			// No srcset, but we have a src attribute
			$result[] = array(
				'path' => $source,
				'size' => '',
			);
		}
		return $result;
	}


	/**
	 * Creates one or more <source> elements if alternative formats
	 * are available.
	 *
	 * @param string $original_source_html, either <source> or <img>
	 * @return array{string} array of <source> html
	 */
	protected function create_alternative_sources( $original_source_html ) {
		$srcsets = $this->get_image_srcsets( $original_source_html );
		if ( empty( $srcsets ) ) {
			return array();
		}

		$is_source_tag = (bool) preg_match( '#<source\b#i', $original_source_html );

		$sources = array();
		foreach ( $this->valid_mimetypes as $mimetype ) {
			$srcset_parts = [];

			foreach ( $srcsets as $srcset ) {
				$alt_source = $this->get_formatted_source( $srcset, $mimetype );
				if ( $alt_source ) {
					$srcset_parts[] = trim( $alt_source['src'] . ' ' . $alt_source['size'] );
				}
			}

			if ( ! empty( $srcset_parts ) ) {
				$source_attr_parts = array();

				$srcset_attr = implode( ', ', $srcset_parts );
				$source_attr_parts['srcset'] = $srcset_attr;

				if ( $is_source_tag ) {
					foreach ( array( 'sizes', 'media', 'width', 'height' ) as $attr ) {
						$attr_value = $this->get_attribute_value( $original_source_html, $attr );
						if ( $attr_value ) {
							$source_attr_parts[ $attr ] = $attr_value;
						}
					}
				}

				$source_attr_parts['type'] = $mimetype;
				$source_parts = array( '<source' );
				foreach ( $source_attr_parts as $source_attr_name => $source_attr_val ) {
					$source_parts[] = $source_attr_name . '="' . $source_attr_val . '"';
				}
				$source_parts[] = '/>';
				$sources[] = implode( ' ', $source_parts );
			}
		}

		return $sources;
	}
}

class Tiny_Picture_Source extends Tiny_Source_Base {



	/**
	 * Adds alternative format sources (e.g., image/webp, image/avif) to an existing
	 * <picture> element based on locally available converted files.
	 *
	 * @return string The augmented <picture> HTML or the original if no additions.
	 */
	public function augment_picture_element() {
		$modified_sources = array();

		// handle existing sources
		$optimized_types = [ 'image/webp', 'image/avif' ];

		foreach ( $this->get_element_by_tag( $this->raw_html, 'source' ) as $source_tag_html ) {
			$type_attr = self::get_attribute_value( $source_tag_html, 'type' );
			$type_attr = null !== $type_attr ? strtolower( trim( $type_attr ) ) : '';

			// Skip if already optimized.
			if ( '' !== $type_attr && in_array( $type_attr, $optimized_types, true ) ) {
				continue;
			}

			$alternative_sources = $this->create_alternative_sources( $source_tag_html );
			if ( is_array( $alternative_sources ) && $alternative_sources ) {
				foreach ( $alternative_sources as $alt ) {
					$modified_sources[] = $alt; // no array_merge in the loop
				}
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

class Tiny_Image_Source extends Tiny_Source_Base {

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
		$picture_element = array( '<picture>' );
		$picture_element[] = implode( '', $sources );
		$picture_element[] = $this->raw_html;
		$picture_element[] = '</picture>';

		return implode( '', $picture_element );
	}
}
