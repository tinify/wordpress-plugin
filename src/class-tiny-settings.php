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
class Tiny_Settings extends Tiny_WP_Base {
	const DUMMY_SIZE = '_tiny_dummy';

	private $sizes;
	private $tinify_sizes;
	private $compressor;
	private $notices;

	protected static $incompatible_plugins = array(
		'CheetahO Image Optimizer' => 'cheetaho-image-optimizer/cheetaho.php',
		'EWWW Image Optimizer' => 'ewww-image-optimizer/ewww-image-optimizer.php',
		'Imagify' => 'imagify/imagify.php',
		'Kraken Image Optimizer' => 'kraken-image-optimizer/kraken.php',
		'ShortPixel Image Optimizer' => 'shortpixel-image-optimiser/wp-shortpixel.php',
		'WP Smush' => 'wp-smushit/wp-smush.php',
		'WP Smush Pro' => 'wp-smush-pro/wp-smush.php',
	);

	protected static $offload_s3_plugin = 'amazon-s3-and-cloudfront/wordpress-s3.php';

	public function __construct() {
		parent::__construct();
		$this->notices = new Tiny_Notices();
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
	}

	private function init_compressor() {
		$this->compressor = Tiny_Compress::create(
			$this->get_api_key(),
			$this->get_method( 'after_compress_callback' )
		);
	}

	public function get_absolute_url() {
		return get_admin_url( null, 'options-general.php?page=tinify' );
	}

	public function xmlrpc_init() {
		try {
			$this->init_compressor();
		} catch ( Tiny_Exception $e ) {
		}
	}

	public function add_menu() {
		add_options_page(
			__( 'Compress JPEG & PNG images', 'tiny-compress-images' ),
			esc_html__( 'Compress JPEG & PNG images', 'tiny-compress-images' ),
			'manage_options',
			'tinify',
			array( $this, 'add_options_to_page' )
		);
	}

	public function admin_init() {
		if ( current_user_can( 'manage_options' ) ) {
			$this->render_notices();
		}

		try {
			$this->init_compressor();
		} catch ( Tiny_Exception $e ) {
			$this->notices->show(
				'compressor_exception',
				esc_html( $e->getMessage(), 'tiny-compress-images' ),
				'error', false
			);
		}

		/* Create link to new settings page from media settings page. */
		add_settings_section( 'section_end', '',
			$this->get_method( 'render_settings_link' ),
			'media'
		);

		$field = self::get_prefixed_name( 'api_key' );
		register_setting( 'tinify', $field );

		$field = self::get_prefixed_name( 'api_key_pending' );
		register_setting( 'tinify', $field );

		$field = self::get_prefixed_name( 'compression_timing' );
		register_setting( 'tinify', $field );

		$field = self::get_prefixed_name( 'sizes' );
		register_setting( 'tinify', $field );

		$field = self::get_prefixed_name( 'resize_original' );
		register_setting( 'tinify', $field );

		$field = self::get_prefixed_name( 'preserve_data' );
		register_setting( 'tinify', $field );

		add_action(
			'wp_ajax_tiny_image_sizes_notice',
			$this->get_method( 'image_sizes_notice' )
		);

		add_action(
			'wp_ajax_tiny_account_status',
			$this->get_method( 'account_status' )
		);

		add_action(
			'wp_ajax_tiny_settings_create_api_key',
			$this->get_method( 'create_api_key' )
		);

		add_action(
			'wp_ajax_tiny_settings_update_api_key',
			$this->get_method( 'update_api_key' )
		);
	}

	public function add_options_to_page() {
		include( dirname( __FILE__ ) . '/views/settings.php' );
	}

	public function image_sizes_notice() {
		$this->render_image_sizes_notice(
			$_GET['image_sizes_selected'],
			isset( $_GET['resize_original'] ),
			isset( $_GET['compress_wr2x'] )
		);
		exit();
	}

	public function account_status() {
		$this->render_account_status();
		exit();
	}

	public function get_compressor() {
		return $this->compressor;
	}

	public function set_compressor( $compressor ) {
		$this->compressor = $compressor;
	}

	public function get_status() {
		return intval( get_option( self::get_prefixed_name( 'status' ) ) );
	}

