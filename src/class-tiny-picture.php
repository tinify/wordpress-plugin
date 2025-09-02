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
class Tiny_Picture extends Tiny_WP_Base
{

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
	function __construct($base_dir = ABSPATH, $domains = array())
	{
		$this->base_dir        = $base_dir;
		$this->allowed_domains = $domains;

		if (is_admin() || is_customize_preview()) {
			return;
		}

		if (defined('DOING_AJAX') && DOING_AJAX) {
			return;
		}

		if (defined('DOING_CRON') && DOING_CRON) {
			return;
		}

		add_action('template_redirect', function () {
			ob_start(array($this, 'replace_sources'), 1000);
		});
	}

	public function replace_sources($content)
	{
		$content = $this->replace_picture_sources($content);
		$content = $this->replace_img_sources($content);

		return $content;
	}

	/**
	 * Will extend existing picture elements with additional sourcesets
	 *
	 * @param string $content
	 * @return string the new source html
	 */
	private function replace_picture_sources($content)
	{
		$picture_sources = $this->filter_pictures($content);
		foreach ($picture_sources as $picture_source) {
			$content = $this->replace_picture($content, $picture_source);
		}
		return $content;
	}

	private function replace_img_sources($content)
	{
		$image_sources = $this->filter_images($content);
		foreach ($image_sources as $image_source) {
			$content = Tiny_Picture::replace_image($content, $image_source);
		}
		return $content;
	}

