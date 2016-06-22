<div id="tiny-update-account" style="display: none">
    <div id="tiny-update-account-container" class="wp-core-ui">
        <div class="tiny-update-account-step2">
            <p>
            <?php $link = '<a href="https://tinypng.com/developers" target="_blank">' . esc_html__( 'TinyPNG Developer section', 'tiny-compress-images' ) . '</a>'; ?>
			<?php printf( esc_html__( 'Enter your API key. If you have lost your key then head over to the %s to retrieve it.', 'tiny-compress-images' ), $link ) ?>
			</p>
			<input class="tiny-update-account-input" type="text" id="tinypng_api_key_modal" name="tinypng_api_key_modal" size="35" spellcheck="false" value="<?php echo htmlspecialchars( $this->get_api_key() ) ?>" />
			<button class="tiny-account-update-key button button-primary">
				<?php echo esc_html__( 'Update', 'tiny-compress-images' ) ?>
			</button>
			<p class="tiny-update-account-message error" style="display: none"></p>
		</div>
	</div>
</div>
