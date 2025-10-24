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

abstract class Tiny_Compress {

	const KEY_MISSING = 'Register an account or provide an API key first';
	const FILE_MISSING = 'File does not exist';
	const WRITE_ERROR = 'No permission to write to file';

	protected $after_compress_callback;

	public static function create( $api_key, $after_compress_callback = null ) {
		if ( Tiny_PHP::client_supported() ) {
			$class = 'Tiny_Compress_Client';
		} elseif ( Tiny_PHP::fopen_available() ) {
			$class = 'Tiny_Compress_Fopen';
		} else {
			throw new Tiny_Exception(
				'No HTTP client is available (cURL or fopen)',
				'NoHttpClient'
			);
		}
		return new $class($api_key, $after_compress_callback);
	}

	/* Based on pricing April 2016. */
	public static function estimate_cost( $compressions, $compressions_used ) {
		return round(
			self::compression_cost( $compressions + $compressions_used ) -
				self::compression_cost( $compressions_used ),
			2
		);
	}

	protected function __construct( $after_compress_callback ) {
		$this->after_compress_callback = $after_compress_callback;
	}

	public abstract function can_create_key();
	public abstract function get_compression_count();
	public abstract function get_remaining_credits();
	public abstract function get_paying_state();
	public abstract function get_email_address();
	public abstract function get_key();

	public function limit_reached() {
		return $this->get_remaining_credits() === 0;
	}

	public function get_status() {
		if ( $this->get_key() == null ) {
			return (object) array(
				'ok' => false,
				'message' => self::KEY_MISSING,
			);
		}

		$result = false;
		$message = null;

		try {
			$result = $this->validate();
		} catch ( Tiny_Exception $err ) {
			if ( $err->get_status() == 404 ) {
				$message = 'The key that you have entered is not valid';
			} else {
				list($message) = explode( ' (HTTP', $err->getMessage(), 2 );
			}
		}

		$this->call_after_compress_callback();

		return (object) array(
			'ok' => $result,
			'message' => $message,
		);
	}

	/**
	 * Compresses a single file
	 *
	 * @param [type] $file
	 * @param array $resize_opts
	 * @param array $preserve_opts
	 * @param array{ string } conversion options
	 * @return void
	 */
	public function compress_file(
		$file,
		$resize_opts = array(),
		$preserve_opts = array(),
		$convert_to = array()
	) {
		if ( $this->get_key() == null ) {
			throw new Tiny_Exception( self::KEY_MISSING, 'KeyError' );
		}

		if ( ! file_exists( $file ) ) {
			throw new Tiny_Exception( self::FILE_MISSING, 'FileError' );
		}

		if ( ! is_writable( $file ) ) {
			throw new Tiny_Exception( self::WRITE_ERROR, 'FileError' );
		}

		if ( ! $this->needs_resize( $file, $resize_opts ) ) {
			$resize_opts = false;
		}

		try {
			$file_data = file_get_contents( $file );

			list($output, $details, $convert_output ) = $this->compress(
				$file_data,
				$resize_opts,
				$preserve_opts,
				$convert_to
			);
		} catch ( Tiny_Exception $err ) {
			$this->call_after_compress_callback();
			throw $err;
		}

		try {
			file_put_contents( $file, $output );
		} catch ( Exception $e ) {
			throw new Tiny_Exception( $e->getMessage(), 'FileError' );
		}

		if ( $convert_output ) {
			$converted_filepath = Tiny_Helpers::replace_file_extension(
				$details['convert']['type'],
				$file
			);

			try {
				file_put_contents( $converted_filepath, $convert_output );
			} catch ( Exception $e ) {
				throw new Tiny_Exception( $e->getMessage(), 'FileError' );
			}
			$details['convert']['path'] = $converted_filepath;
		}

		if ( $resize_opts ) {
			$details['output']['resized'] = true;
		}

		$this->call_after_compress_callback();

		return $details;
	}

	protected abstract function validate();
	protected abstract function compress(
		$input,
		$resize_options,
		$preserve_options,
		$convert_to
	);

	protected static function identifier() {
		return 'WordPress/' . Tiny_Plugin::wp_version() . ' Plugin/' . Tiny_Plugin::version();
	}

	private function call_after_compress_callback() {
		if ( $this->after_compress_callback ) {
			call_user_func( $this->after_compress_callback, $this );
		}
	}

	private static function needs_resize( $file, $resize_options ) {
		if ( ! $resize_options ) {
			return false;
		}

		list($width, $height) = getimagesize( $file );

		$should_resize_width  = isset( $resize_options['width'] ) &&
			$width > $resize_options['width'];
		$should_resize_height = isset( $resize_options['height'] ) &&
			$height > $resize_options['height'];

		return $should_resize_width || $should_resize_height;
	}

	private static function compression_cost( $total ) {
		$cost = 0;

		if ( $total > 10000 ) {
			$compressions = $total - 10000;
			$cost += $compressions * 0.002;
			$total -= $compressions;
		}

		if ( $total > 500 ) {
			$compressions = $total - 500;
			$cost += $compressions * 0.009;
			$total -= $compressions;
		}

		return $cost;
	}
}
