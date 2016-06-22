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

class Tiny_Compress_Fopen extends Tiny_Compress {
	protected function __construct($api_key, $after_compress_callback) {
		parent::__construct( $after_compress_callback );

		$this->last_status_code = 0;
		$this->api_key = $api_key;
	}

	public function can_create_key() {
		return false;
	}

	public function get_compression_count() {
		return null;
	}

	public function get_api_key() {
		return $this->api_key;
	}

	public function is_limit_reached() {
		return $this->last_status_code == 429;
	}

	protected function validate() {
		list($details, $headers, $status_code) = $this->shrink( null );

		if ( $status_code >= 400 && $status_code < 500 && $status_code != 401 ) {
			return (object) array(
				'ok' => true,
				'message' => null,
				'code' => null,
			);
		} else {
			return (object) array(
				'ok' => false,
				'message' => $details['message'],
				'code' => $status_code,
			);
		}
	}

	protected function compress($input, $resize_options, $preserve_options) {
		list($details, $headers, $status_code) = $this->shrink( $input );
		$this->last_status_code = $status_code;

		$outputUrl = isset( $headers['location'] ) ? $headers['location'] : null;
		if ( isset( $details['error'] ) && $details['error'] ) {
			throw new Tiny_Exception( $details['message'], $details['error'] );
		} else if ( $status_code >= 400 ) {
			throw new Tiny_Exception( 'Unexepected error in shrink', 'UnexpectedError' );
		} else if ( $outputUrl === null ) {
			throw new Tiny_Exception( 'Could not find output url', 'OutputNotFound' );
		}

		list($output, $headers, $status_code) = $this->output( $outputUrl, $resize_options, $preserve_options );
		$this->last_status_code = $status_code;

		if ( isset( $headers['content-type'] ) && substr( $headers['content-type'], 0, 16 ) == 'application/json' ) {
			$details = self::decode( $output );
			if ( isset( $details['error'] ) && $details['error'] ) {
				throw new Tiny_Exception( $details['message'], $details['error'] );
			} else {
				throw new Tiny_Exception( 'Unknown error', 'UnknownError' );
			}
		} else if ( $status_code >= 400 ) {
			throw new Tiny_Exception( 'Unexepected error in output', 'UnexpectedError' );
		}

		if ( strlen( $output ) == 0 ) {
			throw new Tiny_Exception( 'Could not download output', 'OutputError' );
		}

		if ( $resize_options || $preserve_options ) {
			$details['output'] = self::update_details( $file, $details ) + $details['output'];
		} else {
			// Combine the details for the output, partially read from FS but extra values come from the API.
			// The filesize read from disk (in update_details) is not correct sometimes.
			$details['output'] = array_merge( self::update_details( $file, $details ), $details['output'] );
		}

		return array($output, $details);
	}

	private function status_code($header) {
		if ( $header && count( $header ) > 0 ) {
			$http_code_values = explode( ' ', $header[0] );
			if ( count( $http_code_values ) > 1 ) {
				return intval( $http_code_values[1] );
			}
		}
		return null;
	}

	protected function shrink_options($input) {
		return array(
			'http' => array(
				'method' => 'POST',
				'header' => array(
					'Content-type: image/png',
					'Authorization: Basic ' . base64_encode( 'api:' . $this->api_key ),
					'User-Agent: ' . Tiny_WP_Base::plugin_identification() . ' fopen',
				 ),
				'content' => $input,
				'follow_location' => 0,
				'max_redirects' => 1, // Necessary for PHP 5.2
				'ignore_errors' => true // Apparently, a 201 is a failure
			),
			'ssl' => array(
				'cafile' => self::get_ca_file(),
				'verify_peer' => true
			)
		);
	}

	protected function shrink($input) {
		$context = stream_context_create( $this->shrink_options( $input ) );
		$request = @fopen( Tiny_Config::URL, 'r', false, $context );

		$status_code = self::status_code( $http_response_header );
		if ( ! $request ) {
			$headers = self::parse_headers( $http_response_header );

			return array(
				array(
					'error' => 'FopenError',
					'message' => 'Could not compress, enable cURL for detailed error',
				),
				$headers,
				$status_code
			);
		}

		$response = stream_get_contents( $request );
		$meta_data = stream_get_meta_data( $request );
		$headers = self::parse_headers( $meta_data['wrapper_data'] );
		fclose( $request );

		return array(self::decode( $response ), $headers, $status_code);
	}

	protected function output_options($resize_options, $preserve_options) {
		$options = array(
			'http' => array(
				'method' => 'GET',
				'header' => array(
					'User-Agent: ' . Tiny_WP_Base::plugin_identification() . ' fopen',
				 ),
			),
			'ssl' => array(
				'cafile' => self::get_ca_file(),
				'verify_peer' => true
			)
		);

		$body = array();

		if ( $preserve_options ) {
			$body['preserve'] = $preserve_options;
		}

		if ( $resize_options ) {
			$body['resize'] = $resize_options;
		}

		if ( $resize_options || $preserve_options ) {
			$options['http']['header'][] = 'Authorization: Basic ' . base64_encode( 'api:' . $this->api_key );
			$options['http']['header'][] = 'Content-Type: application/json';
			$options['http']['content'] = json_encode( $body );
		}
		return $options;
	}

	protected function output($url, $resize_options, $preserve_options) {
		$context = stream_context_create( $this->output_options( $resize_options, $preserve_options ) );
		$request = @fopen( $url, 'rb', false, $context );

		$status_code = self::status_code( $http_response_header );
		if ( $request ) {
			$response = stream_get_contents( $request );
			$meta_data = stream_get_meta_data( $request );
			$headers = self::parse_headers( $meta_data['wrapper_data'] );
			fclose( $request );
		} else {
			$response = '';
			$headers = self::parse_headers( $http_response_header );
		}
		return array($response, $headers, $status_code);
	}

	protected static function get_ca_file() {
		return dirname( __FILE__ ) . '/data/cacert.pem';
	}

	protected static function parse_headers($headers) {
		if ( ! is_array( $headers ) ) {
			$headers = explode( "\r\n", $headers );
		}
		$res = array();
		foreach ( $headers as $header ) {
			$split = explode( ':', $header, 2 );
			if ( count( $split ) === 2 ) {
				$res[strtolower( $split[0] )] = trim( $split[1] );
			}
		}
		return $res;
	}

	protected static function decode($text) {
		$result = json_decode( $text, true );
		if ( $result === null ) {
			$message = sprintf(
				'JSON: %s [%d]',
				(PHP_VERSION_ID >= 50500 ? json_last_error_msg() : 'Unknown error'),
				(PHP_VERSION_ID >= 50300 ? json_last_error() : 'Error')
			);

			throw new Tiny_Exception( $message, 'JsonError' );
		}
		return $result;
	}

	protected static function update_details($file, $details) {
		$size = filesize( $file );
		list($width, $height) = getimagesize( $file );
		return array(
			'size'	=> $size,
			'width'   => $width,
			'height'  => $height,
			'ratio'   => round( $size / $details['input']['size'], 4 )
		);
	}
}
