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

class Tiny_Compress_Tinify extends Tiny_Compress {
    protected function shrink_options($input) {
    }

    protected function shrink($input) {
    }

    protected function output_options($url, $resize_options, $preserve_options) {
    }

    protected function output($url, $resize_options, $preserve_options) {
    }

    public static function createKey($email, $options) {
        try {
            \Tinify\setAppIdentifier(Tiny_WP_Base::plugin_identification());
            \Tinify\createKey($email, $options);
            $key = \Tinify\getKey();
            update_option('tinypng_api_key', $key);
        } catch(\Tinify\Exception $e) {
            // ... ?
        }
    }

    public static function validate($key) {
        try {
            \Tinify\setKey($key);
            \Tinify\validate();
            return true;
        } catch(\Tinify\Exception $e) {
            return false;
        }
    }
}
