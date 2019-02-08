<div class="tiny-account-status" id="tiny-account-status" data-state="missing">
	<div class="update">
		<h4><?php esc_html_e( 'Configure your account', 'tiny-compress-images' ); ?></h4>
		<p class="introduction"><?php
			$link = sprintf( '<a href="https://tinypng.com/developers" target="_blank">%s</a>',
				esc_html__( 'TinyPNG developer section', 'tiny-compress-images' )
			);

			esc_html_e( 'Enter your API key.', 'tiny-compress-images' );
			echo ' ';

			/* translators: %s: link saying TinyPNG developer section */
			printf( esc_html__(
				'If needed you can go to the %s to retrieve it.',
				'tiny-compress-images'
			), $link );
		?></p>

		<input type="text" id="tinypng_api_key"
			name="tinypng_api_key" size="35" spellcheck="false"
			value="<?php echo esc_attr( $key ); ?>">

		<button class="button button-primary" data-tiny-action="update-key"><?php
			esc_html_e( 'Save', 'tiny-compress-images' );
		?></button>

		<p class="message"></p>
	</div>
</div>
