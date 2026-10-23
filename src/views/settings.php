<?php
$tiny_original_tinify = in_array( Tiny_Image::ORIGINAL, $this->get_active_tinify_sizes(), true );
$tiny_sizes_count     = count( array_diff( $this->get_active_tinify_sizes(), array( Tiny_Image::ORIGINAL_UNSCALED ) ) );
$tiny_resize          = $this->get_resize_enabled();
$tiny_wr2x            = $this->compress_wr2x_images();
$tiny_convert         = $this->get_conversion_enabled();
$tiny_logging_name    = self::get_prefixed_name( 'logging_enabled' );
$tiny_timing_name     = self::get_prefixed_name( 'compression_timing' );
?>
<div class="wrap tiny-settings">
	<h1><?php esc_html_e( 'Tinify', 'tiny-compress-images' ); ?></h1>
	<p class="tiny-settings-intro"><?php esc_html_e( 'Choose what Tinify compresses. Changes apply to new uploads and to your next Bulk Optimizer run.', 'tiny-compress-images' ); ?></p>
	<hr class="wp-header-end">
	<span id="tiny-compress-images"></span>

	<div class="tiny-settings-columns">
		<div class="tiny-settings-main">
			<div class="tiny-settings-connect" <?php echo $this->get_api_key() ? 'hidden' : ''; ?>
				data-create-key-next="<?php echo esc_url( $this->get_absolute_url() ); ?>"
				data-update-key-next="<?php echo esc_url( $this->get_absolute_url() ); ?>">
				<?php $this->render_account_connect(); ?>
			</div>

			<form action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>" id="tinify-settings" method="post" class="tiny-card">
				<?php settings_fields( 'tinify' ); ?>

				<div class="tiny-card-section">
					<div class="tiny-card-head">
						<h2><?php esc_html_e( 'Automatic compression', 'tiny-compress-images' ); ?></h2>
					</div>
					<?php if ( $this->background_compress_enabled() && Tiny_AS3CF::is_active() && Tiny_AS3CF::remove_local_files_setting_enabled() ) { ?>
						<div class="notice notice-warning inline">
							<p>
								<strong><?php esc_html_e( 'Warning', 'tiny-compress-images' ); ?></strong> —
								<?php esc_html_e( 'For compression to work you will need to configure WP Offload S3 to keep a copy of the images on the server.', 'tiny-compress-images' ); ?>
							</p>
						</div>
					<?php } ?>
					<input type="hidden" name="<?php echo esc_attr( $tiny_timing_name ); ?>" value="manual">
					<label class="tiny-option">
						<input type="checkbox" role="switch" id="tinypng_auto_compress"
							name="<?php echo esc_attr( $tiny_timing_name ); ?>"
							value="<?php echo 'auto' === $this->get_compression_timing() ? 'auto' : 'background'; ?>"
							<?php checked( $this->auto_compress_enabled() ); ?>>
						<span class="tiny-option-text">
							<span class="tiny-option-label"><?php esc_html_e( 'Compress new uploads automatically', 'tiny-compress-images' ); ?></span>
							<span><?php esc_html_e( 'Every image you upload is compressed in the background.', 'tiny-compress-images' ); ?></span>
						</span>
					</label>
				</div>

				<div class="tiny-card-section" id="tiny-image-sizes">
					<div class="tiny-card-head">
						<h2><?php esc_html_e( 'Image sizes', 'tiny-compress-images' ); ?></h2>
						<p><?php esc_html_e( 'WordPress saves every upload in several sizes. Each size you select uses 1 credit per image.', 'tiny-compress-images' ); ?></p>
					</div>
					<div class="tiny-sizes">
						<?php $this->render_sizes(); ?>
					</div>
					<div class="tiny-credit-summary" id="tiny-image-sizes-notice">
						<?php $this->render_size_checkboxes_description( $tiny_sizes_count, $tiny_resize, $tiny_wr2x, $tiny_convert ); ?>
					</div>
				</div>

				<?php $this->render_modern_formats(); ?>

				<div class="tiny-card-section tiny-resize-unavailable" <?php echo $tiny_original_tinify ? 'hidden' : ''; ?>>
					<p class="tiny-format-note">
						<?php esc_html_e( 'Enable compression of the original image size for more options.', 'tiny-compress-images' ); ?>
					</p>
				</div>

				<?php require __DIR__ . '/settings-original-image.php'; ?>

				<div class="tiny-card-section">
					<div class="tiny-card-head">
						<h2><?php esc_html_e( 'Troubleshooting', 'tiny-compress-images' ); ?></h2>
						<p><?php esc_html_e( 'Whenever you run into issues, we can help faster if we can see what is happening. Please enable logging for a short period, reproduce the problem, then send us a message at support@tinify.com with the diagnostics file attached.', 'tiny-compress-images' ); ?></p>
					</div>
					<label class="tiny-option">
						<input type="checkbox" role="switch" id="tinypng_enable_logging"
							name="<?php echo esc_attr( $tiny_logging_name ); ?>" value="on"
							<?php checked( get_option( $tiny_logging_name ), 'on' ); ?>>
						<span class="tiny-option-text">
							<span class="tiny-option-label"><?php esc_html_e( 'Enable logging', 'tiny-compress-images' ); ?></span>
						</span>
					</label>
					<p class="tiny-actions">
						<button class="button" type="button" id="tiny-download-diagnostics">
							<?php esc_html_e( 'Download Diagnostics', 'tiny-compress-images' ); ?>
						</button>
					</p>
				</div>

				<div class="tiny-card-section">
					<p class="tiny-actions">
						<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_attr_e( 'Save changes', 'tiny-compress-images' ); ?>">
					</p>
				</div>
			</form>
		</div>

		<div class="tiny-settings-sidebar">
			<?php $this->render_pending_status(); ?>

			<?php require __DIR__ . '/settings-credits.php'; ?>
		</div>
	</div>
</div>