	public function disabled_required_functions() {
		$required_functions = array( 'curl_exec' );
		$disabled_required_functions = array();
		$disabled_functions = explode( ',', ini_get( 'disable_functions' ) );

		foreach ( $required_functions as $required_function ) {
			if ( in_array( $required_function, $disabled_functions ) ) {
				array_push( $disabled_required_functions, $required_function );
			}
		}

		return $disabled_required_functions;
	}

	protected function get_api_key() {
		if ( defined( 'TINY_API_KEY' ) ) {
			return TINY_API_KEY;
		} else {
			return get_option( self::get_prefixed_name( 'api_key' ) );
		}
	}

	protected function get_api_key_pending() {
		if ( defined( 'TINY_API_KEY' ) ) {
			return false;
		} else {
			return get_option( self::get_prefixed_name( 'api_key_pending' ) );
		}
	}

	protected function clear_api_key_pending() {
		delete_option( self::get_prefixed_name( 'api_key_pending' ) );
	}

	protected static function get_intermediate_size( $size ) {
		/* Inspired by
		http://codex.wordpress.org/Function_Reference/get_intermediate_image_sizes */
		global $_wp_additional_image_sizes;

		$width  = get_option( $size . '_size_w' );
		$height = get_option( $size . '_size_h' );

		/* Note: dimensions might be 0 to indicate no limit. */
		if ( $width || $height ) {
			return array( $width, $height );
		}

		if ( isset( $_wp_additional_image_sizes[ $size ] ) ) {
			$sizes = $_wp_additional_image_sizes[ $size ];
			return array(
				isset( $sizes['width'] ) ? $sizes['width'] : null,
				isset( $sizes['height'] ) ? $sizes['height'] : null,
			);
		}
		return array( null, null );
	}

	public function get_sizes() {
		if ( is_array( $this->sizes ) ) {
			return $this->sizes;
		}

		$setting = get_option( self::get_prefixed_name( 'sizes' ) );

		$size = Tiny_Image::ORIGINAL;
		$this->sizes = array(
			$size => array(
				'width' => null,
				'height' => null,
				'tinify' => ! is_array( $setting ) ||
					( isset( $setting[ $size ] ) && 'on' === $setting[ $size ] ),
			),
		);

		foreach ( get_intermediate_image_sizes() as $size ) {
			if ( self::DUMMY_SIZE === $size ) {
				continue;
			}

			list($width, $height) = self::get_intermediate_size( $size );
			if ( $width || $height ) {
				$this->sizes[ $size ] = array(
					'width' => $width,
					'height' => $height,
					'tinify' => ! is_array( $setting ) ||
						( isset( $setting[ $size ] ) && 'on' === $setting[ $size ] ),
				);
			}
		}

		return $this->sizes;
	}

	public function get_active_tinify_sizes() {
		if ( is_array( $this->tinify_sizes ) ) {
			return $this->tinify_sizes;
		}

		$this->tinify_sizes = array();
		foreach ( $this->get_sizes() as $size => $values ) {
			if ( $values['tinify'] ) {
				$this->tinify_sizes[] = $size;
			}
		}
		return $this->tinify_sizes;
	}

	public function new_plugin_install() {
		/* We merely have to check whether a newly added setting is already stored. */
		$compression_timing = get_option( self::get_prefixed_name( 'compression_timing' ) );
		return ! $compression_timing;
	}

	public function get_resize_enabled() {
		/* This only applies if the original is being resized. */
		$sizes = $this->get_sizes();
		if ( ! $sizes[ Tiny_Image::ORIGINAL ]['tinify'] ) {
			return false;
		}

		$setting = get_option( self::get_prefixed_name( 'resize_original' ) );
		return isset( $setting['enabled'] ) && 'on' === $setting['enabled'];
	}

	public function get_compression_timing() {
		$setting = get_option( self::get_prefixed_name( 'compression_timing' ) );
		if ( isset( $setting ) && $setting ) {
			return $setting;
		} elseif ( $this->new_plugin_install() ) {
			update_option( self::get_prefixed_name( 'compression_timing' ), 'background' );
			return 'background';
		} else {
			update_option( self::get_prefixed_name( 'compression_timing' ), 'auto' );
			return 'auto';
		}
	}

