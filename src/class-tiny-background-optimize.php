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
 * Optimizes attachments in the background.
 *
 * Starts a worker through a remote 
 */
class Tiny_Background_Optimize extends Tiny_WP_Base {

	/* Meta key for the queue status of an attachment. */
	const META_KEY_STATUS = '_tinywp_queue_status';

	/* Meta key for what optimizing an attachment came to. */
	const META_KEY_RESULT = '_tinywp_queue_result';

	/* Queue status: waiting to be optimized. */
	const STATUS_QUEUED = 'queued';

	/* Queue status: being optimized right now. */
	const STATUS_PROCESSING = 'processing';

	/* Queue status: optimized, or looked at and nothing needed doing. */
	const STATUS_DONE = 'done';

	/* Queue status: optimizing failed. */
	const STATUS_FAILED = 'failed';

	/* Number of images being optimized at the same time */
	const WORKERS = 5;

	/* AJAX action of a worker. */
	const WORKER_ACTION = 'tiny_bulk_queue_work';

	/* Transient per worker, set each time it starts on an attachment. */
	const WORKER_TRANSIENT = 'tiny_bulk_queue_worker_';

	/* Seconds without starting on an attachment before a worker counts as dead. */
	const STALLED_AFTER = 300;

	/**
	 * Tinify settings.
	 *
	 * @var Tiny_Settings
	 */
	private $settings;

	/**
	 * @param Tiny_Settings $settings Tinify settings.
	 */
	public function __construct( $settings ) {
		parent::__construct();
		$this->settings = $settings;
	}

	public function ajax_init() {
		add_action( 'wp_ajax_' . self::WORKER_ACTION, $this->get_method( 'work' ) );
	}

	/**
	 * Queue attachments and start working through them.
	 *
	 * Whatever an earlier run left behind is forgotten first.
	 *
	 * @param int[] $ids Attachments to optimize.
	 */
	public function start( array $ids ) {
		delete_post_meta_by_key( self::META_KEY_STATUS );
		delete_post_meta_by_key( self::META_KEY_RESULT );

		$ids = array_values( array_unique( array_filter( array_map( 'intval', $ids ) ) ) );
		foreach ( $ids as $id ) {
			add_post_meta( $id, self::META_KEY_STATUS, self::STATUS_QUEUED, true );
		}

		// cleanup workers
		for ( $worker = 1; $worker <= self::WORKERS; $worker++ ) {
			delete_transient( self::WORKER_TRANSIENT . $worker );
		}

		$this->start_workers();
	}

	/**
	 * Take every queued attachment off the queue. Attachments that are being
	 * optimized right now still finish.
	 */
	public function cancel() {
		delete_metadata( 'post', 0, self::META_KEY_STATUS, self::STATUS_QUEUED, true );
	}

	/**
	 * Whether something is still being processed
	 * - any worker is active
	 * - any image is queued or processing
	 *
	 * @return bool
	 */
	public function is_running() {
		return $this->has_active_workers() && ( $this->next_queued() || $this->get_processing() );
	}

	/**
	 * Where the given attachments stand in the current run.
	 *
	 * @param int[] $ids Attachment IDs.
	 * @return array<int,array{status: string|null, result: array|null}> Keyed by
	 *         attachment ID. The status is null when the attachment is not part
	 *         of the run; the result is only there once it is done or failed.
	 */
	public function get_results( array $ids ) {
		$ids = array_values( array_unique( array_filter( array_map( 'intval', $ids ) ) ) );

		update_meta_cache( 'post', $ids );

		$results = array();
		foreach ( $ids as $id ) {
			$status   = get_post_meta( $id, self::META_KEY_STATUS, true );
			$finished = self::STATUS_DONE === $status || self::STATUS_FAILED === $status;

			$result = $finished ? get_post_meta( $id, self::META_KEY_RESULT, true ) : null;

			$results[ $id ] = array(
				'status' => '' === $status ? null : $status,
				'result' => $finished ? (array) $result : null,
			);
		}

		return $results;
	}

	/**
	 * Optimize one queued attachment, then send this worker's next request and
	 * start any worker that died.
	 *
	 * Sending the next request only once this one is done keeps the number of
	 * workers at WORKERS.
	 */
	public function work() {
		check_ajax_referer( self::WORKER_ACTION, 'nonce' );

		ignore_user_abort( true );

		$worker = isset( $_POST['worker'] ) ? intval( $_POST['worker'] ) : 0;

		$id = $this->next_queued();
		if ( $id ) {
			$this->task( $id );
			delete_transient( self::WORKER_TRANSIENT . $worker );
			$this->start_workers();
		} else {
			delete_transient( self::WORKER_TRANSIENT . $worker );
		}

		wp_die();
	}

