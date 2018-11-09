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
class Tiny_Plugin extends Tiny_WP_Base {
	const VERSION = '3.1.0';
	const MEDIA_COLUMN = self::NAME;
	const DATETIME_FORMAT = 'Y-m-d G:i:s';

	private static $version;

	private $settings;
	private $twig;

	public static function jpeg_quality() {
		return 85;
	}

	public static function version() {
		/* Avoid using get_plugin_data() because it is not loaded early enough
			 in xmlrpc.php. */
		return self::VERSION;
	}

	public function __construct() {
		parent::__construct();

		$this->settings = new Tiny_Settings();
	}

	public function set_compressor( $compressor ) {
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
			$this->get_method( 'process_attachment' ),
			10, 2
		);

		/* When touching any functionality linked to image compressions when
			 uploading images make sure it also works with XML-RPC. See NOTES. */
		add_filter( 'wp_ajax_nopriv_tiny_rpc',
			$this->get_method( 'process_rpc_request' )
		);

		load_plugin_textdomain( self::NAME, false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages'
		);
	}

	public function admin_init() {
		add_action('wp_dashboard_setup',
			$this->get_method( 'add_dashboard_widget' )
		);

		add_action( 'admin_enqueue_scripts',
			$this->get_method( 'enqueue_scripts' )
		);

		add_action( 'admin_action_tiny_bulk_action',
			$this->get_method( 'media_library_bulk_action' )
		);

		add_action( 'admin_action_-1',
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

		add_filter( 'wp_ajax_tiny_async_optimize_upload_new_media',
			$this->get_method( 'compress_on_upload' )
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

		add_action( 'wp_ajax_tiny_get_compression_status',
			$this->get_method( 'ajax_compression_status' )
		);

		$plugin = plugin_basename(
			dirname( dirname( __FILE__ ) ) . '/tiny-compress-images.php'
		);

		add_filter( "plugin_action_links_$plugin",
			$this->get_method( 'add_plugin_links' )
		);

		add_action( 'wr2x_retina_file_added',
			$this->get_method( 'compress_retina_image' ),
			10, 3
		);

		add_action( 'wr2x_retina_file_removed',
			$this->get_method( 'remove_retina_image' ),
			10, 2
		);

		$this->tiny_compatibility();

		add_thickbox();
	}

	public function admin_menu() {
		add_media_page(
			__( 'Bulk Optimization', 'tiny-compress-images' ),
			esc_html__( 'Bulk Optimization', 'tiny-compress-images' ),
			'upload_files',
			'tiny-bulk-optimization',
			$this->get_method( 'render_bulk_optimization_page' )
		);
	}

	public function add_plugin_links( $current_links ) {
		$additional = array(
			'settings' => sprintf(
				'<a href="options-general.php?page=tinify">%s</a>',
				esc_html__( 'Settings', 'tiny-compress-images' )
			),
			'bulk' => sprintf(
				'<a href="upload.php?page=tiny-bulk-optimization">%s</a>',
				esc_html__( 'Bulk Optimization', 'tiny-compress-images' )
			),
		);
		return array_merge( $additional, $current_links );
	}

	public function tiny_compatibility() {
		if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
			$tiny_wpml_compatibility = new Tiny_WPML();
		}
	}

	public function compress_retina_image( $attachment_id, $path, $size_name ) {
		if ( $this->settings->compress_wr2x_images() ) {
			$tiny_image = new Tiny_Image( $this->settings, $attachment_id );
			$tiny_image->compress_retina( $size_name . '_wr2x', $path );
		}
	}

	public function remove_retina_image( $attachment_id, $path ) {
		$tiny_image = new Tiny_Image( $this->settings, $attachment_id );
		$tiny_image->remove_retina_metadata();
	}

	public function enqueue_scripts( $hook ) {
		wp_enqueue_style( self::NAME . '_admin',
			plugins_url( '/css/admin.css', __FILE__ ),
			array(), self::version()
		);

		wp_enqueue_style( self::NAME . '_chart',
			plugins_url( '/css/chart.css', __FILE__ ),
			array(), self::version()
		);

		wp_register_script( self::NAME . '_admin',
			plugins_url( '/js/admin.js', __FILE__ ),
			array(), self::version(), true
		);

		// WordPress < 3.3 does not handle multidimensional arrays
		wp_localize_script( self::NAME . '_admin', 'tinyCompress', array(
			'nonce' => wp_create_nonce( 'tiny-compress' ),
			'wpVersion' => self::wp_version(),
			'pluginVersion' => self::version(),
			'L10nAllDone' => __( 'All images are processed', 'tiny-compress-images' ),
			'L10nNoActionTaken' => __( 'No action taken', 'tiny-compress-images' ),
			'L10nBulkAction' => __( 'Compress Images', 'tiny-compress-images' ),
			'L10nCancelled' => __( 'Cancelled', 'tiny-compress-images' ),
			'L10nCompressing' => __( 'Compressing', 'tiny-compress-images' ),
			'L10nCompressed' => __( 'compressed', 'tiny-compress-images' ),
			'L10nFile' => __( 'File', 'tiny-compress-images' ),
			'L10nSizesOptimized' => __( 'Sizes optimized', 'tiny-compress-images' ),
			'L10nInitialSize' => __( 'Initial size', 'tiny-compress-images' ),
			'L10nCurrentSize' => __( 'Current size', 'tiny-compress-images' ),
			'L10nSavings' => __( 'Savings', 'tiny-compress-images' ),
			'L10nStatus' => __( 'Status', 'tiny-compress-images' ),
			'L10nShowMoreDetails' => __( 'Show more details', 'tiny-compress-images' ),
			'L10nError' => __( 'Error', 'tiny-compress-images' ),
			'L10nLatestError' => __( 'Latest error', 'tiny-compress-images' ),
			'L10nInternalError' => __( 'Internal error', 'tiny-compress-images' ),
			'L10nOutOf' => __( 'out of', 'tiny-compress-images' ),
			'L10nWaiting' => __( 'Waiting', 'tiny-compress-images' ),
		));

		wp_enqueue_script( self::NAME . '_admin' );

		if ( 'media_page_tiny-bulk-optimization' == $hook ) {
			wp_enqueue_style(
				self::NAME . '_tiny_bulk_optimization',
				plugins_url( '/css/bulk-optimization.css', __FILE__ ),
				array(), self::version()
			);

			wp_enqueue_style( self::NAME . '_chart',
				plugins_url( '/css/chart.css', __FILE__ ),
				array(), self::version()
			);

			wp_register_script(
				self::NAME . '_tiny_bulk_optimization',
				plugins_url( '/js/bulk-optimization.js', __FILE__ ),
				array(), self::version(), true
			);

			wp_enqueue_script( self::NAME . '_tiny_bulk_optimization' );
		}
	}

	public function process_attachment( $metadata, $attachment_id ) {
		if ( $this->settings->auto_compress_enabled() ) {
			if (
				$this->settings->background_compress_enabled() &&
				! $this->settings->remove_local_files_setting_enabled()
			) {
				$this->async_compress_on_upload( $metadata, $attachment_id );
			} else {
				return $this->blocking_compress_on_upload( $metadata, $attachment_id );
			}
		}

		return $metadata;
	}

	public function blocking_compress_on_upload( $metadata, $attachment_id ) {
		if ( ! empty( $metadata ) ) {
			$tiny_image = new Tiny_Image( $this->settings, $attachment_id, $metadata );
			$result = $tiny_image->compress( $this->settings );
			return $tiny_image->get_wp_metadata();
		} else {
			return $metadata;
		}
	}

	public function async_compress_on_upload( $metadata, $attachment_id ) {
		$context     = 'wp';
		$action      = 'tiny_async_optimize_upload_new_media';
		$_ajax_nonce = wp_create_nonce( 'new_media-' . $attachment_id );
		$body = compact( 'action', '_ajax_nonce', 'metadata', 'attachment_id', 'context' );

		$args = array(
			'timeout'   => 0.01,
			'blocking'  => false,
			'body'      => $body,
			'cookies'   => isset( $_COOKIE ) && is_array( $_COOKIE ) ? $_COOKIE : array(),
			'sslverify' => apply_filters( 'https_local_ssl_verify', false ),
		);

		if ( defined( 'XMLRPC_REQUEST' ) && get_current_user_id() ) {
			/* We generate a hash to be used for the transient we use to store the current user. */
			$rpc_hash = md5( maybe_serialize( $body ) );

			$args['body']['tiny_rpc_action'] = $args['body']['action'];
			/* We set a different action to make sure that all RPC requests are first validated. */
			$args['body']['action']          = 'tiny_rpc';
			$args['body']['tiny_rpc_hash']   = $rpc_hash;
			$args['body']['tiny_rpc_nonce']  = wp_create_nonce( 'tiny_rpc_' . $rpc_hash );

			/*
				We can't use cookies here, so we save the user id in a transient
				so that we can retrieve it again when processing the RPC request.
				We should be able to use a relatively short timeout, as the request
				should be processed directly afterwards.
			*/
			set_transient( 'tiny_rpc_' . $rpc_hash, get_current_user_id(), 10 );
		}

		if ( getenv( 'WORDPRESS_HOST' ) !== false ) {
			wp_remote_post( getenv( 'WORDPRESS_HOST' ) . '/wp-admin/admin-ajax.php', $args );
		} else {
			wp_remote_post( admin_url( 'admin-ajax.php' ), $args );
		}
	}

	public function process_rpc_request() {
		if (
			empty( $_POST['tiny_rpc_action'] ) ||
			empty( $_POST['tiny_rpc_hash'] ) ||
			32 !== strlen( $_POST['tiny_rpc_hash'] )
		) {
			exit();
		}

		$rpc_hash = sanitize_key( $_POST['tiny_rpc_hash'] );
		$user_id = absint( get_transient( 'tiny_rpc_' . $rpc_hash ) );
		$user = $user_id ? get_userdata( $user_id ) : false;

		/* We no longer need the transient. */
		delete_transient( 'tiny_rpc_' . $rpc_hash );

		if ( ! $user || ! $user->exists() ) {
			exit();
		}
		wp_set_current_user( $user_id );

		if ( ! check_ajax_referer( 'tiny_rpc_' . $rpc_hash, 'tiny_rpc_nonce', false ) ) {
			exit();
		}

		/* Now that everything is checked, perform the actual action. */
		$action = $_POST['tiny_rpc_action'];
		unset(
			$_POST['action'],
			$_POST['tiny_rpc_action'],
			$_POST['tiny_rpc_id'],
			$_POST['tiny_rpc_nonce']
		);
		do_action( 'wp_ajax_' . $action );
	}

	public function compress_on_upload() {
		$attachment_id = intval( $_POST['attachment_id'] );
		$metadata = $_POST['metadata'];
		if ( is_array( $metadata ) ) {
			$tiny_image = new Tiny_Image( $this->settings, $attachment_id, $metadata );
			$result = $tiny_image->compress( $this->settings );
			// The wp_update_attachment_metadata call is thrown because the
			// dimensions of the original image can change. This will then
			// trigger other plugins and can result in unexpected behaviour and
			// further changes to the image. This may require another approach.
			wp_update_attachment_metadata( $attachment_id, $tiny_image->get_wp_metadata() );
		}
		exit();
	}

	public function compress_image_from_library() {
		if ( ! $this->check_ajax_referer() ) {
			exit();
		}
		if ( ! current_user_can( 'upload_files' ) ) {
			$message = esc_html__(
				"You don't have permission to upload files.",
				'tiny-compress-images'
			);
			echo $message;
			exit();
		}
		if ( empty( $_POST['id'] ) ) {
			$message = esc_html__(
				'Not a valid media file.',
				'tiny-compress-images'
			);
			echo $message;
			exit();
		}
		$id = intval( $_POST['id'] );
		$metadata = wp_get_attachment_metadata( $id );
		if ( ! is_array( $metadata ) ) {
			$message = esc_html__(
				'Could not find metadata of media file.',
				'tiny-compress-images'
			);
			echo $message;
			exit;
		}

		$tiny_image = new Tiny_Image( $this->settings, $id, $metadata );
		$result = $tiny_image->compress( $this->settings );

		// The wp_update_attachment_metadata call is thrown because the
		// dimensions of the original image can change. This will then
		// trigger other plugins and can result in unexpected behaviour and
		// further changes to the image. This may require another approach.
		wp_update_attachment_metadata( $id, $tiny_image->get_wp_metadata() );

		echo $this->render_compress_details( $tiny_image );

		exit();
	}

	public function compress_image_for_bulk() {
		if ( ! $this->check_ajax_referer() ) {
			exit();
		}
		if ( ! current_user_can( 'upload_files' ) ) {
			$message = esc_html__(
				"You don't have permission to upload files.",
				'tiny-compress-images'
			);
			echo json_encode( array(
				'error' => $message,
			) );
			exit();
		}
		if ( empty( $_POST['id'] ) ) {
			$message = esc_html__(
				'Not a valid media file.',
				'tiny-compress-images'
			);
			echo json_encode( array(
				'error' => $message,
			) );
			exit();
		}
		$id = intval( $_POST['id'] );
		$metadata = wp_get_attachment_metadata( $id );
		if ( ! is_array( $metadata ) ) {
			$message = esc_html__(
				'Could not find metadata of media file.',
				'tiny-compress-images'
			);
			echo json_encode( array(
				'error' => $message,
			) );
			exit;
		}

		$tiny_image_before = new Tiny_Image( $this->settings, $id, $metadata );
		$image_statistics_before = $tiny_image_before->get_statistics(
			$this->settings->get_sizes(),
			$this->settings->get_active_tinify_sizes()
		);
		$size_before = $image_statistics_before['optimized_total_size'];

		$tiny_image = new Tiny_Image( $this->settings, $id, $metadata );
		$result = $tiny_image->compress( $this->settings );
		$image_statistics = $tiny_image->get_statistics(
			$this->settings->get_sizes(),
			$this->settings->get_active_tinify_sizes()
		);
		wp_update_attachment_metadata( $id, $tiny_image->get_wp_metadata() );

		$current_library_size = intval( $_POST['current_size'] );
		$size_after = $image_statistics['optimized_total_size'];
		$new_library_size = $current_library_size + $size_after - $size_before;

		$result['message'] = $tiny_image->get_latest_error();
		$result['image_sizes_optimized'] = $image_statistics['image_sizes_optimized'];

		$result['initial_total_size'] = size_format(
			$image_statistics['initial_total_size'], 1
		);

		$result['optimized_total_size'] = size_format(
			$image_statistics['optimized_total_size'], 1
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
		$result['human_readable_library_size'] = size_format( $new_library_size, 2 );

		echo json_encode( $result );

		exit();
	}

	public function ajax_optimization_statistics() {
		if ( ! $this->check_ajax_referer() ) {
			exit();
		}
		$stats = Tiny_Bulk_Optimization::get_optimization_statistics( $this->settings );
		echo json_encode( $stats );
		exit();
	}

	public function ajax_compression_status() {
		if ( ! $this->check_ajax_referer() ) {
			exit();
		}

		if ( empty( $_POST['id'] ) ) {
			$message = esc_html__(
				'Not a valid media file.',
				'tiny-compress-images'
			);
			echo $message;
			exit();
		}
		$id = intval( $_POST['id'] );
		$metadata = wp_get_attachment_metadata( $id );
		if ( ! is_array( $metadata ) ) {
			$message = esc_html__(
				'Could not find metadata of media file.',
				'tiny-compress-images'
			);
			echo $message;
			exit;
		}

		$tiny_image = new Tiny_Image( $this->settings, $id, $metadata );

		echo $this->render_compress_details( $tiny_image );

		exit();
	}

	public function media_library_bulk_action() {
		if ( empty( $_REQUEST['action'] ) || (
				'tiny_bulk_action' != $_REQUEST['action'] &&
				'tiny_bulk_action' != $_REQUEST['action2'] ) ) {
			return;
		}
		if ( empty( $_REQUEST['media'] ) || ( ! $_REQUEST['media'] ) ) {
			$_REQUEST['action'] = '';
			return;
		}
		check_admin_referer( 'bulk-media' );
		$ids = implode( '-', array_map( 'intval', $_REQUEST['media'] ) );
		$location = 'upload.php?mode=list&ids=' . $ids;

		if ( ! empty( $_REQUEST['paged'] ) ) {
			$location = add_query_arg( 'paged', absint( $_REQUEST['paged'] ), $location );
		}
		if ( ! empty( $_REQUEST['s'] ) ) {
			$location = add_query_arg( 's', $_REQUEST['s'], $location );
		}
		if ( ! empty( $_REQUEST['m'] ) ) {
			$location = add_query_arg( 'm', $_REQUEST['m'], $location );
		}

		wp_redirect( admin_url( $location ) );
		exit();
	}

	public function add_media_columns( $columns ) {
		$columns[ self::MEDIA_COLUMN ] = esc_html__( 'Compression', 'tiny-compress-images' );
		return $columns;
	}

	public function render_media_column( $column, $id ) {
		if ( self::MEDIA_COLUMN === $column ) {
			$tiny_image = new Tiny_Image( $this->settings, $id );
			if ( $tiny_image->file_type_allowed() ) {
				echo '<div class="tiny-ajax-container">';
				$this->render_compress_details( $tiny_image );
				echo '</div>';
			}
		}
	}

	public function show_media_info() {
		global $post;
		$tiny_image = new Tiny_Image( $this->settings, $post->ID );
		if ( $tiny_image->file_type_allowed() ) {
			echo '<div class="misc-pub-section tiny-compress-images">';
			echo '<h4>';
			esc_html_e( 'JPEG and PNG optimization', 'tiny-compress-images' );
			echo '</h4>';
			echo '<div class="tiny-ajax-container">';
			$this->render_compress_details( $tiny_image );
			echo '</div>';
			echo '</div>';
		}
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
		$stats = Tiny_Bulk_Optimization::get_optimization_statistics( $this->settings );
		$estimated_costs = Tiny_Compress::estimate_cost(
			$stats['available-unoptimised-sizes'],
			$this->settings->get_compression_count()
		);
		$admin_colors = self::retrieve_admin_colors();

		/* This makes sure that up to date information is retrieved from the API. */
		$this->settings->get_compressor()->get_status();

		$active_tinify_sizes = $this->settings->get_active_tinify_sizes();
		$remaining_credits = $this->settings->get_remaining_credits();
		$is_on_free_plan = $this->settings->is_on_free_plan();
		$email_address = $this->settings->get_email_address();

		include( dirname( __FILE__ ) . '/views/bulk-optimization.php' );
	}

	public function add_dashboard_widget() {
		wp_enqueue_style( self::NAME . '_chart',
			plugins_url( '/css/chart.css', __FILE__ ),
			array(), self::version()
		);

		wp_enqueue_style( self::NAME . '_dashboard_widget',
			plugins_url( '/css/dashboard-widget.css', __FILE__ ),
			array(), self::version()
		);

		wp_register_script( self::NAME . '_dashboard_widget',
			plugins_url( '/js/dashboard-widget.js', __FILE__ ),
			array(), self::version(), true
		);

		/* This might be deduplicated with the admin script localization, but
		   the order of including scripts is sometimes different. So in that
		   case we need to make sure that the order of inclusion is correc.t */
		wp_localize_script( self::NAME . '_dashboard_widget', 'tinyCompressDashboard', array(
			'nonce' => wp_create_nonce( 'tiny-compress' ),
		));

		wp_enqueue_script( self::NAME . '_dashboard_widget' );

		wp_add_dashboard_widget(
			$this->get_prefixed_name( 'dashboard_widget' ),
			esc_html__( 'Compress JPEG & PNG images', 'tiny-compress-images' ),
			$this->get_method( 'add_widget_view' )
		);
	}

	function add_widget_view() {
		$admin_colors = self::retrieve_admin_colors();
		include( dirname( __FILE__ ) . '/views/dashboard-widget.php' );
	}

	private static function retrieve_admin_colors() {
		global $_wp_admin_css_colors;
		$admin_colour_scheme = get_user_option( 'admin_color', get_current_user_id() );
		$admin_colors = array( '#0074aa', '#1685b5', '#78ca44', '#0086ba' ); // default
		if ( isset( $_wp_admin_css_colors[ $admin_colour_scheme ] ) ) {
			if ( isset( $_wp_admin_css_colors[ $admin_colour_scheme ]->colors ) ) {
				$admin_colors = $_wp_admin_css_colors[ $admin_colour_scheme ]->colors;
			}
		}
		if ( '#e5e5e5' == $admin_colors[0] && '#999' == $admin_colors[1] ) {
			$admin_colors[0] = '#bbb';
		}
		if ( '#5589aa' == $admin_colors[0] && '#cfdfe9' == $admin_colors[1] ) {
			$admin_colors[1] = '#85aec5';
		}
		if ( '#7c7976' == $admin_colors[0] && '#c6c6c6' == $admin_colors[1] ) {
			$admin_colors[1] = '#adaba9';
			$admin_colors[2] = '#adaba9';
		}
		if ( self::wp_version() > 3.7 ) {
			if ( 'fresh' == $admin_colour_scheme ) {
				$admin_colors = array( '#0074aa', '#1685b5', '#78ca44', '#0086ba' ); // better
			}
		}
		return $admin_colors;
	}

	function friendly_user_name() {
		$user = wp_get_current_user();
		$name = ucfirst( empty( $user->first_name ) ? $user->display_name : $user->first_name );
		return $name;
	}
}