	public function auto_compress_enabled() {
		return 	$this->get_compression_timing() === 'auto' ||
						$this->get_compression_timing() === 'background';
	}

	public function background_compress_enabled() {
		return $this->get_compression_timing() === 'background';
	}

	public function has_offload_s3_installed() {
		if (
			! function_exists( 'is_plugin_active' ) ||
			! is_plugin_active( self::$offload_s3_plugin )
		) {
			return false;
		}
		$setting = get_option( 'tantan_wordpress_s3' );
		if ( ! is_array( $setting ) ) {
			return false;
		}

		return true;
	}

	public function old_offload_s3_version_installed() {
		if (
			function_exists( 'is_plugin_active' ) &&
			is_plugin_active( self::$offload_s3_plugin ) &&
			function_exists( 'get_plugin_data' )
		) {
			$metadata = get_plugin_data( WP_PLUGIN_DIR . '/' . self::$offload_s3_plugin );
			$offload_s3_version_parts = explode( '.', $metadata['Version'] );
			$major_version = intval( $offload_s3_version_parts[0] );
			$minor_version = intval( $offload_s3_version_parts[1] );
			if ( 0 === $major_version && $minor_version < 7 ) {
				return true;
			}
		}

		return false;
	}

	public function remove_local_files_setting_enabled() {
		/* Check if Offload S3 plugin is installed. */
		if (
			! function_exists( 'is_plugin_active' ) ||
			! is_plugin_active( self::$offload_s3_plugin )
		) {
			return false;
		}
		$setting = get_option( 'tantan_wordpress_s3' );
		if ( ! is_array( $setting ) ) {
			return false;
		}
		/* Check if Offload S3 is configured to remove local files. */
		return ( $this->has_offload_s3_installed() &&
						 array_key_exists( 'remove-local-file', $setting ) &&
						 '1' === $setting['remove-local-file'] );
	}

	public function get_preserve_enabled( $name ) {
		$setting = get_option( self::get_prefixed_name( 'preserve_data' ) );
		return isset( $setting[ $name ] ) && 'on' === $setting[ $name ];
	}

	public function get_preserve_options( $size_name ) {
		if ( ! Tiny_Image::is_original( $size_name ) ) {
			return false;
		}
		$options = array();
		$settings = get_option( self::get_prefixed_name( 'preserve_data' ) );
		if ( $settings ) {
			$keys = array_keys( $settings );
			foreach ( $keys as &$key ) {
				if ( 'on' === $settings[ $key ] ) {
					array_push( $options, $key );
				}
			}
		}
		return $options;
	}

	public function get_resize_options( $size_name ) {
		if ( ! Tiny_Image::is_original( $size_name ) ) {
			return false;
		}
		if ( ! $this->get_resize_enabled() ) {
			return false;
		}
		$setting = get_option( self::get_prefixed_name( 'resize_original' ) );
		$width = intval( $setting['width'] );
		$height = intval( $setting['height'] );
		$method = $width > 0 && $height > 0 ? 'fit' : 'scale';
		$options['method'] = $method;
		if ( $width > 0 ) {
			$options['width'] = $width;
		}
		if ( $height > 0 ) {
			$options['height'] = $height;
		}
		return sizeof( $options ) >= 2 ? $options : false;
	}

	public function render_notices() {
		$this->render_setup_incomplete_notice();
		$this->render_outdated_platform_notice();
		$this->render_incompatible_plugins_notice();
		$this->render_offload_s3_notices();
	}

	public function render_setup_incomplete_notice() {
		if ( ! $this->get_api_key() ) {
			$notice_class = 'error';
			$notice = esc_html__(
				'Please register or provide an API key to start compressing images.',
				'tiny-compress-images'
			);
		} elseif ( $this->get_api_key_pending() ) {
			$notice_class = 'notice-warning';
			$notice = esc_html__(
				'Please activate your account to start compressing images.',
				'tiny-compress-images'
			);
		}

		if ( isset( $notice ) && $notice ) {
			$link = sprintf(
				'<a href="options-general.php?page=tinify">%s</a>', $notice
			);
			$this->notices->show( 'setting', $link, $notice_class, false );
		}
	}

