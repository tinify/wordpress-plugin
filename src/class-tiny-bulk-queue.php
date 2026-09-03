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
 */
class Tiny_Bulk_Queue extends Tiny_Vendor_WP_Background_Process {

	/* Queue state. The compression results themselves stay in Tiny_Config::META_KEY. */
	const META_STATUS   = '_tinywp_status';
	const META_ATTEMPTS = '_tinywp_attempts';
	const META_CLAIMED  = '_tinywp_claimed';
	const META_ERROR    = '_tinywp_error';

	const STATUS_PENDING    = 'pending';
	const STATUS_PROCESSING = 'processing';
	const STATUS_DONE       = 'done';
	const STATUS_FAILED     = 'failed';
	const STATUS_SKIPPED    = 'skipped';
	const STATUS_CANCELLED  = 'cancelled';

	/* Attachments claimed per round trip to the database. */
	const BATCH_SIZE = 20;

	/* Attempts an image gets before it is left alone for the rest of the run. */
	const MAX_ATTEMPTS = 3;

	/*
	How long a claim is honoured. A process that dies mid image leaves the row
		in 'processing'; after this it is handed back to the queue. Keep it above
		the time a single image can reasonably take. */
	const CLAIM_TIMEOUT = 300;

	/*
	Summary of the current run. Written at the boundaries of a run and, for the
		table of recent results, once per image. The counts themselves are never
		stored: they are queried from postmeta. */
	const RUN_OPTION = 'tinypng_bulk_queue_run';

	/* Number of processed images kept around for display. */
	const LOG_SIZE = 20;

	/*
	How long a run may show no progress before it is reported as stuck. Longer
		than the cron health check interval, which restarts a chain that died. */
	const STALL_AFTER = 600;

	protected $prefix = 'tinypng';
	protected $action = 'bulk_queue';

	/*
	An image with many sizes takes longer than the 60 seconds the library locks
		for by default, which would let a second process pick up the same work
		while the first one is still busy with it. */
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

	/* ---------------------------------------------------------------------
	 * Starting and stopping a run
	 * ------------------------------------------------------------------ */

	/**
	 * Start optimizing.
	 *
	 * @param int[]|null $ids Attachments to optimize, or null for the whole library.
	 * @return array The state the run starts out with.
	 */
	public function start( $ids = null ) {
		/*
		A cancel or pause only records a flag; the handler that clears it runs
			in the loopback request. If that request never arrived the flag is
			still set, and it would stop this run before it claimed anything.
			Starting is an explicit instruction, so clear it here. */
		delete_site_option( $this->get_status_key() );

		$this->clear_queue_meta();

		$mode = is_array( $ids ) ? 'selection' : 'all';

		if ( 'selection' === $mode ) {
			$ids = array_values( array_unique( array_map( 'intval', $ids ) ) );
			foreach ( $ids as $id ) {
				add_post_meta( $id, self::META_STATUS, self::STATUS_PENDING, true );
			}
		}

		self::save_run(
			array_merge(
				self::empty_run(),
				array(
					'status'     => 'running',
					'mode'       => $mode,
					'started_at' => time(),
				)
			)
		);

		if ( $this->is_queue_empty() ) {
			$this->complete();
			return $this->get_progress();
		}

		$unreachable = $this->loopback_error();

		if ( ! is_null( $unreachable ) ) {
			$run                  = self::get_run();
			$run['status']        = 'unreachable';
			$run['error_message'] = $unreachable;
			self::save_run( $run );

			return $this->get_progress();
		}

		$this->dispatch();

		return $this->get_progress();
	}

