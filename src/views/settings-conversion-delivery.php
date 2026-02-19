<?php

/**
 * Conversion delivery view
 *
 * @package tiny-compress-images
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
$tinify_delivery_name = self::get_prefixed_name( 'convert_format[delivery_method]' );

$tinify_delivery_option_picture  = self::get_prefixed_name( 'convert_delivery_picture' );
$tinify_delivery_option_htaccess = self::get_prefixed_name( 'convert_delivery_htaccess' );

$tinify_delivery_value = self::get_conversion_delivery_method();
?>

<h4><?php esc_html_e( 'Conversion delivery', 'tiny-compress-images' ); ?></h4>

<?php
$this::render_radiobutton(
	$tinify_delivery_name,
	$tinify_delivery_option_picture,
	'picture',
	$tinify_delivery_value,
	'Picture element',
	'Uses HTML <picture> tags with multiple <source> elements.'
);
?>
<?php $this::render_radiobutton(
	$tinify_delivery_name,
	$tinify_delivery_option_htaccess,
	'htaccess',
	$tinify_delivery_value,
	'Server rules',
	'Adds htaccess rules to deliver the optimized image.'
); ?>