	public function render_outdated_platform_notice() {
		if ( ! Tiny_PHP::client_supported() ) {
			if ( ! Tiny_PHP::has_fully_supported_php() ) {
				$details = 'PHP ' . PHP_VERSION;
				if ( Tiny_PHP::curl_available() ) {
					$curlinfo = curl_version();
					$details .= ' ' . sprintf(
						/* translators: %s: curl version */
						esc_html__( 'with curl %s', 'tiny-compress-images' ), $curlinfo['version']
					);
				} else {
					$details .= ' ' . esc_html__( 'without curl', 'tiny-compress-images' );
				}
				if ( Tiny_PHP::curl_exec_disabled() ) {
					$details .= ' ' .
						esc_html__( 'and curl_exec disabled', 'tiny-compress-images' );
				}
				$message = sprintf(
					/* translators: %s: details of outdated platform */
					esc_html__(
						'You are using an outdated platform (%s).',
						'tiny-compress-images'
					), $details
				);
			} elseif ( ! Tiny_PHP::curl_available() ) {
				$message = esc_html__(
					'We noticed that cURL is not available. For the best experience we recommend to make sure cURL is available.', // WPCS: Needed for proper translation.
					'tiny-compress-images'
				);
			} elseif ( Tiny_PHP::curl_exec_disabled() ) {
				$message = esc_html__(
					'We noticed that curl_exec is disabled in your PHP configuration. Please update this setting for the best experience.', // WPCS: Needed for proper translation.
					'tiny-compress-images'
				);
			}
			$this->notices->show( 'deprecated', $message, 'notice-warning', false );
		} // End if().
	}

	public function render_incompatible_plugins_notice() {
		$incompatible_plugins = array_filter( self::$incompatible_plugins, 'is_plugin_active' );
		if ( count( $incompatible_plugins ) > 0 ) {
			$this->notices->show_incompatible_plugins( $incompatible_plugins );
		}
	}

	public function render_settings_link() {
		echo '<div class="tinify-settings"><h3>';
		esc_html_e( 'Compress JPEG & PNG images', 'tiny-compress-images' );
		echo '</h3>';
		$url = admin_url( 'options-general.php?page=tinify' );
		$link = "<a href='" . $url . "'>";
		$link .= esc_html__( 'settings', 'tiny-compress-images' );
		$link .= '</a>';
		printf(
			wp_kses(
				/* translators: %s: link saying settings */
				__( 'The %s have moved.', 'tiny-compress-images' ),
				array(
					'a' => array(
						'href' => array(),
					),
				)
			),
			$link
		);
		echo '</div>';
	}

	public function render_offload_s3_notices() {
		if ( $this->remove_local_files_setting_enabled() &&
				 'background' === $this->get_compression_timing() ) {
			$message = esc_html__(
				'Removing files from the server is incompatible with background compressions. Images will still be automatically compressed, but no longer in the background.',  // WPCS: Needed for proper translation.
				'tiny-compress-images'
			);
			$this->notices->show( 'offload-s3', $message, 'notice-error', false );
			update_option( self::get_prefixed_name( 'compression_timing' ), 'auto' );
		}

		if ( $this->old_offload_s3_version_installed() &&
				 'background' === $this->get_compression_timing() ) {
			$message = esc_html__(
				'Background compressions are not compatible with the version of WP Offload S3 you have installed. Please update to version 0.7.2 at least.',  // WPCS: Needed for proper translation.
				'tiny-compress-images'
			);
			$this->notices->show( 'old-offload-s3-version', $message, 'notice-error', false );
			update_option( self::get_prefixed_name( 'compression_timing' ), 'auto' );
		}
	}

