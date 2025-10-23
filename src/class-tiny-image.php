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

class Tiny_Image {
	const ORIGINAL = 0;

	private $settings;
	private $id;
	private $name;
	private $wp_metadata;
	private $sizes = array();
	private $statistics = array();

	public function __construct(
			$settings,
			$id,
			$wp_metadata = null,
			$tiny_metadata = null,
			$active_sizes = null,
			$active_tinify_sizes = null
	) {
		$this->settings = $settings;
		$this->id = $id;
		$this->wp_metadata = $wp_metadata;
		$this->parse_wp_metadata();
		$this->parse_tiny_metadata( $tiny_metadata );
		$this->detect_duplicates( $active_sizes, $active_tinify_sizes );
	}

	private function parse_wp_metadata() {
		if ( ! is_array( $this->wp_metadata ) ) {
			$this->wp_metadata = wp_get_attachment_metadata( $this->id );
		}
		if ( ! is_array( $this->wp_metadata ) ) {
			return;
		}
		if ( ! isset( $this->wp_metadata['file'] ) ) {
			/* No file metadata found, this might be another plugin messing with
			   metadata. Simply ignore this! */
			return;
		}

		$upload_dir = wp_upload_dir();
		$path_prefix = $upload_dir['basedir'] . '/';
		$path_info = pathinfo( $this->wp_metadata['file'] );
		if ( isset( $path_info['dirname'] ) ) {
			$path_prefix .= $path_info['dirname'] . '/';
		}

		/* Do not use pathinfo for getting the filename.
			 It doesn't work when the filename starts with a special character. */
		$path_parts = explode( '/', $this->wp_metadata['file'] );
		$this->name = end( $path_parts );
		$filename = $path_prefix . $this->name;
		$this->sizes[ self::ORIGINAL ] = new Tiny_Image_Size( $filename );

		if ( isset( $this->wp_metadata['sizes'] ) && is_array( $this->wp_metadata['sizes'] ) ) {
			foreach ( $this->wp_metadata['sizes'] as $size_name => $info ) {
				$this->sizes[ $size_name ] = new Tiny_Image_Size( $path_prefix . $info['file'] );
			}
		}
	}

	private function detect_duplicates( $active_sizes, $active_tinify_sizes ) {
		$filenames = array();

		if ( is_array( $this->wp_metadata )
			&& isset( $this->wp_metadata['file'] )
			&& isset( $this->wp_metadata['sizes'] )
			&& is_array( $this->wp_metadata['sizes'] ) ) {

			if ( null == $active_sizes ) {
				$active_sizes = $this->settings->get_sizes();
			}
			if ( null == $active_tinify_sizes ) {
				$active_tinify_sizes = $this->settings->get_active_tinify_sizes();
			}

			foreach ( $this->wp_metadata['sizes'] as $size_name => $size ) {
				if ( $this->sizes[ $size_name ]->has_been_compressed()
					&& array_key_exists( $size_name, $active_sizes ) ) {
					$filenames = $this->duplicate_check( $filenames, $size['file'], $size_name );
				}
			}
			foreach ( $this->wp_metadata['sizes'] as $size_name => $size ) {
				if ( in_array( $size_name, $active_tinify_sizes, true ) ) {
					$filenames = $this->duplicate_check( $filenames, $size['file'], $size_name );
				}
			}
			foreach ( $this->wp_metadata['sizes'] as $size_name => $size ) {
				if ( array_key_exists( $size_name, $active_sizes ) ) {
					$filenames = $this->duplicate_check( $filenames, $size['file'], $size_name );
				}
			}
			foreach ( $this->wp_metadata['sizes'] as $size_name => $size ) {
				$filenames = $this->duplicate_check( $filenames, $size['file'], $size_name );
			}
		}
	}