	/**
	 * Start self::WORKERS of workers, if not already active
	 */
	private function start_workers() {
		for ( $worker = 1; $worker <= self::WORKERS; $worker++ ) {
			$is_active = get_transient( self::WORKER_TRANSIENT . $worker );
			if ( ! $is_active ) {
				$this->start_worker( $worker );
			}
		}
	}

	private function has_active_workers() {
		for ( $worker = 1; $worker <= self::WORKERS; $worker++ ) {
			if ( get_transient( self::WORKER_TRANSIENT . $worker ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Send the next request for a worker
	 *
	 * The worker runs as the user whose request starts it.
	 *
	 * @param int $worker Worker number, from 1 to WORKERS.
	 */
	private function start_worker( $worker ) {
		set_transient( self::WORKER_TRANSIENT . $worker, time(), self::STALLED_AFTER );
		$args = array(
			'timeout'   => 0.01,
			'blocking'  => false,
			'body'      => array(
				'action' => self::WORKER_ACTION,
				'nonce'  => wp_create_nonce( self::WORKER_ACTION ),
				'worker' => $worker,
			),
			'cookies'   => isset( $_COOKIE ) && is_array( $_COOKIE ) ? $_COOKIE : array(),
			'sslverify' => apply_filters( 'https_local_ssl_verify', false ),
		);

		if ( getenv( 'WORDPRESS_HOST' ) !== false ) {
			wp_remote_post( getenv( 'WORDPRESS_HOST' ) . '/wp-admin/admin-ajax.php', $args );
		} else {
			wp_remote_post( admin_url( 'admin-ajax.php' ), $args );
		}
	}

	/**
	 * @return int|null The next queued attachment, if any.
	 */
	private function next_queued() {
		global $wpdb;

		$id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT post_id FROM $wpdb->postmeta
				WHERE meta_key = %s AND meta_value = %s
				ORDER BY post_id DESC
				LIMIT 1",
				self::META_KEY_STATUS,
				self::STATUS_QUEUED
			)
		);

		return is_null( $id ) ? null : intval( $id );
	}

	/**
	 * @return int[] Attachments being optimized.
	 */
	private function get_processing() {
		global $wpdb;

		return array_map(
			'intval',
			$wpdb->get_col(
				$wpdb->prepare(
					"SELECT post_id FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value = %s",
					self::META_KEY_STATUS,
					self::STATUS_PROCESSING
				)
			)
		);
	}

	/**
	 * Optimize a queued attachment.
	 *
	 * The attachment always leaves the queue, whatever happens: one that stayed
	 * queued would be handed out again and again.
	 *
	 * @param int $id Attachment ID.
	 */
	private function task( $id ) {
		$claimed = update_post_meta(
			$id,
			self::META_KEY_STATUS,
			self::STATUS_PROCESSING,
			self::STATUS_QUEUED
		);

		if ( ! $claimed ) {
			return;
		}

		try {
			$result = $this->compress( $id );
			$status = $result['failed'] > 0 ? self::STATUS_FAILED : self::STATUS_DONE;
		} catch ( Exception $e ) {
			$result = array(
				'success'     => 0,
				'failed'      => 1,
				'message'     => $e->getMessage(),
				'size_change' => 0,
			);
			$status = self::STATUS_FAILED;
		}

		update_post_meta( $id, self::META_KEY_RESULT, $result );
		update_post_meta( $id, self::META_KEY_STATUS, $status );

		$compressor = $this->settings->get_compressor();
		if ( $compressor && $compressor->limit_reached() ) {
			$this->cancel();
		}
	}

	/**
	 * Compress an attachment and say what that came to.
	 *
	 * @param int $id Attachment ID.
	 * @return array{success: int, failed: int, message: string|null, size_change: int}
	 */
	private function compress( $id ) {
		$active_sizes        = $this->settings->get_sizes();
		$active_tinify_sizes = $this->settings->get_active_tinify_sizes();

		$before = new Tiny_Image( $this->settings, $id );
		$before = $before->get_statistics( $active_sizes, $active_tinify_sizes );

		$tiny_image = new Tiny_Image( $this->settings, $id );

		Tiny_Logger::debug(
			'compress from background queue',
			array(
				'image_id' => $id,
			)
		);

		$result = $tiny_image->compress();
		wp_update_attachment_metadata( $id, $tiny_image->get_wp_metadata() );

		$after = $tiny_image->get_statistics( $active_sizes, $active_tinify_sizes );

		return array(
			'success'     => isset( $result['success'] ) ? intval( $result['success'] ) : 0,
			'failed'      => isset( $result['failed'] ) ? intval( $result['failed'] ) : 0,
			'message'     => $tiny_image->get_latest_error(),
			'size_change' => $after['compressed_total_size'] - $before['compressed_total_size'],
		);
	}
}