	public function render_compression_timing_settings() {
		$heading = esc_html__(
			'When should new images be compressed?',
			'tiny-compress-images'
		);
		echo '<h4>' . $heading . '</h4>';
		echo '<div class="optimization-options">';

		$name = self::get_prefixed_name( 'compression_timing' );
		$compression_timing = $this->get_compression_timing();

		$id = self::get_prefixed_name( 'background_compress_enabled' );
		$checked = ( 'background' === $compression_timing ? ' checked="checked"' : '' );

		$label = esc_html__(
			'Compress new images in the background (Recommended)',
			'tiny-compress-images'
		);
		$description = esc_html__(
			'This is the fastest method, but can cause issues with some image related plugins.',
			'tiny-compress-images'
		);

		$this->render_compression_timing_radiobutton(
			$name,
			$label,
			$description,
			'background',
			$checked,
			$this->remove_local_files_setting_enabled() || $this->old_offload_s3_version_installed()
		);

		$id = self::get_prefixed_name( 'auto_compress_enabled' );
		$checked = ( 'auto' === $compression_timing ? ' checked="checked"' : '' );

		$label = esc_html__(
			'Compress new images during upload',
			'tiny-compress-images'
		);
		$description = esc_html__(
			'Uploads will take longer, but provides higher compatibility with other plugins.',
			'tiny-compress-images'
		);

		$this->render_compression_timing_radiobutton(
			$name,
			$label,
			$description,
			'auto',
			$checked,
			false
		);

		$id = self::get_prefixed_name( 'auto_compress_disabled' );
		$checked = ( 'manual' === $compression_timing ? ' checked="checked"' : '' );

		$label = esc_html__(
			'Do not compress new images automatically',
			'tiny-compress-images'
		);
		$description = esc_html__(
			'Manually select the images you want to compress in the media library.',
			'tiny-compress-images'
		);

		$this->render_compression_timing_radiobutton(
			$name,
			$label,
			$description,
			'manual',
			$checked,
			false
		);

		echo '</div>';
	}

	public function render_sizes() {
		echo '<input type="hidden" name="' .
			self::get_prefixed_name( 'sizes[' . self::DUMMY_SIZE . ']' ) . '" value="on"/>';

		foreach ( $this->get_sizes() as $size => $option ) {
			$this->render_size_checkbox( $size, $option );
		}
		if ( self::wr2x_active() ) {
			$this->render_size_checkbox( 'wr2x', $this->get_wr2x_option() );
		}
		echo '<br>';
		echo '<div id="tiny-image-sizes-notice">';

		$this->render_image_sizes_notice(
			count( self::get_active_tinify_sizes() ),
			self::get_resize_enabled(),
			self::compress_wr2x_images()
		);

		echo '</div>';
	}

	private function render_size_checkbox( $size, $option ) {
		$id = self::get_prefixed_name( "sizes_$size" );
		$name = self::get_prefixed_name( 'sizes[' . $size . ']' );
		$checked = ( $option['tinify'] ? ' checked="checked"' : '' );
		if ( Tiny_Image::is_original( $size ) ) {
			$label = esc_html__( 'Original image', 'tiny-compress-images' ) . ' (' .
				esc_html__(
					'overwritten by compressed image',
					'tiny-compress-images'
				) . ')';
		} elseif ( Tiny_Image::is_retina( $size ) ) {
			$label = esc_html__( 'WP Retina 2x sizes', 'tiny-compress-images' );
		} else {
			$width = $option['width'];
			if ( ! $width ) {
				$width = '?';
			}

			$height = $option['height'];
			if ( ! $height ) {
				$height = '?';
			}

			$label = esc_html( ucfirst( str_replace( '_', ' ', $size ) ) )
				. ' - ' . $width . 'x' . $height;
		}
		echo '<p>';
		echo '<input type="checkbox" id="' . $id . '" name="' . $name .
			'" value="on" ' . $checked . '/>';
		echo '<label for="' . $id . '">' . $label . '</label>';
		echo '</p>';
	}