	private function duplicate_check( $filenames, $file, $size_name ) {
		if ( isset( $filenames[ $file ] ) ) {
			if ( $filenames[ $file ] != $size_name ) {
				$this->sizes[ $size_name ]->mark_duplicate( $filenames[ $file ] );
			}
		} else {
			$filenames[ $file ] = $size_name;
		}
		return $filenames;
	}

	private function parse_tiny_metadata( $tiny_metadata = null ) {
		if ( is_null( $tiny_metadata ) ) {
			$tiny_metadata = get_post_meta( $this->id, Tiny_Config::META_KEY, true );
		}
		if ( $tiny_metadata ) {
			foreach ( $tiny_metadata as $size => $meta ) {
				if ( ! isset( $this->sizes[ $size ] ) ) {
					if ( self::is_retina( $size ) && Tiny_Settings::wr2x_active() ) {
						$size_name = rtrim( $size, '_wr2x' );
						if ( 'original' === $size_name ) {
							$size_name = '0';
						}
						$retina_path = wr2x_get_retina(
							$this->sizes[ $size_name ]->filename
						);
						$this->sizes[ $size ] = new Tiny_Image_Size( $retina_path );
					} else {
						$this->sizes[ $size ] = new Tiny_Image_Size();
					}
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

	public function file_type_allowed() {
		return in_array( $this->get_mime_type(), array( 'image/jpeg', 'image/png', 'image/webp' ) );
	}

	public function get_mime_type() {
		return get_post_mime_type( $this->id );
	}

	public function compress() {
		if ( $this->settings->get_compressor() === null || ! $this->file_type_allowed() ) {
			return;
		}

		$success = 0;
		$failed = 0;

		$compressor = $this->settings->get_compressor();
		$active_tinify_sizes = $this->settings->get_active_tinify_sizes();

		if ( $this->settings->get_conversion_enabled() ) {
			$uncompressed_sizes = $this->filter_image_sizes( 'uncompressed', $active_tinify_sizes );
			$unconverted_sizes = $this->filter_image_sizes( 'unconverted', $active_tinify_sizes );

			$unprocessed_sizes = $uncompressed_sizes + $unconverted_sizes;
		} else {
			$unprocessed_sizes = $this->filter_image_sizes( 'uncompressed', $active_tinify_sizes );
		}

		foreach ( $unprocessed_sizes as $size_name => $size ) {
			if ( ! $size->is_duplicate() ) {
				$size->add_tiny_meta_start();
				$this->update_tiny_post_meta();
				$resize = $this->settings->get_resize_options( $size_name );
				$preserve = $this->settings->get_preserve_options( $size_name );
				$convert_opts = $this->settings->get_conversion_options();
				try {
					$response = $compressor->compress_file(
						$size->filename,
						$resize,
						$preserve,
						$convert_opts
					);
					$size->add_tiny_meta( $response );
					$success++;
				} catch ( Tiny_Exception $e ) {
					$size->add_tiny_meta_error( $e );
					$failed++;
				}
				$this->add_wp_metadata( $size_name, $size );
				$this->update_tiny_post_meta();
			}
		}

		/*
			Other plugins can hook into this action to execute custom logic
			after the image sizes have been compressed, ie. cache flushing.
		*/
		do_action( 'tiny_image_after_compression', $this->id, $success );

		return array(
			'success' => $success,
			'failed' => $failed,
		);
	}

	public function delete_converted_image() {
		$sizes = $this->get_image_sizes();
		foreach ( $sizes as $size ) {
			$size->delete_converted_image_size();
		}
	}

	public function compress_retina( $size_name, $path ) {
		if ( $this->settings->get_compressor() === null || ! $this->file_type_allowed() ) {
			return;
		}

		if ( ! isset( $this->sizes[ $size_name ] ) ) {
			$this->sizes[ $size_name ] = new Tiny_Image_Size( $path );
		}
		$size = $this->sizes[ $size_name ];

		if ( ! $size->has_been_compressed() ) {
			$size->add_tiny_meta_start();
			$this->update_tiny_post_meta();
			$compressor = $this->settings->get_compressor();
			$preserve = $this->settings->get_preserve_options( $size_name );
			$conversion = $this->settings->get_conversion_options();

			try {
				$response = $compressor->compress_file( $path, false, $preserve, $conversion );
				$size->add_tiny_meta( $response );
			} catch ( Tiny_Exception $e ) {
				$size->add_tiny_meta_error( $e );
			}
			$this->update_tiny_post_meta();
		}
	}

	public function remove_retina_metadata() {
		// Remove metadata from all sizes, as this callback only fires when all
		// retina sizes are deleted.
		foreach ( $this->sizes as $size_name => $size ) {
			if ( self::is_retina( $size_name ) ) {
				unset( $this->sizes[ $size_name ] );
			}
		}
		$this->update_tiny_post_meta();
	}

	public function add_wp_metadata( $size_name, $size ) {
		if ( self::is_original( $size_name ) ) {
			if ( isset( $size->meta['output'] ) ) {
				$output = $size->meta['output'];
				if ( isset( $output['width'] ) && isset( $output['height'] ) ) {
					$this->wp_metadata['width'] = $output['width'];
					$this->wp_metadata['height'] = $output['height'];
					$this->wp_metadata['filesize'] = $output['size'];
				}
			}
		}
	}

	public function update_tiny_post_meta() {
		$tiny_metadata = array();
		foreach ( $this->sizes as $size_name => $size ) {
			$tiny_metadata[ $size_name ] = $size->meta;
		}
		update_post_meta( $this->id, Tiny_Config::META_KEY, $tiny_metadata );
		/*
			This action is being used by WPML:
			https://gist.github.com/srdjan-jcc/5c47685cda4da471dff5757ba3ce5ab1
		*/
		do_action( 'updated_tiny_postmeta', $this->id, Tiny_Config::META_KEY, $tiny_metadata );
	}

	public function get_image_sizes() {
		$original = isset( $this->sizes[ self::ORIGINAL ] )
			? array(
				self::ORIGINAL => $this->sizes[ self::ORIGINAL ],
			)
			: array();
		$compressed = array();
		$uncompressed = array();
		foreach ( $this->sizes as $size_name => $size ) {
			if ( self::is_original( $size_name ) ) {
				continue;
			}

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

	public function get_image_size( $size = self::ORIGINAL, $create = false ) {
		if ( isset( $this->sizes[ $size ] ) ) {
			return $this->sizes[ $size ];
		} elseif ( $create ) {
			return new Tiny_Image_Size();
		} else {
			return null;
		}
	}

	public function filter_image_sizes( $method, $filter_sizes = null ) {
		$selection = array();
		if ( is_null( $filter_sizes ) ) {
			$filter_sizes = array_keys( $this->sizes );
		}
		foreach ( $filter_sizes as $size_name ) {
			if ( ! isset( $this->sizes[ $size_name ] ) ) {
				continue;
			}

			$tiny_image_size = $this->sizes[ $size_name ];
			if ( $tiny_image_size->$method() ) {
				$selection[ $size_name ] = $tiny_image_size;
			}
		}
		return $selection;
	}

	public function get_count( $methods, $count_sizes = null ) {
		$stats = array_fill_keys( $methods, 0 );
		if ( is_null( $count_sizes ) ) {
			$count_sizes = array_keys( $this->sizes );
		}
		foreach ( $count_sizes as $size ) {
			if ( ! isset( $this->sizes[ $size ] ) ) {
				continue;
			}

			foreach ( $methods as $method ) {
				if ( $this->sizes[ $size ]->$method() ) {
					$stats[ $method ]++;
				}
			}
		}
		return $stats;
	}

	public function get_latest_error() {
		$active_tinify_sizes = $this->settings->get_active_tinify_sizes();
		$error_message = null;
		$last_timestamp = null;
		foreach ( $this->sizes as $size_name => $size ) {
			if ( in_array( $size_name, $active_tinify_sizes, true ) ) {
				if ( isset( $size->meta['error'] ) && isset( $size->meta['message'] ) ) {
					if ( null === $last_timestamp || $last_timestamp < $size->meta['timestamp'] ) {
						$last_timestamp = $size->meta['timestamp'];
						$error_message = Tiny_Helpers::truncate_text( $size->meta['message'], 140 );
					}
				}
			}
		}
		return $error_message;
	}

	public function get_savings( $stats ) {
		$before = $stats['initial_total_size'];
		$after = $stats['compressed_total_size'];
		if ( 0 === $before ) {
			$savings = 0;
		} else {
			$savings = ($before - $after) / $before * 100;
		}
		return '' . number_format( $savings, 1 );
	}

	public function get_statistics( $active_sizes, $active_tinify_sizes ) {
		if ( $this->statistics ) {
			error_log( 'Strangely the image statistics are asked for again.' );
			return $this->statistics;
		}

		$this->statistics['initial_total_size'] = 0;
		$this->statistics['compressed_total_size'] = 0;
		$this->statistics['image_sizes_compressed'] = 0;
		$this->statistics['available_uncompressed_sizes'] = 0;
		$this->statistics['image_sizes_converted'] = 0;
		$this->statistics['available_unconverted_sizes'] = 0;

		foreach ( $this->sizes as $size_name => $size ) {
			// skip duplicates or inactive sizes
			if ( $size->is_duplicate() || ! isset( $active_sizes[ $size_name ] ) ) {
				continue;
			}

			$file_size       = $size->filesize();
			$is_active_size  = in_array( $size_name, $active_tinify_sizes, true );

			if ( isset( $size->meta['input'] ) ) {
				$input_size = (int) $size->meta['input']['size'];
				$this->statistics['initial_total_size'] += $input_size;

				if ( isset( $size->meta['output'] ) ) {
					$output_size = (int) $size->meta['output']['size'];

					if ( $size->modified() ) {
						$this->statistics['compressed_total_size'] += $file_size;
						if ( $is_active_size ) {
							$this->statistics['available_uncompressed_sizes']++;
						}
					} else {
						$this->statistics['compressed_total_size'] += $output_size;
						$this->statistics['image_sizes_compressed']++;
					}
				} else {
					$this->statistics['compressed_total_size'] += $input_size;
				}
			} elseif ( $size->exists() ) {
				$this->statistics['initial_total_size']   += $file_size;
				$this->statistics['compressed_total_size'] += $file_size;
				if ( $is_active_size ) {
					$this->statistics['available_uncompressed_sizes']++;
				}
			}

			if ( $is_active_size ) {
				if ( $size->has_been_converted() ) {
					$this->statistics['image_sizes_converted']++;
				} else {
					$this->statistics['available_unconverted_sizes']++;
				}
			}
		}// End foreach().

		return $this->statistics;
	}


	public static function is_original( $size ) {
		return self::ORIGINAL === $size;
	}

	public static function is_retina( $size ) {
		return strrpos( $size, 'wr2x' ) === strlen( $size ) - strlen( 'wr2x' );
	}

	public function can_be_converted() {
		return $this->settings->get_conversion_enabled() && $this->file_type_allowed();
	}

	/**
	 * Marks the image as compressed without actually compressing it.
	 *
	 * This method parses existing metadata and delegates to each image size to mark
	 * itself as compressed. It considers conversion settings when marking the sizes.
	 * This is useful for images that are already optimized or when you want to skip
	 * compression while still marking them as processed in the system.
	 *
	 * @since 3.0.0
	 */
	public function mark_as_compressed() {
		$this->parse_tiny_metadata();

		$conversion_enabled = $this->settings->get_conversion_enabled();

		foreach ( $this->sizes as $size ) {
			$size->mark_as_compressed( $conversion_enabled );
		}

		$this->update_tiny_post_meta();
	}
}
