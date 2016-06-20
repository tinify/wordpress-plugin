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

class Tiny_Image {
    const META_KEY = 'tiny_compress_images';
    const ORIGINAL = 0;

    private $id;
    private $name;
    private $sizes = array();
    private $statistics_calculated = false;
    private $image_sizes_optimized = 0;
    private $image_sizes_available_for_compression = 0;
    private $initial_total_size = 0;
    private $optimized_total_size = 0;

    public static function is_original($size) {
        return $size === self::ORIGINAL;
    }

    public function __construct($id, $wp_metadata=null) {
        $this->id = $id;

        if (is_null($wp_metadata)) {
            $wp_metadata = wp_get_attachment_metadata($id);
        }
        $this->parse_wp_metadata($wp_metadata);

        $values = get_post_meta($this->id, self::META_KEY, true);

        if (!is_array($values)) {
            $values = array();
        }
        foreach ($values as $size => $meta) {
            if (!isset($this->sizes[$size])) {
                $this->sizes[$size] = new Tiny_Image_Size();
            }
            $this->sizes[$size]->meta = $meta;
        }
    }

    private function parse_wp_metadata($wp_metadata) {
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

        $this->name = $path_info['basename'];

        $this->sizes[self::ORIGINAL] = new Tiny_Image_Size(
            "$path_prefix${path_info['basename']}",
            "$url_prefix${path_info['basename']}");

        $unique_sizes = array();
        if (isset($wp_metadata['sizes']) && is_array($wp_metadata['sizes'])) {
            foreach ($wp_metadata['sizes'] as $size => $info) {
                $filename = $info['file'];

                if (!isset($unique_sizes[$filename])) {
                    $unique_sizes[$filename] = true;
                    $this->sizes[$size] = new Tiny_Image_Size(
                        "$path_prefix$filename", "$url_prefix$filename");
                }
            }
        }
    }

    public function get_image_size($size=self::ORIGINAL, $create=false) {
        if (isset($this->sizes[$size]))
            return $this->sizes[$size];
        elseif ($create)
            return new Tiny_Image_Size();
        else
            return null;
    }

    public function update_wp_metadata($wp_metadata) {
        $original = $this->get_image_size();
        if (is_null($original) || !is_array($original->meta)) {
            return $wp_metadata;
        }

        $m = $original->meta;
        if (isset($m['output']) && isset($m['output']['width']) && isset($m['output']['height'])) {
            $wp_metadata['width'] = $m['output']['width'];
            $wp_metadata['height'] = $m['output']['height'];
        }
        return $wp_metadata;
    }

    public function update() {
        $values = array();
        foreach ($this->sizes as $size_name => $size) {
            if (is_array($size->meta)) {
                $values[$size_name] = $size->meta;
            }
        }

        $val = update_post_meta($this->id, self::META_KEY, $values);
    }

    public function get_id() {
        return $this->id;
    }

    public function get_name() {
        return $this->name;
    }

    public function can_be_compressed() {
        return in_array($this->get_mime_type(), array("image/jpeg", "image/png"));
    }

    public function get_mime_type() {
        return get_post_mime_type($this->id);
    }

    public function get_image_sizes() {
        $original = isset($this->sizes[self::ORIGINAL])
            ? array(self::ORIGINAL => $this->sizes[self::ORIGINAL])
            : array();
        $compressed = array();
        $uncompressed = array();
        foreach ($this->sizes as $size_name => $size) {
            if (self::is_original($size_name)) continue;
            if ($size->has_been_compressed()) {
                $compressed[$size_name] = $size;
            } else {
                $uncompressed[$size_name] = $size;
            }
        }
        ksort($compressed);
        ksort($uncompressed);
        return $original + $compressed + $uncompressed;
    }

    public function filter_image_sizes($method, $filter_sizes = null) {
        $selection = array();
        if (is_null($filter_sizes)) {
            $filter_sizes = array_keys($this->sizes);
        }
        foreach ($filter_sizes as $size_name) {
            if (!isset($this->sizes[$size_name])) continue;
            $tiny_image_size = $this->sizes[$size_name];
            if ($tiny_image_size->$method()) {
                $selection[$size_name] = $tiny_image_size;
            }
        }
        return $selection;
    }

    public function get_count($methods, $count_sizes = null) {
        $stats = array_fill_keys($methods, 0);
        if (is_null($count_sizes)) {
            $count_sizes = array_keys($this->sizes);
        }
        foreach ($count_sizes as $size) {
            if (!isset($this->sizes[$size])) continue;
            foreach ($methods as $method) {
                if ($this->sizes[$size]->$method()) {
                    $stats[$method]++;
                }
            }
        }
        return $stats;
    }

    public function get_latest_error() {
        $error_message = null;
        $last_timestamp = null;
        foreach ($this->sizes as $size) {
            if (isset($size->meta['error']) && isset($size->meta['message'])) {
                if ($last_timestamp === null || $last_timestamp < $size->meta['timestamp']) {
                    $last_timestamp = $size->meta['timestamp'];
                    $error_message = $size->meta['message'];
                }
            }
        }
        return $error_message;
    }

    public function get_image_sizes_optimized() {
        if (!$this->statistics_calculated) $this->calculate_statistics();
        return $this->image_sizes_optimized;
    }

    public function get_image_sizes_available_for_compression() {
        if (!$this->statistics_calculated) $this->calculate_statistics();
        return $this->image_sizes_available_for_compression;
    }

    public function get_total_size_without_optimization() {
        if (!$this->statistics_calculated) $this->calculate_statistics();
        return $this->initial_total_size;
    }

    public function get_total_size_with_optimization() {
        if (!$this->statistics_calculated) $this->calculate_statistics();
        return $this->optimized_total_size;
    }

    public function get_savings() {
       if (!$this->statistics_calculated) $this->calculate_statistics();
       $before = $this->get_total_size_without_optimization();
       $after = $this->get_total_size_with_optimization();

       /* Avoid division by zero. */
       if ($before === 0) {
           return 0;
       } else {
           return ($before - $after) / $before * 100;
       }
    }

    private function calculate_statistics() {
        if ($this->statistics_calculated) return;

        $settings = new Tiny_Settings();
        $active_sizes = $settings->get_sizes();
        $active_tinify_sizes = $settings->get_active_tinify_sizes();

        foreach ($this->sizes as $size_name => $size) {
            if (array_key_exists( $size_name, $active_sizes )) {
                if (isset($size->meta['input'])) {
                    $this->initial_total_size += intval($size->meta['input']['size']);

                    if (isset($size->meta['output'])) {
                        if ($size->modified()) {
                            $this->optimized_total_size += $size->filesize();
                            if (in_array($size_name, $active_tinify_sizes, true)) {
                                $this->image_sizes_available_for_compression += 1;
                            }
                        } else {
                            $this->optimized_total_size += intval($size->meta['output']['size']);
                            $this->image_sizes_optimized += 1;
                        }
                    } else {
                        $this->optimized_total_size += intval($size->meta['input']['size']);
                    }
                } elseif ( $size->exists() ) {
                    $this->initial_total_size += $size->filesize();
                    $this->optimized_total_size += $size->filesize();
                    if (in_array($size_name, $active_tinify_sizes, true)) {
                        $this->image_sizes_available_for_compression += 1;
                    }
                }
            }
        }

        $this->statistics_calculated = true;
    }
}
