<?php

/**
 * Conversion enabled view
 *
 * @package tiny-compress-images
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
$convertopts_convert_subfields_classname = self::get_prefixed_name( 'convert_fields' );
$convertopts_convert_to_id               = self::get_prefixed_name( 'convert_convert_to' );
$convertopts_convert_disabled            = self::get_conversion_enabled() ? '' : ' disabled="disabled"';
?>

<div class="conversion-options">
	<?php require __DIR__ . '/settings-conversion-enabled.php'; ?>

	<fieldset
		class="<?php echo esc_attr( $convertopts_convert_subfields_classname ); ?>"
		id="<?php echo esc_attr( $convertopts_convert_to_id ); ?>"
		<?php echo esc_html( $convertopts_convert_disabled ); ?>>
		<?php
		require __DIR__ . '/settings-conversion-output.php';
		require __DIR__ . '/settings-conversion-delivery.php';
		?>
	</fieldset>
</div>
