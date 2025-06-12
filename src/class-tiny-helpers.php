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

class Tiny_Helpers {

	/**
	 * truncate_text will truncate a string to a given length.
	 * When text is longer than the given length, the string will be truncated and
	 * the last characters will be replaced with an ellipsis.
	 *
	 * We can use mb_strlen & mb_substr as WordPress provides a compat function for
	 * it if mbstring php module is not installed.
	 *
	 * @param string $text the text
	 * @param integer $length the maximum length of the string
	 * @return string the truncated string
	 */
	public static function truncate_text( $text, $length ) {
		if ( mb_strlen( $text ) > $length ) {
			return mb_substr( $text, 0, $length - 3 ) . '...';
		}
		return $text;
	}

	/**
	 * Will replace the file extension with the specified mimetype.
	 *
	 * @param string $mimetype The format to replace the extension with.
	 * Currently supports 'image/avif' or 'image/webp'
	 * @param string $filepath The full path to replace the extension in, ex /home/user/image.png
	 *
	 * @return string The full path to the file with the new extension, ex /home/user/image.avif
	 */
	public static function replace_file_extension( $mimetype, $filepath ) {
		$parts = pathinfo( $filepath );

		if ( ! isset( $parts['extension'] ) ) {
			return $filepath;
		}

		$extension_new = self::mimetype_to_extension( $mimetype );
		if ( null === $extension_new ) {
			return $filepath;
		}

		$dir      = $parts['dirname'];
		$name     = $parts['filename'];
		$sep      = DIRECTORY_SEPARATOR;

		if ( '.' === $dir ) {
			return $name . '.' . $extension_new;
		}

		if ( $dir === $sep ) {
			return $sep . $name . '.' . $extension_new;
		}

		$dir = rtrim( $dir, '/\\' );
		return $dir . $sep . $name . '.' . $extension_new;
	}

	private static function mimetype_to_extension( $mimetype ) {
		switch ( $mimetype ) {
			case 'image/jpeg':
				return 'jpg';
			case 'image/png':
				return 'png';
			case 'image/webp':
				return 'webp';
			case 'image/avif':
				return 'avif';
			default:
				return null;
		}
	}

	/**
	 * Will return the mimetype of the file
	 *
	 * ref: https://www.php.net/manual/en/class.finfo.php
	 *
	 * @param string $input The file contents
	 * @return string The mimetype of the file
	 */
	public static function get_mimetype( $input ) {
		if ( class_exists( 'finfo' ) ) {
			$finfo = new finfo( FILEINFO_MIME_TYPE );
			$mime = $finfo->buffer( $input );
			return $mime;
		} else {
			throw new Exception( 'finfo extension is not available.' );
		}
	}
}
