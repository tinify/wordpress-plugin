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
        add_filter('manage_media_columns', $this->get_method('add_media_columns'));
        add_action('manage_media_custom_column', $this->get_method('render_media_column'), 10, 2);
        add_action('attachment_submitbox_misc_actions', $this->get_method('show_media_info'));
        add_action('wp_ajax_tiny_compress_image', $this->get_method('compress_image'));
        add_action('wp_ajax_tiny_create_api_key', $this->get_method('create_api_key'));
        add_action('wp_ajax_tiny_save_api_key', $this->get_method('save_api_key'));
        add_action('wp_ajax_tiny_get_optimization_statistics', $this->get_method('ajax_optimization_statistics'));
        add_action('admin_action_tiny_bulk_optimization', $this->get_method('bulk_optimization'));
        add_action('admin_enqueue_scripts', $this->get_method('enqueue_scripts'));
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
        wp_enqueue_style(self::NAME .'_admin', plugins_url('/styles/admin.css', __FILE__),
            array(), self::plugin_version());
        wp_enqueue_style(self::NAME .'_tiny_bulk_optimization', plugins_url('/styles/bulk-optimization.css', __FILE__),
            array(), self::plugin_version());
        wp_enqueue_style(self::NAME .'_create-api-key', plugins_url('/styles/create-api-key.css', __FILE__),
            array(), self::plugin_version());

        wp_register_script(self::NAME .'_admin', plugins_url('/scripts/admin.js', __FILE__),
            array(), self::plugin_version(), true);
        wp_register_script(self::NAME . '_tiny_bulk_optimization', plugins_url('/scripts/bulk-optimization.js', __FILE__),
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
        wp_enqueue_script(self::NAME .'_tiny_bulk_optimization');
    }

    private function compress($metadata, $attachment_id) {
        $mime_type = get_post_mime_type($attachment_id);
        $tiny_metadata = new Tiny_Metadata($attachment_id, $metadata);

        if ($this->settings->get_compressor() === null || !$tiny_metadata->can_be_compressed()) {
            return array($tiny_metadata, null);
        }

        $success = 0;
        $failed = 0;

        $compressor = $this->settings->get_compressor();
        $active_tinify_sizes = $this->settings->get_active_tinify_sizes();
        $uncompressed_images = $tiny_metadata->filter_images('uncompressed', $active_tinify_sizes);

        foreach ($uncompressed_images as $size => $image) {
            try {
                $image->add_request();
                $tiny_metadata->update();

                $resize = Tiny_Metadata::is_original($size) ? $this->settings->get_resize_options() : false;
                $preserve = count($this->settings->get_preserve_options()) > 0 ? $this->settings->get_preserve_options() : false;
                $response = $compressor->compress_file($image->filename, $resize, $preserve);

                $image->add_response($response);
                $tiny_metadata->update();
                $success++;
            } catch (Tiny_Exception $e) {
                $image->add_exception($e);
                $tiny_metadata->update();
                $failed++;
            }
        }

        return array($tiny_metadata, array('success' => $success, 'failed' => $failed));
    }

    public function compress_attachment($metadata, $attachment_id) {
        if (!empty($metadata)) {
            list($tiny_metadata, $result) = $this->compress($metadata, $attachment_id);
            return $tiny_metadata->update_wp_metadata($metadata);
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

        list($tiny_metadata, $result) = $this->compress($metadata, $id);
        wp_update_attachment_metadata($id, $tiny_metadata->update_wp_metadata($metadata));

        if ($json) {
            $result['message'] = $tiny_metadata->get_latest_error();
            $result['image_sizes_optimized'] = $tiny_metadata->get_image_sizes_optimized();
            $result['initial_total_size'] = size_format($tiny_metadata->get_total_size_before_optimization(), 2);
            $result['optimized_total_size'] = size_format($tiny_metadata->get_total_size_after_optimization(), 2);
            $result['savings'] = "" . number_format($tiny_metadata->get_savings(), 1);
            $result['status'] = $this->settings->get_status();
            $thumb = $tiny_metadata->get_image('thumbnail');
            if (!$thumb) {
                $thumb = $tiny_metadata->get_image();
            }
            $result['thumbnail'] = $thumb->url;
            echo json_encode($result);
        } else {
            echo $this->render_compress_details($tiny_metadata);
        }

        exit();
    }

    public function create_api_key() {
        $compressor = $this->settings->get_compressor();
        if ($compressor->can_create_key()) {
            try {
                $compressor->create_key($_POST['email'], array(
                    "name" => $_POST['name'],
                    "identifier" => $_POST['identifier'],
                    "link" => $_POST['link'],
                ));
                echo json_encode(array('created' => true, 'exists' => false));
            } catch (Exception $err) {
                error_log($err);
                echo json_encode(array('created' => false, 'exists' => true, 'message' => 'add me'));
            }
        } else {
            throw new Tiny_Exception('Old PHP/cURL version', 'ClientLibraryNotSupported');
        }
        die();
    }

    public function save_api_key() {
        $key = $_POST['key'];
        if ($key == '') {
            update_option('tinypng_api_key', $key);
            echo json_encode(array('valid' => true));
            die();
        }
        $status = Tiny_Compress::create($key)->get_status();
        error_log($status->ok);
        error_log($status->message);
        if ($status->ok) {

            update_option('tinypng_api_key', $key);
            echo json_encode(array('valid' => true));
        } else {
            echo json_encode(array('valid' => false));
        }
        die();
    }

    public function get_optimization_statistics() {
        global $wpdb;
        $result = $wpdb->get_results(
            "SELECT ID FROM $wpdb->posts
             WHERE post_type = 'attachment' AND (post_mime_type = 'image/jpeg' OR post_mime_type = 'image/png')
             ORDER BY ID DESC", ARRAY_A);

        $optimized_image_sizes = 0;
        $unoptimized_image_sizes = 0;
        $optimized_library_size = 0;
        $unoptimized_library_size = 0;

        for ($i = 0; $i < sizeof($result); $i++) {
            $tiny_metadata = new Tiny_Metadata($result[$i]["ID"]);
            $unoptimized_image_sizes += $tiny_metadata->get_image_sizes_to_be_optimized();
            $optimized_image_sizes += $tiny_metadata->get_image_sizes_optimized();
            $optimized_library_size += $tiny_metadata->get_total_size_after_optimization();
            $unoptimized_library_size += $tiny_metadata->get_total_size_before_optimization();
        }

        $usage_this_month = $this->settings->get_compression_count();
        $estimated_cost = $this->estimate_cost($unoptimized_image_sizes + $usage_this_month) -
            $this->estimate_cost($usage_this_month);

        $savings_percentage = 0;
        if ($optimized_library_size != 0 && $unoptimized_library_size != 0) {
            $savings_percentage = (100 - ($optimized_library_size / $unoptimized_library_size * 100));
            $savings_percentage  = round($savings_percentage, 2);
        }

        return array(
            'optimized-image-sizes' => $optimized_image_sizes,
            'unoptimized-image-sizes' => $unoptimized_image_sizes,
            'optimized-library-size' => $optimized_library_size,
            'unoptimized-library-size' => $unoptimized_library_size,
            'estimated-cost' => $estimated_cost,
            'savings-percentage' => $savings_percentage);
    }

    public function ajax_optimization_statistics() {
        if (!$this->check_ajax_referer()) {
            exit();
        }

        $stats = $this->get_optimization_statistics();

        echo json_encode(array(
            'optimized-image-sizes' => $stats['optimized-image-sizes'],
            'unoptimized-image-sizes' => $stats['unoptimized-image-sizes'],
            'optimized-library-size' => ($stats['optimized-library-size'] ? size_format($stats['optimized-library-size'], 2) : '-'),
            'unoptimized-library-size' => ($stats['unoptimized-library-size'] ? size_format($stats['unoptimized-library-size'], 2) : '-'),
            'estimated-cost' => $stats['estimated-cost'],
            'savings-percentage' => $stats['savings-percentage']));

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
            $this->render_compress_details(new Tiny_Metadata($id));
            echo '</div>';
        }
    }

    public function show_media_info() {
        global $post;
        echo '<div class="misc-pub-section tiny-compress-images">';
        echo '<h4>' . __('Compress JPEG & PNG Images', 'tiny-compress-images') . '</h4>';
        echo '<div class="tiny-ajax-container">';
        $this->render_compress_details(new Tiny_Metadata($post->ID));
        echo '</div></div>';
    }

    private function render_compress_details($tiny_metadata) {
        $available_sizes = array_keys($this->settings->get_sizes());
        $active_tinify_sizes = $this->settings->get_active_tinify_sizes();
        $in_progress = count($tiny_metadata->filter_images('in_progress'));

        if ($in_progress > 0) {
            include(dirname(__FILE__) . '/views/compress-details-processing.php');
        } else {
            include(dirname(__FILE__) . '/views/compress-details.php');
        }
    }

    public function render_bulk_optimization_page() {
        $attachment_counts = ((array) wp_count_attachments());
        $number_of_png_images = array_key_exists('image/png', $attachment_counts) ? intval($attachment_counts['image/png']) : 0;
        $number_of_jpeg_images = array_key_exists('image/jpeg', $attachment_counts) ? intval($attachment_counts['image/jpeg']) : 0;
        $uploaded_images = $number_of_png_images + $number_of_jpeg_images;

        $stats = $this->get_optimization_statistics();
        $optimized_image_sizes = $stats['optimized-image-sizes'];
        $unoptimized_image_sizes = $stats['unoptimized-image-sizes'];
        $optimized_library_size = $stats['optimized-library-size'];
        $unoptimized_library_size = $stats['unoptimized-library-size'];
        $estimated_cost = $stats['estimated-cost'];
        $savings_percentage = $stats['savings-percentage'];
        $ids_to_compress = $this->get_ids_to_compress();
        $active_tinify_sizes = $this->settings->get_active_tinify_sizes();

        include(dirname(__FILE__) . '/views/bulk-optimization.php');
    }

    // Based on pricing April 2016.
    public function estimate_cost($compressions) {
        $cost = 0;

        if ($compressions > 10000) {
            $cheap = ($compressions - 10000);
            $cost += $cheap * 0.002;
            $compressions -= $cheap;
        }

        if ($compressions > 500) {
            $normal = ($compressions - 500);
            $cost += $normal * 0.009;
            $compressions -= $normal;
        }

        return $cost;
    }

    private function get_ids_to_compress() {
        if (empty($_POST['start-optimization']) && empty($_REQUEST['ids'])) {
            return array();
        }

        $condition = "";
        if (!empty($_REQUEST['ids'])) {
            $ids = implode(',', array_map('intval', explode('-', $_REQUEST['ids'])));
            $condition = "AND ID IN($ids)";
        }

        global $wpdb;
        return $wpdb->get_results(
            "SELECT ID, post_title FROM $wpdb->posts
             WHERE post_type = 'attachment' $condition
             AND (post_mime_type = 'image/jpeg' OR post_mime_type = 'image/png')
             ORDER BY ID DESC", ARRAY_A);
    }
}
