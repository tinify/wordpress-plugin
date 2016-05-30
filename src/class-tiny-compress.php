<?php
/*
* Tiny Compress Images - WordPress plugin.
* Copyright (C) 2015-2016 Voormedia B.V.
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

abstract class Tiny_Compress {
    protected $after_compress_callback;

    public static function get_compressor($api_key, $after_compress_callback=null) {
        if (Tiny_PHP::client_library_supported()) {
            $class = "Tiny_Compress_Client";
        } elseif (Tiny_PHP::fopen_available()) {
            $class = "Tiny_Compress_Fopen";
        } else {
            throw new Tiny_Exception('No HTTP client is available (cURL or fopen)', 'NoHttpClient');
        }
        return new $class($api_key, $after_compress_callback);
    }

    protected function __construct($after_compress_callback) {
        $this->after_compress_callback = $after_compress_callback;
    }

    public abstract function can_create_key();
    public abstract function get_compression_count();
    public abstract function is_limit_reached();

    protected abstract function validate();
    protected abstract function compress($input, $resize_options, $preserve_options);

    public function get_status() {
        return $this->validate();
    }

    public function compress_file($file, $resize_options, $preserve_options) {
        if (!file_exists($file)) {
            throw new Tiny_Exception('File does not exist', 'FileError');
        }

        if (!self::needs_resize($file, $resize_options)) {
            $resize_options = false;
        }

        list($output, $details) = $this->compress(file_get_contents($file), $resize_options, $preserve_options);
        file_put_contents($file, $output);

        if ($resize_options) {
            $details['output']['resized'] = true;
        }

        $this->call_after_compress_callback();
        return $details;
    }

    protected function call_after_compress_callback() {
        if ($this->after_compress_callback) {
            call_user_func($this->after_compress_callback, $this);
        }
    }

    protected static function needs_resize($file, $resize_options) {
        if (!$resize_options) {
            return false;
        }

        list($width, $height) = getimagesize($file);
        return $width > $resize_options['width'] || $height > $resize_options['height'];
    }
}
