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
    private $filenames;
    private $urls;

    public function __construct($id, $wp_metadata=null) {
        $this->id = $id;

        if (is_null($wp_metadata)) {
            $wp_metadata = wp_get_attachment_metadata($id);
        }
        $this->parse_wp_metadata($wp_metadata);
        $this->values = get_post_meta($id, self::META_KEY, true);
        if (!is_array($this->values)) {
            $this->values = array();
        }
    }

    private function parse_wp_metadata($wp_metadata) {
        $this->filenames = array();
        $this->urls = array();
        if (!is_array($wp_metadata)) {
            return;
        }

        $path_info = pathinfo($wp_metadata['file']);
        $upload_dir = wp_upload_dir();
        $path_prefix = $upload_dir['basedir'] . '/';
        $url_prefix = $upload_dir['baseurl'] . '/';
        if (isset($path_info['dirname'])) {
            $path_prefix .= $path_info['dirname'] .'/';
            $url_prefix .= $path_info['dirname'] .'/';
        }

        $this->filenames[self::ORIGINAL] = "$path_prefix${path_info['basename']}";
        $this->urls[self::ORIGINAL] = "$url_prefix${path_info['basename']}";
        if (isset($wp_metadata['sizes']) && is_array($wp_metadata['sizes'])) {
            foreach ($wp_metadata['sizes'] as $size => $info) {
                $this->filenames[$size] = "$path_prefix${info['file']}";
                $this->urls[$size] = "$url_prefix${info['file']}";
            }
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

    public function get_id() {
        return $this->id;
    }

    public function get_filename($size=self::ORIGINAL) {
        return isset($this->filenames[$size]) ? $this->filenames[$size] : null;
    }

    public function get_url($size=self::ORIGINAL) {
        return isset($this->urls[$size]) ? $this->urls[$size] : null;
    }

    public function get_value($size=self::ORIGINAL) {
        return isset($this->values[$size]) ? $this->values[$size] : null;
    }

    public function is_compressed($size=self::ORIGINAL) {
        $filename = $this->get_filename($size);
        return isset($this->values[$size]) && isset($this->values[$size]['output'])
            && file_exists($filename) && filesize($filename) == $this->values[$size]['output']['size'];
    }

    public function get_sizes() {
        return array_keys($this->filenames);
    }

    public function get_success_sizes() {
        return array_filter(array_keys($this->values), array($this, 'is_compressed'));
    }

    public function get_missing_sizes($tinify_sizes) {
        $sizes = array_intersect($this->get_sizes(), $tinify_sizes);
        return array_diff($sizes, $this->get_success_sizes());
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
