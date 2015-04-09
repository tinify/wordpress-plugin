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
    protected $count_callback;

    public static function get_ca_file() {
        return dirname(__FILE__) . '/cacert.pem';
    }

    public static function get_compressor($api_key, $count_callback=null) {
        if (Tiny_PHP::is_curl_available()) {
            return new Tiny_Compress_Curl($api_key, $count_callback);
        } elseif (Tiny_PHP::is_fopen_available()) {
            return new Tiny_Compress_Fopen($api_key, $count_callback);
        }
        throw new Tiny_Exception('No HTTP client is available (cURL or fopen)', 'NoHttpClient');
    }

    protected function __construct($api_key, $count_callback) {
        $this->api_key = $api_key;
        $this->count_callback = $count_callback;
    }

    abstract protected function shrink($input);
    abstract protected function output($url);

    public function get_status() {
        list($details, $headers) = $this->shrink(null);
        $this->update_compression_count($headers);
        if ($details["error"] == 'InputMissing') {
            return Tiny_Compressor_Status::Green;
        } else {
            return Tiny_Compressor_Status::Red;
        }
    }

    public function compress($input) {
        list($details, $headers) = $this->shrink($input);
        $this->update_compression_count($headers);
        $outputUrl = $headers["Location"];
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
            throw new Tiny_Exception('File does not exists', 'FileError');
        }
        list($output, $details) = $this->compress(file_get_contents($file));
        file_put_contents($file, $output);
        return $details;
    }

    protected function update_compression_count($headers) {
        if ($this->count_callback && isset($headers["Compression-Count"])) {
            call_user_func($this->count_callback, $headers["Compression-Count"]);
        }
    }

    protected static function parse_headers($headers) {
        if (!is_array($headers)) {
            $headers = explode("\r\n", $headers);
        }
        $res = array();
        foreach ($headers as $header) {
            list($key, $value) = explode(":", $header, 2);
            if ($value) {
                $res[$key] = trim($value);
            }
        }
        return $res;
    }

    protected static function decode($text) {
        $result = json_decode($text, true);
        if ($result === null) {
            throw new Tiny_Exception('Could not decode JSON', 'JsonError');
        }
        return $result;
    }
}

