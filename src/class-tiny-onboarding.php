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

	function admin_init() {
		if ( $this->is_onboarded() ) {
			return;
		}

		$this->set_is_onboarded( 1 );

		if ( self::is_bulk_activation() ) {
			return;
		}

		wp_safe_redirect( $this->get_step_url( 1 ) );
		exit();
	}

	/**
	 * Onboarding is skipped when activating multiple plugins
	 *
	 * @return bool
	 */
	private static function is_bulk_activation() {
		return filter_has_var( INPUT_GET, 'activate-multi' );
	}

	function admin_menu() {
		$this->page_title = __( 'Welcome to TinyPNG', 'tiny-compress-images' );

		foreach ( $this->steps as $step ) {
			$slug = $this->get_step_slug( $step );

			$hook = add_submenu_page(
				'options-general.php',
				$this->page_title,
				$this->page_title,
				'manage_options',
				$slug,
				function () use ( $step ) {
					include __DIR__ . '/views/onboarding-' . $step . '.php';
				}
			);

			if ( ! $hook ) {
				continue;
			}

			remove_submenu_page( 'options-general.php', $slug );

			/**
			 * because title is retrieved from submenu, which is not part of the menu,
			 * resolve it through globals
			 */
			add_action( 'load-' . $hook, $this->get_method( 'set_page_title' ) );
		}
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
	function is_onboarded() {
		$onboarding_status_field = self::get_prefixed_name( 'onboarding_status' );
		return 1 === (int) get_option( $onboarding_status_field, 1 );
	}

	/**
	 * Returns the page slug of the given onboarding step
	 *
	 * @param int $step
	 * @return string
	 */
	private function get_step_slug( $step ) {
		return self::PAGE_SLUG . '-' . $step;
	}

	/**
	 * Whether the current admin request is one of the onboarding steps.
	 *
	 * Reads the page WordPress itself resolved, so no request input is touched.
	 *
	 * @return bool
	 */
	public static function is_onboarding_page() {
		$page = isset( $GLOBALS['plugin_page'] ) ? $GLOBALS['plugin_page'] : '';

		return 0 === strpos( $page, self::PAGE_SLUG . '-' );
	}

	/**
	 * Retrieves the url of the given onboarding step
	 *
	 * @param int $step
	 * @return string
	 */
	public function get_step_url( $step ) {
		return admin_url( 'options-general.php?page=' . $this->get_step_slug( $step ) );
	}

	/**
	 * Sets the onboarding status
	 *
	 * @param int $is_onboarded status
	 */
	static function set_is_onboarded( $is_onboarded ) {
		$onboarding_status_field = self::get_prefixed_name( 'onboarding_status' );
		return update_option( $onboarding_status_field, $is_onboarded );
	}

	function render_register() {
		$compressor = $this->settings->get_compressor();
		if ( $compressor->can_create_key() ) {
			include __DIR__ . '/views/account-status-create-advanced.php';
		} else {
			include __DIR__ . '/views/account-status-create-simple.php';
		}
	}

	/**
	 * Decides on activation whether this site still needs onboarding.
	 *
	 * A site that already has a key, or that has compressed before, keeps the
	 * default onboarded state so it is never sent through onboarding again.
	 */
	static function on_activate() {
		$api_key           = get_option( self::get_prefixed_name( 'api_key' ) );
		$compression_count = get_option( self::get_prefixed_name( 'status' ) );

		if ( empty( $api_key ) && empty( $compression_count ) ) {
			self::set_is_onboarded( 0 );
		}
	}
}
