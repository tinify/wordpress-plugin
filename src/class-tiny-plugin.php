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

class Tiny_Plugin extends Tiny_WP_Base {
    const MEDIA_COLUMN = self::NAME;
    const MEDIA_COLUMN_HEADER = 'Compression';

    private $settings;

    public static function jpeg_quality() {
          return 95;
    }

    public function __construct() {
        parent::__construct();
        $this->settings = new Tiny_Settings();
    }

    public function set_compressor($compressor) {
        $this->settings->set_compressor($compressor);
    }

    public function init() {
        add_filter('jpeg_quality', $this->get_static_method('jpeg_quality'));
        add_filter('wp_editor_set_quality', $this->get_static_method('jpeg_quality'));
        add_filter('wp_generate_attachment_metadata', $this->get_method('compress_attachment'), 10, 2);
        load_plugin_textdomain(self::NAME, false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function admin_init() {
        add_filter('manage_media_columns', $this->get_method('add_media_columns'));
        add_action('manage_media_custom_column', $this->get_method('render_media_column'), 10, 2);
        add_action('wp_ajax_tiny_compress_image', $this->get_method('compress_image'));
        add_action('admin_action_tiny_bulk_compress', $this->get_method('bulk_compress'));
        add_action('admin_enqueue_scripts', $this->get_method('enqueue_scripts'));
    }

    public function enqueue_scripts($hook) {
        wp_enqueue_style(self::NAME .'_admin', plugins_url('/styles/admin.css', __FILE__),
            array(), self::plugin_version());

        $handle = self::NAME .'_admin';
        wp_register_script($handle, plugins_url('/scripts/admin.js', __FILE__),
            array(), self::plugin_version(), true);

        wp_localize_script($handle, 'tinyCompressL10n', array(
            'bulkAction' => self::translate('Compress all uncompressed sizes'),
        ));
        wp_enqueue_script($handle);
    }

    public function compress_attachment($metadata, $attachment_id) {
        $mime_type = get_post_mime_type($attachment_id);
        $tiny_metadata = new Tiny_Metadata($attachment_id);

        if ($this->settings->get_compressor() === null || strpos($mime_type, 'image/') !== 0) {
            return $metadata;
        }

        $path_info = pathinfo($metadata['file']);
        $upload_dir = wp_upload_dir();
        $prefix = $upload_dir['basedir'] . '/' . $path_info['dirname'] . '/';

        $settings = $this->settings->get_sizes();

        if ($settings[Tiny_Metadata::ORIGINAL]['tinify'] && !$tiny_metadata->is_compressed()) {
            try {
                $response = $this->settings->get_compressor()->compress_file("$prefix${path_info['basename']}");
                $tiny_metadata->add_response($response);
            } catch (Tiny_Exception $e) {
                $tiny_metadata->add_exception($e);
            }
        }

        if (!isset($metadata['sizes']) || !is_array($metadata['sizes'])) {
            $tiny_metadata->update();
            return $metadata;
        }

        foreach ($metadata['sizes'] as $size => $info) {
            if (isset($settings[$size]) && $settings[$size]['tinify'] && !$tiny_metadata->is_compressed($size)) {
                try {
                    $response = $this->settings->get_compressor()->compress_file("$prefix${info['file']}");
                    $tiny_metadata->add_response($response, $size);
                } catch (Tiny_Exception $e) {
                    $tiny_metadata->add_exception($e, $size);
                }
            }
        }

        $tiny_metadata->update();
        return $metadata;
    }

    public function compress_image() {
        $id = $_POST['id'];
        if (!current_user_can('upload_files')) {
            echo self::translate("You don't have permission to work with uploaded files") . '.';
            exit();
        }
        if (!$id) {
            echo self::translate("Not a valid media file") . '.';
            exit();
        }
        $metadata = wp_get_attachment_metadata($id);
        if (!$metadata) {
            echo self::translate("Could not find metadata of media file") . '.';
        }

        $this->compress_attachment($metadata, $id);
        $this->render_media_column(self::MEDIA_COLUMN, $id);

        exit();
    }

    public function bulk_compress() {
        check_admin_referer('bulk-media');

        if (empty($_REQUEST['media']) || !is_array( $_REQUEST['media'])) {
            return;
        }

        foreach ($_REQUEST['media'] as $id) {
            $metadata = wp_get_attachment_metadata($id);
            if ($metadata) {
                $this->compress_attachment($metadata, $id);
            }
        }
    }

    public function add_media_columns($columns) {
        $columns[self::MEDIA_COLUMN] = self::translate(self::MEDIA_COLUMN_HEADER);
        return $columns;
    }

    public function render_media_column($column, $id) {
        if ($column === self::MEDIA_COLUMN) {
            $wp_metadata = wp_get_attachment_metadata($id);
            $wp_sizes = isset($wp_metadata['sizes']) ? array_keys($wp_metadata['sizes']) : array();
            $wp_sizes[] = Tiny_Metadata::ORIGINAL;

            $sizes = array_intersect($wp_sizes, $this->settings->get_tinify_sizes());

            $tiny_metadata = new Tiny_Metadata($id);
            $missing = $tiny_metadata->get_missing_sizes($sizes);
            $total = count($sizes);
            $success = $total - count($missing);

            if (count($missing) > 0) {
                printf(self::translate_escape('Compressed %d out of %d sizes'), $success, $total);
                echo '<br/>';
                if (($error = $tiny_metadata->get_latest_error())) {
                    echo '<span class="error">' . self::translate_escape('Latest error') . ': '. self::translate_escape($error) .'<br/>';
                }
                echo '<button type="button" class="tiny-compress" data-id="' . $id . '">' .
                    self::translate_escape('Compress') . '</button>';
                echo '<div class="spinner"></div>';
            } else {
                printf(self::translate_escape('Compressed %d out of %d sizes'), $success, $total);
                $savings = $tiny_metadata->get_savings();
                if ($savings['count'] > 0) {
                    echo '<br/>';
                    echo self::translate_escape('Total size') . ': ' . size_format($savings['input']) . '<br/>';
                    echo self::translate_escape('Compressed size') . ': ' . size_format($savings['output']);
                }
            }
        }
    }
}
