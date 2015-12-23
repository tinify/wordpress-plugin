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

class Tiny_Compress_Fopen extends Tiny_Compress {
    protected function shrink_options($input) {
        return array(
            'http' => array(
                'method' => 'POST',
                'header' => array(
                    'Content-type: image/png',
                    'Authorization: Basic ' . base64_encode('api:' . $this->api_key),
                    'User-Agent: ' . Tiny_WP_Base::plugin_identification() . ' fopen',
                 ),
                'content' => $input,
                'follow_location' => 0,
                'max_redirects' => 1, // Necessary for PHP 5.2
                'ignore_errors' => true // Apparently, a 201 is a failure
            ),
            'ssl' => array(
                'cafile' => self::get_ca_file(),
                'verify_peer' => true
            )
        );
    }

    protected function shrink($input) {
        $context = stream_context_create($this->shrink_options($input));
        $request = @fopen(Tiny_Config::URL, 'r', false, $context);

        $status_code = null;
        if ($http_response_header && count($http_response_header) > 0) {
            $http_code_values = explode(' ', $http_response_header[0]);
            if (count($http_code_values) > 1) {
                $status_code = intval($http_code_values[1]);
            }
        }

        if (!$request) {
            $headers = self::parse_headers($http_response_header);

            return array(array(
                'error' => 'FopenError',
                'message' => 'Could not compress, enable cURL for detailed error',
              ), $headers, $status_code
            );
        }

        $response = stream_get_contents($request);
        $meta_data = stream_get_meta_data($request);
        $headers = self::parse_headers($meta_data['wrapper_data']);
        fclose($request);

        return array(self::decode($response), $headers, $status_code);
    }

    protected function output_options($resize) {
        $options = array(
            'http' => array(
                'method' => 'GET',
            ),
            'ssl' => array(
                'cafile' => self::get_ca_file(),
                'verify_peer' => true
            )
        );
        if ($resize) {
            $options['http']['header'] = array(
                'Authorization: Basic ' . base64_encode('api:' . $this->api_key),
                'Content-Type: application/json',
                'User-Agent: ' . Tiny_WP_Base::plugin_identification() . ' fopen'
            );
            $options['http']['content'] = json_encode(array('resize' => $resize));
        }
        return $options;
    }

    protected function output($url, $resize) {
        $context = stream_context_create($this->output_options($resize));
        $request = @fopen($url, 'rb', false, $context);

        if ($request) {
            $response = stream_get_contents($request);
            $meta_data = stream_get_meta_data($request);
            $headers = self::parse_headers($meta_data['wrapper_data']);
            fclose($request);
        } else {
            $response = '';
            $headers = array();
        }
        return array($response, $headers);
    }
}
