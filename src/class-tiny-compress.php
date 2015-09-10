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

abstract class Tiny_Compress {
    protected $api_key;
    protected $after_compress_callback;

    public static function get_ca_file() {
        return dirname(__FILE__) . '/cacert.pem';
    }

    public static function get_compressor($api_key, $after_compress_callback=null) {
        if (Tiny_PHP::is_curl_available()) {
            return new Tiny_Compress_Curl($api_key, $after_compress_callback);
        } elseif (Tiny_PHP::is_fopen_available()) {
            return new Tiny_Compress_Fopen($api_key, $after_compress_callback);
        }
        throw new Tiny_Exception('No HTTP client is available (cURL or fopen)', 'NoHttpClient');
    }

    protected function __construct($api_key, $after_compress_callback) {
        $this->api_key = $api_key;
        $this->after_compress_callback = $after_compress_callback;
    }

    abstract protected function shrink($input);
    abstract protected function output($url);

    public function get_status(&$details) {
        list($details, $headers, $status_code) = $this->shrink(null);

        $this->call_after_compress_callback($details, $headers);
        if ($status_code >= 400 && $status_code < 500 && $status_code != 401) {
            return true;
        } else {
            return false;
        }
    }

    public function compress($input) {
        list($details, $headers) = $this->shrink($input);
        $this->call_after_compress_callback($details, $headers);
        $outputUrl = isset($headers['location']) ? $headers['location'] : null;
        if (isset($details['error']) && $details['error']) {
            throw new Tiny_Exception($details['message'], $details['error']);
        } else if ($outputUrl === null) {
            throw new Tiny_Exception('Could not find output url', 'OutputNotFound');
        }
        $output = $this->output($outputUrl);
        if (strlen($output) == 0) {
            throw new Tiny_Exception('Could not download output', 'OutputError');
        }
        return array($output, $details);
    }

    public function compress_file($file) {
        if (!file_exists($file)) {
            throw new Tiny_Exception('File does not exist', 'FileError');
        }
        list($output, $details) = $this->compress(file_get_contents($file));
        file_put_contents($file, $output);
        return $details;
    }

    protected function call_after_compress_callback($details, $headers) {
        if ($this->after_compress_callback) {
            call_user_func($this->after_compress_callback, $details, $headers);
        }
    }

    protected static function parse_headers($headers) {
        if (!is_array($headers)) {
            $headers = explode("\r\n", $headers);
        }
        $res = array();
        foreach ($headers as $header) {
            $split = explode(":", $header, 2);
            if (count($split) === 2) {
                $res[strtolower($split[0])] = trim($split[1]);
            }
        }
        return $res;
    }

    protected static function decode($text) {
        $result = json_decode($text, true);
        if ($result === null) {
            throw new Tiny_Exception(sprintf('JSON: %s [%d]',
                    PHP_VERSION_ID >= 50500 ? json_last_error_msg() : 'Error',
                    json_last_error()),
                'JsonError');
        }
        return $result;
    }
}

