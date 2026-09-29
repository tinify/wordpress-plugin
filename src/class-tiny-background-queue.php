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
 * Optimizes a selection of attachments in the background instead of from the
 * browser.
 *
 * The queue is the status postmeta of the attachments themselves: the library's
 * batch rows in the options table are never written. Every batch the library
 * asks for is worked out on the spot and holds the next queued attachment.
 */
class Tiny_Background_Queue extends Tiny_Vendor_WP_Background_Process {

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

	protected $prefix = 'tiny';
	protected $action = 'optimize';

	/*
	An image with many sizes can take longer than the library's default lock of
		60 seconds. Once the lock expires, the cron health check starts a second
		process next to the one still busy. */
	protected $queue_lock_time = 300;

	/**
	 * Tinify settings.
	 *
	 * @var Tiny_Settings
	 */
	private $settings;

	/**
	 * Chain ID for runs dispatched outside of the queue's own loopback request.
	 *
	 * @var string
	 */
	private $started_chain_id;

	/**
	 * @param Tiny_Settings $settings Tinify settings.
	 */
	public function __construct( $settings ) {
		$this->settings = $settings;
		parent::__construct();
	}

	/**
	 * Queue attachments and start working through them.
	 *
	 * Whatever an earlier run left behind is forgotten first.
	 *
	 * @param int[] $ids Attachments to optimize.
	 * @return bool Whether the run was started.
	 */
	public function start( array $ids ) {
		/*
		Cancelling only sets a flag, which the next loopback request clears. If
			that request never came, the flag would stop this run straight away. */
		delete_site_option( $this->get_status_key() );

		delete_post_meta_by_key( self::META_KEY_STATUS );
		delete_post_meta_by_key( self::META_KEY_RESULT );

		$ids = array_values( array_unique( array_filter( array_map( 'intval', $ids ) ) ) );
		foreach ( $ids as $id ) {
			add_post_meta( $id, self::META_KEY_STATUS, self::STATUS_QUEUED, true );
		}

		$this->dispatch();

		return true;
	}

	/**
	 * Whether a run still has attachments queued or one being optimized.
	 *
	 * Not is_active(): that also counts a cancel flag no loopback request has
	 * cleared yet, and a run is over once it is cancelled.
	 *
	 * @return bool
	 */
	public function is_running() {
		return $this->is_queued() || $this->is_processing();
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
	 * The next queued attachment, as a batch of one.
	 *
	 * Everything in the library that asks whether there is work left comes
	 * through here, so an empty queue has to be an empty array: a batch without
	 * data would still count as work.
	 *
	 * @param int $limit Number of batches; there is never more than one.
	 * @return stdClass[]
	 */
	public function get_batches( $limit = 0 ) {
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

		if ( is_null( $id ) ) {
			return array();
		}

		$batch       = new stdClass();
		$batch->key  = $this->identifier . '_batch_postmeta';
		$batch->data = array( intval( $id ) );

		return array( $batch );
	}

	/**
	 * Nothing to store: an attachment's status records its progress.
	 *
	 * @param string $key  Batch key.
	 * @param array  $data Batch data.
	 * @return $this
	 */
	public function update( $key, $data ) {
		return $this;
	}

	/**
	 * Nothing to remove: a batch is used up once its attachment is no longer
	 * queued.
	 *
	 * @param string $key Batch key.
	 * @return $this
	 */
	public function delete( $key ) {
		return $this;
	}

	/**
	 * Take what is still queued off the queue, as the library does when a run
	 * is cancelled.
	 *
	 * Attachments that were already optimized keep their status and result.
	 */
	public function delete_all() {
		delete_metadata( 'post', 0, self::META_KEY_STATUS, self::STATUS_QUEUED, true );
		delete_site_option( $this->get_status_key() );
		$this->cancelled();
	}

	/**
	 * Optimize a queued attachment.
	 *
	 * The attachment always leaves the queue, whatever happens: one that stayed
	 * queued would be handed out again and again.
	 *
	 * @param int $item Attachment ID.
	 * @return false The library can forget the item.
	 */
	protected function task( $item ) {
		$id = intval( $item );

		/* Another process may have claimed it since the batch was read. */
		$claimed = update_post_meta(
			$id,
			self::META_KEY_STATUS,
			self::STATUS_PROCESSING,
			self::STATUS_QUEUED
		);

		if ( ! $claimed ) {
			return false;
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

		return false;
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

	/**
	 * ID tying together the loopback requests of a single run.
	 *
	 * The library takes any AJAX request that asks for it to be its own loopback
	 * request, and dies when the nonce does not match. Starting or cancelling a
	 * run from the bulk optimization page is an AJAX request too, so only defer
	 * to the library when the nonce really is the queue's.
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
}
