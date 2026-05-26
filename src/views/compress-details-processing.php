<?php
/**
 * Loading indicator on media overview page
 *
 * @var Tiny_Image  $tiny_image
 */
?>
<div class="details-container" data-status="compressing" data-id="<?php echo esc_attr( $tiny_image->get_id() ); ?>">
	<div class="details">
		<span class="icon spinner"></span>
		<span class="message">
			<span><?php esc_html_e( 'compressing', 'tiny-compress-images' ); ?></span>
		</span>
	</div>
</div>
