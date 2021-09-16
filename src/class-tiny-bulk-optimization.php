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

class Tiny_Bulk_Optimization {
	// Page the retrieved database results for memory purposes
	// in case the media library is extremely big.
	const PAGING_SIZE = 25000;

	public static function get_optimization_statistics( $settings, $result = null ) {
		$stats = array();
		$stats['uploaded-images'] = 0;
		$stats['optimized-image-sizes'] = 0;
		$stats['available-unoptimised-sizes'] = 0;
		$stats['optimized-library-size'] = 0;
		$stats['unoptimized-library-size'] = 0;
		$stats['available-for-optimization'] = array();

		if ( is_null( $result ) ) {
			$last_image_id = null;
			do {
				$result = self::wpdb_retrieve_images_and_metadata( $last_image_id );
				$stats = self::populate_optimization_statistics( $settings, $result, $stats );
				$last_image = end( $result );
				$last_image_id = $last_image['ID'];
			} while ( sizeof( $result ) == self::PAGING_SIZE );
		} else {
			$stats = self::populate_optimization_statistics( $settings, $result, $stats );
		}
		unset( $result );

		if ( 0 != $stats['unoptimized-library-size'] ) {
			$stats['display-percentage'] = round(
				100 -
				( $stats['optimized-library-size'] / $stats['unoptimized-library-size'] * 100 ), 1
			);
		} else {
			$stats['display-percentage'] = 0;
		}
		return $stats;
	}

	private static function wpdb_retrieve_images_and_metadata( $start_id ) {
		global $wpdb;

		// Retrieve posts that have "_wp_attachment_metadata" image metadata
		// and optionally contain "tiny_compress_images" metadata.
		$sql_start_id = ( $start_id ? " $wpdb->posts.ID < $start_id AND " : '' );
		$query =
			"SELECT
				$wpdb->posts.ID,
				$wpdb->posts.post_title,
				$wpdb->postmeta.meta_value,
				wp_postmeta_file.meta_value AS unique_attachment_name,
				wp_postmeta_tiny.meta_value AS tiny_meta_value
			FROM $wpdb->posts
			LEFT JOIN $wpdb->postmeta
				ON $wpdb->posts.ID = $wpdb->postmeta.post_id
			LEFT JOIN $wpdb->postmeta AS wp_postmeta_file
				ON $wpdb->posts.ID = wp_postmeta_file.post_id
					AND wp_postmeta_file.meta_key = '_wp_attached_file'
			LEFT JOIN $wpdb->postmeta AS wp_postmeta_tiny
				ON $wpdb->posts.ID = wp_postmeta_tiny.post_id
					AND wp_postmeta_tiny.meta_key = '" . Tiny_Config::META_KEY . "'
			WHERE
				$sql_start_id
				$wpdb->posts.post_type = 'attachment'
				AND (
					$wpdb->posts.post_mime_type = 'image/jpeg' OR
					$wpdb->posts.post_mime_type = 'image/png' OR
					$wpdb->posts.post_mime_type = 'image/webp'
				)
				AND $wpdb->postmeta.meta_key = '_wp_attachment_metadata'
			GROUP BY unique_attachment_name
			ORDER BY ID DESC
			LIMIT " . self::PAGING_SIZE;

		return $wpdb->get_results( $query, ARRAY_A ); // WPCS: unprepared SQL OK.
	}

	private static function populate_optimization_statistics( $settings, $result, $stats ) {
		$active_sizes = $settings->get_sizes();
		$active_tinify_sizes = $settings->get_active_tinify_sizes();
		for ( $i = 0; $i < sizeof( $result ); $i++ ) {
			$wp_metadata = unserialize( $result[ $i ]['meta_value'] );
			$tiny_metadata = unserialize( $result[ $i ]['tiny_meta_value'] );
			if ( ! is_array( $tiny_metadata ) ) {
				$tiny_metadata = array();
			}
			$tiny_image = new Tiny_Image(
				$settings,
				$result[ $i ]['ID'],
				$wp_metadata,
				$tiny_metadata,
				$active_sizes,
				$active_tinify_sizes
			);
			$image_stats = $tiny_image->get_statistics( $active_sizes, $active_tinify_sizes );
			$stats['uploaded-images']++;
			$stats['available-unoptimised-sizes'] += $image_stats['available_unoptimized_sizes'];
			$stats['optimized-image-sizes'] += $image_stats['image_sizes_optimized'];
			$stats['optimized-library-size'] += $image_stats['optimized_total_size'];
			$stats['unoptimized-library-size'] += $image_stats['initial_total_size'];
			if ( $image_stats['available_unoptimized_sizes'] > 0 ) {
				$stats['available-for-optimization'][] = array(
					'ID' => $result[ $i ]['ID'],
					'post_title' => $result[ $i ]['post_title'],
				);
			}
		}
		return $stats;
	}
}
