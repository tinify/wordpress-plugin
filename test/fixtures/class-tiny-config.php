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

// Whether this site can reach itself with a loopback request.
define( 'TINY_TEST_LOOPBACK', true );

// Seconds each image takes to compress
define( 'TINY_TEST_COMPRESSION_SECONDS', 0.05 );

function tiny_test_loopback() {
	if ( isset( $_COOKIE['tiny_test_no_loopback'] ) ) {
		return empty( $_COOKIE['tiny_test_no_loopback'] );
	}
	return TINY_TEST_LOOPBACK;
}

function tiny_test_compression_seconds() {
	if ( isset( $_COOKIE['tiny_test_slow_compression'] ) ) {
		return floatval( $_COOKIE['tiny_test_slow_compression'] );
	}
	return TINY_TEST_COMPRESSION_SECONDS;
}

// With a docker test, loopbacks will not worker as within docker, it is on localhost:80
// With this filter we can enforce loopback by overriding the url
if ( tiny_test_loopback() ) {
	add_filter( 'pre_http_request', 'tiny_test_loopback_to_port_80', 10, 3 );
} else {
	add_filter( 'pre_http_request', 'tiny_test_fail_loopback', 10, 3 );
}

function tiny_test_fail_loopback( $response, $args, $url ) {
	if ( admin_url( 'admin-ajax.php' ) !== $url ) {
		return $response;
	}
	return new WP_Error( 'http_request_failed', 'Loopback requests fail because the mock config turned them off.' );
}

// WordPress listens on port 80 in its container, not on the host port in siteurl,
// so we redirect these requests to the localhost when testing
function tiny_test_loopback_to_port_80( $response, $args, $url ) {
	$port_80_url = 'http://localhost/wp-admin/admin-ajax.php';
	if ( admin_url( 'admin-ajax.php' ) !== $url || $port_80_url === $url ) {
		return $response;
	}
	return wp_remote_request( $port_80_url, $args );
}

// Slow compression down, so a bulk run lasts long enough to act on it.
if ( tiny_test_compression_seconds() > 0 ) {
	add_action( 'tiny_image_before_compression', 'tiny_test_slow_compression' );
}
function tiny_test_slow_compression() {
	usleep( intval( tiny_test_compression_seconds() * 1000000 ) );
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