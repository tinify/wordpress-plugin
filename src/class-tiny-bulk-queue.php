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
 * Optimizes the media library from a queue instead of from the browser.
 *
 * The queue is handed to wp-background-processing, which walks it in loopback
 * requests to admin-ajax.php and falls back to a WP-Cron health check when
 * those requests are cut short. Nothing depends on the bulk optimization page
 * staying open, so the browser is free to navigate away once the queue starts.
 */
class Tiny_Bulk_Queue extends Tiny_Vendor_WP_Background_Process {

	/* Progress of the current run, shown on the bulk optimization page. */
	const STATE_OPTION = 'tinypng_bulk_queue_state';

	/*
	Number of attachment IDs stored per batch row. Writing the whole library
		into a single option would produce a row of several megabytes. */
	const BATCH_SIZE = 100;

	/* Number of processed images kept around for display. */
	const LOG_SIZE = 100;

	/*
	How long a run may show no progress before it is reported as stuck. Longer
		than the cron health check interval, which restarts a chain that died,
		and long enough not to trip over the options cache holding on to the
		state this request wrote just before dispatching. */
	const STALL_AFTER = 600;

	protected $prefix = 'tinypng';
	protected $action = 'bulk_queue';

	/*
	An image with many sizes takes longer than the 60 seconds the library
		locks for by default, which would let a second process pick up the same
		batch while the first one is still working on it. */
	protected $queue_lock_time = 300;

	/**
	 * Tinify settings.
	 *
	 * @var Tiny_Settings
	 */
	private $settings;

	/**
	 * Chain ID for runs started outside of the queue's own loopback request.
	 *
	 * @var string
	 */
	private $started_chain_id;

	public function __construct( $settings ) {
		$this->settings = $settings;
		parent::__construct();
	}

	/**
	 * ID tying together the loopback requests of a single run.
	 *
	 * The library assumes that any AJAX request it finds itself in is its own
	 * loopback request, and validates that request's nonce with a check that
	 * halts the request when it fails. Starting or cancelling the queue from
	 * one of the plugin's own AJAX endpoints trips that check, so only let the
	 * parent inspect the request when the nonce really is there. Everywhere
	 * else a run simply begins a new chain.
	 *
	 * @return string
	 */
	public function get_chain_id() {
		if ( wp_doing_ajax() && ! check_ajax_referer( $this->identifier, 'nonce', false ) ) {
			if ( empty( $this->started_chain_id ) ) {
				$this->started_chain_id = wp_generate_uuid4();
			}

			return $this->started_chain_id;
		}

		return parent::get_chain_id();
	}

	/**
	 * Where the loopback request that keeps the queue moving is sent.
	 *
	 * Mirrors the WORDPRESS_HOST escape hatch used for compression on upload:
	 * in the development containers the site URL carries the port published on
	 * the host, which the container itself cannot reach.
	 *
	 * @return string
	 */
	protected function get_query_url() {
		$host = getenv( 'WORDPRESS_HOST' );

		if ( false !== $host ) {
			return $host . '/wp-admin/admin-ajax.php';
		}

		return parent::get_query_url();
	}

	/**
	 * Queue the given attachment IDs and start processing them.
	 *
	 * @param int[] $ids Attachment IDs to optimize.
	 * @return array The state the run starts out with.
	 */
	public function start( array $ids ) {
		$ids = array_values( array_unique( array_map( 'intval', $ids ) ) );

		$state                  = self::empty_state();
		$state['status']        = empty( $ids ) ? 'done' : 'running';
		$state['total']         = count( $ids );
		$state['started_at']    = time();
		$state['library_bytes'] = $this->current_library_bytes();

		self::save_state( $state );

		if ( empty( $ids ) ) {
			return $state;
		}

		$queued = 0;
		foreach ( $ids as $id ) {
			$this->push_to_queue( $id );
			++$queued;

			if ( 0 === $queued % self::BATCH_SIZE ) {
				$this->save();
			}
		}

		$this->save()->dispatch();

		return $state;
	}

	/**
	 * Stop the current run and throw away whatever is still queued.
	 */
	public function stop() {
		if ( $this->is_active() ) {
			$this->cancel();
		} else {
			$this->cancelled();
		}
	}

	/**
	 * Progress of the current run, including whether the queue is still alive.
	 *
	 * @return array
	 */
	public function get_progress() {
		$state = self::get_state();

		$state['is_processing'] = $this->is_processing();
		$state['is_queued']     = $this->is_queued();
		$state['is_active']     = $this->is_active();

		/*
		A run that is neither queued nor holding the lock, and that has not
			reported progress for a while, is never going to finish on its own:
			say so rather than leave the page spinning forever. */
		if ( 'running' === $state['status'] && ! $state['is_active'] ) {
			$last = $state['updated_at'] ? $state['updated_at'] : $state['started_at'];

			if ( $last && ( time() - $last ) > self::STALL_AFTER ) {
				$state['status'] = 'stalled';
			}
		}

		return $state;
	}

