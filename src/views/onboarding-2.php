<?php
/* Saved when the account was created on step 1. */
$tiny_email = get_option( self::get_prefixed_name( 'email_address' ) );
?>

<div class="tiny-onboarding" data-activated-next="<?php echo esc_url( $this->get_step_url( 3 ) ); ?>">
	<?php $this->render_steps( 2 ); ?>
	<img class="tiny-onboarding-panda" width="112" height="112" alt=""
		src="<?php echo esc_url( plugins_url( '/images/panda-unbox.png', __DIR__ ) ); ?>">
	<div class="tiny-onboarding-header">
		<h1><?php esc_html_e( 'Check your inbox', 'tiny-compress-images' ); ?></h1>
		<p>
			<?php
			printf(
				/* translators: %s: email address the activation link was sent to */
				esc_html__( 'Click the link we sent to %s. This page continues automatically.', 'tiny-compress-images' ),
				esc_html( $tiny_email )
			);
			?>
		</p>
	</div>

	<div class="tiny-onboarding-card">
		<div class="tiny-card-section">
			<div class="tiny-card-head">
				<h2><?php esc_html_e( 'Didn’t get the email?', 'tiny-compress-images' ); ?></h2>
				<p><?php esc_html_e( 'It can take a minute. Check your spam folder.', 'tiny-compress-images' ); ?></p>
			</div>
			<p class="tiny-actions">
				<a class="button" href="<?php echo esc_url( $this->get_step_url( 3 ) ); ?>">
					<?php esc_html_e( 'Continue to settings', 'tiny-compress-images' ); ?>
				</a>
				<a href="<?php echo esc_url( $this->get_step_url( 1 ) ); ?>">
					<?php esc_html_e( 'Change email address', 'tiny-compress-images' ); ?>
				</a>
			</p>
		</div>
	</div>
</div>
