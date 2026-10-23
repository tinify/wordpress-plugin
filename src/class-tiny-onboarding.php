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

/**
 * Class responsible for onboarding a new user
 */
class Tiny_Onboarding extends Tiny_WP_Base {

	/**
	 * Prefix of every onboarding step page slug.
	 *
	 * @var string
	 */
	const PAGE_SLUG = 'tiny-onboarding';

	/**
	* @var string
	*/
	private $page_title;

	/**
	 * Tiny settings
	 *
	 * @var Tiny_Settings
	 */
	private $settings;

	/**
	 * @param Tiny_Settings $settings
	 */
	public function __construct( $settings ) {
		parent::__construct();
		$this->settings = $settings;
	}

	public function admin_init() {
		add_action(
			'admin_post_tiny_onboarding_save_settings',
			array( $this, 'save_settings' )
		);

		if ( $this->is_onboarded() ) {
			return;
		}

		$this->set_is_onboarded( 1 );

		if ( self::is_bulk_activation() ) {
			return;
		}
	}

	/**
	 * Onboarding is skipped when activating multiple plugins
	 *
	 * @return bool
	 */
	private static function is_bulk_activation() {
		return filter_has_var( INPUT_GET, 'activate-multi' );
	}

	public function admin_menu() {
		$this->page_title = __( 'Welcome to TinyPNG', 'tiny-compress-images' );

		$hook = add_submenu_page(
			'options-general.php',
			$this->page_title,
			$this->page_title,
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_step' )
		);

		if ( ! $hook ) {
			return;
		}

		remove_submenu_page( 'options-general.php', self::PAGE_SLUG );

		/**
		 * because title is retrieved from submenu, which is not part of the menu,
		 * resolve it through globals
		 */
		add_action( 'load-' . $hook, array( $this, 'set_page_title' ) );
	}

	/**
	 * Renders the step from the `step` query arg, or step 1 when it is not a known step.
	 */
	public function render_step() {
		$step = filter_input( INPUT_GET, 'step', FILTER_VALIDATE_INT );
		if ( ! in_array( $step, array( 1, 2, 3 ), true ) ) {
			$step = 1;
		}
		include __DIR__ . '/views/onboarding-' . $step . '.php';
	}

	/**
	 * Supplies the title of a page that is not listed in the menu.
	 *
	 * Hooked to `load-{$hook}` of every onboarding step.
	 */
	public function set_page_title() {
		$GLOBALS['title'] = $this->page_title;
	}

	/**
	 * Checks wether user is onboarded
	 * defaults to true
	 *
	 * @return boolean true if onboarded
	 */
	private function is_onboarded() {
		$onboarding_status_field = self::get_prefixed_name( 'onboarding_status' );
		return 1 === (int) get_option( $onboarding_status_field, 1 );
	}

	/**
	 * Whether the current admin request is the onboarding page.
	 *
	 * @return bool
	 */
	public static function is_onboarding_page() {
		return isset( $GLOBALS['plugin_page'] ) && self::PAGE_SLUG === $GLOBALS['plugin_page'];
	}

	/**
	 * Retrieves the url of the given onboarding step
	 *
	 * @param int $step
	 * @return string
	 */
	public function get_step_url( $step ) {
		return admin_url( 'options-general.php?page=' . self::PAGE_SLUG . '&step=' . $step );
	}

	/**
	 * Sets the onboarding status
	 *
	 * @param int $is_onboarded status
	 */
	public static function set_is_onboarded( $is_onboarded ) {
		$onboarding_status_field = self::get_prefixed_name( 'onboarding_status' );
		return update_option( $onboarding_status_field, $is_onboarded );
	}

	/**
	 * Renders the onboarding progress with the given step as current.
	 *
	 * @param int  $current_step
	 * @param bool $confirm_email false when connecting with an API key skipped that step
	 */
	public function render_steps( $current_step, $confirm_email = true ) {
		include __DIR__ . '/views/onboarding-steps.php';
	}

	/**
	 * Saves settings from onboading page,
	 * redirects to bulk optimizer.
	 *
	 * Hooked to `admin_post_tiny_onboarding_save_settings`.
	 */
	public function save_settings() {
		check_admin_referer( 'tiny-onboarding-save-settings' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die(
				esc_html__(
					'Sorry, you are not allowed to manage options for this site.',
					'tiny-compress-images'
				)
			);
		}

		$convert_to = isset( $_POST['tinypng_convert_format']['convert_to'] ) ?
			sanitize_key( wp_unslash( $_POST['tinypng_convert_format']['convert_to'] ) ) : '';
		if ( ! in_array( $convert_to, array( 'smallest', 'webp', 'avif' ), true ) ) {
			$convert_to = 'smallest';
		}

		$delivery_method = isset( $_POST['tinypng_convert_format']['delivery_method'] ) ?
			sanitize_key( wp_unslash( $_POST['tinypng_convert_format']['delivery_method'] ) ) : '';
		if ( ! in_array( $delivery_method, array( 'htaccess', 'picture' ), true ) ) {
			$delivery_method = 'picture';
		}

		$convert_format = array( 'convert' => 'off' );
		if ( isset( $_POST['tinypng_convert_format']['convert'] ) ) {
			$convert_format = array(
				'convert'         => 'on',
				'convert_to'      => $convert_to,
				'delivery_method' => $delivery_method,
			);
		}

		update_option(
			self::get_prefixed_name( 'compression_timing' ),
			isset( $_POST['auto_compress'] ) ? 'background' : 'manual'
		);
		update_option( self::get_prefixed_name( 'convert_format' ), $convert_format );

		$next = isset( $_POST['next'] ) ? sanitize_key( wp_unslash( $_POST['next'] ) ) : '';
		if ( 'settings' === $next ) {
			wp_safe_redirect( admin_url( 'options-general.php?page=tinify' ) );
		} else {
			wp_safe_redirect( admin_url( 'upload.php?page=tiny-bulk-optimization' ) );
		}
		exit();
	}

	/**
	 * Decides on activation whether this site still needs onboarding.
	 *
	 * A site that already has a key, or that has compressed before, keeps the
	 * default onboarded state so it is never sent through onboarding again.
	 */
	public static function on_activate() {
		$api_key           = get_option( self::get_prefixed_name( 'api_key' ) );
		$compression_count = get_option( self::get_prefixed_name( 'status' ) );

		if ( empty( $api_key ) && empty( $compression_count ) ) {
			self::set_is_onboarded( 0 );
		}
	}
}
