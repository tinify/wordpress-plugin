<?php
/**
 * Create a Tinify account, or connect an existing one with its API key.
 *
 * @var bool   $show_create        whether this server can register new accounts
 * @var string $name               full name to prefill
 * @var string $email              email address to prefill
 * @var bool   $has_api_key        whether an account is connected already
 * @var string $api_key_masked     last characters of the connected API key, shown as placeholder
 */

$tiny_dashboard_link = sprintf(
	'<a href="https://tinify.com/dashboard/api" target="_blank">%s</a>',
	esc_html__( 'API dashboard', 'tiny-compress-images' )
);
?>
<div class="tiny-account-connect">
	<?php if ( $show_create ) { ?>
		<div class="tiny-account-connect-column">
			<div class="tiny-card-head">
				<h2><?php esc_html_e( 'Create a free account', 'tiny-compress-images' ); ?></h2>
				<p>
				<?php
				esc_html_e(
					'Enter your name and email. We’ll send you a link to activate your account.',
					'tiny-compress-images'
				);
				?>
				</p>
			</div>

			<p class="tiny-field">
				<label for="tinypng_api_key_name"><?php esc_html_e( 'Full name', 'tiny-compress-images' ); ?></label>
				<input type="text" id="tinypng_api_key_name" name="tinypng_api_key_name"
					placeholder="<?php esc_attr_e( 'Jack Johnson', 'tiny-compress-images' ); ?>"
					value="<?php echo esc_attr( $name ); ?>">
				<span class="tiny-field-error"></span>
			</p>

			<p class="tiny-field">
				<label for="tinypng_api_key_email"><?php esc_html_e( 'Email address', 'tiny-compress-images' ); ?></label>
				<input type="text" id="tinypng_api_key_email" name="tinypng_api_key_email"
					placeholder="<?php esc_attr_e( 'you@example.com', 'tiny-compress-images' ); ?>"
					value="<?php echo esc_attr( $email ); ?>">
				<span class="tiny-field-error"></span>
			</p>

			<p class="tiny-actions">
				<button type="button" class="button button-primary" data-tiny-action="create-key">
					<?php esc_html_e( 'Create account', 'tiny-compress-images' ); ?>
				</button>
				<span class="spinner"></span>
			</p>
		</div>
	<?php } ?>

	<div class="tiny-account-connect-column">
		<div class="tiny-card-head">
			<h2><?php esc_html_e( 'Already have an account?', 'tiny-compress-images' ); ?></h2>
			<p>
			<?php
			echo wp_kses_post(
				sprintf(
					/* translators: %s: link saying API dashboard */
					__( 'Paste your API key. You’ll find it in your %s.', 'tiny-compress-images' ),
					$tiny_dashboard_link
				)
			);
			?>
			</p>
		</div>

		<p class="tiny-field">
			<label for="tinypng_api_key"><?php esc_html_e( 'API key', 'tiny-compress-images' ); ?></label>
			<input
				type="text"
				id="tinypng_api_key"
				name="tinypng_api_key"
				spellcheck="false"
				placeholder="<?php echo esc_attr( $has_api_key ? $api_key_masked : __( 'Paste your API key', 'tiny-compress-images' ) ); ?>">
			<span class="tiny-field-error">
			<?php
			echo wp_kses_post(
				sprintf(
					/* translators: %s: link saying API dashboard */
					__( 'This API key isn’t valid. Check it in your %s.', 'tiny-compress-images' ),
					$tiny_dashboard_link
				)
			);
			?>
			</span>
		</p>

		<p class="tiny-actions">
			<?php if ( $has_api_key ) { ?>
				<button type="button" class="button button-primary" data-tiny-action="update-key">
					<?php esc_html_e( 'Update Key', 'tiny-compress-images' ); ?>
				</button>
				<button type="button" class="button" data-tiny-action="clear-key">
					<?php esc_html_e( 'Disconnect', 'tiny-compress-images' ); ?>
				</button>
				<?php } else { ?>
				<button type="button" class="button" data-tiny-action="update-key">
					<?php esc_html_e( 'Connect', 'tiny-compress-images' ); ?>
				</button>
			<?php } ?>
			<span class="spinner"></span>
		</p>
	</div>
</div>
