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

class Tiny_Compress_Curl extends Tiny_Compress {
    protected function shrink_options($input) {
        $options = array(
              CURLOPT_URL => Tiny_Config::URL,
              CURLOPT_USERPWD => 'api:' . $this->api_key,
              CURLOPT_POSTFIELDS => $input,
              CURLOPT_BINARYTRANSFER => true,
              CURLOPT_RETURNTRANSFER => true,
              CURLOPT_HEADER => true,
              CURLOPT_CAINFO => self::get_ca_file(),
              CURLOPT_SSL_VERIFYPEER => true,
              CURLOPT_USERAGENT => Tiny_WP_Base::plugin_identification() . ' cURL'
        );
        if (TINY_DEBUG) {
            $f = fopen(dirname(__FILE__) . '/curl.log', 'w');
            if (is_resource($f)) {
                $options[CURLOPT_VERBOSE] = true;
                $options[CURLOPT_STDERR] = $f;
            }
        }
        return $options;
    }

    protected function shrink($input) {
        $request = curl_init();
        curl_setopt_array($request, $this->shrink_options($input));

        $output_url = null;
        $response = curl_exec($request);
        if ($response === false) {
            return array(array(
                'error' => 'CurlError',
                'message' => sprintf("%s [%d]", curl_error($request), curl_errno($request))
              ), null
            );
        }

        $header_size = curl_getinfo($request, CURLINFO_HEADER_SIZE);
        $headers = self::parse_headers(substr($response, 0, $header_size));
        curl_close($request);

        return array(self::decode(substr($response, $header_size)), $headers);
    }

    protected function output_options($url) {
        return array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CAINFO => self::get_ca_file(),
            CURLOPT_SSL_VERIFYPEER => true
        );
    }

    protected function output($url) {
        $request = curl_init();
        curl_setopt_array($request, $this->output_options($url));

        $response = curl_exec($request);
        curl_close($request);

        return $response;
    }
}
