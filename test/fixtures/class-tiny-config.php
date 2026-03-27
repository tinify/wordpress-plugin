<?php

if (! defined('TINY_DEBUG')) {
	define('TINY_DEBUG', null);
}

class Tiny_Config
{
	/* URL is only used by fopen driver. */
	const SHRINK_URL = 'http://tinify-mock-api/shrink';
	const KEYS_URL = 'http://tinify-mock-api/keys';
	const MONTHLY_FREE_COMPRESSIONS = 500;
	const META_KEY = 'tiny_compress_images';
}

// ajax hook to delete all attachments as doing it via UI is flaky
add_action( 'wp_ajax_clear_media_library', 'clear_media_library' );
function clear_media_library() {
	$attachments = get_posts( array(
		'post_type'      => 'attachment',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	) );

	foreach ( $attachments as $id ) {
		wp_delete_attachment( $id, true );
	}

	wp_send_json_success( array(
		'deleted' => count( $attachments ),
	) );
}