	public function render_image_sizes_notice(
		$active_sizes_count, $resize_original_enabled, $compress_wr2x ) {
		echo '<p>';
		esc_html_e(
			'Remember each selected size counts as a compression.',
			'tiny-compress-images'
		);
		echo '</p>';
		echo '<p>';
		if ( $resize_original_enabled ) {
			$active_sizes_count++;
		}
		if ( $compress_wr2x ) {
			$active_sizes_count *= 2;
		}

		if ( $active_sizes_count < 1 ) {
			esc_html_e(
				'With these settings no images will be compressed.',
				'tiny-compress-images'
			);
		} else {
			$free_images_per_month = floor(
				Tiny_Config::MONTHLY_FREE_COMPRESSIONS / $active_sizes_count
			);

			$strong = array(
				'strong' => array(),
			);

			/* translators: %1$s: number of images */
			printf( wp_kses( __(
				'With these settings you can compress <strong>at least %1$s images</strong> for free each month.', // WPCS: Needed for proper translation.
				'tiny-compress-images'
			), $strong ), $free_images_per_month );

			if ( self::wr2x_active() ) {
				echo '</p>';
				echo '<p>';
				esc_html_e(
					'If selected, retina sizes will be compressed when generated by WP Retina 2x',
					'tiny-compress-images'
				);
				echo '<br>';
				esc_html_e(
					'Each retina size will count as an additional compression.',
					'tiny-compress-images'
				);
			}
		} // End if().
		echo '</p>';
	}

	public function render_resize() {
		$strong = array(
			'strong' => array(),
		);

		echo '<div class="tiny-resize-unavailable" style="display: none">';
		esc_html_e(
			'Enable compression of the original image size for more options.',
			'tiny-compress-images'
		);
		echo '</div>';

		$id = self::get_prefixed_name( 'resize_original_enabled' );
		$name = self::get_prefixed_name( 'resize_original[enabled]' );
		$checked = ( $this->get_resize_enabled() ? ' checked="checked"' : '' );

		$label = esc_html__(
			'Resize the original image',
			'tiny-compress-images'
		);

		echo '<div class="tiny-resize-available">';
		echo '<input  type="checkbox" id="' . $id . '" name="' . $name .
			'" value="on" ' . $checked . '/>';
		echo '<label for="' . $id . '">' . $label . '</label><br>';

		echo '<div class="tiny-resize-available tiny-resize-resolution">';
		echo '<span>';
		echo wp_kses( __( '<strong>Save space</strong> by setting a maximum width and height for all images uploaded.', 'tiny-compress-images' ), $strong );  // WPCS: Needed for proper translation.
		echo '<br>';
		echo wp_kses( __( 'Resizing takes <strong>1 additional compression</strong> for each image that is larger.', 'tiny-compress-images' ), $strong ); // WPCS: Needed for proper translation.
		echo '</span>';
		echo '<div class="tiny-resize-inputs">';
		printf( '%s: ', esc_html__( 'Max Width' ) );
		$this->render_resize_input( 'width' );
		printf( '%s: ', esc_html__( 'Max Height' ) );
		$this->render_resize_input( 'height' );
		echo '</div></div></div>';

		$this->render_preserve_input(
			'creation',
			esc_html__(
				'Preserve creation date and time in the original image',
				'tiny-compress-images'
			)
		);

		$this->render_preserve_input(
			'copyright',
			esc_html__(
				'Preserve copyright information in the original image',
				'tiny-compress-images'
			)
		);

		$this->render_preserve_input(
			'location',
			esc_html__(
				'Preserve GPS location in the original image',
				'tiny-compress-images'
			) . ' ' .
			esc_html__( '(JPEG only)', 'tiny-compress-images' )
		);
	}

	public function render_compression_timing_radiobutton(
			$name,
			$label,
			$desc,
			$value,
			$checked,
			$disabled
	) {
		if ( $disabled ) {
			echo '<div class="notice notice-warning inline"><p>';
			echo '<strong>' . esc_html__( 'Warning', 'tiny-compress-images' ) . '</strong> — ';
			if ( $this->old_offload_s3_version_installed() ) {
				$message = esc_html_e(
					'Background compressions are not compatible with the version of WP Offload S3 you have installed. Please update to version 0.7.2 at least.', // WPCS: Needed for proper translation.
					'tiny-compress-images'
				);
			} elseif ( $this->remove_local_files_setting_enabled() ) {
				$message = esc_html_e(
					'For background compression to work you will need to configure WP Offload S3 to keep a copy of the images on the server.', // WPCS: Needed for proper translation.
					'tiny-compress-images'
				);
			}
			echo '</p></div>';
			echo '<p class="tiny-radio disabled">';
		} else {
			echo '<p class="tiny-radio">';
		}
		$id = sprintf( self::get_prefixed_name( 'compression_timing_%s' ), $value );
		$label = esc_html( $label, 'tiny-compress-images' );
		$desc = esc_html( $desc, 'tiny-compress-images' );
		$disabled = ( $disabled ? ' disabled="disabled"' : '' );
		echo '<input type="radio" id="' . $id . '" name="' . $name .
							'" value="' . $value . '" ' . $checked . ' ' . $disabled . '/>';
		echo '<label for="' . $id . '">' . $label . '</label>';
		echo '<br>';
		echo '<span class="description">' . $desc . '</span>';
		echo '<br>';
		echo '</p>';
	}

