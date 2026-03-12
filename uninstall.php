<?php
/**
 * Uninstaller for plugin.
 *
 * @package tiny-compress-images
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

require_once dirname( __FILE__ ) . '/src/class-tiny-apache-rewrite.php';

Tiny_Apache_Rewrite::uninstall_rules();
