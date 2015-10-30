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
        if (is_admin()) {
            add_action('admin_menu', $this->get_method('admin_menu'));
        }
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
        $plugin = plugin_basename(dirname(dirname(__FILE__)) . '/tiny-compress-images.php');
        add_filter("plugin_action_links_$plugin", $this->get_method('add_plugin_links'));
    }

    public function admin_menu() {
        add_management_page(
            self::translate('Compress JPEG & PNG Images'), self::translate('Compress All Images'),
            'upload_files', 'tiny-bulk-compress', $this->get_method('bulk_compress_page')
        );

    }

    public function add_plugin_links($current_links) {
        $additional[] = sprintf('<a href="options-media.php#%s">%s</a>', self::NAME,
            self::translate_escape('Settings'));
        return array_merge($additional, $current_links);
    }

    public function enqueue_scripts($hook) {
        wp_enqueue_style(self::NAME .'_admin', plugins_url('/styles/admin.css', __FILE__),
            array(), self::plugin_version());

        $handle = self::NAME .'_admin';
        wp_register_script($handle, plugins_url('/scripts/admin.js', __FILE__),
            array(), self::plugin_version(), true);

        // Wordpress < 3.3 does not handle multi dimensional arrays
        wp_localize_script($handle, 'tinyCompress', array(
            'nonce' => wp_create_nonce('tiny-compress'),
            'wpVersion' => self::wp_version(),
            'pluginVersion' => self::plugin_version(),
            'L10nAllDone' => self::translate('All images are processed'),
            'L10nBulkAction' => self::translate('Compress Images'),
            'L10nCompressing' => self::translate('Compressing'),
            'L10nCompressions' => self::translate('compressions'),
            'L10nError' => self::translate('Error'),
            'L10nInternalError' => self::translate('Internal error'),
            'L10nOutOf' => self::translate('out of'),
            'L10nWaiting' => self::translate('Waiting'),
        ));
        wp_enqueue_script($handle);
    }

    private function compress($metadata, $attachment_id) {
        $mime_type = get_post_mime_type($attachment_id);
        $tiny_metadata = new Tiny_Metadata($attachment_id, $metadata);

        if ($this->settings->get_compressor() === null || strpos($mime_type, 'image/') !== 0) {
            return $metadata;
        }

        $success = 0;
        $failed = 0;

        $compressor = $this->settings->get_compressor();
        $active_tinify_sizes = $this->settings->get_active_tinify_sizes();
        $uncompressed_sizes = $tiny_metadata->get_uncompressed_sizes($active_tinify_sizes);

        foreach ($uncompressed_sizes as $uncompressed_size) {
            try {
                $tiny_metadata->add_request($uncompressed_size);
                $tiny_metadata->update();
                $response = $compressor->compress_file($tiny_metadata->get_filename($uncompressed_size));
                $responses[$uncompressed_size] = $response;

                $tiny_metadata->add_response($response, $uncompressed_size);
                $success++;
            } catch (Tiny_Exception $e) {
                $tiny_metadata->add_exception($e, $uncompressed_size);
                $failed++;
            }
        }
        $tiny_metadata->update();

        return array($tiny_metadata, array('success' => $success, 'failed' => $failed));
    }

    public function compress_attachment($metadata, $attachment_id) {
        $this->compress($metadata, $attachment_id);
        return $metadata;
    }

    public function compress_image() {
        if (!$this->check_ajax_referer()) {
            exit();
        }
        $json = !empty($_POST['json']) && $_POST['json'];
        if (!current_user_can('upload_files')) {
            $message = self::translate("You don't have permission to work with uploaded files") . '.';
            echo $json ? json_encode(array('error' => $message)) : $message;
            exit();
        }
        if (empty($_POST['id'])) {
            $message = self::translate("Not a valid media file") . '.';
            echo $json ? json_encode(array('error' => $message)) : $message;
            exit();
        }
        $id = intval($_POST['id']);
        $metadata = wp_get_attachment_metadata($id);
        if (!is_array($metadata)) {
            $message = self::translate("Could not find metadata of media file") . '.';
            echo $json ? json_encode(array('error' => $message)) : $message;
            exit;
        }

        list($tiny_metadata, $result) = $this->compress($metadata, $id);
        if ($json) {
            $result['message'] = $tiny_metadata->get_latest_error();
            $result['status'] = $this->settings->get_status();
            $result['thumbnail'] = $tiny_metadata->get_url('thumbnail');
            echo json_encode($result);
        } else {
            echo $this->render_compress_details($tiny_metadata);
        }

        exit();
    }

    public function bulk_compress() {
        check_admin_referer('bulk-media');

        if (empty($_REQUEST['media']) || !is_array( $_REQUEST['media'])) {
            return;
        }

        $ids = implode('-', array_map('intval', $_REQUEST['media']));
        wp_redirect(add_query_arg(
            '_wpnonce',
            wp_create_nonce('tiny-bulk-compress'),
            admin_url("tools.php?page=tiny-bulk-compress&ids=$ids")
        ));
        exit();
    }

    public function add_media_columns($columns) {
        $columns[self::MEDIA_COLUMN] = self::translate(self::MEDIA_COLUMN_HEADER);
        return $columns;
    }

    public function render_media_column($column, $id) {
        if ($column === self::MEDIA_COLUMN) {
            $this->render_compress_details(new Tiny_Metadata($id));
        }
    }

    private function render_compress_details($tiny_metadata) {
        $missing = $tiny_metadata->get_uncompressed_sizes($this->settings->get_active_tinify_sizes());
        $success = count($tiny_metadata->get_success_sizes());
        $total = count($missing) + $success;
        $progress = count($tiny_metadata->get_in_progress_sizes());

        $duplicates = count($this->settings->get_active_tinify_sizes()) - $total;
        $success += $duplicates;
        $total += $duplicates;

        if (count($missing) > 0) {
            printf(self::translate_escape('Compressed %d out of %d sizes'), $success, $total);
            echo '<br/>';
            if (($error = $tiny_metadata->get_latest_error())) {
                echo '<span class="error">' . self::translate_escape('Latest error') . ': '. self::translate_escape($error) .'</span><br/>';
            }
            echo '<button type="button" class="tiny-compress" data-id="' . $tiny_metadata->get_id() . '">' .
                self::translate_escape('Compress') . '</button>';
            echo '<div class="spinner hidden"></div>';
        } elseif ($progress > 0) {
            printf(self::translate_escape('Compressing %d sizes...'), count($this->settings->get_active_tinify_sizes()));
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

    public function bulk_compress_page() {
        global $wpdb;

        echo '<div class="wrap" id="tiny-bulk-compress">';
        echo '<h2>' . self::translate('Compress JPEG & PNG Images') . '</h2>';
        if (empty($_POST['tiny-bulk-compress']) && empty($_REQUEST['ids'])) {
            $result = $wpdb->get_results("SELECT COUNT(*) AS `count` FROM $wpdb->posts WHERE post_type = 'attachment' AND post_mime_type LIKE 'image/%' ORDER BY ID DESC", ARRAY_A);
            $count = $result[0]['count'];

            echo '<p>' . self::translate_escape("Use this tool to compress all images in your media library") . '. ';
            echo self::translate_escape("Only images that have not been compressed will be compressed") . '.</p>';
            echo '<p>' . sprintf(self::translate_escape("We have found %d images in your media library"), $count) . '. ';
            echo self::translate_escape("To begin, just press the button below") . '.</p>';

            echo '<form method="POST" action="?page=tiny-bulk-compress">';
            echo '<input type="hidden" name="_wpnonce" value="' . wp_create_nonce('tiny-bulk-compress') . '">';
            echo '<input type="hidden" name="tiny-bulk-compress" value="1">';
            echo '<p><button class="button button-primary button-large" type="submit">' .
                self::translate_escape('Compress All Images') . '</p>';
            echo '</form>';
        } else {
            check_admin_referer('tiny-bulk-compress');

            if (!empty($_REQUEST['ids'])) {
                $ids = implode(',', array_map('intval', explode('-', $_REQUEST['ids'])));
                $cond = "AND ID IN($ids)";
            } else {
                $cond = "";
            }

            // Get all ids and names of the images and not the whole objects which will only fill memory
            $items = $wpdb->get_results("SELECT ID, post_title FROM $wpdb->posts WHERE post_type = 'attachment' AND post_mime_type LIKE 'image/%' $cond ORDER BY ID DESC", ARRAY_A);

            echo '<p>';
            echo self::translate_escape("Please be patient while the images are being compressed") . '. ';
            echo self::translate_escape("This can take a while if you have many images") . '. ';
            echo self::translate_escape("Do not navigate away from this page because it will stop the process") . '. ';
            echo self::translate_escape("You will be notified via this page when the processing is done") . '.';
            echo "</p>";

            echo '<div id="tiny-status"><p>'. self::translate_escape('Compressions this month') . sprintf(' <span>%d</span></p></div>', $this->settings->get_status());
            echo '<div id="tiny-progress"><p>'. self::translate_escape('Processing') . ' <span>0</span> ' . self::translate_escape('out of') . sprintf(' %d </p></div>', count($items));
            echo '<div id="media-items">';
            echo '</div>';

            echo '<script type="text/javascript">jQuery(function() { tinyBulkCompress('. json_encode($items) . ')})</script>';
        }

        echo '</div>';
    }
}
