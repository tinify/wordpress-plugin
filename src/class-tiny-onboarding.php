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
        // $this->set_is_onboarded( 0 );
        if ( $this->is_new_user() && !$this->is_onboarded() ) {
            // if user is new and not onboarded
            $this->set_is_onboarded( 1 );
            wp_safe_redirect( admin_url( 'options-general.php?page=' . $this->onboarding_url ) );
            exit();
        }
    }

    function admin_menu() {
        add_submenu_page(
            null,
            'Onboarding TinyPNG',
            'Welcome to TinyPNG',
            'manage_options',
            $this->onboarding_url,
            array( $this, 'render_onboarding_page'),
        );
    }
    
    function render_onboarding_page() {
        include __DIR__ . '/views/onboarding-1.php';
    }

	/**
	 * Checks if user is 'new'
	 *
	 * @return bool true if user has no compressions or api key
	 */
    function is_new_user() {
        $api_key = $this->settings->get_api_key();
        $compression_count = $this->settings->get_compression_count();
        return empty( $api_key ) && empty($compression_count);
    }

    function is_onboarded() {
        $onboarding_status_field = self::get_prefixed_name( 'onboarding_status' );
        return get_option( $onboarding_status_field );
    }

	/**
	 * Sets the onboarding status
	 *
     * @param int $is_onboarded status
	 */
    function set_is_onboarded( $is_onboarded ) {
        $onboarding_status_field = self::get_prefixed_name( 'onboarding_status' );
        return update_option( $onboarding_status_field, $is_onboarded );
    }
}