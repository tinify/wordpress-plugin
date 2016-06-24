<?php
/*
* Tiny Compress Images - WordPress plugin.
* Copyright (C) 2015-2016 Voormedia B.V.
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

class Tiny_Image {
	const META_KEY = 'tiny_compress_images';
	const ORIGINAL = 0;

	private $id;
	private $name;
	private $wp_metadata;
	private $sizes = array();
	private $statistics = array();

	public function __construct( $id, $wp_metadata = null, $tiny_metadata = null ) {
		$this->id = $id;
		$this->wp_metadata = $wp_metadata;
		$this->parse_wp_metadata();
		$this->parse_tiny_metadata( $tiny_metadata );
	}

	private function parse_wp_metadata() {
		if ( ! is_array( $this->wp_metadata ) ) {
			$this->wp_metadata = wp_get_attachment_metadata( $this->id );
		}
		if ( ! is_array( $this->wp_metadata ) ) {
			return;
		}
		$path_info = pathinfo( $this->wp_metadata['file'] );
		$upload_dir = wp_upload_dir();
		$path_prefix = $upload_dir['basedir'] . '/';
		$url_prefix = $upload_dir['baseurl'] . '/';
		if ( isset( $path_info['dirname'] ) ) {
			$path_prefix .= $path_info['dirname'] .'/';
			$url_prefix .= $path_info['dirname'] .'/';
		}

		$this->name = $path_info['basename'];

		$this->sizes[ self::ORIGINAL ] = new Tiny_Image_Size(
			"$path_prefix${path_info['basename']}",
			"$url_prefix${path_info['basename']}"
		);

		$unique_sizes = array();
		if ( isset( $this->wp_metadata['sizes'] ) && is_array( $this->wp_metadata['sizes'] ) ) {
			foreach ( $this->wp_metadata['sizes'] as $size => $info ) {
				$filename = $info['file'];

				if ( ! isset( $unique_sizes[ $filename ] ) ) {
					$unique_sizes[ $filename ] = true;
					$this->sizes[ $size ] = new Tiny_Image_Size(
					"$path_prefix$filename", "$url_prefix$filename");
				}
			}
		}
	}

	private function parse_tiny_metadata( $tiny_metadata ) {
		if ( is_null( $tiny_metadata ) ) {
			$tiny_metadata = get_post_meta( $this->id, self::META_KEY, true );
		}
		if ( $tiny_metadata ) {
			foreach ( $tiny_metadata as $size => $meta ) {
				if ( ! isset( $this->sizes[ $size ] ) ) {
					$this->sizes[ $size ] = new Tiny_Image_Size();
				}
				$this->sizes[ $size ]->meta = $meta;
			}
		}
	}

	public function get_id() {
		return $this->id;
	}

	public function get_name() {
		return $this->name;
	}

	public function get_wp_metadata() {
		return $this->wp_metadata;
	}

	public function can_be_compressed() {
		return in_array( $this->get_mime_type(), array( 'image/jpeg', 'image/png' ) );
	}

	public function get_mime_type() {
		return get_post_mime_type( $this->id );
	}

	public function compress( $settingsObject ) {

		if ( $settingsObject->get_compressor() === null || ! $this->can_be_compressed() ) {
			return;
		}

		$success = 0;
		$failed = 0;

		$compressor = $settingsObject->get_compressor();
		$active_tinify_sizes = $settingsObject->get_active_tinify_sizes();
		$uncompressed_sizes = $this->filter_image_sizes( 'uncompressed', $active_tinify_sizes );

		foreach ( $uncompressed_sizes as $size_name => $size ) {
			try {
				$size->add_request();
				$this->update_tiny_meta();
				$resize = self::is_original( $size_name ) ? $settingsObject->get_resize_options() : false;
				$preserve = count( $settingsObject->get_preserve_options() ) > 0 ? $settingsObject->get_preserve_options() : false;
				$response = $compressor->compress_file( $size->filename, $resize, $preserve );
				$size->add_response( $response );
				$this->update_wp_metadata( $size_name, $response );
				$this->update_tiny_meta();
				$success++;
			} catch (Tiny_Exception $e) {
				$size->add_exception( $e );
				$this->update_tiny_meta();
				$failed++;
			}
		}
		return array( 'success' => $success, 'failed' => $failed );
	}

	public function update_wp_metadata( $size_name, $response ) {
		if ( self::is_original( $size_name ) ) {
			if ( isset( $response['output'] ) ) {
				if ( isset( $response['output']['width'] ) && isset( $response['output']['height'] ) ) {
					$this->wp_metadata['width'] = $response['output']['width'];
					$this->wp_metadata['height'] = $response['output']['height'];
				}
			}
		}
	}

	public function update_tiny_meta() {
		$values = array();
		foreach ( $this->sizes as $size_name => $size ) {
			if ( is_array( $size->meta ) ) {
				$values[ $size_name ] = $size->meta;
			}
		}
		update_post_meta( $this->id, self::META_KEY, $values );
	}

	public function get_image_sizes() {
		$original = isset( $this->sizes[ self::ORIGINAL ] )
			? array( self::ORIGINAL => $this->sizes[ self::ORIGINAL ] )
			: array();
		$compressed = array();
		$uncompressed = array();
		foreach ( $this->sizes as $size_name => $size ) {
			if ( self::is_original( $size_name ) ) { continue; }
			if ( $size->has_been_compressed() ) {
				$compressed[ $size_name ] = $size;
			} else {
				$uncompressed[ $size_name ] = $size;
			}
		}
		ksort( $compressed );
		ksort( $uncompressed );
		return $original + $compressed + $uncompressed;
	}

	public function get_image_size($size = self::ORIGINAL, $create = false) {
		if ( isset( $this->sizes[ $size ] ) ) {
			return $this->sizes[ $size ]; }
		elseif ( $create ) {
			return new Tiny_Image_Size(); }
		else {
			return null; }
	}

	public function filter_image_sizes($method, $filter_sizes = null) {
		$selection = array();
		if ( is_null( $filter_sizes ) ) {
			$filter_sizes = array_keys( $this->sizes );
		}
		foreach ( $filter_sizes as $size_name ) {
			if ( ! isset( $this->sizes[ $size_name ] ) ) { continue; }
			$tiny_image_size = $this->sizes[ $size_name ];
			if ( $tiny_image_size->$method() ) {
				$selection[ $size_name ] = $tiny_image_size;
			}
		}
		return $selection;
	}

	public function get_count($methods, $count_sizes = null) {
		$stats = array_fill_keys( $methods, 0 );
		if ( is_null( $count_sizes ) ) {
			$count_sizes = array_keys( $this->sizes );
		}
		foreach ( $count_sizes as $size ) {
			if ( ! isset( $this->sizes[ $size ] ) ) { continue; }
			foreach ( $methods as $method ) {
				if ( $this->sizes[ $size ]->$method() ) {
					$stats[ $method ]++;
				}
			}
		}
		return $stats;
	}

	public function get_latest_error() {
		$error_message = null;
		$last_timestamp = null;
		foreach ( $this->sizes as $size ) {
			if ( isset( $size->meta['error'] ) && isset( $size->meta['message'] ) ) {
				if ( $last_timestamp === null || $last_timestamp < $size->meta['timestamp'] ) {
					$last_timestamp = $size->meta['timestamp'];
					$error_message = $size->meta['message'];
				}
			}
		}
		return $error_message;
	}

	public function get_savings( $stats ) {
		$before = $stats['initial_total_size'];
		$after = $stats['optimized_total_size'];
		if ( $before === 0 ) {
			$savings = 0;
		} else {
			$savings = ($before - $after) / $before * 100;
		}
		return '' . number_format( $savings, 1 );
	}

	public function get_statistics() {

		if ( $this->statistics ) {
			error_log( 'Strangely the image statistics are asked for again.' );
			return $this->statistics;
		}

		$this->statistics['initial_total_size'] = 0;
		$this->statistics['optimized_total_size'] = 0;
		$this->statistics['image_sizes_optimized'] = 0;
		$this->statistics['available_unoptimised_sizes'] = 0;

		$settings = new Tiny_Settings();
		$active_sizes = $settings->get_sizes();
		$active_tinify_sizes = $settings->get_active_tinify_sizes();

		foreach ( $this->sizes as $size_name => $size ) {
			if ( array_key_exists( $size_name, $active_sizes ) ) {
				if ( isset( $size->meta['input'] ) ) {
					$this->statistics['initial_total_size'] += intval( $size->meta['input']['size'] );

					if ( isset( $size->meta['output'] ) ) {
						if ( $size->modified() ) {
							$this->statistics['optimized_total_size'] += $size->filesize();
							if ( in_array( $size_name, $active_tinify_sizes, true ) ) {
								$this->statistics['available_unoptimised_sizes'] += 1;
							}
						} else {
							$this->statistics['optimized_total_size'] += intval( $size->meta['output']['size'] );
							$this->statistics['image_sizes_optimized'] += 1;
						}
					} else {
						$this->statistics['optimized_total_size'] += intval( $size->meta['input']['size'] );
					}
				} elseif ( $size->exists() ) {
					$this->statistics['initial_total_size'] += $size->filesize();
					$this->statistics['optimized_total_size'] += $size->filesize();
					if ( in_array( $size_name, $active_tinify_sizes, true ) ) {
						$this->statistics['available_unoptimised_sizes'] += 1;
					}
				}
			}
		}

		return $this->statistics;
	}

	public static function get_optimization_statistics( $result = null ) {
		global $wpdb;

		if ( is_null( $result ) ) {
			// Select posts that have "_wp_attachment_metadata" image metadata
			// and optionally contain "tiny_compress_images" metadata.
			$query =
				"SELECT
					$wpdb->posts.ID,
					$wpdb->posts.post_title,
					$wpdb->postmeta.meta_value,
					wp_postmeta_tiny.meta_value AS tiny_meta_value
				FROM $wpdb->posts
				LEFT JOIN $wpdb->postmeta
					ON $wpdb->posts.ID = $wpdb->postmeta.post_id
				LEFT JOIN $wpdb->postmeta AS wp_postmeta_tiny
					ON $wpdb->posts.ID = wp_postmeta_tiny.post_id
						AND wp_postmeta_tiny.meta_key = '" . self::META_KEY . "'
				WHERE $wpdb->posts.post_type = 'attachment'
					AND ( $wpdb->posts.post_mime_type = 'image/jpeg' OR $wpdb->posts.post_mime_type = 'image/png' )
					AND $wpdb->postmeta.meta_key = '_wp_attachment_metadata'
				ORDER BY ID DESC";

			$result = $wpdb->get_results( $query, ARRAY_A );
		}

		$stats = array();
		$stats['uploaded-images'] = 0;
		$stats['optimized-image-sizes'] = 0;
		$stats['available-unoptimised-sizes'] = 0;
		$stats['optimized-library-size'] = 0;
		$stats['unoptimized-library-size'] = 0;
		$stats['available-for-optimization'] = array();

		for ( $i = 0; $i < sizeof( $result ); $i++ ) {
			$wp_metadata = unserialize( $result[$i]['meta_value'] );
			$tiny_metadata = unserialize( $result[$i]['tiny_meta_value'] );
			if ( ! is_array( $tiny_metadata ) ) {
				$tiny_metadata = array();
			}
			$tiny_image = new Tiny_Image( $result[$i]['ID'], $wp_metadata, $tiny_metadata );
			$image_stats = $tiny_image->get_statistics();
			$stats['uploaded-images']++;
			$stats['available-unoptimised-sizes'] += $image_stats['available_unoptimised_sizes'];
			$stats['optimized-image-sizes'] += $image_stats['image_sizes_optimized'];
			$stats['optimized-library-size'] += $image_stats['optimized_total_size'];
			$stats['unoptimized-library-size'] += $image_stats['initial_total_size'];
			if ( $image_stats['available_unoptimised_sizes'] > 0 ) {
				$stats['available-for-optimization'][] = array( 'ID' => $result[$i]['ID'], 'post_title' => $result[$i]['post_title'] );
			}
		}
		return $stats;
	}

	public static function is_original( $size ) {
		return $size === self::ORIGINAL;
	}
}
