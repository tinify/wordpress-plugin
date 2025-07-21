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

class Tiny_Cli {
	public static function register_command( $settings ) {
		$command_instance = new Tiny_Command( $settings );
		WP_CLI::add_command( 'tiny', $command_instance );
	}
}

class Tiny_Command {

	/**
	 * Tinify Settings
	 *
	 * @var Tiny_Settings
	 */
	private $tiny_settings;

	public function __construct( $settings ) {
		$this->tiny_settings = $settings;
	}

	/**
	 * Optimize will process images
	 *
	 * [--attachments=<strings>]
	 * : A comma separated list of attachment IDs to process. If omitted
	 * will optimize all uncompressed attachments
	 *
	 *
	 * ## EXAMPLES
	 *
	 *      optimize specific attachments
	 *      wp tiny optimize --attachments=532,603,705
	 *
	 *      optimize all unprocessed images
	 *      wp tiny optimize
	 *
	 *
	 * @param array $args
	 * @param array $assoc_args
	 * @return void
	 */
	public function optimize( $args, $assoc_args ) {
		$attachments = isset( $assoc_args['attachments'] ) ?
			array_map( 'trim', explode( ',', $assoc_args['attachments'] ) ) :
			array();

		if ( empty( $attachments ) ) {
			$attachments = $this->get_unoptimized_attachments();
		}

		if ( empty( $attachments ) ) {
			WP_CLI::success( 'No images found that need optimization.' );
			return;
		}

		$total = count( $attachments );
		WP_CLI::log( 'Optimizing ' . $total . ' images.' );

		$progress = WP_CLI\Utils\make_progress_bar( 'Optimizing images', $total );
		$optimized = 0;
		foreach ( $attachments as $attachment_id ) {
			$attachment_id = intval( $attachment_id );

			if ( ! $this->is_valid_attachment( $attachment_id ) ) {
				WP_CLI::warning( 'skipping - invalid attachment: ' . $attachment_id );
				$progress->tick();
				continue;
			}

			try {
				$result = $this->optimize_attachment( $attachment_id );
				if ( isset( $result['success'] ) && $result['success'] > 0 ) {
					$optimized++;
				}
			} catch ( Exception $e ) {
				WP_CLI::warning(
					'skipping - error: ' .
					$e->getMessage() .
					' (ID: ' .
					$attachment_id .
					')'
				);
			}

			$progress->tick();
		}

		$progress->finish();
		WP_CLI::success( 'Done! Optimized ' . $optimized . ' of ' . $total . ' images.' );
	}

	private function get_unoptimized_attachments() {
		$stats = Tiny_Bulk_Optimization::get_optimization_statistics( $this->tiny_settings );

		if ( empty( $stats['available-for-optimization'] ) ) {
			return array();
		}

		$ids = array();
		foreach ( $stats['available-for-optimization'] as $item ) {
			if ( isset( $item['ID'] ) ) {
				$ids[] = $item['ID'];
			}
		}
		return $ids;
	}

	/**
	 * Will process an attachment for optimization
	 *
	 * @return array{ success: int, failed: int }
	 */
	private function optimize_attachment( $attachment_id ) {
		$tiny_image = new Tiny_Image( $this->tiny_settings, $attachment_id );
		return $tiny_image->compress();
	}

	private function is_valid_attachment( $attachment_id ) {
		$mime_type = get_post_mime_type( $attachment_id );
		if ( ! $mime_type || strpos( $mime_type, 'image/' ) !== 0 ) {
			return false;
		}

		$supported_types = array( 'image/jpeg', 'image/png', 'image/webp' );
		if ( ! in_array( $mime_type, $supported_types, true ) ) {
			return false;
		}

		return true;
	}
}
