<?php
/**
 * Plugin Name: Compress JPEG & PNG images
 * Description: Speed up your website. Optimize your JPEG and PNG images automatically with TinyPNG.
 * Version: 1.2.1
 * Author: TinyPNG
 * Author URI: https://tinypng.com
 * License: GPLv2 or later
 */


require (dirname(__FILE__) . '/src/config/tiny-config.php');
require (dirname(__FILE__) . '/src/class-tiny-php.php');
require (dirname(__FILE__) . '/src/class-tiny-wp-base.php');
require (dirname(__FILE__) . '/src/class-tiny-exception.php');
require (dirname(__FILE__) . '/src/class-tiny-compressor-status.php');
require (dirname(__FILE__) . '/src/class-tiny-compress.php');
require (dirname(__FILE__) . '/src/class-tiny-compress-curl.php');
require (dirname(__FILE__) . '/src/class-tiny-compress-fopen.php');
require (dirname(__FILE__) . '/src/class-tiny-metadata.php');
require (dirname(__FILE__) . '/src/class-tiny-settings.php');
require (dirname(__FILE__) . '/src/class-tiny-plugin.php');

$tiny_plugin = new Tiny_Plugin();
