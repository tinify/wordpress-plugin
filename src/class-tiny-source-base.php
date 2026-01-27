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

abstract class Tiny_Source_Base {

	public $raw_html;
	protected $base_dir;
	protected $allowed_domains;
	protected $valid_mimetypes;

	public function __construct( $html, $base_dir, $domains ) {
		$this->raw_html        = $html;
		$this->base_dir        = $base_dir;
		$this->allowed_domains = $domains;
		$this->valid_mimetypes = array( 'image/avif', 'image/webp' );
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
		$results = array();

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
				'src'  => $format_url,
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

				// Split by whitespace to separate path and size/density descriptor
				$parts = preg_split( '/\s+/', $entry, 2 );

				if ( count( $parts ) === 2 ) {
					// We have both path and size
					$result[] = array(
						'path' => $parts[0],
						'size' => $parts[1],
					);
				} elseif ( count( $parts ) === 1 ) {
					// We only have a path, will be interpreted as pixel
					// density 1x (unusual in srcset)
					$result[] = array(
						'path' => $parts[0],
						'size' => '',
					);
				}
			}
		}
		return $result;
	}

	/**
	 * Retrieves the sources from the <img> or <source> element
	 *
	 * @return array{path: string, size: string}[] The image sources
	 */
	private function get_image_src( $html ) {
		$source = $this::get_attribute_value( $html, 'src' );
		if ( ! empty( $source ) ) {
			// No srcset, but we have a src attribute
			return array(
				'path' => $source,
				'size' => '',
			);
		}
		return array();
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
			// no srcset, try src attribute
			$srcsets[] = $this->get_image_src( $original_source_html );
		}

		if ( empty( $srcsets ) ) {
			return array();
		}

		$is_source_tag = (bool) preg_match( '#<source\b#i', $original_source_html );

		$sources          = array();
		$width_descriptor = $this->get_largest_width_descriptor( $srcsets );

		foreach ( $this->valid_mimetypes as $mimetype ) {
			$srcset_parts = array();

			foreach ( $srcsets as $srcset ) {
				$alt_source = $this->get_formatted_source( $srcset, $mimetype );
				if ( $alt_source ) {
					$srcset_parts[] = trim( $alt_source['src'] . ' ' . $alt_source['size'] );
				}
			}

			if (
				$width_descriptor &&
				! self::srcset_contains_width_descriptor(
					$srcset_parts,
					$width_descriptor
				)
			) {
				continue;
			}

			if ( empty( $srcset_parts ) ) {
				continue;
			}

			$source_attr_parts = array();

			$srcset_attr                 = implode( ', ', $srcset_parts );
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
			$source_parts              = array( '<source' );
			foreach ( $source_attr_parts as $source_attr_name => $source_attr_val ) {
				$source_parts[] = $source_attr_name . '="' . esc_attr( $source_attr_val ) . '"';
			}
			$source_parts[] = '/>';
			$sources[]      = implode( ' ', $source_parts );
		} // End foreach().

		return $sources;
	}

	/**
	 * Returns the largest numeric width descriptor
	 * (e.g. 2000 from "2000w") found in the srcset data.
	 *
	 * @param array<array{path: string, size: string}> $srcsets
	 * @return int
	 */
	public static function get_largest_width_descriptor( $srcsets ) {
		$largest = 0;

		foreach ( $srcsets as $srcset ) {
			if ( empty( $srcset['size'] ) ) {
				continue;
			}

			if ( preg_match( '/(\d+)w/', $srcset['size'], $matches ) ) {
				$width = (int) $matches[1];
				if ( $width > $largest ) {
					$largest = $width;
				}
			}
		}

		return $largest;
	}

	/**
	 * Determines whether a srcset list contains the provided width descriptor.
	 *
	 * @param string[] $srcset_parts
	 * @param int      $width_descriptor
	 * @return bool    true if width is in srcset
	 */
	public static function srcset_contains_width_descriptor( $srcset_parts, $width_descriptor ) {
		if ( empty( $srcset_parts ) || $width_descriptor <= 0 ) {
			return false;
		}

		$suffix        = ' ' . $width_descriptor . 'w';
		$suffix_length = strlen( $suffix );

		foreach ( $srcset_parts as $srcset_part ) {
			if ( substr( $srcset_part, -$suffix_length ) === $suffix ) {
				return true;
			}
		}

		return false;
	}
}
