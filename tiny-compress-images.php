<?php
/**
 * Plugin Name: Compress JPEG & PNG images
 * Description: Speed up your website. Optimize your JPEG and PNG images automatically with TinyPNG.
 * Version: 1.0.0
 * Author: TinyPNG
 * Author URI: https://tinypng.com
 * License: GPLv2 or later
 */


function tiny_is_network_activated() {
    if (!function_exists('is_plugin_active_for_network')) {
        require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
    }

    return is_plugin_active_for_network(plugin_basename( __FILE__ ));
}

require (dirname(__FILE__) . '/src/class-tiny-php.php');
require (dirname(__FILE__) . '/src/class-tiny-wp-base.php');
require (dirname(__FILE__) . '/src/class-tiny-exception.php');
require (dirname(__FILE__) . '/src/class-tiny-compress.php');
require (dirname(__FILE__) . '/src/class-tiny-compress-curl.php');
require (dirname(__FILE__) . '/src/class-tiny-compress-fopen.php');
require (dirname(__FILE__) . '/src/class-tiny-metadata.php');
require (dirname(__FILE__) . '/src/class-tiny-settings.php');
require (dirname(__FILE__) . '/src/class-tiny-plugin.php');

$tiny_plugin = new Tiny_Plugin();