	/**
	 * Optimize a single attachment.
	 *
	 * Always returns false: an image that could not be optimized is not worth
	 * retrying within the same run, and returning the item would put it back
	 * onto the queue indefinitely.
	 *
	 * @param mixed $item Attachment ID.
	 * @return false
	 */
	protected function task( $item ) {
		$id = intval( $item );

		if ( ! $this->is_supported_attachment( $id ) ) {
			$this->record(
				array(
					'id'     => $id,
					'title'  => get_the_title( $id ),
					'status' => 'skipped',
				)
			);
			return false;
		}

		$active_sizes        = $this->settings->get_sizes();
		$active_tinify_sizes = $this->settings->get_active_tinify_sizes();

		/*
		Compressing mutates the image, so the size it started at is read from
			a separate instance, the same way the AJAX handler does it. */
		$before       = new Tiny_Image( $this->settings, $id );
		$stats_before = $before->get_statistics( $active_sizes, $active_tinify_sizes );
		$size_before  = $stats_before['compressed_total_size'];

		$tiny_image = new Tiny_Image( $this->settings, $id );

		Tiny_Logger::debug(
			'compress from bulk queue',
			array(
				'image_id' => $id,
			)
		);

		try {
			$result = $tiny_image->compress();
		} catch ( Exception $e ) {
			$this->record(
				array(
					'id'      => $id,
					'title'   => $tiny_image->get_name(),
					'status'  => 'error',
					'message' => $e->getMessage(),
				)
			);
			return false;
		}

		wp_update_attachment_metadata( $id, $tiny_image->get_wp_metadata() );

		$stats     = $tiny_image->get_statistics( $active_sizes, $active_tinify_sizes );
		$optimized = isset( $result['success'] ) ? intval( $result['success'] ) : 0;

		$this->record(
			array(
				'id'               => $id,
				'title'            => $tiny_image->get_name(),
				'status'           => $this->result_status( $result ),
				'message'          => $tiny_image->get_latest_error(),
				'sizes_compressed' => $stats['image_sizes_compressed'],
				'sizes_converted'  => $stats['image_sizes_converted'],
				'sizes_optimized'  => $stats['image_sizes_optimized'],
				'initial_size'     => size_format( $stats['initial_total_size'], 1 ),
				'optimized_size'   => size_format( $stats['compressed_total_size'], 1 ),
				'savings'          => $tiny_image->get_savings( $stats ),
				'thumbnail'        => wp_get_attachment_image(
					$id,
					array( '30', '30' ),
					true,
					array(
						'class' => 'pinkynail',
						'alt'   => '',
					)
				),
				'size_change'      => $stats['compressed_total_size'] - $size_before,
				'optimized'        => $optimized,
			)
		);

		return false;
	}

	/**
	 * Mark the run as finished once the queue has drained.
	 */
	protected function complete() {
		parent::complete();

		$state = self::get_state();
		if ( 'running' === $state['status'] ) {
			$state['status']      = 'done';
			$state['finished_at'] = time();
			self::save_state( $state );
		}
	}

	/**
	 * Mark the run as cancelled once the library has dropped the queue.
	 */
	protected function cancelled() {
		parent::cancelled();

		$state                = self::get_state();
		$state['status']      = 'cancelled';
		$state['finished_at'] = time();
		self::save_state( $state );
	}

	/**
	 * Fold the outcome of a single image into the stored progress.
	 *
	 * @param array $entry Result of optimizing one attachment.
	 */
	private function record( array $entry ) {
		$entry = array_merge(
			array(
				'id'               => 0,
				'title'            => '',
				'status'           => 'no-action',
				'message'          => null,
				'sizes_compressed' => 0,
				'sizes_converted'  => 0,
				'sizes_optimized'  => 0,
				'initial_size'     => null,
				'optimized_size'   => null,
				'savings'          => 0,
				'thumbnail'        => '',
				'size_change'      => 0,
				'optimized'        => 0,
			),
			$entry
		);

		$state = self::get_state();

		++$state['processed'];
		$state['sizes_optimized'] += $entry['optimized'];
		$state['library_bytes']   += $entry['size_change'];
		$state['updated_at']       = time();

		if ( 'error' === $entry['status'] ) {
			++$state['failed'];
		} elseif ( 'skipped' === $entry['status'] ) {
			++$state['skipped'];
		} elseif ( $entry['optimized'] > 0 ) {
			++$state['optimized'];
		}

		$state['log'][] = $entry;
		if ( count( $state['log'] ) > self::LOG_SIZE ) {
			$state['log'] = array_slice( $state['log'], -self::LOG_SIZE );
		}

		self::save_state( $state );
	}

	/**
	 * Translate the counters returned by Tiny_Image::compress() into a status.
	 *
	 * @param mixed $result Return value of Tiny_Image::compress().
	 * @return string
	 */
	private function result_status( $result ) {
		if ( ! is_array( $result ) ) {
			return 'no-action';
		}
		if ( ! empty( $result['failed'] ) ) {
			return 'error';
		}
		if ( ! empty( $result['success'] ) ) {
			return 'optimized';
		}
		return 'no-action';
	}

	private function is_supported_attachment( $id ) {
		if ( 'attachment' !== get_post_type( $id ) ) {
			return false;
		}

		return in_array(
			get_post_mime_type( $id ),
			array( 'image/jpeg', 'image/png', 'image/webp' ),
			true
		);
	}

	private function current_library_bytes() {
		$stats = Tiny_Bulk_Optimization::get_optimization_statistics( $this->settings );
		return $stats['optimized-library-size'];
	}

	public static function get_state() {
		$state = get_site_option( self::STATE_OPTION );

		if ( ! is_array( $state ) ) {
			return self::empty_state();
		}

		return array_merge( self::empty_state(), $state );
	}

	private static function save_state( array $state ) {
		update_site_option( self::STATE_OPTION, $state );
	}

	private static function empty_state() {
		return array(
			'status'          => 'idle',
			'total'           => 0,
			'processed'       => 0,
			'optimized'       => 0,
			'failed'          => 0,
			'skipped'         => 0,
			'sizes_optimized' => 0,
			'library_bytes'   => 0,
			'started_at'      => null,
			'updated_at'      => null,
			'finished_at'     => null,
			'log'             => array(),
		);
	}
}
