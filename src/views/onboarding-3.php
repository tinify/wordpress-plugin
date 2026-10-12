<?php
/*
New uploads are compressed and modern formats are off, unless the site
	already made a choice. */
$tiny_auto_compress  = 'manual' !== get_option( self::get_prefixed_name( 'compression_timing' ) );
$tiny_convert_format = get_option( self::get_prefixed_name( 'convert_format' ) );
$tiny_convert        = isset( $tiny_convert_format['convert'] ) && 'on' === $tiny_convert_format['convert'];
$tiny_convert_to     = isset( $tiny_convert_format['convert_to'] ) ? $tiny_convert_format['convert_to'] : 'smallest';

/* Server rules are only offered when Apache can rewrite. */
$tiny_server_rules = Tiny_Settings::can_render_delivery_method();
if ( isset( $tiny_convert_format['delivery_method'] ) ) {
	$tiny_delivery = $tiny_convert_format['delivery_method'];
} elseif ( $tiny_server_rules ) {
	$tiny_delivery = 'htaccess';
} else {
	$tiny_delivery = 'picture';
}

/* Connecting with an API key skips the confirm email step. */
$tiny_confirm_email = 'api-key' !== filter_input( INPUT_GET, 'path' );

/* A new account can continue before its email is confirmed, but its key does not work yet. */
$tiny_key_pending = $this->settings->is_api_key_pending();

/* Stores the account's email and plan, which the connect request did not. */
if ( ! $tiny_key_pending ) {
	$this->settings->get_compressor()->get_status();
}

$tiny_stats        = Tiny_Bulk_Optimization::get_optimization_statistics( $this->settings );
$tiny_uncompressed = count( $tiny_stats['available-for-optimization'] );
?>

