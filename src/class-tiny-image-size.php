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

class Tiny_Image_Size {
	public $filename;
	public $url;
	public $meta = array();

	/* Used more than once and not trivial, so we are memoizing these */
	private $_exists;
	private $_file_size;

	public function __construct($filename = null, $url = null) {
		$this->filename = $filename;
		$this->url = $url;
	}

	public function end_time() {
		if ( isset( $this->meta['end'] ) ) {
			return $this->meta['end']; }
		elseif ( isset( $this->meta['timestamp'] ) ) {
			return $this->meta['timestamp']; }
		else {
			return null; }
	}

	public function add_request() {
		$this->meta = array( 'start' => time() );
	}

	public function add_response($response) {
		if ( isset( $this->meta['start'] ) ) {
			$this->meta = $response;
			$this->meta['end'] = time();
		}
	}

	public function add_exception($exception) {
		if ( isset( $this->meta['start'] ) ) {
			$this->meta = array(
				'error'   => $exception->get_type(),
				'message' => $exception->get_message(),
				'timestamp' => time()
			);
		}
	}

	public function has_been_compressed() {
		return isset( $this->meta['output'] );
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

	public function compressed() {
		return $this->still_exists() && $this->same_size();
	}

	public function modified() {
		return $this->still_exists() && ! $this->same_size();
	}

	public function uncompressed() {
		return $this->exists() && ! (isset( $this->meta['output'] ) && $this->same_size());
	}

	public function in_progress() {
		return $this->recently_started() && ! isset( $this->meta['output'] );
	}

	public function resized() {
		return isset( $this->meta['output'] ) && isset( $this->meta['output']['resized'] ) && $this->meta['output']['resized'];
	}

	private function recently_started() {
		$thirty_minutes_ago = date( 'U' ) - ( 60 * 30 );
		return isset( $this->meta['start'] ) && ( $this->meta['start'] > $thirty_minutes_ago );
	}
}
