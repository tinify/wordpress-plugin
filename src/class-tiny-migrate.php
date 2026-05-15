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
	 * When migration fails, will pause migration for an hour
	 * when the key exists in memory
	 *
	 * @since 3.7.0
	 * @var string
	 */
	const MIGRATION_BACKOFF_KEY = 'tinypng_migration_backoff';

	/**
	 * Returns an ordered map of migrations keyed by version number.
	 *
	 * Each entry maps a version integer to a callable that performs the
	 * corresponding migration. Add new entries in ascending version order.
	 * Increment `DB_VERSION` when adding a new migration.
	 *
	 * @since 3.7.0
	 *
	 * @return array<int, callable> Ordered map of version to migration callable.
	 */
	private static function migrations() {
		return array(
			1 => array( self::class, 'migrate_meta_key_to_private' ),
		);
	}

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

		foreach ( self::migrations() as $version => $migration ) {
			if ( $stored_version >= $version ) {
				continue;
			}

			if ( get_transient( self::MIGRATION_BACKOFF_KEY ) ) {
				// transient key to hold migrations exists so exit early
				return;
			}

			if ( ! call_user_func( $migration ) ) {
				set_transient( self::MIGRATION_BACKOFF_KEY, 1, HOUR_IN_SECONDS );
				return;
			}
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
	 * @return bool True on success or when there is nothing to migrate, false on DB error.
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
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'Tinify: failed to migrate meta key. DB error: ' . $wpdb->last_error );
			return false;
		}

		// A return value of 0 means there was nothing to migrate, which is valid
		// for fresh installs or databases that were already migrated.
		return false !== $result;
	}
}
