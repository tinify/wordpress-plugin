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

class Tiny_Plugin extends Tiny_WP_Base {
    const MEDIA_COLUMN = self::NAME;
    const DATETIME_FORMAT = 'Y-m-d G:i:s';

    private $settings;
    private $twig;

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
        add_action('admin_action_tiny_bulk_optimization', $this->get_method('bulk_optimization'));
        add_action('admin_enqueue_scripts', $this->get_method('enqueue_scripts'));
        add_action('attachment_submitbox_misc_actions', $this->get_method('show_media_info'));

        add_filter('manage_media_columns', $this->get_method('add_media_columns'));
        add_action('manage_media_custom_column', $this->get_method('render_media_column'), 10, 2);

        add_action('wp_ajax_tiny_compress_image', $this->get_method('compress_image'));
        add_action('wp_ajax_tiny_get_optimization_statistics', $this->get_method('ajax_optimization_statistics'));
        add_action('wp_ajax_tiny_size_format', $this->get_method('ajax_size_format'));

        $plugin = plugin_basename(dirname(dirname(__FILE__)) . '/tiny-compress-images.php');
        add_filter("plugin_action_links_$plugin", $this->get_method('add_plugin_links'));
        add_thickbox();
    }

    public function admin_menu() {
        add_media_page(
            __('Compress JPEG & PNG Images', 'tiny-compress-images'), __('Bulk Optimization', 'tiny-compress-images'),
            'upload_files', 'tiny-bulk-optimization', $this->get_method('render_bulk_optimization_page')
        );
    }

    public function add_plugin_links($current_links) {
        $additional[] = sprintf('<a href="options-media.php#%s">%s</a>', self::NAME,
            esc_html__('Settings', 'tiny-compress-images'));
        return array_merge($additional, $current_links);
    }

    public function enqueue_scripts($hook) {
        wp_enqueue_style(self::NAME .'_admin', plugins_url('/css/admin.css', __FILE__),
            array(), self::plugin_version());
        wp_register_script(self::NAME .'_admin', plugins_url('/js/admin.js', __FILE__),
            array(), self::plugin_version(), true);

        // WordPress < 3.3 does not handle multidimensional arrays
        wp_localize_script(self::NAME .'_admin', 'tinyCompress', array(
            'nonce' => wp_create_nonce('tiny-compress'),
            'wpVersion' => self::wp_version(),
            'pluginVersion' => self::plugin_version(),
            'L10nAllDone' => __('All images are processed', 'tiny-compress-images'),
            'L10nNoActionTaken' => __('No action taken', 'tiny-compress-images'),
            'L10nBulkAction' => __('Bulk Optimization', 'tiny-compress-images'),
            'L10nCancelled' => __('Cancelled', 'tiny-compress-images'),
            'L10nCompressing' => __('Compressing', 'tiny-compress-images'),
            'L10nCompressed' => __('compressed', 'tiny-compress-images'),
            'L10nError' => __('Error', 'tiny-compress-images'),
            'L10nLatestError' => __('Latest error', 'tiny-compress-images'),
            'L10nInternalError' => __('Internal error', 'tiny-compress-images'),
            'L10nOutOf' => __('out of', 'tiny-compress-images'),
            'L10nWaiting' => __('Waiting', 'tiny-compress-images'),
        ));

        wp_enqueue_script(self::NAME .'_admin');

        if ($hook == "media_page_tiny-bulk-optimization") {
            wp_enqueue_style(self::NAME .'_tiny_bulk_optimization', plugins_url('/css/bulk-optimization.css', __FILE__),
                array(), self::plugin_version());
            wp_register_script(self::NAME . '_tiny_bulk_optimization', plugins_url('/js/bulk-optimization.js', __FILE__),
                array(), self::plugin_version(), true);
            wp_enqueue_script(self::NAME .'_tiny_bulk_optimization');
        }

    }

    private function compress($metadata, $attachment_id) {
        $mime_type = get_post_mime_type($attachment_id);
        $tiny_image = new Tiny_Image($attachment_id, $metadata);

        if ($this->settings->get_compressor() === null || !$tiny_image->can_be_compressed()) {
            return array($tiny_image, null);
        }

        $success = 0;
        $failed = 0;

        $compressor = $this->settings->get_compressor();
        $active_tinify_sizes = $this->settings->get_active_tinify_sizes();
        $uncompressed_sizes = $tiny_image->filter_image_sizes('uncompressed', $active_tinify_sizes);

        foreach ($uncompressed_sizes as $size_name => $size) {
            try {
                $size->add_request();
                $tiny_image->update();

                $resize = Tiny_Image::is_original($size_name) ? $this->settings->get_resize_options() : false;
                $preserve = count($this->settings->get_preserve_options()) > 0 ? $this->settings->get_preserve_options() : false;
                $response = $compressor->compress_file($size->filename, $resize, $preserve);

                $size->add_response($response);
                $tiny_image->update();
                $success++;
            } catch (Tiny_Exception $e) {
                $size->add_exception($e);
                $tiny_image->update();
                $failed++;
            }
        }

        return array($tiny_image, array('success' => $success, 'failed' => $failed));
    }

    public function compress_attachment($metadata, $attachment_id) {
        if (!empty($metadata)) {
            list($tiny_image, $result) = $this->compress($metadata, $attachment_id);
            return $tiny_image->update_wp_metadata($metadata);
        } else {
            return $metadata;
        }
    }

    public function compress_image() {
        if (!$this->check_ajax_referer()) {
            exit();
        }
        $json = !empty($_POST['json']) && $_POST['json'];
        if (!current_user_can('upload_files')) {
            $message = __("You don't have permission to upload files.", 'tiny-compress-images');
            echo $json ? json_encode(array('error' => $message)) : $message;
            exit();
        }
        if (empty($_POST['id'])) {
            $message = __('Not a valid media file.', 'tiny-compress-images');
            echo $json ? json_encode(array('error' => $message)) : $message;
            exit();
        }
        $id = intval($_POST['id']);
        $metadata = wp_get_attachment_metadata($id);
        if (!is_array($metadata)) {
            $message = __('Could not find metadata of media file.', 'tiny-compress-images');
            echo $json ? json_encode(array('error' => $message)) : $message;
            exit;
        }

        $tiny_image = new Tiny_Image($id, $metadata);
        $size_before = $tiny_image->get_total_size_with_optimization();

        list($tiny_image, $result) = $this->compress($metadata, $id);

        wp_update_attachment_metadata($id, $tiny_image->update_wp_metadata($metadata));

        $size_after = $tiny_image->get_total_size_with_optimization();

        if ($json) {
            $result['message'] = $tiny_image->get_latest_error();
            $result['image_sizes_optimized'] = $tiny_image->get_image_sizes_optimized();
            $result['initial_total_size'] = size_format($tiny_image->get_total_size_without_optimization(), 2);
            $result['optimized_total_size'] = size_format($tiny_image->get_total_size_with_optimization(), 2);
            $result['savings'] = "" . number_format($tiny_image->get_savings(), 1);
            $result['status'] = $this->settings->get_status();
            $thumb = $tiny_image->get_image_size('thumbnail');
            if (!$thumb) {
                $thumb = $tiny_image->get_image_size();
            }
            $result['thumbnail'] = $thumb->url;
            $result['change'] = $size_after - $size_before;
            echo json_encode($result);
        } else {
            error_log("Please check the code this may actually never be executed.");
            echo $this->render_compress_details($tiny_image);
        }

        exit();
    }

    public function get_optimization_statistics() {
        global $wpdb;
        $result = $wpdb->get_results(
            "SELECT ID, post_title FROM $wpdb->posts
             WHERE post_type = 'attachment' AND (post_mime_type = 'image/jpeg' OR post_mime_type = 'image/png')
             ORDER BY ID DESC", ARRAY_A);

        $stats = array();
        $stats['uploaded-images'] = 0;
        $stats['optimized-image-sizes'] = 0;
        $stats['available-unoptimised-sizes'] = 0;
        $stats['optimized-library-size'] = 0;
        $stats['unoptimized-library-size'] = 0;
        $stats['available-for-optimization'] = array();

        for ($i = 0; $i < sizeof($result); $i++) {
            $tiny_image = new Tiny_Image($result[$i]["ID"]);
            $stats['uploaded-images']++;
            $stats['available-unoptimised-sizes'] += $tiny_image->get_image_sizes_available_for_compression();
            $stats['optimized-image-sizes'] += $tiny_image->get_image_sizes_optimized();
            $stats['optimized-library-size'] += $tiny_image->get_total_size_with_optimization();
            $stats['unoptimized-library-size'] += $tiny_image->get_total_size_without_optimization();
            if ( $tiny_image->get_image_sizes_available_for_compression() > 0 ) {
                $stats['available-for-optimization'][] = array( "ID" => $result[$i]["ID"], "post_title" => $result[$i]["post_title"] );
            }
        }
        $stats['estimated-cost'] = $this->estimate_cost($stats['available-unoptimised-sizes'], $this->settings->get_compression_count());

        return $stats;
    }

    public function ajax_optimization_statistics() {
        if (!$this->check_ajax_referer()) {
            exit();
        }
        $stats = $this->get_optimization_statistics();
        echo json_encode($stats);
        exit();
    }

    public function ajax_size_format() {
        if (!$this->check_ajax_referer()) {
            exit();
        }
        $bytes = intval($_POST['size']);
        echo json_encode(array('formatted-size' => size_format($bytes, 2)));
        exit();
    }

    public function bulk_optimization() {
        check_admin_referer('bulk-media');

        if (empty($_REQUEST['media']) || !is_array( $_REQUEST['media'])) {
            return;
        }

        $ids = implode('-', array_map('intval', $_REQUEST['media']));
        wp_redirect(add_query_arg(
            '_wpnonce',
            wp_create_nonce('tiny-bulk-optimization'),
            admin_url("upload.php?page=tiny-bulk-optimization&ids=$ids")
        ));
        exit();
    }

    public function add_media_columns($columns) {
        $columns[self::MEDIA_COLUMN] = __('Compression', 'tiny-compress-images');
        return $columns;
    }

    public function render_media_column($column, $id) {
        if ($column === self::MEDIA_COLUMN) {
            echo '<div class="tiny-ajax-container">';
            $this->render_compress_details(new Tiny_Image($id));
            echo '</div>';
        }
    }

    public function show_media_info() {
        global $post;
        echo '<div class="misc-pub-section tiny-compress-images">';
        echo '<h4>' . __('Compress JPEG & PNG Images', 'tiny-compress-images') . '</h4>';
        echo '<div class="tiny-ajax-container">';
        $this->render_compress_details(new Tiny_Image($post->ID));
        echo '</div></div>';
    }

    private function render_compress_details($tiny_image) {
        $in_progress = $tiny_image->filter_image_sizes('in_progress');
        if (count($in_progress) > 0) {
            include(dirname(__FILE__) . '/views/compress-details-processing.php');
        } else {
            include(dirname(__FILE__) . '/views/compress-details.php');
        }
    }

    public function render_bulk_optimization_page() {
        $stats = $this->get_optimization_statistics();
        $active_tinify_sizes = $this->settings->get_active_tinify_sizes();

        $auto_start_bulk = isset($_REQUEST['ids']);

        include(dirname(__FILE__) . '/views/bulk-optimization.php');
    }

    // Based on pricing April 2016.
    public function estimate_cost($compressions, $usage) {
        return $this->compression_cost($compressions + $usage) - $this->compression_cost($usage);
    }

    private function compression_cost($total) {
        $cost = 0;
        if ($total > 10000) {
            $compressions = $total - 10000;
            $cost += $compressions * 0.002;
            $total -= $compressions;
        }
        if ($total > 500) {
            $compressions = $total - 500;
            $cost += $compressions * 0.009;
            $total -= $compressions;
        }
        return $cost;
    }

    private function get_ids_to_compress() {
        if (empty($_REQUEST['ids'])) {
            return array();
        }

        $ids = implode(',', array_map('intval', explode('-', $_REQUEST['ids'])));
        $condition = "AND ID IN($ids)";

        global $wpdb;
        return $wpdb->get_results(
            "SELECT ID, post_title FROM $wpdb->posts
             WHERE post_type = 'attachment' $condition
             AND (post_mime_type = 'image/jpeg' OR post_mime_type = 'image/png')
             ORDER BY ID DESC", ARRAY_A);
    }
}
