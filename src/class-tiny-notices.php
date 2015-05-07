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

class Tiny_Notices extends Tiny_WP_Base {

    public function admin_init() {
        add_action('wp_ajax_tiny_dismiss_notice', $this->get_method('dismiss'));
        if (current_user_can('manage_options')) {
            $this->show_notices();
        }
    }

    private function get_option_key() {
        return self::get_prefixed_name('admin_notices');
    }

    private function get_notices() {
        $option = get_option(self::get_option_key());
        return is_array($option) ? $option : array();
    }

    private function get_user_meta_key() {
        return self::get_prefixed_name('admin_notice_dismissals');
    }

    private function get_dismissals() {
        $meta = get_user_meta($this->get_user_id(), $this->get_user_meta_key(), true);
        return is_array($meta) ? $meta : array();
    }

    private function show_notices() {
        $dismissals = $this->get_dismissals();
        foreach ($this->get_notices() as $name => $message) {
            if (empty($dismissals[$name])) {
                $this->show($name, $message);
            }
        }
    }

    public function add($name, $message) {
        $notices = $this->get_notices();
        $notices[$name] = $message;
        update_option(self::get_option_key(), $notices);
    }

    public function remove($name) {
        $notices = get_option(self::get_option_key());
        unset($notices[$name]);

        if (count($notices) > 0) {
            update_option(self::get_option_key(), $notices);
        } else {
            delete_option(self::get_option_key());
        }
    }

    public function dismiss() {
        if (empty($_POST['name'])) {
            echo json_encode(false);
            exit();
        }
        $dismissals = $this->get_dismissals();
        $dismissals[$_POST['name']] = true;
        update_user_meta($this->get_user_id(), $this->get_user_meta_key() , $dismissals);
        echo json_encode(true);
        exit();
    }

    public function show($name, $message, $dismissable=true) {
        $link = $dismissable ? "&nbsp;<a href=\"#\" data-name=\"$name\" class=\"tiny-dismiss\">" . self::translate_escape('Dismiss') . '</a>' : '';
        add_action('admin_notices', create_function('', "echo '<div class=\"updated\"><p>Compress JPEG & PNG images: $message$link</p></div>';"));
    }
}
