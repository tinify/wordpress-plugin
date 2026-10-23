<?php
$tiny_key_pending   = $this->settings->get_api_key_pending();
$tiny_auto_compress = $this->settings->auto_compress_enabled();
$tiny_stats         = Tiny_Bulk_Optimization::get_optimization_statistics( $this->settings );
$tiny_uncompressed  = count( $tiny_stats['available-for-optimization'] );
?>

<form class="tiny-onboarding" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
	<input type="hidden" name="action" value="tiny_onboarding_save_settings">
	<?php wp_nonce_field( 'tiny-onboarding-save-settings' ); ?>

	<?php $this->render_steps( 3 ); ?>
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
					<span class="tiny-option-descr"><?php esc_html_e( 'Every image you upload is compressed in the background.', 'tiny-compress-images' ); ?></span>
				</span>
			</label>
		</div>

		<?php $this->settings->render_modern_formats(); ?>

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
					<?php esc_html_e( 'Save', 'tiny-compress-images' ); ?>
				</button>
				<button type="submit" name="next" value="settings" class="button-link">
					<?php esc_html_e( 'All settings', 'tiny-compress-images' ); ?>
				</button>
			</p>
		</div>
	</div>
</form>
