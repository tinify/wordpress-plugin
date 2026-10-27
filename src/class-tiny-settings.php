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

	public function __construct() {
		parent::__construct();
		$this->notices = new Tiny_Notices( $this );
		new Tiny_Diagnostics( $this );
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
			Tiny_Logger::error( $e->getMessage() );
		}
	}

	public function cli_init() {
		try {
			$this->init_compressor();
		} catch ( Tiny_Exception $e ) {
			Tiny_Logger::error( $e->getMessage() );
		}
	}

	public function ajax_init() {
		try {
			$this->init_compressor();
		} catch ( Tiny_Exception $e ) {
			Tiny_Logger::error( $e->getMessage() );
		}

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

	public function rest_init() {
		try {
			$this->init_compressor();
		} catch ( Tiny_Exception $e ) {
			Tiny_Logger::error( $e->getMessage() );
		}
	}

	public function admin_init() {
		try {
			$this->init_compressor();
		} catch ( Tiny_Exception $e ) {
			$this->notices->show(
				'compressor_exception',
				esc_html( $e->getMessage() ),
				'error',
				false
			);
		}

		if ( current_user_can( 'manage_options' ) && ! Tiny_Onboarding::is_onboarding_page() ) {
			$this->setup_incomplete_checks();
		}

		$field = self::get_prefixed_name( 'compression_timing' );
		register_setting( 'tinify', $field );

		$field = self::get_prefixed_name( 'sizes' );
		register_setting( 'tinify', $field );

		$field = self::get_prefixed_name( 'resize_original' );
		register_setting( 'tinify', $field );

		$field = self::get_prefixed_name( 'backup' );
		register_setting( 'tinify', $field );

		$field = self::get_prefixed_name( 'preserve_data' );
		register_setting( 'tinify', $field );

		$field = self::get_prefixed_name( 'convert_format' );
		register_setting( 'tinify', $field );

		$field = self::get_prefixed_name( 'logging_enabled' );
		register_setting( 'tinify', $field );
	}

	public function admin_menu() {
		/* Create link to new settings page from media settings page. */
		add_settings_section(
			'section_end',
			'',
			$this->get_method( 'render_settings_moved' ),
			'media'
		);

		add_options_page(
			__( 'TinyPNG - JPEG, PNG & WebP image compression', 'tiny-compress-images' ),
			esc_html__( 'TinyPNG', 'tiny-compress-images' ),
			'manage_options',
			'tinify',
			array( $this, 'add_options_to_page' )
		);
	}

	public function add_options_to_page() {
		include __DIR__ . '/views/settings.php';
	}

	public function image_sizes_notice() {
		check_ajax_referer( 'tiny-compress' );

		if ( current_user_can( 'manage_options' ) ) {
			$selected_sizes  = isset( $_GET['image_sizes_selected'] ) ?
				intval( $_GET['image_sizes_selected'] ) : 0;
			$resize_original = isset( $_GET['resize_original'] );
			$compress_wr2x   = isset( $_GET['compress_wr2x'] );
			$convert         = isset( $_GET['convert'] );

			echo '<div id="tiny-image-sizes-notice">';
			$this->render_size_checkboxes_description(
				$selected_sizes,
				$resize_original,
				$compress_wr2x,
				$convert
			);
			echo '</div><div id="tiny-credits-per-image">';
			$this->render_credits_per_image(
				self::get_credits_per_image(
					$selected_sizes,
					$resize_original,
					$compress_wr2x,
					$convert
				)
			);
			echo '</div>';
		}
		exit();
	}

	public function account_status() {
		if ( current_user_can( 'manage_options' ) ) {
			$this->render_account_status();
		}
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
		$required_functions          = array( 'curl_exec' );
		$disabled_required_functions = array();
		$disabled_functions          = explode( ',', ini_get( 'disable_functions' ) );

		foreach ( $required_functions as $required_function ) {
			if ( in_array( $required_function, $disabled_functions, true ) ) {
				array_push( $disabled_required_functions, $required_function );
			}
		}

		return $disabled_required_functions;
	}

	public static function get_api_key() {
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

	/**
	 * Whether the API key still waits for the user to confirm their email.
	 * Clears the pending flag once the key works.
	 *
	 * @return bool
	 */
	public function is_api_key_pending() {
		if ( ! $this->get_api_key_pending() ) {
			return false;
		}

		if ( $this->compressor->get_status()->ok ) {
			$this->clear_api_key_pending();
			return false;
		}

		return true;
	}

	protected function clear_api_key_pending() {
		delete_option( self::get_prefixed_name( 'api_key_pending' ) );
	}

	protected static function get_intermediate_size( $size ) {
		/*
		Inspired by
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

	/**
	 * Retrieves image sizes as a map of size and width, height and tinify meta data.
	 *
	 * The first entry will always be '0' (the original uploaded image), the second
	 * will always be 'original_unscaled' (the unscaled copy WordPress stores for images
	 * that exceed the big_image_size_threshold). Both entries share the same tinify setting.
	 *
	 * @return array<int|string, array{width: int|null, height: int|null, tinify: bool}>
	 */
	public function get_sizes() {
		if ( is_array( $this->sizes ) ) {
			return $this->sizes;
		}

		$setting = get_option( self::get_prefixed_name( 'sizes' ) );

		$size            = Tiny_Image::ORIGINAL;
		$original_tinify = ! is_array( $setting ) ||
			( isset( $setting[ $size ] ) && 'on' === $setting[ $size ] );
		$this->sizes     = array(
			$size                         => array(
				'width'  => null,
				'height' => null,
				'tinify' => $original_tinify,
			),
			Tiny_Image::ORIGINAL_UNSCALED => array(
				'width'  => null,
				'height' => null,
				'tinify' => $original_tinify,
			),
		);

		foreach ( get_intermediate_image_sizes() as $size ) {
			if ( self::DUMMY_SIZE === $size ) {
				continue;
			}

			list($width, $height) = self::get_intermediate_size( $size );
			if ( $width || $height ) {
				$this->sizes[ $size ] = array(
					'width'  => $width,
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

	/**
	 * Checks if backup is enabled.
	 * backup is enabled when original will be compressed and setting is on.
	 *
	 * @return bool true if backup is enabled
	 */
	public function get_backup_enabled() {
		$sizes = $this->get_sizes();
		if ( ! $sizes[ Tiny_Image::ORIGINAL ]['tinify'] ) {
			return false;
		}

		$setting = get_option( self::get_prefixed_name( 'backup' ) );
		return isset( $setting['enabled'] ) && 'on' === $setting['enabled'];
	}

	/**
	 * Wether or not the original is resized
	 *
	 * @return bool true if original file is resized
	 */
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
		return $this->get_compression_timing() === 'auto' ||
			$this->get_compression_timing() === 'background';
	}

	public function background_compress_enabled() {
		return $this->get_compression_timing() === 'background';
	}

	public function get_preserve_enabled( $name ) {
		$setting = get_option( self::get_prefixed_name( 'preserve_data' ) );
		return isset( $setting[ $name ] ) && 'on' === $setting[ $name ];
	}

	/**
	 * Retrieves the preserve options for the original image
	 *
	 * @param string $size_name Name of the size
	 * @return array<string>|false false if size is not original, otherwise array of preserved keys
	 */
	public function get_preserve_options( $size_name ) {
		if ( ! Tiny_Image::is_original( $size_name ) &&
			! Tiny_Image::is_original_unscaled( $size_name )
		) {
			return false;
		}
		$options  = array();
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
		$setting           = get_option( self::get_prefixed_name( 'resize_original' ) );
		$width             = intval( $setting['width'] );
		$height            = intval( $setting['height'] );
		$method            = $width > 0 && $height > 0 ? 'fit' : 'scale';
		$options['method'] = $method;
		if ( $width > 0 ) {
			$options['width'] = $width;
		}
		if ( $height > 0 ) {
			$options['height'] = $height;
		}
		return count( $options ) >= 2 ? $options : false;
	}

	/**
	 * Retrieves the configured settings for conversion.
	 *
	 * @return array{ convert: bool, convert_to: array{string} } The conversion options.
	 */
	public function get_conversion_options() {
		return array(
			'convert'    => $this->get_conversion_enabled(),
			'convert_to' => $this->get_convertto_mimetype(),
		);
	}

	/**
	 * Checks wether converting to optimized file format is enabled
	 *
	 * @return bool true if conversion is enabled, false otherwise
	 */
	public function get_conversion_enabled() {
		$conversion_enabled = self::get_convert_format_option( 'convert', 'off' );

		return 'on' === $conversion_enabled;
	}

	/**
	 * Retrieve the mimetypes to convert to
	 *
	 * @return array{string} mimetypes to convert to
	 */
	private function get_convertto_mimetype() {
		$convert_to = self::get_convert_format_option( 'convert_to', 'smallest' );
		if ( 'webp' == $convert_to ) {
			return array( 'image/webp' );
		}
		if ( 'avif' == $convert_to ) {
			return array( 'image/avif' );
		}
		return array( 'image/avif', 'image/webp' );
	}

	/**
	 * Retrieve the image delivery method.
	 *
	 * @return string The delivery method: 'picture' or 'htaccess'. Default is 'picture'.
	 */
	public function get_conversion_delivery_method() {
		return self::get_convert_format_option( 'delivery_method', 'picture' );
	}

	private function setup_incomplete_checks() {
		if ( ! $this->get_api_key() ) {
			$this->notices->api_key_missing_notice();
		} elseif ( $this->get_api_key_pending() ) {
			$this->notices->get_api_key_pending_notice();
		}
	}

	public function render_settings_moved() {
		echo '<div class="tinify-settings"><h3>';
		esc_html_e( 'TinyPNG - JPEG, PNG & WebP image compression', 'tiny-compress-images' );
		echo '</h3>';
		$url   = admin_url( 'options-general.php?page=tinify' );
		$link  = "<a href='" . $url . "'>";
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
			wp_kses(
				$link,
				array(
					'a' => array(
						'href' => array(),
					),
				)
			)
		);
		echo '</div>';
	}

	public function render_sizes() {
		$dummy_size_name = self::get_prefixed_name( 'sizes[' . self::DUMMY_SIZE . ']' );
		echo '<input type="hidden" name="' .
			esc_attr( $dummy_size_name ) . '" value="on"/>';

		foreach ( $this->get_sizes() as $size => $option ) {
			if ( Tiny_Image::is_original_unscaled( $size ) ) {
				// unscaled is selected implicitly by original size
				continue;
			}
			$this->render_size_checkboxes( $size, $option );
		}
		if ( self::wr2x_active() ) {
			$this->render_size_checkboxes( 'wr2x', $this->get_wr2x_option() );
		}
	}

	private function render_size_checkboxes( $size, $option ) {
		$id      = self::get_prefixed_name( "sizes_$size" );
		$name    = self::get_prefixed_name( 'sizes[' . $size . ']' );
		$checked = ! empty( $option['tinify'] );
		echo '<label class="tiny-option tiny-check">';
		echo '<input type="checkbox" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) .
			'" value="on"' . checked( $checked, true, false ) . '/>';
		echo '<span class="tiny-option-label">' .
			esc_html( self::get_size_label( $size, $option ) ) . '</span>';
		echo '</label>';
	}

	/**
	 * Names a size like "Thumbnail (150 × 150)", "Medium large (768 wide)" or,
	 * when the name is just its dimensions, "1536 × 1536".
	 */
	public static function get_size_label( $size, $option ) {
		if ( Tiny_Image::is_original( $size ) ) {
			return __(
				'Original image (replaced by the compressed version)',
				'tiny-compress-images'
			);
		}
		if ( Tiny_Image::is_retina( $size ) ) {
			return __( 'WP Retina 2x sizes', 'tiny-compress-images' );
		}

		$width  = intval( $option['width'] );
		$height = intval( $option['height'] );
		if ( ! $height ) {
			/* translators: %s: width in pixels */
			$dimensions = sprintf( __( '%s wide', 'tiny-compress-images' ), $width );
		} elseif ( ! $width ) {
			/* translators: %s: height in pixels */
			$dimensions = sprintf( __( '%s tall', 'tiny-compress-images' ), $height );
		} else {
			$dimensions = $width . ' × ' . $height;
		}

		if ( $size === $width . 'x' . $height ) {
			return $dimensions;
		}
		return ucfirst( str_replace( '_', ' ', $size ) ) . ' (' . $dimensions . ')';
	}

	/**
	 * Credits one new upload uses: one per size, one more when the original is
	 * resized, doubled for retina sizes and doubled again for WebP or AVIF versions.
	 */
	public static function get_credits_per_image(
		$active_sizes_count,
		$resize_original_enabled,
		$compress_wr2x,
		$conversion_enabled
	) {
		$credits = $active_sizes_count;
		if ( $resize_original_enabled ) {
			++$credits;
		}
		if ( $compress_wr2x ) {
			$credits *= 2;
		}
		if ( $conversion_enabled ) {
			$credits *= 2;
		}
		return $credits;
	}

	public function render_size_checkboxes_description(
		$active_sizes_count,
		$resize_original_enabled,
		$compress_wr2x,
		$conversion_enabled
	) {
		echo '<p>';
		if ( $active_sizes_count < 1 ) {
			esc_html_e(
				'With these settings no images will be compressed.',
				'tiny-compress-images'
			);
		} else {
			$credits = self::get_credits_per_image(
				$active_sizes_count,
				$resize_original_enabled,
				$compress_wr2x,
				$conversion_enabled
			);
			printf(
				esc_html(
					/* translators: %s: number of image sizes */
					_n(
						'%s size selected.',
						'%s sizes selected.',
						$active_sizes_count,
						'tiny-compress-images'
					)
				),
				esc_html( number_format_i18n( $active_sizes_count ) )
			);
			echo ' ';
			printf(
				esc_html(
					/* translators: %s: number of credits */
					_n(
						'Each new image uses %s credit.',
						'Each new image uses %s credits.',
						$credits,
						'tiny-compress-images'
					)
				),
				esc_html( number_format_i18n( $credits ) )
			);
		}
		echo '</p>';

		if ( self::wr2x_active() ) {
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
			echo '</p>';
		}
	}

	public function render_credits_per_image( $credits ) {
		echo '<p>';
		printf(
			esc_html(
				/* translators: %s: number of credits */
				_n(
					'With your settings, each new image uses %s credit.',
					'With your settings, each new image uses %s credits.',
					$credits,
					'tiny-compress-images'
				)
			),
			esc_html( number_format_i18n( $credits ) )
		);
		echo '</p>';
	}

	public function render_preserve_input( $name, $description ) {
		$data = array(
			'id'      => sprintf( self::get_prefixed_name( 'preserve_data_%s' ), $name ),
			'field'   => sprintf( self::get_prefixed_name( 'preserve_data[%s]' ), $name ),
			'checked' => $this->get_preserve_enabled( $name ),
			'label'   => $description,
		);
		include plugin_dir_path( __FILE__ ) . 'views/settings-original-image-preserve.php';
	}

	public function render_resize_input( $name ) {
		$settings = get_option( self::get_prefixed_name( 'resize_original' ) );
		$data     = array(
			'id'    => sprintf( self::get_prefixed_name( 'resize_original_%s' ), $name ),
			'field' => sprintf( self::get_prefixed_name( 'resize_original[%s]' ), $name ),
			'value' => isset( $settings[ $name ] ) ? $settings[ $name ] : '2048',
		);
		include plugin_dir_path( __FILE__ ) . 'views/settings-original-image-original.php';
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
			$this->notices->add_limit_reached_notice( $email_address );
		} else {
			$this->notices->remove( 'limit-reached' );
		}
	}

	public function render_account_status() {
		$key = $this->get_api_key();
		if ( ! empty( $key ) ) {
			$status          = $this->compressor->get_status();
			$status->pending = false;
			if ( $status->ok ) {
				if ( $this->get_api_key_pending() ) {
					$this->clear_api_key_pending();
				}
			} elseif ( $this->get_api_key_pending() ) {
					$status->ok      = true;
					$status->pending = true;
			}
			include __DIR__ . '/views/account-status-connected.php';
		}
	}

	/**
	 * Renders the form to create a Tinify account or to connect one with an API key.
	 */
	public function render_account_connect() {
		$can_create_key = $this->get_compressor()->can_create_key();
		$user           = wp_get_current_user();
		$name           = trim( $user->user_firstname . ' ' . $user->user_lastname );
		$email          = trim( $user->user_email );
		$has_api_key    = (bool) $this->get_api_key();
		include __DIR__ . '/views/account-status-connect.php';
	}

	/**
	 * Renders the switch for WebP or AVIF versions, with the format and the delivery to use.
	 * Server rules are recommended, so they are selected when Apache can rewrite and
	 * no delivery was chosen yet.
	 */
	public function render_modern_formats() {
		$convert_name = self::get_prefixed_name( 'convert_format' );
		$convert      = $this->get_conversion_enabled();
		$convert_to   = self::get_convert_format_option( 'convert_to', 'smallest' );
		$server_rules = self::can_render_delivery_method();
		$delivery     = self::get_convert_format_option(
			'delivery_method',
			$server_rules ? 'htaccess' : 'picture'
		);
		include __DIR__ . '/views/settings-modern-formats.php';
	}

	public function render_pending_status() {
		$key = $this->get_api_key();
		if ( ! empty( $key ) ) {
			include __DIR__ . '/views/account-status-loading.php';
		}
	}

	public function create_api_key() {
		check_ajax_referer( 'tiny-compress', '_nonce' );

		$compressor = $this->get_compressor();
		if ( ! current_user_can( 'manage_options' ) ) {
			$status = (object) array(
				'ok'      => false,
				'message' => 'This feature requires certain user capabilities',
			);
		} elseif ( $compressor->can_create_key() ) {
			if ( empty( $_POST['name'] ) ) {
				$status = (object) array(
					'ok'      => false,
					'message' => __(
						'Please enter your name',
						'tiny-compress-images'
					),
				);
				echo json_encode( $status );
				exit();
			}

			if ( empty( $_POST['email'] ) ) {
				$status = (object) array(
					'ok'      => false,
					'message' => __(
						'Please enter your email address',
						'tiny-compress-images'
					),
				);
				echo json_encode( $status );
				exit();
			}

			try {
				$name       = sanitize_text_field( wp_unslash( $_POST['name'] ) );
				$email      = sanitize_email( wp_unslash( $_POST['email'] ) );
				$site       = str_replace(
					array( 'http://', 'https://' ),
					'',
					get_bloginfo( 'url' )
				);
				$identifier = 'WordPress plugin for ' . $site;
				$link       = $this->get_absolute_url();
				$options    = array(
					'name'       => $name,
					'identifier' => $identifier,
				);
				$compressor->create_key( $email, $options );

				update_option( self::get_prefixed_name( 'api_key_pending' ), true );
				update_option( self::get_prefixed_name( 'api_key' ), $compressor->get_key() );
				update_option( self::get_prefixed_name( 'status' ), 0 );
				update_option( self::get_prefixed_name( 'email_address' ), $email );

				$status = (object) array(
					'ok'      => true,
					'message' => null,
				);
			} catch ( Tiny_Exception $err ) {
				list($message) = explode( ' (HTTP', $err->getMessage(), 2 );
				$status        = (object) array(
					'ok'      => false,
					'message' => $message,
				);
			}
		} else {
			$status = (object) array(
				'ok'      => false,
				'message' => 'This feature is not available on your platform',
			);
		} // End if().

		echo json_encode( $status );
		exit();
	}

	public function update_api_key() {
		check_ajax_referer( 'tiny-compress', '_nonce' );

		$key = null;
		if ( ! current_user_can( 'manage_options' ) ) {
			$status = (object) array(
				'ok'      => false,
				'message' => 'This feature requires certain user capabilities',
			);
		} elseif ( empty( $_POST['key'] ) ) {
			/* Always save if key is blank, so the key can be deleted. */
			$status = (object) array(
				'ok'      => true,
				'message' => null,
			);
		} else {
			$key    = sanitize_text_field( wp_unslash( $_POST['key'] ) );
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
		return function_exists( 'wr2x_get_retina' );
	}

	public function get_wr2x_option() {
		$setting = get_option( self::get_prefixed_name( 'sizes' ) );
		return array(
			'width'  => null,
			'height' => null,
			'tinify' => ( isset( $setting['wr2x'] ) && 'on' === $setting['wr2x'] ),
		);
	}

	/**
	 * Wether or not wr2x is active
	 *
	 * @return bool true if active
	 */
	public function compress_wr2x_images() {
		$option = $this->get_wr2x_option();
		return self::wr2x_active() && $option['tinify'];
	}


	/**
	 * @return bool true if apache with mod_rewrite loaded
	 */
	public static function can_render_delivery_method() {
		global $is_apache;
		if ( ! $is_apache ) {
			return false;
		}

		if (
			! function_exists( 'apache_mod_loaded' ) ||
			! function_exists( 'apache_get_modules' )
		) {
			return false;
		}

		$modules = apache_get_modules();
		return in_array( 'mod_rewrite', $modules, true );
	}

	private static function get_convert_format_option( $option, $default_value ) {
		$setting = get_option( self::get_prefixed_name( 'convert_format' ) );
		if ( isset( $setting[ $option ] ) && $setting[ $option ] ) {
			return $setting[ $option ];
		}
		return $default_value;
	}
}