	/**
	 * Will search for all picture elements within the given source html
	 *
	 * @param string $content
	 * @return array<Tiny_Picture_Source> an array of picture element sources
	 */
	private function filter_pictures($content)
	{
		$matches = array();
		/*
		 * Match <picture> blocks that contain one or more <source> tags.
		 *
		 * Pattern parts:
		 * - (?:<picture[^>]*?>\s*): opening <picture> with optional attributes and
		 *   trailing whitespace.
		 * - (?:<source[^>]*?>)+: one or more <source> tags inside the picture.
		 * - (?:.*?</picture>)?: optionally include everything up to the closing
		 *   </picture>.
		 *
		 * Modifiers:
		 * - i: case-insensitive.
		 * - s: dot matches newlines.
		 */
		if (! preg_match_all('#(?:<picture[^>]*?>\s*)(?:<source[^>]*?>)+(?:.*?</picture>)?#is', $content, $matches)) {
			return array();
		}

		$pictures = array();
		foreach ($matches[0] as $raw_picture) {
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
	private function replace_picture($content, $source)
	{
		$content = str_replace($source->raw_html, $source->augment_picture_element(), $content);
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
	private static function replace_image($content, $source)
	{
		$content = str_replace($source->raw_html, $source->create_picture_elements(), $content);
		return $content;
	}

	/**
	 * Filters out all images from the content and returns them as an array.
	 *
	 * @return Tiny_Image[]
	 */
	private function filter_images($content)
	{
		// Extract only the <body>...</body> section.
		if (preg_match('/(?=<body).*<\/body>/is', $content, $body)) {
			$content = $body[0];
		}

		// strip HTML comments.
		$content = preg_replace('/<!--(.*)-->/Uis', '', $content);

		// strip existing <picture> blocks to avoid double-processing.
		$content = preg_replace('/<picture\b.*?>.*?<\/picture>/is', '', $content);

		// Strip <noscript> blocks to avoid altering their contents.
		$content = preg_replace('/<noscript\b.*?>.*?<\/noscript>/is', '', $content);


		// Find all <img> tags with any attributes.
		if (!preg_match_all('/<img\b[^>]*>/is', $content, $matches)) {
			return array();
		}

		$images = array();
		foreach ($matches[0] as $img) {
			$images[] = new Tiny_Image_Source(
				$img,
				$this->base_dir,
				$this->allowed_domains
			);
		}

		return $images;
	}
}

abstract class Tiny_Source_Base
{
	public $raw_html;
	protected $base_dir;
	protected $allowed_domains;
	protected $valid_mimetypes;

	public function __construct($html, $base_dir, $domains)
	{
		$this->raw_html 	   = $html;
		$this->base_dir        = $base_dir;
		$this->allowed_domains = $domains;
		$this->valid_mimetypes = array('image/webp', 'image/avif');
	}

	protected static function get_attribute_value($element, $name)
	{
		// Find {name} enclosed in single or double quotes after '='
		$regex = '#\b' . preg_quote($name, '#') . '\s*=\s*(["\'])(.*?)\1#is';
		if (preg_match($regex, $element, $attr_matches)) {
			return $attr_matches[2];
		}
		return null;
	}

	protected function get_local_path($url)
	{
		if (strpos($url, 'http') === 0) {
			$matched_domain = null;

			foreach ($this->allowed_domains as $domain) {
				if (strpos($url, $domain) === 0) {
					$matched_domain = $domain;
					break;
				}
			}

			if (null === $matched_domain) {
				return '';
			}

			$url = substr($url, strlen($matched_domain));
		}
		$url = $this->base_dir . $url;

		return $url;
	}

	protected function get_formatted_source($image_source_data, $mimetype)
	{
		$format_url = Tiny_Helpers::replace_file_extension($mimetype, $image_source_data['path']);
		$local_path = $this->get_local_path($format_url);
		if (empty($local_path)) {
			return null;
		}

		$exists_local = file_exists($local_path);
		if ($exists_local) {
			return array(
				'src' => $format_url,
				'size' => $image_source_data['size'],
				'type' => $mimetype,
			);
		}
		return null;
	}

	protected function build_alternative_sources_for_url($url, $size = '')
	{
		$sources = array();
		foreach ($this->valid_mimetypes as $mimetype) {
			$formatted = $this->get_formatted_source(array('path' => $url, 'size' => $size), $mimetype);
			if ($formatted) {
				$srcset = trim($formatted['src'] . ' ' . $formatted['size']);
				$sources[] = '<source srcset="' . $srcset . '" type="' . $formatted['type'] . '" />';
				break;
			}
		}
		return $sources;
	}

	/**
	 * Will parse the srcset attribute
	 *
	 * @param string $srcset
	 * @return array{ path: string, size: string } srcset parts
	 */
	protected static function parse_srcset_list($srcset)
	{
		$out = [];
		foreach (explode(',', $srcset) as $entry) {
			$entry = trim($entry);
			if ($entry === '') continue;
			$parts = preg_split('/\s+/', $entry, 2);
			$out[] = ['path' => $parts[0], 'size' => $parts[1] ?? ''];
		}
		return $out;
	}
}

class Tiny_Picture_Source extends Tiny_Source_Base
{

	/**
	 * Adds alternative format sources (e.g., image/webp, image/avif) to an existing
	 * <picture> element based on locally available converted files.
	 * 
	 *
	 * @return string The augmented <picture> HTML or the original if no additions.
	 */
	public function augment_picture_element()
	{
		$new_sources = array();

		// Find existing <source> tags inside the <picture>.
		if (preg_match_all('#<source\b[^>]*>#i', $this->raw_html, $source_tag_matches)) {
			foreach ($source_tag_matches[0] as $source_tag_html) {
				// Extract srcset="..."
				if (!preg_match('#\bsrcset\s*=\s*([\"\'])(.*?)\1#i', $source_tag_html, $m)) continue;
				$media = '';
				// Extract optional media="..." to preserve any media query
				if (preg_match('#\bmedia\s*=\s*([\"\'])(.*?)\1#i', $source_tag_html, $mm)) {
					$media = $mm[2];
				}
				foreach (self::parse_srcset_list($m[2]) as $entry) {
					foreach ($this->valid_mimetypes as $mimetype) {
						$formatted = $this->get_formatted_source($entry, $mimetype);
						if ($formatted) {
							$srcset = trim($formatted['src'] . ' ' . $formatted['size']);
							$tag = '<source srcset="' . $srcset . '" type="' . $formatted['type'] . '"';
							if ($media) $tag .= ' media="' . $media . '"';
							$new_sources[] = $tag . ' />';
							break;
						}
					}
				}
			}
		}

		// inner <img>
		if (preg_match('#<img\b[^>]*>#i', $this->raw_html, $img_tag_match)) {
			$img_tag = $img_tag_match[0];
			$candidates = [];
			// Extract srcset="..."
			if (preg_match('#\bsrcset\s*=\s*([\"\'])(.*?)\1#i', $img_tag, $m)) {
				$candidates = array_merge($candidates, self::parse_srcset_list($m[2]));
			}
			// Extract fallback src="..."
			if (preg_match('#\bsrc\s*=\s*([\"\'])(.*?)\1#i', $img_tag, $m)) {
				$candidates[] = ['path' => $m[2], 'size' => ''];
			}
			foreach ($candidates as $entry) {
				foreach ($this->valid_mimetypes as $mimetype) {
					$formatted = $this->get_formatted_source($entry, $mimetype);
					if ($formatted) {
						$srcset = trim($formatted['src'] . ' ' . $formatted['size']);
						$new_sources[] = '<source srcset="' . $srcset . '" type="' . $formatted['type'] . '" />';
						break;
					}
				}
			}
		}
		if (empty($new_sources)) {
			return $this->raw_html;
		}

		$insertion = implode('', $new_sources);

		// Insert newly built <source> elements immediately before the first <img>
		return preg_replace('#(<img\b)#i', $insertion . '$1', $this->raw_html, 1);
	}
}

class Tiny_Image_Source extends Tiny_Source_Base
{
	/**
	 * Retrieves the image sources from the img element
	 *
	 * @return array{path: string, size: string}[] The image sources
	 */
	private function get_image_srcsets()
	{
		$result = array();
		$srcset = $this::get_attribute_value($this->raw_html, 'srcset');

		if ($srcset) {
			// Split the srcset to get individual entries
			$srcset_entries = explode(',', $srcset);

			foreach ($srcset_entries as $entry) {
				// Trim whitespace
				$entry = trim($entry);

				// Split by whitespace to separate path and size descriptor
				$parts = preg_split('/\s+/', $entry, 2);

				if (count($parts) === 2) {
					// We have both path and size
					$result[] = array(
						'path' => $parts[0],
						'size' => $parts[1],
					);
				} elseif (count($parts) === 1) {
					// We only have a path (unusual in srcset)
					$result[] = array(
						'path' => $parts[0],
						'size' => '',
					);
				}
			}
		}

		$source = $this::get_attribute_value($this->raw_html, 'src');
		if (! empty($source)) {
			// No srcset, but we have a src attribute
			$result[] = array(
				'path' => $source,
				'size' => '',
			);
		}
		return $result;
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

	public function create_picture_elements()
	{
		$srcsets = $this->get_image_srcsets();

		$srcset_parts = array();
		foreach ($srcsets as $srcset) {
			foreach ($this->valid_mimetypes as $mimetype) {
				$new_srcset = $this->get_formatted_source($srcset, $mimetype);

				if ($new_srcset) {
					$srcset_parts[] = $new_srcset;
					break;
				}
			}
		}

		if (empty($srcset_parts)) {
			return $this->raw_html;
		}

		$picture_element = array('<picture>');
		foreach ($srcset_parts as $source_part) {
			$srcset = trim($source_part['src'] . ' ' . $source_part['size']);
			$picture_element[] =
				'<source srcset="' . $srcset . '" type="' . $source_part['type'] . '" />';
		}
		$picture_element[] = $this->raw_html;
		$picture_element[] = '</picture>';

		return implode('', $picture_element);
	}
}
