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

if (!defined("\Tinify\VERSION")) {
    /* Load vendored client if it is not yet loaded. */
    require_once("vendor/tinify/Tinify/Exception.php");
    require_once("vendor/tinify/Tinify/ResultMeta.php");
    require_once("vendor/tinify/Tinify/Result.php");
    require_once("vendor/tinify/Tinify/Source.php");
    require_once("vendor/tinify/Tinify/Client.php");
    require_once("vendor/tinify/Tinify.php");
}

class Tiny_Compress_Client extends Tiny_Compress {
    protected function __construct($api_key, $after_compress_callback) {
        parent::__construct($after_compress_callback);

        $this->last_status_code = 0;
        $this->last_message = "";
        $this->proxy = new WP_HTTP_Proxy();

        \Tinify\setAppIdentifier(Tiny_WP_Base::plugin_identification());
        \Tinify\setKey($api_key);
    }

    public function can_create_key() {
        return true;
    }

    public function get_compression_count() {
        return \Tinify\getCompressionCount();
    }

    public function is_limit_reached() {
        return $this->last_status_code == 429;
    }

    protected function validate() {
        try {
            $this->last_status_code = 0;
            return (object) array("ok" => \Tinify\validate());
        } catch(\Tinify\Exception $err) {
            $this->last_status_code = $err->status;
            list($message) = explode(" (HTTP", $err->getMessage(), 2);
            return (object) array("ok" => false, "message" => $message);
        }

        return true;
    }

    protected function compress($input, $resize_options, $preserve_options) {
        $this->set_request_options(\Tinify\Tinify::getClient());

        try {
            $this->last_status_code = 0;
            $source = \Tinify\fromBuffer($input);

            if ($resize_options) {
                $source = $source->resize($resize_options);
            }

            if ($preserve_options) {
                $source = $source->preserve($preserve_options);
            }

            $result = $source->result();

            $meta = array(
                "input" => array(
                    "size" => strlen($input),
                    "type" => $result->mediaType(),
                ),
                "output" => array(
                    "size" => $result->size(),
                    "type" => $result->mediaType(),
                    "width" => $result->width(),
                    "height" => $result->height(),
                    "ratio" => round($result->size() / strlen($input), 4),
                )
            );

            $buffer = $result->toBuffer();
        } catch(\Tinify\Exception $err) {
            $this->last_status_code = $err->status;
            throw $err;
        }

        return array($buffer, $meta);
    }

    public function create_key($email, $options) {
        $this->set_request_options(\Tinify\Tinify::getAnonymousClient());

        \Tinify\createKey($email, $options);
        update_option('tinypng_api_key', \Tinify\getKey());
    }

    private function set_request_options($client) {
        if (TINY_DEBUG) {
            $file = fopen(dirname(__FILE__) . '/curl.log', 'w');
            if (is_resource($file)) {
                $client->options[CURLOPT_VERBOSE] = true;
                $client->options[CURLOPT_STDERR] = $file;
            }
        }

        if ($this->proxy->is_enabled() && $this->proxy->send_through_proxy($url)) {
            $client->options[CURLOPT_PROXYTYPE] = CURLPROXY_HTTP;
            $client->options[CURLOPT_PROXY] = $this->proxy->host();
            $client->options[CURLOPT_PROXYPORT] = $this->proxy->port();

            if ($this->proxy->use_authentication()) {
                $client->options[CURLOPT_PROXYAUTH] = CURLAUTH_ANY;
                $client->options[CURLOPT_PROXYUSERPWD] = $this->proxy->authentication();
            }
        }
    }
}
