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

class Tiny_Conversion extends Tiny_WP_Base
{
    /**
     * @var Tiny_Settings
     */
    private $settings;

    /**
     * @param $settings Tiny_Settings
     */
    public function __construct($settings)
    {
        return parent::__construct();
        $this->$settings = $settings;
    }

    /**
     * Invoked in init hook from Tiny_WP_Base
     */
    public function init()
    {
        if ($this->settings->get_conversion_enabled()) {
            return;
        }

        $this->enable_conversion();
    }

    /**
     * Will check wether conversion needs to be enabled
     * and which form of delivery should be applied.
     * 
     * @return void
     */
    function enable_conversion()
    {
        $delivery_method = $this->settings->get_conversion_delivery_method();
        
        /**
         * Controls wether the page should replace <img> with <picture> elements
         * converted sources.
         *
         * @since 3.7.0
         */
        if ($delivery_method === 'picture' && apply_filters('tiny_replace_with_picture', true)) {
            new Tiny_Picture(ABSPATH, array(get_site_url()));
        } else {
            new Tiny_Apache_Rewrite();
        }
    }
}
