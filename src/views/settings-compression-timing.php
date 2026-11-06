<?php
/**
 * When new uploads are compressed: in the background, during upload or not at all.
 *
 * @var string $tiny_timing background, auto or manual
 */
$tiny_timing_name = self::get_prefixed_name( 'compression_timing' );
?>
<div class="tiny-card-section">
	<div class="tiny-card-head">
		<h2><?php esc_html_e( 'Automatic compression', 'tiny-compress-images' ); ?></h2>
	</div>
	<?php if ( 'background' === $tiny_timing && Tiny_AS3CF::is_active() && Tiny_AS3CF::remove_local_files_setting_enabled() ) { ?>
		<div class="notice notice-warning inline">
			<p>
				<strong><?php esc_html_e( 'Warning', 'tiny-compress-images' ); ?></strong> —
				<?php esc_html_e( 'For compression to work you will need to configure WP Offload S3 to keep a copy of the images on the server.', 'tiny-compress-images' ); ?>
			</p>
		</div>
	<?php } ?>
	<label class="tiny-option">
		<input type="radio" id="tinypng_compression_timing_background" name="<?php echo esc_attr( $tiny_timing_name ); ?>" value="background"
			<?php checked( $tiny_timing, 'background' ); ?>>
		<span class="tiny-option-text">
			<span class="tiny-option-label"><?php esc_html_e( 'Compress new images in the background (Recommended)', 'tiny-compress-images' ); ?></span>
			<span><?php esc_html_e( 'This is the fastest method, but can cause issues with some image related plugins.', 'tiny-compress-images' ); ?></span>
		</span>
	</label>
	<label class="tiny-option">
		<input type="radio" id="tinypng_compression_timing_auto" name="<?php echo esc_attr( $tiny_timing_name ); ?>" value="auto"
			<?php checked( $tiny_timing, 'auto' ); ?>>
		<span class="tiny-option-text">
			<span class="tiny-option-label"><?php esc_html_e( 'Compress new images during upload', 'tiny-compress-images' ); ?></span>
			<span><?php esc_html_e( 'Uploads will take longer, but provides higher compatibility with other plugins.', 'tiny-compress-images' ); ?></span>
		</span>
	</label>
	<label class="tiny-option">
		<input type="radio" id="tinypng_compression_timing_manual" name="<?php echo esc_attr( $tiny_timing_name ); ?>" value="manual"
			<?php checked( $tiny_timing, 'manual' ); ?>>
		<span class="tiny-option-text">
			<span class="tiny-option-label"><?php esc_html_e( 'Do not compress new images automatically', 'tiny-compress-images' ); ?></span>
			<span><?php esc_html_e( 'Manually select the images you want to compress in the media library.', 'tiny-compress-images' ); ?></span>
		</span>
	</label>
</div>
