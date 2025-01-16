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
	public static function truncate_text( string $text, int $length ) {
		if ( mb_strlen( $text ) > $length ) {
			return mb_substr( $text, 0, $length - 3 ) . '...';
		}
		return $text;
	}
}
