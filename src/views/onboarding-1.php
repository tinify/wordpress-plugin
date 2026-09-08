<div class="tiny-onboarding">
	<h1><?php esc_html_e( 'Tinify Account', 'tiny-compress-images' ); ?></h1>
	<p>
		<?php
		esc_html_e(
			'Increase performance and save space with the best compression algorithm in WordPress.',
			'tiny-compress-images'
		);
		?>
	</p>
	<div id="tiny-onboarding-step">
		<?php $this->settings->render_account_status(); ?>
	</div>
	<p class="tiny-onboarding-actions" id="tiny-onboarding-continue" style="display: none">
		<a class="button button-primary button-hero"
			href="<?php echo esc_url( $this->get_step_url( 2 ) ); ?>">
			<?php esc_html_e( 'Continue', 'tiny-compress-images' ); ?>
		</a>
	</p>
</div>
