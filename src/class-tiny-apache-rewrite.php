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
* Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.\n*/

class Tiny_Apache_Rewrite extends Tiny_WP_Base
{

	const MARKER = 'tiny-compress-images';

	/**
	 * Generate .htaccess rewrite rules for serving WebP and AVIF images.
	 *
	 * @return string The .htaccess rules
	 */
	public static function get_rewrite_rules()
	{
		$rules = array(
			'<IfModule mod_rewrite.c>',
			'RewriteEngine On',
			'RewriteOptions Inherit',
		);

		$rules = array_merge($rules, self::get_avif_rules());
		$rules = array_merge($rules, self::get_webp_rules());

		$rules[] = '</IfModule>';

		$rules[] = '<IfModule mod_headers.c>';
		$rules[] = 'Header append Vary Accept';
		$rules[] = '<FilesMatch "\\.(webp|avif)$">';
		$rules[] = 'Header set Cache-Control "max-age=31536000, public"';
		$rules[] = '</FilesMatch>';
		$rules[] = '</IfModule>';

		$rules[] = '<IfModule mod_mime.c>';
		$rules[] = 'AddType image/webp .webp';
		$rules[] = 'AddType image/avif .avif';
		$rules[] = '</IfModule>';

		return implode("\n", $rules);
	}

	/**
	 * Generate AVIF rewrite rules.
	 *
	 * @return array[] AVIF rewrite rules
	 */
	private static function get_avif_rules()
	{
		$rules = array();
		$rules[] = 'RewriteCond %{HTTP_ACCEPT} image/avif';
		$rules[] = 'RewriteCond %{REQUEST_URI} ^(.+)\\.(?:jpe?g|png|gif)$';
		$rules[] = 'RewriteCond %{DOCUMENT_ROOT}/%1.avif -f';
		$rules[] = 'RewriteRule (.+)\\.(?:jpe?g|png|gif)$ $1.avif [T=image/avif,L]';
		return $rules;
	}

	/**
	 * Generate WebP rewrite rules.
	 *
	 * @return array[] WebP rewrite rules
	 */
	private static function get_webp_rules()
	{
		$rules = array();
		$rules[] = 'RewriteCond %{HTTP_ACCEPT} image/webp [OR]';
		$rules[] = 'RewriteCond %{HTTP_USER_AGENT} Chrome';
		$rules[] = 'RewriteCond %{REQUEST_URI} ^(.+)\\.(?:jpe?g|png|gif)$';
		$rules[] = 'RewriteCond %{DOCUMENT_ROOT}/%1.webp -f';
		$rules[] = 'RewriteRule (.+)\\.(?:jpe?g|png|gif)$ $1.webp [T=image/webp,L]';
		return $rules;
	}

	/**
	 * Install rewrite rules to .htaccess files.
	 *
	 * @return bool True on success, false otherwise
	 */
	public static function install_rules()
	{
		$rules = self::get_rewrite_rules();

		$upload_dir = wp_upload_dir();
		if (isset($upload_dir['basedir']) && is_writable($upload_dir['basedir'])) {
			$htaccess_file = $upload_dir['basedir'] . '/.htaccess';
			insert_with_markers($htaccess_file, self::MARKER, $rules);
		}

		if (is_writable(get_home_path())) {
			$htaccess_file = get_home_path() . '.htaccess';
			insert_with_markers($htaccess_file, self::MARKER, $rules);
		}

		return true;
	}

	/**
	 * Remove rewrite rules from .htaccess files.
	 *
	 * @return bool True on success, false otherwise
	 */
	public static function uninstall_rules()
	{
		$upload_dir = wp_upload_dir();
		if (isset($upload_dir['basedir']) && file_exists($upload_dir['basedir'] . '/.htaccess')) {
			$htaccess_file = $upload_dir['basedir'] . '/.htaccess';
			insert_with_markers($htaccess_file, self::MARKER, '');
		}

		if (file_exists(get_home_path() . '.htaccess')) {
			$htaccess_file = get_home_path() . '.htaccess';
			insert_with_markers($htaccess_file, self::MARKER, '');
		}

		return true;
	}
}
