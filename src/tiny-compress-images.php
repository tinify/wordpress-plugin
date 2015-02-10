<?php
/**
 * Plugin Name: Tiny Compress Images
 * Plugin URI: https://tinypng.com
 * Description: Speed up your website. Optimize your JPEG and PNG images automatically with TinyPNG.
 * Version: 1.0.0
 * Author: TinyPNG
 * Author URI: https://tinypng.com
 * License: GPLv2 or later
 */

require (dirname(__FILE__) . '/class-tiny-wp-base.php');
require (dirname(__FILE__) . '/class-tiny-exception.php');
require (dirname(__FILE__) . '/class-tiny-compress.php');
require (dirname(__FILE__) . '/class-tiny-compress-curl.php');
require (dirname(__FILE__) . '/class-tiny-compress-fopen.php');
require (dirname(__FILE__) . '/class-tiny-metadata.php');
require (dirname(__FILE__) . '/class-tiny-settings.php');
require (dirname(__FILE__) . '/class-tiny-plugin.php');

$tiny_plugin = new Tiny_Plugin();
