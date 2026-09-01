<?php
/*
Onboarding presents both options enabled. A site that has explicitly turned
	one of them off keeps that choice. */
$tiny_compression_timing = get_option( self::get_prefixed_name( 'compression_timing' ) );
$tiny_auto_compress      = 'manual' !== $tiny_compression_timing;

$tiny_convert_format = get_option( self::get_prefixed_name( 'convert_format' ) );
$tiny_convert        = ! isset( $tiny_convert_format['convert'] ) ||
	'on' === $tiny_convert_format['convert'];

$tiny_timing_field  = self::get_prefixed_name( 'compression_timing' );
$tiny_convert_field = self::get_prefixed_name( 'convert_format' );
?>

<div class="tiny-onboarding">
	<h1><?php esc_html_e( 'How should TinyPNG work?', 'tiny-compress-images' ); ?></h1>
	<p class="tiny-text-center">
		<?php esc_html_e( 'These two options cover most sites.', 'tiny-compress-images' ); ?>
		<br>
		<?php
		/* translators: "Settings → TinyPNG" is the path to the plugin's settings page in the admin menu. */
		esc_html_e(
			'You can fine-tune the rest any time under Settings → TinyPNG.',
			'tiny-compress-images'
		);
		?>
	</p>

	<div class="tiny-card">
		<p class="tiny-check">
			<input type="hidden" name="<?php echo esc_attr( $tiny_timing_field ); ?>" value="manual">
			<input type="checkbox" id="<?php echo esc_attr( $tiny_timing_field ); ?>"
				name="<?php echo esc_attr( $tiny_timing_field ); ?>" value="background"
				<?php checked( $tiny_auto_compress ); ?>>
			<label for="<?php echo esc_attr( $tiny_timing_field ); ?>">
				<strong><?php esc_html_e( 'Compress new images automatically', 'tiny-compress-images' ); ?></strong>
				<span class="description"><?php esc_html_e( 'Every new image will be compressed in the background.', 'tiny-compress-images' ); ?></span>
			</label>
		</p>
		<p class="tiny-check">
			<input type="hidden" name="<?php echo esc_attr( $tiny_convert_field ); ?>[convert]" value="off">
			<input type="checkbox" id="<?php echo esc_attr( $tiny_convert_field ); ?>"
				name="<?php echo esc_attr( $tiny_convert_field ); ?>[convert]" value="on"
				<?php checked( $tiny_convert ); ?>>
			<label for="<?php echo esc_attr( $tiny_convert_field ); ?>">
				<strong><?php esc_html_e( 'Generate optimized image formats', 'tiny-compress-images' ); ?></strong>
				<span class="description"><?php esc_html_e( 'Also serve WebP or AVIF where the browser supports it.', 'tiny-compress-images' ); ?></span>
			</label>
		</p>
	</div>

	<p class="tiny-onboarding-actions">
		<a class="button button-primary button-hero"
			href="<?php echo esc_url( admin_url( 'upload.php?page=tiny-bulk-optimization' ) ); ?>">
			<?php esc_html_e( 'Optimize my images', 'tiny-compress-images' ); ?>
		</a>
	</p>
	<p class="tiny-onboarding-secondary description">
		<a href="<?php echo esc_url( admin_url( 'options-general.php?page=tinify' ) ); ?>">
			<?php esc_html_e( 'Review all settings', 'tiny-compress-images' ); ?>
		</a>
	</p>
</div>