	public function render_preserve_input( $name, $description ) {
		echo '<p class="tiny-preserve">';
		$id = sprintf( self::get_prefixed_name( 'preserve_data_%s' ), $name );
		$field = sprintf( self::get_prefixed_name( 'preserve_data[%s]' ), $name );
		$checked = ( $this->get_preserve_enabled( $name ) ? ' checked="checked"' : '' );
		$label = esc_html( $description, 'tiny-compress-images' );
		echo '<input type="checkbox" id="' . $id . '" name="' . $field .
			'" value="on" ' . $checked . '/>';
		echo '<label for="' . $id . '">' . $label . '</label>';
		echo '<br>';
		echo '</p>';
	}

	public function render_resize_input( $name ) {
		$id = sprintf( self::get_prefixed_name( 'resize_original_%s' ), $name );
		$field = sprintf( self::get_prefixed_name( 'resize_original[%s]' ), $name );
		$settings = get_option( self::get_prefixed_name( 'resize_original' ) );
		$value = isset( $settings[ $name ] ) ? $settings[ $name ] : '2048';
		echo '<input type="number" id="' . $id . '" name="' . $field .
			'" value="' . $value . '" size="5" />';
	}

	public function get_compression_count() {
		$field = self::get_prefixed_name( 'status' );
		return get_option( $field );
	}

	public function limit_reached() {
		$this->compressor->get_compression_count();
		return $this->compressor->limit_reached();
	}

	public function get_remaining_credits() {
		$field = self::get_prefixed_name( 'remaining_credits' );
		return get_option( $field );
	}

	public function get_paying_state() {
		$field = self::get_prefixed_name( 'paying_state' );
		return get_option( $field );
	}

	public function is_on_free_plan() {
		return self::get_paying_state() === 'free';
	}

	public function get_email_address() {
		$field = self::get_prefixed_name( 'email_address' );
		return get_option( $field );
	}

	public function after_compress_callback( $compressor ) {
		$count = $compressor->get_compression_count();
		if ( ! is_null( $count ) ) {
			$field = self::get_prefixed_name( 'status' );
			update_option( $field, $count );
		}
		$remaining_credits = $compressor->get_remaining_credits();
		if ( ! is_null( $remaining_credits ) ) {
			$field = self::get_prefixed_name( 'remaining_credits' );
			update_option( $field, $remaining_credits );
		}
		$paying_state = $compressor->get_paying_state();
		if ( ! is_null( $paying_state ) ) {
			$field = self::get_prefixed_name( 'paying_state' );
			update_option( $field, $paying_state );
		}
		$email_address = $compressor->get_email_address();
		if ( ! is_null( $email_address ) ) {
			$field = self::get_prefixed_name( 'email_address' );
			update_option( $field, $email_address );
		}
		if ( $compressor->limit_reached() ) {
			$encoded_email = str_replace( '%20', '%2B', rawurlencode( $email_address ) );
			$url = 'https://tinypng.com/dashboard/api?type=upgrade&mail=' . $encoded_email;
			$link = '<a href="' . $url . '" target="_blank">' .
				esc_html__( 'TinyPNG API account', 'tiny-compress-images' ) . '</a>';

			$this->notices->add('limit-reached',
				esc_html__(
					'You have reached your free limit this month.',
					'tiny-compress-images'
				) . ' ' .
				sprintf(
					/* translators: %s: link saying TinyPNG API account */
					esc_html__(
						'Upgrade your %s if you like to compress more images.',
						'tiny-compress-images'
					),
					$link
				)
			);
		} else {
			$this->notices->remove( 'limit-reached' );
		}
	}

