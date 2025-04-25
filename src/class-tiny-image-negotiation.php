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
class Tiny_Image_Negotiation extends Tiny_WP_Base
{
	function __construct()
	{	
		if (is_admin()) {
			return;
		}
		add_filter( 'wp_get_attachment_url', array($this, 'filter_attachment_url'), 10, 2 );
		add_filter( 'wp_get_attachment_image_src', array($this, 'filter_attachment_image_src'), 10, 4 );
		add_filter( 'wp_calculate_image_srcset', array($this, 'filter_image_srcset'), 10, 5 );
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
			$accepted_formats[] = array( 'ext' => '.avif', 'mime' => 'image/avif' );
		}
		if ( stripos( $accept, 'image/webp' ) !== false ) {
			$accepted_formats[] = array( 'ext' => '.webp', 'mime' => 'image/webp' );
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
		if ( empty($supported_formats) ) {
			return $url;
		}

		$uploads = wp_upload_dir();
		$basedir = trailingslashit( $uploads['basedir'] );
		$baseurl = trailingslashit( $uploads['baseurl'] );

		$relative = str_replace( $baseurl, '', $url );
		$path     = $basedir . $relative;

		foreach($supported_formats as $format) {
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