	/**
	 * Check that WordPress can reach its own admin-ajax.php.
	 *
	 * @return string|null Why the loopback failed, or null when it works.
	 */
	protected function loopback_error() {
		$url = $this->get_query_url();

		$response = wp_remote_post(
			$url,
			array(
				'timeout'   => 10,
				'blocking'  => true,
				/* An action nobody handles: WordPress answers without side effects. */
				'body'      => array( 'action' => $this->identifier . '_ping' ),
				'sslverify' => apply_filters( 'https_local_ssl_verify', false ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return sprintf( '%s (%s)', $response->get_error_message(), $url );
		}

		$code = intval( wp_remote_retrieve_response_code( $response ) );

		/* Password protected staging sites answer, but never run the handler. */
		if ( 401 === $code || 403 === $code ) {
			return sprintf( 'HTTP %d (%s)', $code, $url );
		}

		return null;
	}

	/**
	 * Stop the current run and leave the results of what did run in place.
	 */
	public function stop() {
		if ( $this->is_active() ) {
			$this->cancel();
		} else {
			$this->cancelled();
		}
	}

	/**
	 * Progress of the current run.
	 *
	 * @return array
	 */
	public function get_progress() {
		$run    = self::get_run();
		$counts = $this->status_counts();

		$queued = array_sum( $counts );
		$total  = $queued;

		if ( 'all' === $run['mode'] ) {
			/*
			Everything that has not been given a status yet is still waiting,
				so the library total is the denominator. */
			$total = max( $queued, $this->image_attachment_count() );
		}

		$run['counts']    = $counts;
		$run['total']     = $total;
		$run['processed'] = $counts[ self::STATUS_DONE ]
			+ $counts[ self::STATUS_FAILED ]
			+ $counts[ self::STATUS_SKIPPED ]
			+ $counts[ self::STATUS_CANCELLED ];
		$run['optimized'] = $counts[ self::STATUS_DONE ];
		$run['failed']    = $counts[ self::STATUS_FAILED ];
		$run['skipped']   = $counts[ self::STATUS_SKIPPED ];

		$run['is_processing'] = $this->is_processing();
		$run['is_queued']     = ! $this->is_queue_empty();
		$run['is_active']     = $this->is_active();

		/*
		A run that is neither queued nor holding the lock, and that has not
			reported progress for a while, is never going to finish on its own:
			say so rather than leave the page spinning forever. */
		if ( 'running' === $run['status'] && ! $run['is_active'] ) {
			$last = $run['updated_at'] ? $run['updated_at'] : $run['started_at'];

			if ( $last && ( time() - $last ) > self::STALL_AFTER ) {
				$run['status'] = 'stalled';
			}
		}

		return $run;
	}

	/**
	 * Replaces the library's check against its batch rows in the options table.
	 *
	 * @return bool
	 */
	protected function is_queue_empty() {
		$run = self::get_run();

		/* A run that was cancelled or finished leaves nothing claimable. */
		if ( 'running' !== $run['status'] ) {
			return true;
		}

		$counts = $this->status_counts();

		if ( $counts[ self::STATUS_PENDING ] > 0 || $counts[ self::STATUS_PROCESSING ] > 0 ) {
			return false;
		}

		if ( 'all' === $run['mode'] ) {
			/*
			Attachments without a status row have not been looked at yet.
				Comparing counts avoids the NOT EXISTS scan on every poll. */
			return $this->image_attachment_count() <= array_sum( $counts );
		}

		return true;
	}

	/**
	 * Take the next attachments off the queue.
	 *
	 * Claiming is a compare and set on the status: update_post_meta only writes
	 * when the row still reads 'pending', so two processes cannot walk away with
	 * the same image.
	 *
	 * @param int $limit Maximum number of attachments to claim.
	 * @return int[] Attachment IDs claimed by this process.
	 */
	protected function claim_batch( $limit ) {
		global $wpdb;

		$this->reclaim_stale();

		$run = self::get_run();
		if ( 'all' === $run['mode'] ) {
			$this->mark_next_pending( $limit );
		}

		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT post_id FROM $wpdb->postmeta
				WHERE meta_key = %s AND meta_value = %s
				LIMIT %d",
				self::META_STATUS,
				self::STATUS_PENDING,
				$limit
			)
		);

		$claimed = array();
		foreach ( $ids as $id ) {
			$id = intval( $id );

			if ( intval( get_post_meta( $id, self::META_ATTEMPTS, true ) ) >= self::MAX_ATTEMPTS ) {
				update_post_meta( $id, self::META_STATUS, self::STATUS_FAILED );
				continue;
			}

			$claimed_by_us = update_post_meta(
				$id,
				self::META_STATUS,
				self::STATUS_PROCESSING,
				self::STATUS_PENDING
			);

			if ( ! $claimed_by_us ) {
				/* Another process got there first. */
				continue;
			}

			update_post_meta( $id, self::META_CLAIMED, time() );
			$claimed[] = $id;
		}

		return $claimed;
	}