	public function render_account_status() {
		$key = $this->get_api_key();
		if ( empty( $key ) ) {
			$compressor = $this->get_compressor();
			if ( $compressor->can_create_key() ) {
				include( dirname( __FILE__ ) . '/views/account-status-create-advanced.php' );
			} else {
				include( dirname( __FILE__ ) . '/views/account-status-create-simple.php' );
			}
		} else {
			$status = $this->compressor->get_status();
			$status->pending = false;
			if ( $status->ok ) {
				if ( $this->get_api_key_pending() ) {
					$this->clear_api_key_pending();
				}
			} else {
				if ( $this->get_api_key_pending() ) {
					$status->ok = true;
					$status->pending = true;
					$status->message = (
						'An email has been sent with a link to activate your account'
					);
				}
			}
			include( dirname( __FILE__ ) . '/views/account-status-connected.php' );
		}
	}

	public function render_pending_status() {
		$key = $this->get_api_key();
		if ( empty( $key ) ) {
			$compressor = $this->get_compressor();
			if ( $compressor->can_create_key() ) {
				include( dirname( __FILE__ ) . '/views/account-status-create-advanced.php' );
			} else {
				include( dirname( __FILE__ ) . '/views/account-status-create-simple.php' );
			}
		} else {
			include( dirname( __FILE__ ) . '/views/account-status-loading.php' );
		}
	}

	public function create_api_key() {
		$compressor = $this->get_compressor();
		if ( $compressor->can_create_key() ) {
			if ( ! isset( $_POST['name'] ) || ! $_POST['name'] ) {
				$status = (object) array(
					'ok' => false,
					'message' => __(
						'Please enter your name', 'tiny-compress-images'
					),
				);
				echo json_encode( $status );
				exit();
			}

			if ( ! isset( $_POST['email'] ) || ! $_POST['email'] ) {
				$status = (object) array(
					'ok' => false,
					'message' => __(
						'Please enter your email address', 'tiny-compress-images'
					),
				);
				echo json_encode( $status );
				exit();
			}

			try {
				$site = str_replace( array( 'http://', 'https://' ), '', get_bloginfo( 'url' ) );
				$identifier = 'WordPress plugin for ' . $site;
				$link = $this->get_absolute_url();
				$compressor->create_key( $_POST['email'], array(
					'name' => $_POST['name'],
					'identifier' => $identifier,
					'link' => $link,
				) );

				update_option( self::get_prefixed_name( 'api_key_pending' ), true );
				update_option( self::get_prefixed_name( 'api_key' ), $compressor->get_key() );
				update_option( self::get_prefixed_name( 'status' ), 0 );

				$status = (object) array(
					'ok' => true,
					'message' => null,
				);
			} catch ( Tiny_Exception $err ) {
				list( $message ) = explode( ' (HTTP', $err->getMessage(), 2 );
				$status = (object) array(
					'ok' => false,
					'message' => $message,
				);
			}
		} else {
			$status = (object) array(
				'ok' => false,
				'message' => 'This feature is not available on your platform',
			);
		}// End if().

		echo json_encode( $status );
		exit();
	}

	public function update_api_key() {
		$key = $_POST['key'];
		if ( empty( $key ) ) {
			/* Always save if key is blank, so the key can be deleted. */
			$status = (object) array(
				'ok' => true,
				'message' => null,
			);
		} else {
			$status = Tiny_Compress::create( $key )->get_status();
		}
		if ( $status->ok ) {
			update_option( self::get_prefixed_name( 'api_key_pending' ), false );
			update_option( self::get_prefixed_name( 'api_key' ), $key );
		}
		echo json_encode( $status );
		exit();
	}

	public static function wr2x_active() {
		return is_plugin_active( 'wp-retina-2x/wp-retina-2x.php' );
	}

	public function get_wr2x_option() {
		$setting = get_option( self::get_prefixed_name( 'sizes' ) );
		return array(
				'width' => null,
				'height' => null,
				'tinify' => ( isset( $setting['wr2x'] ) && 'on' === $setting['wr2x'] ),
			);
	}

	public function compress_wr2x_images() {
		$option = $this->get_wr2x_option();
		return self::wr2x_active() && $option['tinify'];
	}
}
