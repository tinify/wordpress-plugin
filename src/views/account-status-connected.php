<?php
/**
 * Account status connected view.
 *
 * Account connection status.
 * @var object $status {
 *     @type bool        $ok      Whether the API connection is successful.
 *     @type bool        $pending Whether the connection is pending activation.
 *     @type string|null $message Optional status message.
 * }
 * @var string $key The API key.
 * @var bool   $link_to_settings Whether Change account links to the settings page.
 */

// Every account gets free credits each month. Only free accounts have a
// limited number of credits, including free accounts that bought a credit
// bundle. Paid accounts pay per compression once the free credits are used.
$tiny_email             = self::get_email_address();
$tiny_on_free_plan      = self::is_on_free_plan();
$tiny_remaining_credits = self::get_remaining_credits();
$tiny_limited_credits   = $status->ok && $tiny_on_free_plan && is_numeric( $tiny_remaining_credits );
$tiny_show_credits      = $tiny_limited_credits || ( $status->ok && ! $status->pending && ! $tiny_on_free_plan );
$tiny_free_credits      = Tiny_Config::MONTHLY_FREE_COMPRESSIONS;
$tiny_free_credits_left = max( 0, $tiny_free_credits - intval( self::get_compression_count() ) );
$tiny_remaining_credits = $tiny_on_free_plan ? intval( $tiny_remaining_credits ) : $tiny_free_credits_left;
$tiny_free_credits_left = min( $tiny_remaining_credits, $tiny_free_credits_left );
$tiny_bundle_credits    = $tiny_remaining_credits - $tiny_free_credits_left;
$tiny_credits_bar_total = $tiny_free_credits + $tiny_bundle_credits;
$tiny_dashboard_url     = 'https://tinify.com/dashboard/api';
$tiny_credits_url       = 'https://tinify.com/checkout/credits?utm_source=wordpress-plugin&utm_medium=referral&utm_campaign=upgrade&utm_content=settings-credits';
$tiny_api_url           = 'https://tinify.com/checkout/api?utm_source=wordpress-plugin&utm_medium=referral&utm_campaign=upgrade&utm_content=settings-api';
?>
<div class="tiny-card <?php echo $status->ok ? ( $status->pending ? 'status-pending' : 'status-success' ) : 'status-failure'; ?>" id="tiny-account-status" data-state="complete">
	<div class="tiny-card-section">
		<div class="tiny-card-head">
			<h2><?php esc_html_e( 'Tinify account', 'tiny-compress-images' ); ?></h2>
		</div>

		<?php if ( $status->pending ) { ?>
			<div class="notice notice-warning inline">
				<p>
					<?php
					printf(
						/* translators: %s: email address the activation link was sent to */
						esc_html__( 'Your API key is not confirmed yet. Click the link we sent to %s to activate it.', 'tiny-compress-images' ),
						esc_html( $tiny_email )
					);
					?>
				</p>
			</div>
		<?php } elseif ( ! $status->ok ) { ?>
			<div class="notice notice-error inline">
				<p>
					<strong><?php esc_html_e( 'Connection unsuccessful', 'tiny-compress-images' ); ?></strong><br>
					<?php
					if ( isset( $status->message ) ) {
						echo esc_html__( 'Error', 'tiny-compress-images' ) . ': ';
						echo esc_html( $status->message );
					} else {
						esc_html_e( 'API status could not be checked, enable cURL for more information', 'tiny-compress-images' );
					}
					?>
				</p>
			</div>
		<?php } ?>

		<dl class="tiny-account-details">
			<?php if ( $tiny_email ) { ?>
				<div>
					<dt><?php esc_html_e( 'Email', 'tiny-compress-images' ); ?></dt>
					<dd><?php echo esc_html( $tiny_email ); ?></dd>
				</div>
			<?php } ?>
			<?php if ( $status->ok && ! $status->pending ) { ?>
				<div>
					<dt><?php esc_html_e( 'Plan', 'tiny-compress-images' ); ?></dt>
					<dd>
						<?php
						if ( self::is_on_free_plan() ) {
							esc_html_e( 'Free', 'tiny-compress-images' );
						} else {
							esc_html_e( 'Pay per compression', 'tiny-compress-images' );
						}
						?>
					</dd>
				</div>
			<?php } ?>
		</dl>

		<?php if ( $tiny_show_credits ) { ?>
			<div class="tiny-credits">
				<p class="tiny-credits-figure <?php echo $tiny_limited_credits && 0 === $tiny_remaining_credits ? 'tiny-credits-empty' : ''; ?>">
					<strong><?php echo esc_html( number_format_i18n( $tiny_remaining_credits ) ); ?></strong>
					<?php
					if ( $tiny_limited_credits ) {
						echo esc_html( _n( 'credit left', 'credits left', $tiny_remaining_credits, 'tiny-compress-images' ) );
					} else {
						esc_html_e( 'free credits left', 'tiny-compress-images' );
					}
					?>
				</p>
				<div class="tiny-credits-bar">
					<span style="width: <?php echo esc_attr( round( 100 * $tiny_free_credits_left / $tiny_credits_bar_total, 1 ) ); ?>%"></span>
					<span class="tiny-credits-bundle" style="width: <?php echo esc_attr( round( 100 * $tiny_bundle_credits / $tiny_credits_bar_total, 1 ) ); ?>%"></span>
				</div>
				<div class="tiny-credits-keys">
					<p class="tiny-credits-key">
						<?php
						if ( $tiny_limited_credits ) {
							printf(
								/* translators: %s: number of free credits left */
								esc_html( _n( '%s free credit', '%s free credits', $tiny_free_credits_left, 'tiny-compress-images' ) ),
								esc_html( number_format_i18n( $tiny_free_credits_left ) )
							);
						} else {
							printf(
								/* translators: %s: number of free credits left */
								esc_html__( '%s free credits, then pay per compression', 'tiny-compress-images' ),
								esc_html( number_format_i18n( $tiny_free_credits_left ) )
							);
						}
						?>
					</p>
					<?php if ( $tiny_bundle_credits > 0 ) { ?>
						<p class="tiny-credits-key tiny-credits-bundle">
							<?php
							printf(
								/* translators: %s: number of bought credits left */
								esc_html( _n( '%s bundle credit (never expires)', '%s bundle credits (never expire)', $tiny_bundle_credits, 'tiny-compress-images' ) ),
								esc_html( number_format_i18n( $tiny_bundle_credits ) )
							);
							?>
						</p>
					<?php } ?>
				</div>
			</div>
		<?php } ?>

		<p class="tiny-account-links">
			<a href="<?php echo esc_url( $tiny_dashboard_url ); ?>" target="_blank" rel="noopener">
				<?php esc_html_e( 'Tinify Dashboard', 'tiny-compress-images' ); ?>
				<span aria-hidden="true">&nearr;</span>
			</a>
			<?php if ( defined( 'TINY_API_KEY' ) ) { ?>
				<span>
					<?php
					printf(
						/* translators: %s: wp-config.php */
						esc_html__( 'The API key has been configured in %s', 'tiny-compress-images' ),
						'wp-config.php'
					);
					?>
				</span>
			<?php } elseif ( $link_to_settings ) { ?>
				<a class="tiny-link-secondary" href="<?php echo esc_url( admin_url( 'options-general.php?page=tinify' ) ); ?>">
					<?php esc_html_e( 'Change account', 'tiny-compress-images' ); ?>
				</a>
			<?php } else { ?>
				<button type="button" class="button-link tiny-link-secondary" data-tiny-action="change-account">
					<?php esc_html_e( 'Change API Key', 'tiny-compress-images' ); ?>
				</button>
			<?php } ?>
		</p>
	</div>

	<?php if ( $tiny_limited_credits ) { ?>
		<div class="tiny-card-footer">
			<h3><?php esc_html_e( 'Get more credits', 'tiny-compress-images' ); ?></h3>
			<div class="tiny-credit-option">
				<strong><?php esc_html_e( 'Credit bundle', 'tiny-compress-images' ); ?></strong>
				<span><?php esc_html_e( 'Extra credits that never expire.', 'tiny-compress-images' ); ?></span>
				<a href="<?php echo esc_url( $tiny_credits_url ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Buy a credit bundle', 'tiny-compress-images' ); ?> <span aria-hidden="true">&nearr;</span></a>
			</div>
			<div class="tiny-credit-option">
				<strong><?php esc_html_e( 'Pay per compression', 'tiny-compress-images' ); ?></strong>
				<span>
					<?php
					printf(
						/* translators: %s: number of free credits per month */
						esc_html__( 'Keep %s free credits a month, then pay for what you compress.', 'tiny-compress-images' ),
						esc_html( number_format_i18n( $tiny_free_credits ) )
					);
					?>
				</span>
				<a href="<?php echo esc_url( $tiny_api_url ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Switch to pay per compression', 'tiny-compress-images' ); ?> <span aria-hidden="true">&nearr;</span></a>
			</div>
		</div>
	<?php } ?>
</div>
