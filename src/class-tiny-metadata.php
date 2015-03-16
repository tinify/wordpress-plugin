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

class Tiny_Metadata {
    const META_KEY = 'tiny_compress_images';
    const ORIGINAL = 0;

    private $id;
    private $values;

    public function __construct($id) {
        $this->id = $id;
        $this->values = get_post_meta($id, self::META_KEY, true);
        if (!is_array($this->values)) {
            $this->values = array();
        }
    }

    public function update() {
        update_post_meta($this->id, self::META_KEY, $this->values);
    }

    public function add_response($response, $size=self::ORIGINAL) {
        $this->values[$size] = array(
            'input'  => array('size' => $response['input']['size']),
            'output' => array('size' => $response['output']['size']),
            'timestamp' => time()
        );
    }

    public function add_exception($exception, $size=self::ORIGINAL) {
        $this->values[$size] = array(
            'error'   => $exception->get_error(),
            'message' => $exception->getMessage(),
            'timestamp' => time()
        );
    }

    public function get_value($size=self::ORIGINAL) {
        return isset($this->values[$size]) ? $this->values[$size] : null;
    }

    public function is_compressed($size=self::ORIGINAL) {
        return isset($this->values[$size]) && isset($this->values[$size]['output']);
    }

    public function get_missing_sizes($sizes) {
        return array_diff($sizes, array_filter(array_keys($this->values), array($this, 'is_compressed')));
    }

    public function get_latest_error() {
        $last_time = null;
        $message = null;
        foreach ($this->values as $key => $details) {
            if (isset($details['error']) && isset($details['message']) && ($last_time === null || $last_time < $details['timestamp'])) {
                $last_time = $details['timestamp'];
                $message = $details['message'];
            }
        }
        return $message;
    }

    public function get_savings() {
        $result = array(
            'input' => 0,
            'output' => 0,
            'count' => 0
        );
        foreach ($this->values as $key => $details) {
            if (isset($details['input']) && isset($details['output'])) {
                $result['count']++;
                $result['input'] += $details['input']['size'];
                $result['output'] += $details['output']['size'];
            }
        }
        return $result;
    }
}
