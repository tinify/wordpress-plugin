<div class="tiny-onboarding">
	<h1>Tinify Account</h1>
	<p>Increase performance and save space with the best
		compression algorithm in WordPress.</p>
	<div id="tiny-onboarding-step" data-next-step="<?php echo esc_url( $this->get_step_url( 2 ) ); ?>">
		<?php $this->settings->render_account_status(); ?>
	</div>
</div>