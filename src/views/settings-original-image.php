<?php
$tiny_resize_name = self::get_prefixed_name( 'resize_original[enabled]' );
$tiny_backup_name = self::get_prefixed_name( 'backup[enabled]' );
$tiny_resize_on   = $this->get_resize_enabled();
?>
<div class="tiny-card-section tiny-resize-available" <?php echo $tiny_original_tinify ? '' : 'hidden'; ?>>
	<div class="tiny-card-head">
		<h2><?php esc_html_e( 'Resize large originals', 'tiny-compress-images' ); ?></h2>
	</div>
	<label class="tiny-option">
		<input type="checkbox" role="switch" id="tinypng_resize_original_enabled"
			name="<?php echo esc_attr( $tiny_resize_name ); ?>" value="on"
			<?php checked( $tiny_resize_on ); ?>>
		<span class="tiny-option-text">
			<span class="tiny-option-label"><?php esc_html_e( 'Resize large originals', 'tiny-compress-images' ); ?></span>
			<span><?php esc_html_e( 'Scales down uploads that are bigger than you need. Uses 1 extra credit per resized image.', 'tiny-compress-images' ); ?></span>
		</span>
	</label>
	<div class="tiny-format-options tiny-resize-inputs" id="tiny-resize-inputs" <?php echo $tiny_resize_on ? '' : 'hidden'; ?>>
		<p class="tiny-field">
			<label for="tinypng_resize_original_width"><?php esc_html_e( 'Max Width', 'tiny-compress-images' ); ?></label>
			<?php $this->render_resize_input( 'width' ); ?>
		</p>
		<p class="tiny-field">
			<label for="tinypng_resize_original_height"><?php esc_html_e( 'Max Height', 'tiny-compress-images' ); ?></label>
			<?php $this->render_resize_input( 'height' ); ?>
		</p>
	</div>
</div>

<div class="tiny-card-section tiny-resize-available" <?php echo $tiny_original_tinify ? '' : 'hidden'; ?>>
	<div class="tiny-card-head">
		<h2><?php esc_html_e( 'Originals and metadata', 'tiny-compress-images' ); ?></h2>
	</div>
	<label class="tiny-option">
		<input type="checkbox" role="switch" id="tinypng_backup"
			name="<?php echo esc_attr( $tiny_backup_name ); ?>" value="on"
			<?php checked( $this->get_backup_enabled() ); ?>>
		<span class="tiny-option-text">
			<span class="tiny-option-label"><?php esc_html_e( 'Keep a backup of each original', 'tiny-compress-images' ); ?></span>
			<span><?php esc_html_e( 'Stores the uncompressed upload so you can restore it from the Media Library.', 'tiny-compress-images' ); ?></span>
		</span>
	</label>
	<p class="tiny-option-group"><?php esc_html_e( 'Keep in compressed images', 'tiny-compress-images' ); ?></p>
	<?php
	$this->render_preserve_input( 'creation', __( 'Creation date and time', 'tiny-compress-images' ) );
	$this->render_preserve_input( 'copyright', __( 'Copyright information', 'tiny-compress-images' ) );
	$this->render_preserve_input( 'location', __( 'GPS location (JPEG only)', 'tiny-compress-images' ) );
	?>
</div>
