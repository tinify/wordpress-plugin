<?php
/**
 * Plugin Name: TinyPNG - JPEG, PNG & WebP image compression
 * Description: Speed up your website. Optimize your JPEG, PNG, and WebP images automatically with TinyPNG.
 * Version: 3.6.2
 * Author: TinyPNG
 * Author URI: https://tinypng.com
 * Text Domain: tiny-compress-images
 * License: GPLv2 or later
 */

require dirname( __FILE__ ) . '/src/config/class-tiny-config.php';
require dirname( __FILE__ ) . '/src/class-tiny-helpers.php';
require dirname( __FILE__ ) . '/src/class-tiny-php.php';
require dirname( __FILE__ ) . '/src/class-tiny-wp-base.php';
require dirname( __FILE__ ) . '/src/class-tiny-exception.php';
require dirname( __FILE__ ) . '/src/class-tiny-compress.php';
require dirname( __FILE__ ) . '/src/class-tiny-bulk-optimization.php';
require dirname( __FILE__ ) . '/src/class-tiny-image-size.php';
require dirname( __FILE__ ) . '/src/class-tiny-image.php';
require dirname( __FILE__ ) . '/src/class-tiny-settings.php';
require dirname( __FILE__ ) . '/src/class-tiny-plugin.php';
require dirname( __FILE__ ) . '/src/class-tiny-notices.php';
require dirname( __FILE__ ) . '/src/class-tiny-cli.php';
require dirname( __FILE__ ) . '/src/class-tiny-picture.php';
require dirname( __FILE__ ) . '/src/compatibility/wpml/class-tiny-wpml.php';
require dirname( __FILE__ ) . '/src/compatibility/as3cf/class-tiny-as3cf.php';

if ( Tiny_PHP::client_supported() ) {
	require dirname( __FILE__ ) . '/src/class-tiny-compress-client.php';
} else {
	require dirname( __FILE__ ) . '/src/class-tiny-compress-fopen.php';
}

$tiny_plugin = new Tiny_Plugin();
