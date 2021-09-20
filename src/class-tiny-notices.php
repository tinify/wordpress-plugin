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

class Tiny_Notices extends Tiny_WP_Base {
	private $notices;
	private $dismissals;

	protected static $incompatible_plugins = array(
		'CheetahO Image Optimizer' => 'cheetaho-image-optimizer/cheetaho.php',
		'EWWW Image Optimizer' => 'ewww-image-optimizer/ewww-image-optimizer.php',
		'Imagify' => 'imagify/imagify.php',
		'Kraken Image Optimizer' => 'kraken-image-optimizer/kraken.php',
		'ShortPixel Image Optimizer' => 'shortpixel-image-optimiser/wp-shortpixel.php',
		'WP Smush' => 'wp-smushit/wp-smush.php',
		'WP Smush Pro' => 'wp-smush-pro/wp-smush.php',
	);

	private static function get_option_key() {
		return self::get_prefixed_name( 'admin_notices' );
	}

	private static function get_user_meta_key() {
		return self::get_prefixed_name( 'admin_notice_dismissals' );
	}

	public function ajax_init() {
		add_action( 'wp_ajax_tiny_dismiss_notice', $this->get_method( 'dismiss' ) );
	}

	public function admin_init() {
		if ( current_user_can( 'manage_options' ) ) {
			$this->show_stored();
			$this->show_notices();
		}
	}

	private function load_notices() {
		if ( is_array( $this->notices ) ) {
			return;
		}
		$option = get_option( self::get_option_key() );
		$this->notices = is_array( $option ) ? $option : array();
	}

	private function save_notices() {
		update_option( self::get_option_key(), $this->notices );
	}

	private function load() {
		$this->load_notices();
		$this->load_dismissals();
	}

	private function load_dismissals() {
		if ( is_array( $this->dismissals ) ) {
			return;
		}

		$meta = get_user_meta(
			$this->get_user_id(),
			$this->get_user_meta_key(),
			true
		);

		$this->dismissals = is_array( $meta ) ? $meta : array();
	}

	private function save_dismissals() {
		update_user_meta(
			$this->get_user_id(),
			$this->get_user_meta_key(),
			$this->dismissals
		);
	}

	private function show_stored() {
		$this->load();
		foreach ( $this->notices as $name => $message ) {
			if ( empty( $this->dismissals[ $name ] ) ) {
				$this->show( $name, $message );
			}
		}
	}

	public function show_notices() {
		$this->incompatible_plugins_notice();
		$this->outdated_platform_notice();
	}

	public function add( $name, $message ) {
		$this->load_notices();
		$this->notices[ $name ] = $message;
		$this->save_notices();
	}

	public function remove( $name ) {
		$this->load();
		if ( isset( $this->notices[ $name ] ) ) {
			unset( $this->notices[ $name ] );
			$this->save_notices();
		}
		if ( isset( $this->dismissals[ $name ] ) ) {
			unset( $this->dismissals[ $name ] );
			$this->save_dismissals();
		}
	}

	public function dismiss() {
		if ( empty( $_POST['name'] ) || ! $this->check_ajax_referer() ) {
			echo json_encode( false );
			exit();
		}
		$this->load_dismissals();
		$this->dismissals[ $_POST['name'] ] = true;
		$this->save_dismissals();
		echo json_encode( true );
		exit();
	}

	public function show( $name, $message, $klass = 'error', $dismissible = true ) {
		$css = array( $klass, 'notice', 'tiny-notice' );
		if ( ! $dismissible ) {
			$add = '</p>';
		} elseif ( self::check_wp_version( 4.2 ) ) {
			$add = '</p>';
			$css[] = 'is-dismissible';
		} else {
			$add = '&nbsp;<a href="#" class="tiny-dismiss">' .
				esc_html__( 'Dismiss', 'tiny-compress-images' ) . '</a></p>';
		}

		$css = implode( ' ', $css );
		$plugin_name = esc_html__( 'TinyPNG - JPEG, PNG & WebP image compression', 'tiny-compress-images' );

		add_action( 'admin_notices',
			function() use ( $css, $name, $plugin_name, $message, $add ) {
				echo '<div class="' . $css . '" data-name="' . $name . '"><p>' .
					$plugin_name . ': ' . $message . $add . '</div>';
			}
		);
	}

