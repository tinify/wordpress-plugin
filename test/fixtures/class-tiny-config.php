<?php

if ( ! defined( 'TINY_DEBUG' ) ) {
	define( 'TINY_DEBUG', null );
}

class Tiny_Config {
	/* URL is only used by fopen driver. */
	const SHRINK_URL                = 'http://tinify-mock-api/shrink';
	const KEYS_URL                  = 'http://tinify-mock-api/keys';
	const MONTHLY_FREE_COMPRESSIONS = 500;
	const META_KEY                  = '_tiny_compress_images';
	const LEGACY_META_KEY           = 'tiny_compress_images';
}

// WordPress listens on port 80 in its container, not on the host port in siteurl.
// Tests set the tiny_test_no_loopback cookie to make loopback requests fail.
if ( empty( $_COOKIE['tiny_test_no_loopback'] ) ) {
	putenv( 'WORDPRESS_HOST=http://localhost' );
}

// Tests set the tiny_test_slow_compression cookie to the seconds each image takes,
// so a bulk run lasts long enough to act on it.
if ( ! empty( $_COOKIE['tiny_test_slow_compression'] ) ) {
	add_action( 'tiny_image_before_compression', 'tiny_test_slow_compression' );
}
function tiny_test_slow_compression() {
	sleep( intval( $_COOKIE['tiny_test_slow_compression'] ) );
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

// ajax hook to upload copies of an image from test/fixtures in one request, as
// uploading through the UI takes four page loads per image
add_action( 'wp_ajax_upload_images', 'upload_images' );
function upload_images() {
	// Generating thumbnail, medium and the other sizes takes seconds per image.
	if ( isset( $_POST['generate_sizes'] ) && 'no' === $_POST['generate_sizes'] ) {
		add_filter( 'intermediate_image_sizes_advanced', '__return_empty_array' );
	}

	$file = basename( $_POST['file'] );
	$ids  = array();
	for ( $i = 0; $i < intval( $_POST['count'] ); $i++ ) {
		$tmp = wp_tempnam( $file );
		copy( dirname( __FILE__ ) . '/../../test/fixtures/' . $file, $tmp );

		$id = media_handle_sideload(
			array(
				'name'     => $file,
				'tmp_name' => $tmp,
			),
			0
		);
		if ( is_wp_error( $id ) ) {
			wp_send_json_error( $id->get_error_message() );
		}
		$ids[] = $id;
	}

	wp_send_json_success( array(
		'ids' => $ids,
	) );
}