<?php

/**
 * Conversion enabled view
 *
 * @package tiny-compress-images
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
$convertopts_convert_to_name = self::get_prefixed_name( 'convert_format[convert_to]' );
$convertopts_convert_value = self::get_convert_format_option( 'convert_to', 'smallest' );
?>
<h4><?php esc_html_e( 'Conversion output', 'tiny-compress-images' ); ?></h4>

<?php self::render_radiobutton(
	$convertopts_convert_to_name,
	sprintf( self::get_prefixed_name( 'convert_convert_to_%s' ), 'smallest' ),
	'smallest',
	$convertopts_convert_value,
	__( 'Convert to smallest file type (Recommended)', 'tiny-compress-images' ),
	__(
		'We will calculate what is the best format for your image.',
		'tiny-compress-images'
	)
);
?>

<?php
self::render_radiobutton(
	$convertopts_convert_to_name,
	sprintf( self::get_prefixed_name( 'convert_convert_to_%s' ), 'webp' ),
	'webp',
	$convertopts_convert_value,
	__( 'Convert to WebP', 'tiny-compress-images' ),
	__(
		'WebP balances a small file size with good visual quality, supporting transparency and animation.',
		'tiny-compress-images'
	)
);
?>

<?php self::render_radiobutton(
	$convertopts_convert_to_name,
	sprintf( self::get_prefixed_name( 'convert_convert_to_%s' ), 'avif' ),
	'avif',
	$convertopts_convert_value,
	__( 'Convert to AVIF', 'tiny-compress-images' ),
	__( 'AVIF delivers even better compression and image quality than WebP. Browser support is not as good as WebP.', 'tiny-compress-images' )
);
?>
