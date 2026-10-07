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
 * The status of each attachment is the queue. Workers are requests to
 * admin-ajax.php: each optimizes one queued attachment and then starts the
 * next worker, so a run goes on after the bulk optimization page is closed.
 */
class Tiny_Background_Queue extends Tiny_WP_Base {

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

	/* Set when workers are started, and each time one starts on an attachment. */
	const ALIVE_TRANSIENT = 'tiny_bulk_queue_alive';

	/* Seconds without a worker starting on an attachment before a run counts as stalled. */
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
		add_action( 'wp_ajax_nopriv_' . self::WORKER_ACTION, $this->get_method( 'work' ) );
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
	 * Whether a run still has attachments queued or being optimized.
	 *
	 * @return bool
	 */
	public function is_running() {
		global $wpdb;

		return (bool) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT post_id FROM $wpdb->postmeta
				WHERE meta_key = %s AND meta_value IN ( %s, %s )
				LIMIT 1",
				self::META_KEY_STATUS,
				self::STATUS_QUEUED,
				self::STATUS_PROCESSING
			)
		);
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
	 * Optimize one queued attachment, then start the next worker.
	 *
	 * Starting the next worker only once this one is done keeps the number of
	 * workers at WORKERS.
	 */
	public function work() {
		$key = isset( $_POST['key'] ) ? sanitize_key( wp_unslash( $_POST['key'] ) ) : '';
		if ( ! hash_equals( wp_hash( self::WORKER_ACTION ), $key ) ) {
			wp_die( -1, 403 );
		}

		/* The request that started this worker does not wait for it. */
		ignore_user_abort( true );

		$id = $this->next_queued();
		if ( $id ) {
			set_transient( self::ALIVE_TRANSIENT, time(), self::STALLED_AFTER );
			$this->task( $id );
			$this->start_worker();
		}

		wp_die();
	}

	/**
	 * Restart any stalled workers.
	 * A worker is stalled when still processing after STALLED_AFTER seconds
	 * and marks them as failed.
	 */
	public function restart_stalled_workers() {
		if ( ! $this->is_running() || get_transient( self::ALIVE_TRANSIENT ) ) {
			return;
		}

		
		foreach ( $this->get_processing() as $id ) {
			update_post_meta(
				$id,
				self::META_KEY_RESULT,
				array(
					'failed'  => 1,
					'message' => __( 'Optimization was interrupted', 'tiny-compress-images' ),
				)
			);
			update_post_meta( $id, self::META_KEY_STATUS, self::STATUS_FAILED );
		}

		$this->start_workers();
	}

	private function start_workers() {
		/* Counts as progress, so the next status check does not start them again. */
		set_transient( self::ALIVE_TRANSIENT, time(), self::STALLED_AFTER );
		for ( $i = 0; $i < self::WORKERS; $i++ ) {
			$this->start_worker();
		}
	}

	/**
	 * Start a worker in a request of its own, without waiting for it.
	 *
	 * The worker runs logged out, so logging out or an expiring login cannot
	 * stop a run. A nonce is tied to a user, so a key from the site's salts
	 * shows the request comes from the site itself.
	 */
	private function start_worker() {
		$args = array(
			'timeout'   => 0.01,
			'blocking'  => false,
			'body'      => array(
				'action' => self::WORKER_ACTION,
				'key'    => wp_hash( self::WORKER_ACTION ),
			),
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
		/* Another worker may have claimed it since it was read. */
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

		/* Statistics are worked out once per instance, so before needs its own. */
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