<form class="tiny-onboarding" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
	<input type="hidden" name="action" value="tiny_onboarding_save_settings">
	<?php wp_nonce_field( 'tiny-onboarding-save-settings' ); ?>

	<?php $this->render_steps( 3, $tiny_confirm_email ); ?>
	<img class="tiny-onboarding-panda" width="112" height="112" alt=""
		src="<?php echo esc_url( plugins_url( '/images/panda-thumbs-up.png', __DIR__ ) ); ?>">
	<div class="tiny-onboarding-header">
		<h1><?php esc_html_e( 'You’re connected', 'tiny-compress-images' ); ?></h1>
		<p>
			<?php
			esc_html_e(
				'Choose how Tinify handles new images. You can change this later in Settings.',
				'tiny-compress-images'
			);
			?>
		</p>
	</div>

	<div class="tiny-onboarding-card">
		<div class="tiny-card-section">
			<div class="tiny-card-head">
				<h2>
					<?php
					printf(
						/* translators: %s: email address of the Tinify account */
						esc_html__( 'Connected as %s', 'tiny-compress-images' ),
						esc_html( $this->settings->get_email_address() )
					);
					?>
				</h2>
				<?php if ( $this->settings->is_on_free_plan() ) { ?>
					<p><?php esc_html_e( 'Free plan, 500 credits a month.', 'tiny-compress-images' ); ?></p>
				<?php } ?>
			</div>
			<?php if ( $tiny_key_pending ) { ?>
				<div class="notice notice-warning inline">
					<p>
						<?php
						printf(
							/* translators: %s: email address the activation link was sent to */
							esc_html__( 'Your API key is not confirmed yet. Click the link we sent to %s to activate it.', 'tiny-compress-images' ),
							esc_html( $this->settings->get_email_address() )
						);
						?>
					</p>
				</div>
			<?php } ?>
		</div>

		<div class="tiny-card-section">
			<div class="tiny-card-head">
				<h2><?php esc_html_e( 'Automatic compression', 'tiny-compress-images' ); ?></h2>
			</div>
			<label class="tiny-option">
				<input type="checkbox" role="switch" name="auto_compress" value="on"
					<?php checked( $tiny_auto_compress ); ?>>
				<span class="tiny-option-text">
					<span class="tiny-option-label"><?php esc_html_e( 'Compress new uploads automatically', 'tiny-compress-images' ); ?></span>
					<span><?php esc_html_e( 'Every image you upload is compressed in the background.', 'tiny-compress-images' ); ?></span>
				</span>
			</label>
		</div>

		<div class="tiny-card-section">
			<div class="tiny-card-head">
				<h2><?php esc_html_e( 'Modern formats', 'tiny-compress-images' ); ?></h2>
			</div>
			<label class="tiny-option">
				<input type="checkbox" role="switch" name="convert" value="on" id="tiny-convert"
					<?php checked( $tiny_convert ); ?>>
				<span class="tiny-option-text">
					<span class="tiny-option-label"><?php esc_html_e( 'Create WebP or AVIF versions', 'tiny-compress-images' ); ?></span>
					<span><?php esc_html_e( 'Smaller files for browsers that support them. Uses 1 extra credit per image size.', 'tiny-compress-images' ); ?></span>
				</span>
			</label>

			<div class="tiny-format-options" id="tiny-format-options" <?php echo $tiny_convert ? '' : 'hidden'; ?>>
				<p class="tiny-option-group"><?php esc_html_e( 'Format', 'tiny-compress-images' ); ?></p>
				<label class="tiny-option">
					<input type="radio" name="convert_to" value="smallest" <?php checked( $tiny_convert_to, 'smallest' ); ?>>
					<span class="tiny-option-text">
						<span class="tiny-option-label"><?php esc_html_e( 'Smallest file per image (recommended)', 'tiny-compress-images' ); ?></span>
						<span><?php esc_html_e( 'Tinify picks WebP or AVIF, whichever is smaller.', 'tiny-compress-images' ); ?></span>
					</span>
				</label>
				<label class="tiny-option">
					<input type="radio" name="convert_to" value="webp" <?php checked( $tiny_convert_to, 'webp' ); ?>>
					<span class="tiny-option-text">
						<span class="tiny-option-label"><?php esc_html_e( 'WebP', 'tiny-compress-images' ); ?></span>
						<span><?php esc_html_e( 'Works in every modern browser.', 'tiny-compress-images' ); ?></span>
					</span>
				</label>
				<label class="tiny-option">
					<input type="radio" name="convert_to" value="avif" <?php checked( $tiny_convert_to, 'avif' ); ?>>
					<span class="tiny-option-text">
						<span class="tiny-option-label"><?php esc_html_e( 'AVIF', 'tiny-compress-images' ); ?></span>
						<span><?php esc_html_e( 'The smallest files, but not every browser shows them.', 'tiny-compress-images' ); ?></span>
					</span>
				</label>

				<?php if ( $tiny_server_rules ) { ?>
					<p class="tiny-option-group"><?php esc_html_e( 'Delivery', 'tiny-compress-images' ); ?></p>
					<label class="tiny-option">
						<input type="radio" name="delivery_method" value="htaccess" <?php checked( $tiny_delivery, 'htaccess' ); ?>>
						<span class="tiny-option-text">
							<span class="tiny-option-label"><?php esc_html_e( 'Server rules (recommended)', 'tiny-compress-images' ); ?></span>
							<span><?php esc_html_e( 'Adds rules to .htaccess so browsers get the new format automatically.', 'tiny-compress-images' ); ?></span>
						</span>
					</label>
					<label class="tiny-option">
						<input type="radio" name="delivery_method" value="picture" <?php checked( $tiny_delivery, 'picture' ); ?>>
						<span class="tiny-option-text">
							<span class="tiny-option-label"><?php esc_html_e( 'Picture element', 'tiny-compress-images' ); ?></span>
							<span><?php esc_html_e( 'Uses <picture> tags in your pages. Works on any server.', 'tiny-compress-images' ); ?></span>
						</span>
					</label>
				<?php } ?>

				<p class="tiny-format-note">
					<?php esc_html_e( 'Existing images get the new formats from the Bulk Optimizer.', 'tiny-compress-images' ); ?>
				</p>
			</div>
		</div>

		<div class="tiny-card-section">
			<div class="tiny-card-head">
				<h2>
					<?php
					if ( $tiny_uncompressed > 0 ) {
						printf(
							esc_html(
								/* translators: %s: number of images */
								_n(
									'Your library has %s image that isn’t compressed yet.',
									'Your library has %s images that aren’t compressed yet.',
									$tiny_uncompressed,
									'tiny-compress-images'
								)
							),
							esc_html( number_format_i18n( $tiny_uncompressed ) )
						);
					} else {
						esc_html_e( 'Your library has no images that need compressing.', 'tiny-compress-images' );
					}
					?>
				</h2>
				<p><?php esc_html_e( 'Your settings are saved when you continue.', 'tiny-compress-images' ); ?></p>
			</div>
			<p class="tiny-actions">
				<button type="submit" name="next" value="bulk-optimization" class="button button-primary">
					<?php esc_html_e( 'Go to Bulk Optimizer', 'tiny-compress-images' ); ?>
				</button>
				<button type="submit" name="next" value="settings" class="button-link">
					<?php esc_html_e( 'All settings', 'tiny-compress-images' ); ?>
				</button>
			</p>
		</div>
	</div>
</form>
