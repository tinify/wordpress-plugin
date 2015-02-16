<?php
/*
* Tiny Compress Images - WordPress plugin.
* Copyright (C) 2015 Voormedia B.V.
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

abstract class Tiny_WP_Base {
    const NAME = 'tiny-compress-images';

    public static function plugin_version() {
        $plugin_data = get_plugin_data(dirname(__FILE__) . '/../tiny-compress-images.php');
        return $plugin_data['Version'];
    }

    public static function plugin_identification() {
        return 'Wordpress/' . $GLOBALS['wp_version'] . ' Tiny/' . self::plugin_version();
    }

    protected static function translate($phrase) {
        return translate($phrase, self::NAME);
    }

    protected static function translate_escape($phrase) {
        return htmlspecialchars(translate($phrase, self::NAME));
    }

    public function __construct() {
        add_action('init', $this->get_method('init'));
        add_action('admin_init', $this->get_method('admin_init'));
    }

    protected function get_method($name) {
        return array($this, $name);
    }

    protected function get_static_method($name) {
        return array(get_class($this), $name);
    }

    public function init() {
    }

    public function admin_init() {
    }

    public function add_admin_notice($message) {
        add_action('admin_notices', create_function('', "echo '<div class=\"error\"><p>Tiny Compress Images: $message</p></div>';"));
    }
}
