<?php
/*
* Tiny Compress Images - WordPress plugin.
* Copyright (C) 2015-2026 Tinify B.V.
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

/**
 * Handles sequential database migrations for the TinyPNG plugin.
 *
 * Each migration method targets a specific version and is only executed
 * once per site, tracked via the `DB_VERSION_OPTION` constant.
 *
 * @since 3.7.0
 */
class Tiny_Migrate {

	/**
	 * The current database schema version.
	 *
	 * Increment this integer by 1 each time a new migration is added.
	 *
	 * @since 3.7.0
	 * @var int
	 */
	const DB_VERSION = 1;

	/**
	 * WordPress option key used to track the applied database version.
	 *
	 * @since 3.7.0
	 * @var string
	 */
	const DB_VERSION_OPTION = 'tinypng_db_version';

	/**
	 * Runs all pending migrations in version order.
	 *
	 * Compares the stored database version against each known migration
	 * and executes any that have not yet been applied. Updates the stored
	 * version upon completion.
	 *
	 * @since 3.7.0
	 *
	 * @return void
	 */
	public static function run() {
		$stored_version = (int) get_option( self::DB_VERSION_OPTION, 0 );

		if ( $stored_version >= self::DB_VERSION ) {
			return;
		}

		if ( $stored_version < 1 && ! self::migrate_meta_key_to_private() ) {
			return;
		}

		update_option( self::DB_VERSION_OPTION, self::DB_VERSION );
	}

	/**
	 * Migrates the tiny meta key from public to private.
	 *
	 * Renames all `tiny_compress_images` post meta entries to
	 * `_tiny_compress_images`.
	 *
	 * @since 3.7.0
	 *
	 * @return boolean
	 */
	private static function migrate_meta_key_to_private() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->update(
			$wpdb->postmeta,
			array( 'meta_key' => '_tiny_compress_images' ),
			array( 'meta_key' => 'tiny_compress_images' ),
			array( '%s' ),
			array( '%s' )
		);

		if ( false === $result ) {
			return false;
		}

		// A return value of 0 means there was nothing to migrate, which is valid
 		// for fresh installs or databases that were already migrated.
		return false !== $result;;
	}
}
