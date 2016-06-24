<?php
/*
* Tiny Compress Images - WordPress plugin.
* Copyright (C) 2015-2016 Voormedia B.V.
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

class Tiny_Plugin extends Tiny_WP_Base {
	const MEDIA_COLUMN = self::NAME;
	const DATETIME_FORMAT = 'Y-m-d G:i:s';

	private static $version;

	private $settings;
	private $twig;

	public static function jpeg_quality() {
		return 95;
	}

	public static function version() {
		if ( is_null( self::$version ) ) {
			$plugin_data = get_plugin_data( dirname( __FILE__ ) . '/../tiny-compress-images.php' );
			self::$version = $plugin_data['Version'];
		}
		return self::$version;
	}


	public function __construct() {
		parent::__construct();

		$this->settings = new Tiny_Settings();
	}

	public function set_compressor($compressor) {
		$this->settings->set_compressor( $compressor );
	}

	public function init() {
		add_filter( 'jpeg_quality',
			$this->get_static_method( 'jpeg_quality' )
		);

		add_filter( 'wp_editor_set_quality',
			$this->get_static_method( 'jpeg_quality' )
		);

		add_filter( 'wp_generate_attachment_metadata',
			$this->get_method( 'compress_on_upload' ),
			10, 2
		);

		load_plugin_textdomain( self::NAME, false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages'
		);
	}

	public function admin_init() {
		add_action( 'admin_enqueue_scripts',
			$this->get_method( 'enqueue_scripts' )
		);

		add_action( 'admin_action_tiny_bulk_action',
			$this->get_method( 'media_library_bulk_action' )
		);

		add_filter( 'manage_media_columns',
			$this->get_method( 'add_media_columns' )
		);

		add_action( 'manage_media_custom_column',
			$this->get_method( 'render_media_column' ),
			10, 2
		);

		add_action( 'attachment_submitbox_misc_actions',
			$this->get_method( 'show_media_info' )
		);

		add_action( 'wp_ajax_tiny_compress_image_from_library',
			$this->get_method( 'compress_image_from_library' )
		);

		add_action( 'wp_ajax_tiny_compress_image_for_bulk',
			$this->get_method( 'compress_image_for_bulk' )
		);

		add_action( 'wp_ajax_tiny_get_optimization_statistics',
			$this->get_method( 'ajax_optimization_statistics' )
		);

		$plugin = plugin_basename(
			dirname( dirname( __FILE__ ) ) . '/tiny-compress-images.php'
		);

		add_filter( "plugin_action_links_$plugin",
			$this->get_method( 'add_plugin_links' )
		);

		add_thickbox();
	}

	public function admin_menu() {
		add_media_page(
			__( 'Compress JPEG & PNG Images', 'tiny-compress-images' ),
			__( 'Bulk Optimization', 'tiny-compress-images' ),
			'upload_files',
			'tiny-bulk-optimization',
			$this->get_method( 'render_bulk_optimization_page' )
		);
	}

	public function add_plugin_links( $current_links ) {
		$additional[] = sprintf('<a href="options-media.php#%s">%s</a>', self::NAME,
		esc_html__( 'Settings', 'tiny-compress-images' ));
		return array_merge( $additional, $current_links );
	}

	public function enqueue_scripts( $hook ) {
		wp_enqueue_style( self::NAME .'_admin', plugins_url( '/css/admin.css', __FILE__ ),
		array(), self::plugin_version() );
		wp_register_script( self::NAME .'_admin', plugins_url( '/js/admin.js', __FILE__ ),
		array(), self::plugin_version(), true );

		// WordPress < 3.3 does not handle multidimensional arrays
		wp_localize_script( self::NAME .'_admin', 'tinyCompress', array(
			'nonce' => wp_create_nonce( 'tiny-compress' ),
			'wpVersion' => self::wp_version(),
			'pluginVersion' => self::plugin_version(),
			'L10nAllDone' => __( 'All images are processed', 'tiny-compress-images' ),
			'L10nNoActionTaken' => __( 'No action taken', 'tiny-compress-images' ),
			'L10nBulkAction' => __( 'Compress Images', 'tiny-compress-images' ),
			'L10nCancelled' => __( 'Cancelled', 'tiny-compress-images' ),
			'L10nCompressing' => __( 'Compressing', 'tiny-compress-images' ),
			'L10nCompressed' => __( 'compressed', 'tiny-compress-images' ),
			'L10nError' => __( 'Error', 'tiny-compress-images' ),
			'L10nLatestError' => __( 'Latest error', 'tiny-compress-images' ),
			'L10nInternalError' => __( 'Internal error', 'tiny-compress-images' ),
			'L10nOutOf' => __( 'out of', 'tiny-compress-images' ),
			'L10nWaiting' => __( 'Waiting', 'tiny-compress-images' ),
		));

		wp_enqueue_script( self::NAME .'_admin' );

		if ( 'media_page_tiny-bulk-optimization' == $hook ) {
			wp_enqueue_style(
				self::NAME . '_tiny_bulk_optimization',
				plugins_url( '/css/bulk-optimization.css', __FILE__ ),
				array(), self::plugin_version()
			);

			wp_register_script(
				self::NAME . '_tiny_bulk_optimization',
				plugins_url( '/js/bulk-optimization.js', __FILE__ ),
				array(), self::plugin_version(), true
			);

			wp_enqueue_script( self::NAME .'_tiny_bulk_optimization' );
		}

	}

	public function compress_on_upload( $metadata, $attachment_id ) {
		if ( ! empty( $metadata ) ) {
			$tiny_image = new Tiny_Image( $attachment_id, $metadata );
			$result = $tiny_image->compress( $this->settings );
			return $tiny_image->get_wp_metadata();
		} else {
			return $metadata;
		}
	}

	public function compress_image_from_library() {
		if ( ! $this->check_ajax_referer() ) {
			exit();
		}
		if ( ! current_user_can( 'upload_files' ) ) {
			$message = __(
				"You don't have permission to upload files.",
				'tiny-compress-images'
			);
			echo $message;
			exit();
		}
		if ( empty( $_POST['id'] ) ) {
			$message = __(
				'Not a valid media file.',
				'tiny-compress-images'
			);
			echo $message;
			exit();
		}
		$id = intval( $_POST['id'] );
		$metadata = wp_get_attachment_metadata( $id );
		if ( ! is_array( $metadata ) ) {
			$message = __(
				'Could not find metadata of media file.',
				'tiny-compress-images'
			);
			echo $message;
			exit;
		}

		$tiny_image = new Tiny_Image( $id, $metadata );
		$result = $tiny_image->compress( $this->settings );
		wp_update_attachment_metadata( $id, $tiny_image->get_wp_metadata() );

		echo $this->render_compress_details( $tiny_image );

		exit();
	}

	public function compress_image_for_bulk() {
		if ( ! $this->check_ajax_referer() ) {
			exit();
		}
		if ( ! current_user_can( 'upload_files' ) ) {
			$message = __(
				"You don't have permission to upload files.",
				'tiny-compress-images'
			);
			echo json_encode( array( 'error' => $message ) );
			exit();
		}
		if ( empty( $_POST['id'] ) ) {
			$message = __(
				'Not a valid media file.',
				'tiny-compress-images'
			);
			echo json_encode( array( 'error' => $message ) );
			exit();
		}
		$id = intval( $_POST['id'] );
		$metadata = wp_get_attachment_metadata( $id );
		if ( ! is_array( $metadata ) ) {
			$message = __(
				'Could not find metadata of media file.',
				'tiny-compress-images'
			);
			echo json_encode( array( 'error' => $message ) );
			exit;
		}

		$tiny_image_before = new Tiny_Image( $id, $metadata );
		$image_statistics_before = $tiny_image_before->get_statistics();
		$size_before = $image_statistics_before['optimized_total_size'];

		$tiny_image = new Tiny_Image( $id, $metadata );
		$result = $tiny_image->compress( $this->settings );
		$image_statistics = $tiny_image->get_statistics();
		wp_update_attachment_metadata( $id, $tiny_image->get_wp_metadata() );

		$currentLibrarySize = intval( $_POST['current_size'] );
		$size_after = $image_statistics['optimized_total_size'];
		$newLibrarySize = $currentLibrarySize + $size_after - $size_before;

		$result['message'] = $tiny_image->get_latest_error();
		$result['image_sizes_optimized'] = $image_statistics['image_sizes_optimized'];

		$result['initial_total_size'] = size_format(
			$image_statistics['initial_total_size'], 2
		);

		$result['optimized_total_size'] = size_format(
			$image_statistics['optimized_total_size'], 2
		);

		$result['savings'] = $tiny_image->get_savings( $image_statistics );
		$result['status'] = $this->settings->get_status();
		$result['thumbnail'] = wp_get_attachment_image(
			$id, array( '30', '30' ), true, array(
				'class' => 'pinkynail',
				'alt' => '',
			)
		);
		$result['size_change'] = $size_after - $size_before;
		$result['human_readable_library_size'] = size_format( $newLibrarySize, 2 );

		echo json_encode( $result );

		exit();
	}

	public function ajax_optimization_statistics() {
		if ( ! $this->check_ajax_referer() ) {
			exit();
		}
		$stats = Tiny_Image::get_optimization_statistics();
		echo json_encode( $stats );
		exit();
	}

	public function media_library_bulk_action() {
		check_admin_referer( 'bulk-media' );

		if ( empty( $_REQUEST['media'] ) || ! is_array( $_REQUEST['media'] ) ) {
			return;
		}

		$ids = implode( '-', array_map( 'intval', $_REQUEST['media'] ) );
		wp_redirect(add_query_arg(
			'_wpnonce',
			wp_create_nonce( 'tiny-bulk-optimization' ),
			admin_url( "upload.php?page=tiny-bulk-optimization&ids=$ids" )
		));
		exit();
	}

	public function add_media_columns($columns) {
		$columns[ self::MEDIA_COLUMN ] = __( 'Compression', 'tiny-compress-images' );
		return $columns;
	}

	public function render_media_column( $column, $id ) {
		if ( $column === self::MEDIA_COLUMN ) {
			echo '<div class="tiny-ajax-container">';
			$this->render_compress_details( new Tiny_Image( $id ) );
			echo '</div>';
		}
	}

	public function show_media_info() {
		global $post;
		echo '<div class="misc-pub-section tiny-compress-images">';
		echo '<h4>' . __( 'Compress JPEG & PNG Images', 'tiny-compress-images' ) . '</h4>';
		echo '<div class="tiny-ajax-container">';
		$this->render_compress_details( new Tiny_Image( $post->ID ) );
		echo '</div></div>';
	}

	private function render_compress_details( $tiny_image ) {
		$in_progress = $tiny_image->filter_image_sizes( 'in_progress' );
		if ( count( $in_progress ) > 0 ) {
			include( dirname( __FILE__ ) . '/views/compress-details-processing.php' );
		} else {
			include( dirname( __FILE__ ) . '/views/compress-details.php' );
		}
	}

	public function render_bulk_optimization_page() {
		$stats = Tiny_Image::get_optimization_statistics();
		$estimated_costs = Tiny_Compress::estimate_cost(
			$stats['available-unoptimised-sizes'],
			$this->settings->get_compression_count()
		);

		$active_tinify_sizes = $this->settings->get_active_tinify_sizes();

		$auto_start_bulk = isset( $_REQUEST['ids'] );

		include( dirname( __FILE__ ) . '/views/bulk-optimization.php' );
	}

	private function get_ids_to_compress() {
		if ( empty( $_REQUEST['ids'] ) ) {
			return array();
		}

		$ids = implode( ',', array_map( 'intval', explode( '-', $_REQUEST['ids'] ) ) );
		$condition = "AND ID IN($ids)";

		global $wpdb;
		return $wpdb->get_results(
			"SELECT ID, post_title FROM $wpdb->posts
             WHERE post_type = 'attachment' $condition
             AND (post_mime_type = 'image/jpeg' OR post_mime_type = 'image/png')
             ORDER BY ID DESC", ARRAY_A);
	}
}
