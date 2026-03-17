<?php

/**
 * Conversion enabled view
 *
 * @package tiny-compress-images
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
$convertopts_convert         = self::get_prefixed_name( 'convert_format[convert]' );
$convertopts_convert_id      = self::get_prefixed_name( 'conversion_convert' );
$convertopts_convert_checked = $this->get_conversion_enabled() ?
	' checked="checked"' : '';
?>

<div class="conversion-enabled">
	<p class="tiny-check">
		<input
			type="checkbox"
			id="<?php echo esc_attr( $convertopts_convert_id ); ?>"
			name="<?php echo esc_attr( $convertopts_convert ); ?>"
			value="on"
			<?php echo esc_html( $convertopts_convert_checked ); ?> />
		<label for="$convertopts_convert_id">
			<?php esc_html_e( 'Generate optimized image formats', 'tiny-compress-images' ); ?>
		</label>
	</p>
</div>
