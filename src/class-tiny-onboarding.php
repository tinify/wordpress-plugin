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

	private $onboarding_url = 'tiny-onboarding';
	private $steps          = array( 1, 2 );

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
		wp_safe_redirect( $this->get_step_url( 1 ) );
		exit();
	}

	function admin_menu() {
		$title = __( 'Welcome to TinyPNG', 'tiny-compress-images' );

		foreach ( $this->steps as $step ) {
			$slug = $this->get_step_slug( $step );

			$hook = add_submenu_page(
				'options-general.php',
				$title,
				$title,
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
		}
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
		return $this->onboarding_url . '-' . $step;
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