	/**
	 * Give a chunk of not yet considered attachments a pending status.
	 *
	 * This is the only query that has to look at wp_posts, and it runs with a
	 * small limit once per batch rather than once per run over the whole library.
	 *
	 * @param int $limit Maximum number of attachments to mark.
	 */
	protected function mark_next_pending( $limit ) {
		global $wpdb;

		$mimes        = self::mime_types();
		$placeholders = implode( ', ', array_fill( 0, count( $mimes ), '%s' ) );

		$query = "SELECT p.ID FROM $wpdb->posts p
			WHERE p.post_type = 'attachment'
				AND p.post_mime_type IN ($placeholders)
				AND NOT EXISTS (
					SELECT 1 FROM $wpdb->postmeta m
					WHERE m.post_id = p.ID AND m.meta_key = %s
				)
			ORDER BY p.ID DESC
			LIMIT %d";

		$args = array_merge( $mimes, array( self::META_STATUS, $limit ) );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- Only the generated list of %s placeholders is interpolated.
		$ids = $wpdb->get_col( $wpdb->prepare( $query, $args ) );

		foreach ( $ids as $id ) {
			add_post_meta( intval( $id ), self::META_STATUS, self::STATUS_PENDING, true );
		}
	}

	/**
	 * Hand back claims from processes that died mid image.
	 */
	protected function reclaim_stale() {
		global $wpdb;

		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT status.post_id FROM $wpdb->postmeta status
				INNER JOIN $wpdb->postmeta claimed
					ON claimed.post_id = status.post_id AND claimed.meta_key = %s
				WHERE status.meta_key = %s
					AND status.meta_value = %s
					AND CAST(claimed.meta_value AS UNSIGNED) < %d
				LIMIT %d",
				self::META_CLAIMED,
				self::META_STATUS,
				self::STATUS_PROCESSING,
				time() - self::CLAIM_TIMEOUT,
				self::BATCH_SIZE
			)
		);

		foreach ( $ids as $id ) {
			$this->release( intval( $id ) );
		}
	}

	/**
	 * Put a claimed attachment back on the queue.
	 *
	 * @param int $id Attachment ID.
	 */
	protected function release( $id ) {
		update_post_meta( $id, self::META_STATUS, self::STATUS_PENDING );
	}

	/**
	 * How many attachments sit in each status.
	 *
	 * @return array<string,int>
	 */
	protected function status_counts() {
		global $wpdb;

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT meta_value AS status, COUNT(*) AS total
				FROM $wpdb->postmeta
				WHERE meta_key = %s
				GROUP BY meta_value",
				self::META_STATUS
			),
			ARRAY_A
		);

		$counts = array_fill_keys( self::statuses(), 0 );

		foreach ( (array) $rows as $row ) {
			if ( isset( $counts[ $row['status'] ] ) ) {
				$counts[ $row['status'] ] = intval( $row['total'] );
			}
		}

		return $counts;
	}

	/**
	 * Number of images in the library the plugin can optimize.
	 *
	 * @return int
	 */
	protected function image_attachment_count() {
		global $wpdb;

		$mimes        = self::mime_types();
		$placeholders = implode( ', ', array_fill( 0, count( $mimes ), '%s' ) );

		$query = "SELECT COUNT(*) FROM $wpdb->posts
			WHERE post_type = 'attachment' AND post_mime_type IN ($placeholders)";

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- Only the generated list of %s placeholders is interpolated.
		return intval( $wpdb->get_var( $wpdb->prepare( $query, $mimes ) ) );
	}

	/**
	 * Forget the queue, keeping the compression results themselves.
	 */
	protected function clear_queue_meta() {
		global $wpdb;

		$keys         = self::meta_keys();
		$placeholders = implode( ', ', array_fill( 0, count( $keys ), '%s' ) );

		do {
			$select = "SELECT DISTINCT post_id FROM $wpdb->postmeta
				WHERE meta_key IN ($placeholders)
				LIMIT 500";

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- Only the generated list of %s placeholders is interpolated.
			$ids = $wpdb->get_col( $wpdb->prepare( $select, $keys ) );

			if ( empty( $ids ) ) {
				return;
			}

			$ids = array_map( 'intval', $ids );
			$in  = implode( ', ', $ids );

			$delete = "DELETE FROM $wpdb->postmeta
				WHERE meta_key IN ($placeholders) AND post_id IN ($in)";

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- Only generated %s placeholders and a list of integers are interpolated.
			$wpdb->query( $wpdb->prepare( $delete, $keys ) );

			foreach ( $ids as $id ) {
				wp_cache_delete( $id, 'post_meta' );
			}
		} while ( true );
	}

	/* ---------------------------------------------------------------------
	 * Processing
	 * ------------------------------------------------------------------ */

	/**
	 * Work through the queue.
	 *
	 * The library's own loop reads and rewrites a batch row in the options
	 * table; this one claims attachments instead. Everything around it, the
	 * lock, the limits and the decision to hand over to the next process, is
	 * kept the same.
	 */
	protected function handle() {
		$this->lock_process();

		/**
		 * Number of seconds to sleep between batches. Defaults to 0 seconds.
		 *
		 * @param int $seconds
		 */
		$throttle = max(
			0,
			apply_filters( $this->identifier . '_seconds_between_batches', 0 )
		);

		$worked = false;

		do {
			$batch = $this->claim_batch( self::BATCH_SIZE );

			foreach ( $batch as $index => $id ) {
				$worked = true;
				$this->task( $id );

				sleep( $throttle );

				if ( ! $this->should_continue() ) {
					/*
					Hand back what this process will not get to, so the next
						one can pick it up right away instead of waiting for
						the claim to go stale. */
					foreach ( array_slice( $batch, $index + 1 ) as $unclaimed ) {
						$this->release( $unclaimed );
					}
					break 2;
				}
			}
		} while ( ! empty( $batch ) && $this->should_continue() );

		$this->unlock_process();

		if ( $this->is_queue_empty() ) {
			$this->complete();
		} elseif ( $worked ) {
			$this->dispatch();
		}

		/*
		Nothing claimed and nothing finished means the only thing left is a
			claim held by another process. Leave it to the cron health check
			rather than spinning up another chain immediately. */

		return $this->maybe_wp_die();
	}

	/**
	 * Optimize a single attachment.
	 *
	 * Always returns false: the queue lives in postmeta, so there is nothing to
	 * hand back to the library's batch.
	 *
	 * @param mixed $item Attachment ID.
	 * @return false
	 */
	protected function task( $item ) {
		$id       = intval( $item );
		$attempts = intval( get_post_meta( $id, self::META_ATTEMPTS, true ) ) + 1;

		update_post_meta( $id, self::META_ATTEMPTS, $attempts );

		if ( ! $this->is_supported_attachment( $id ) ) {
			$this->finish(
				$id,
				self::STATUS_SKIPPED,
				array( 'title' => get_the_title( $id ) )
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
			$this->fail( $id, $attempts, $tiny_image->get_name(), $e->getMessage() );
			return false;
		}

		wp_update_attachment_metadata( $id, $tiny_image->get_wp_metadata() );

		$stats     = $tiny_image->get_statistics( $active_sizes, $active_tinify_sizes );
		$optimized = isset( $result['success'] ) ? intval( $result['success'] ) : 0;

		if ( ! empty( $result['failed'] ) ) {
			$this->fail( $id, $attempts, $tiny_image->get_name(), $tiny_image->get_latest_error() );
			return false;
		}

		$this->finish(
			$id,
			$optimized > 0 ? self::STATUS_DONE : self::STATUS_SKIPPED,
			array(
				'title'            => $tiny_image->get_name(),
				'sizes_compressed' => $stats['image_sizes_compressed'],
				'sizes_converted'  => $stats['image_sizes_converted'],
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
			)
		);

		return false;
	}

	/**
	 * Record the outcome of an attachment that was processed.
	 *
	 * @param int    $id     Attachment ID.
	 * @param string $status One of the STATUS_ constants.
	 * @param array  $entry  Details for the table of recent results.
	 */
	protected function finish( $id, $status, array $entry = array() ) {
		update_post_meta( $id, self::META_STATUS, $status );
		delete_post_meta( $id, self::META_ERROR );

		$this->log(
			array_merge(
				$entry,
				array(
					'id'     => $id,
					'status' => $status,
				)
			)
		);
	}

	/**
	 * Record a failure, and queue the attachment again if it has attempts left.
	 *
	 * One bad image cannot poison the run: it is retried a few times and then
	 * left alone while the rest of the library carries on.
	 *
	 * @param int    $id       Attachment ID.
	 * @param int    $attempts Attempts made so far, including this one.
	 * @param string $title    Attachment name, for display.
	 * @param string $message  Why it failed.
	 */
	protected function fail( $id, $attempts, $title, $message ) {
		update_post_meta( $id, self::META_ERROR, (string) $message );

		if ( $attempts < self::MAX_ATTEMPTS ) {
			$this->release( $id );
			return;
		}

		update_post_meta( $id, self::META_STATUS, self::STATUS_FAILED );

		$this->log(
			array(
				'id'      => $id,
				'title'   => $title,
				'status'  => self::STATUS_FAILED,
				'message' => $message,
			)
		);
	}

	/* ---------------------------------------------------------------------
	 * Run boundaries
	 * ------------------------------------------------------------------ */

	/**
	 * Mark the run as finished once the queue has drained.
	 */
	protected function complete() {
		parent::complete();

		$run = self::get_run();
		if ( 'running' === $run['status'] ) {
			$run['status']      = 'done';
			$run['finished_at'] = time();
			self::save_run( $run );
		}
	}

	/**
	 * Drop what is still queued, keeping the results of what already ran.
	 *
	 * Replaces the library's batch cleanup. Anything still waiting or claimed
	 * becomes 'cancelled' so the counts on the page stay meaningful.
	 */
	public function delete_all() {
		global $wpdb;

		do {
			$ids = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT post_id FROM $wpdb->postmeta
					WHERE meta_key = %s AND meta_value IN ( %s, %s )
					LIMIT 500",
					self::META_STATUS,
					self::STATUS_PENDING,
					self::STATUS_PROCESSING
				)
			);

			if ( empty( $ids ) ) {
				break;
			}

			$ids = array_map( 'intval', $ids );
			$in  = implode( ', ', $ids );

			$update = "UPDATE $wpdb->postmeta SET meta_value = %s
				WHERE meta_key = %s AND post_id IN ($in)";

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Only a list of integers is interpolated.
			$wpdb->query( $wpdb->prepare( $update, self::STATUS_CANCELLED, self::META_STATUS ) );

			foreach ( $ids as $id ) {
				wp_cache_delete( $id, 'post_meta' );
			}
		} while ( true );

		delete_site_option( $this->get_status_key() );

		$this->cancelled();
	}

	/**
	 * Mark the run as cancelled.
	 */
	protected function cancelled() {
		parent::cancelled();

		$run                = self::get_run();
		$run['status']      = 'cancelled';
		$run['finished_at'] = time();
		self::save_run( $run );
	}

	/* ---------------------------------------------------------------------
	 * Library plumbing
	 * ------------------------------------------------------------------ */

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
	 * ID tying together the loopback requests of a single run.
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

	/* ---------------------------------------------------------------------
	 * Helpers
	 * ------------------------------------------------------------------ */

	private function is_supported_attachment( $id ) {
		if ( 'attachment' !== get_post_type( $id ) ) {
			return false;
		}

		return in_array( get_post_mime_type( $id ), self::mime_types(), true );
	}

	private static function mime_types() {
		return array( 'image/jpeg', 'image/png', 'image/webp' );
	}

	private static function meta_keys() {
		return array(
			self::META_STATUS,
			self::META_ATTEMPTS,
			self::META_CLAIMED,
			self::META_ERROR,
		);
	}

	private static function statuses() {
		return array(
			self::STATUS_PENDING,
			self::STATUS_PROCESSING,
			self::STATUS_DONE,
			self::STATUS_FAILED,
			self::STATUS_SKIPPED,
			self::STATUS_CANCELLED,
		);
	}

	/**
	 * Keep the last handful of results for the table on the page.
	 *
	 * Display only: the numbers the page shows come from postmeta, this is just
	 * the detail that would be expensive to recompute for every poll.
	 *
	 * @param array $entry Result of processing one attachment.
	 */
	private function log( array $entry ) {
		$run = self::get_run();

		$run['updated_at'] = time();
		$run['log'][]      = array_merge(
			array(
				'id'               => 0,
				'title'            => '',
				'status'           => self::STATUS_SKIPPED,
				'message'          => null,
				'sizes_compressed' => 0,
				'sizes_converted'  => 0,
				'initial_size'     => null,
				'optimized_size'   => null,
				'savings'          => 0,
				'thumbnail'        => '',
				'size_change'      => 0,
			),
			$entry
		);

		if ( count( $run['log'] ) > self::LOG_SIZE ) {
			$run['log'] = array_slice( $run['log'], -self::LOG_SIZE );
		}

		self::save_run( $run );
	}

	public static function get_run() {
		$run = get_site_option( self::RUN_OPTION );

		if ( ! is_array( $run ) ) {
			return self::empty_run();
		}

		return array_merge( self::empty_run(), $run );
	}

	private static function save_run( array $run ) {
		update_site_option( self::RUN_OPTION, $run );
	}

	private static function empty_run() {
		return array(
			'status'        => 'idle',
			'mode'          => 'all',
			'error_message' => null,
			'started_at'    => null,
			'updated_at'    => null,
			'finished_at'   => null,
			'log'           => array(),
		);
	}
}
