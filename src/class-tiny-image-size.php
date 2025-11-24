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

class Tiny_Image_Size {
	public $filename;
	public $meta = array();

	/* Used more than once and not trivial, so we are memoizing these */
	private $_exists;
	private $_file_size;
	private $_mime_type;
	private $_duplicate         = false;
	private $_duplicate_of_size = '';

	public function __construct( $filename = null ) {
		$this->filename = $filename;
	}

	public function end_time() {
		if ( isset( $this->meta['end'] ) ) {
			return $this->meta['end'];
		} elseif ( isset( $this->meta['timestamp'] ) ) {
			return $this->meta['timestamp'];
		} else {
			return null;
		}
	}

	public function add_tiny_meta_start() {
		$this->meta = array(
			'start' => time(),
		);
	}

	public function add_tiny_meta( $response ) {
		if ( isset( $this->meta['start'] ) ) {
			$this->meta        = $response;
			$this->meta['end'] = time();
		}
	}

	public function add_tiny_meta_error( $exception ) {
		if ( isset( $this->meta['start'] ) ) {
			$this->meta = array(
				'error'     => $exception->get_type(),
				'message'   => $exception->get_message(),
				'timestamp' => time(),
			);
		}
	}

	/**
	 * Marks the image size as compressed without actually processing it.
	 *
	 * This method simulates the compression process by creating metadata that
	 * indicates the image has been processed, while keeping the original file
	 * size and format unchanged. Useful for marking images as compressed when
	 * they don't need actual compression or have been processed externally.
	 *
	 * @since 3.0.0
	 *
	 * @param bool $include_conversion Optional. Whether to include conversion metadata.
	 *                                 When true, adds conversion data with current
	 *                                 file information. Default false.
	 * @return void
	 */
	public function mark_as_compressed( $include_conversion = false ) {

		if ( ! $this->has_been_compressed() ) {
			$this->add_tiny_meta_start();
			$tiny_image_size_meta = array(
				'input'  => array(
					'size' => $this->filesize(),
				),
				'output' => array(
					'size' => $this->filesize(),
					'type' => $this->mimetype(),
				),
			);
			$this->add_tiny_meta( $tiny_image_size_meta );
		}

		if ( ! $this->has_been_converted() && $include_conversion ) {
			$this->meta['convert'] = array(
				'size' => $this->filesize(),
				'type' => $this->mimetype(),
				'path' => $this->filename,
			);
		}
	}

	public function has_been_compressed() {
		return isset( $this->meta['output'] );
	}

	public function has_been_converted() {
		return isset( $this->meta['convert'] );
	}

	public function never_compressed() {
		return ! $this->has_been_compressed();
	}

	public function filesize() {
		if ( is_null( $this->_file_size ) ) {
			if ( $this->exists() ) {
				$this->_file_size = filesize( $this->filename );
			} else {
				$this->_file_size = 0;
			}
		}
		return $this->_file_size;
	}

	public function mimetype() {
		if ( is_null( $this->_mime_type ) ) {
			if ( $this->exists() ) {
				$file             = file_get_contents( $this->filename );
				$this->_mime_type = Tiny_Helpers::get_mimetype( $file );
			} else {
				$this->_mime_type = 'application/octet-stream';
			}
		}
		return $this->_mime_type;
	}

	public function exists() {
		if ( is_null( $this->_exists ) ) {
			$this->_exists = $this->filename && file_exists( $this->filename );
		}
		return $this->_exists;
	}

	private function same_size() {
		return ( $this->filesize() == $this->meta['output']['size'] );
	}

	public function still_exists() {
		return $this->has_been_compressed() && $this->exists();
	}

	public function missing() {
		return $this->has_been_compressed() && ! $this->exists();
	}

	/**
	 * Checks wether the image has been processed for conversion.
	 * Will still return true if conversion was not needed.
	 *
	 * @return bool true if image is processed for conversion
	 */
	public function converted() {
		return isset( $this->meta['convert'] );
	}

	/**
	 * Checks wether the image is applicable for conversion and has
	 * not been converted yet.
	 *
	 * @return bool true if image can be converted and has not been converted
	 */
	public function unconverted() {
		return ! $this->converted() && $this->exists();
	}

	/**
	 * Checks if the converted image size exists
	 *
	 * @return boolean true if the image size has a optimized alternative format
	 */
	public function converted_image_exists() {
		if ( ! $this->converted() ) {
			return false;
		}
		return file_exists( $this->meta['convert']['path'] );
	}

	public function conversion_text() {
		if ( ! $this->converted() ) {
			return esc_html__( 'Not converted', 'tiny-compress-images' );
		}
		$conversion_text = $this->meta['convert']['type'] . ' (' .
			size_format( $this->meta['convert']['size'], 1 ) . ')';
		return $conversion_text;
	}

	public function compressed() {
		return $this->still_exists() && $this->same_size();
	}

	public function modified() {
		return $this->still_exists() && ! $this->same_size();
	}

	public function uncompressed() {
		return $this->exists() &&
			! $this->is_duplicate() &&
			! ( isset( $this->meta['output'] ) && $this->same_size() );
	}

	public function in_progress() {
		return $this->recently_started() && ! isset( $this->meta['output'] );
	}

	public function resized() {
		return (
			isset( $this->meta['output'] ) &&
			isset( $this->meta['output']['resized'] ) &&
			$this->meta['output']['resized']
		);
	}

	public function mark_duplicate( $duplicate_size_name ) {
		$this->_duplicate         = true;
		$this->_duplicate_of_size = $duplicate_size_name;
	}

	public function is_duplicate() {
		return $this->_duplicate;
	}

	public function duplicate_of_size() {
		return $this->_duplicate_of_size;
	}

	public function delete_converted_image_size() {
		if ( $this->converted_image_exists() ) {
			unlink( $this->meta['convert']['path'] );
		}
	}

	private function recently_started() {
		$thirty_minutes_ago = date( 'U' ) - ( 60 * 30 );
		return (
			isset( $this->meta['start'] ) &&
			$this->meta['start'] > $thirty_minutes_ago
		);
	}
}
