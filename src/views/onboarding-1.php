<div class="tiny-onboarding"
	data-create-key-next="<?php echo esc_url( $this->get_step_url( 2 ) ); ?>"
	data-update-key-next="<?php echo esc_url( add_query_arg( 'path', 'api-key', $this->get_step_url( 3 ) ) ); ?>">
	<?php $this->render_steps( 1 ); ?>
	<img class="tiny-onboarding-panda" width="112" height="112" alt=""
		src="<?php echo esc_url( plugins_url( '/images/panda-juggle.png', __DIR__ ) ); ?>">
	<div class="tiny-onboarding-header">
		<h1><?php esc_html_e( 'Tinify account', 'tiny-compress-images' ); ?></h1>
		<p>
			<?php
			esc_html_e(
				'Compress your images automatically for a faster site. A free account includes 500 credits a month.',
				'tiny-compress-images'
			);
			?>
		</p>
	</div>
	<?php $this->settings->render_account_connect(); ?>
</div>
