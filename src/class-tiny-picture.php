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

require_once __DIR__ . '/class-tiny-source-base.php';
require_once __DIR__ . '/class-tiny-source-image.php';
require_once __DIR__ . '/class-tiny-source-picture.php';

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
	public function __construct( $base_dir = ABSPATH, $domains = array() ) {
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

		if ( Tiny_Helpers::is_pagebuilder_request() ) {
			return;
		}

		add_action(
			'template_redirect',
			function () {
				ob_start( array( $this, 'replace_sources' ), 1000 );
			}
		);
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
	 * @return array<Tiny_Source_Picture> an array of picture element sources
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
			$pictures[] = new Tiny_Source_Picture(
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
	 * @param Tiny_Source_Picture $source the picture element
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
	 * @param Tiny_Source_Image $source the picture element
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
			$images[] = new Tiny_Source_Image(
				$img,
				$this->base_dir,
				$this->allowed_domains
			);
		}

		return $images;
	}
}