	public function api_key_missing_notice() {
		$notice_class = 'error';
		$notice = esc_html__(
			'Please register or provide an API key to start compressing images.',
			'tiny-compress-images'
		);
		$link = sprintf(
			'<a href="options-general.php?page=tinify">%s</a>', $notice
		);
		$this->show( 'setting', $link, $notice_class, false );
	}

	public function get_api_key_pending_notice() {
		$notice_class = 'notice-warning';
		$notice = esc_html__(
			'Please activate your account to start compressing images.',
			'tiny-compress-images'
		);
		$link = sprintf(
			'<a href="options-general.php?page=tinify">%s</a>', $notice
		);
		$this->show( 'setting', $link, $notice_class, false );
	}

	public function add_limit_reached_notice( $email ) {
		$encoded_email = str_replace( '%20', '%2B', rawurlencode( $email ) );
		$url = 'https://tinypng.com/dashboard/api?type=upgrade&mail=' . $encoded_email;
		$link = '<a href="' . $url . '" target="_blank">' .
			esc_html__( 'TinyPNG API account', 'tiny-compress-images' ) . '</a>';

		$this->add('limit-reached',
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
	}

	public function outdated_platform_notice() {
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
			$this->show( 'deprecated', $message, 'notice-warning', false );
		} // End if().
	}

	public function show_offload_s3_notice() {
		$message = esc_html__(
			'Removing files from the server is incompatible with background compressions. Images will still be automatically compressed, but no longer in the background.',  // WPCS: Needed for proper translation.
			'tiny-compress-images'
		);
		$this->show( 'offload-s3', $message, 'notice-error', false );
	}

	public function old_offload_s3_version_notice() {
		$message = esc_html__(
			'Background compressions are not compatible with the version of WP Offload S3 you have installed. Please update to version 0.7.2 at least.',  // WPCS: Needed for proper translation.
			'tiny-compress-images'
		);
		$this->show( 'old-offload-s3-version', $message, 'notice-error', false );
	}

	public function incompatible_plugins_notice() {
		$incompatible_plugins = array_filter( self::$incompatible_plugins, 'is_plugin_active' );
		if ( count( $incompatible_plugins ) > 0 ) {
			$this->show_incompatible_plugins( $incompatible_plugins );
		}
	}

	private function show_incompatible_plugins( $incompatible_plugins ) {
		$notice = '<div class="error notice tiny-notice incompatible-plugins">';
		$notice .= '<h3>';
		$notice .= esc_html__( 'TinyPNG - JPEG, PNG & WebP image compression', 'tiny-compress-images' );
		$notice .= '</h3>';
		$notice .= '<p>';
		$notice .= esc_html__(
			'You have activated multiple image optimization plugins. This may lead to unexpected results. The following plugins were detected:', // WPCS: Needed for proper translation.
			'tiny-compress-images'
		);
		$notice .= '</p>';
		$notice .= '<table>';
		$notice .= '<tr><td class="bullet">•</td><td class="name">';
		$notice .= esc_html__( 'TinyPNG - JPEG, PNG & WebP image compression', 'tiny-compress-images' );
		$notice .= '</td><td></td></tr>';
		foreach ( $incompatible_plugins as $name => $file ) {
			$notice .= '<tr><td class="bullet">•</td><td class="name">';
			$notice .= $name;
			$notice .= '</td><td>';
			$nonce = wp_create_nonce( 'deactivate-plugin_' . $file );
			$query_string = 'action=deactivate&plugin=' . $file . '&_wpnonce=' . $nonce;
			$url = admin_url( 'plugins.php?' . $query_string );
			$notice .= '<a class="button button-primary" href="' . $url . '">';
			$notice .= esc_html__( 'Deactivate' );
			$notice .= '</a></td></tr>';
		}
		$notice .= '</table>';
		$notice .= '</div>';

		add_action( 'admin_notices',
			function() use ( $notice ) {
				echo $notice;
			}
		);
	}
}
