<?php

/**
 * Dashboard Widget
 *
 * @var array $widget See Tiny_Dashboard::get_widget_data().
 */
$state = $widget['state'];

$bulk_url     = admin_url( 'upload.php?page=tiny-bulk-optimization' );
$start_url    = $bulk_url . '&start=1';
$settings_url = admin_url( 'options-general.php?page=tinify' );
$upload_url   = admin_url( 'media-new.php' );
$credits_url  = 'https://tinify.com/checkout/credits?utm_source=wordpress-plugin&utm_medium=referral&utm_campaign=upgrade&utm_content=dashboard-widget-credits';
$api_url      = 'https://tinify.com/checkout/api?utm_source=wordpress-plugin&utm_medium=referral&utm_campaign=upgrade&utm_content=dashboard-widget-api';
$strong       = array( 'strong' => array() );

$badges = array(
	'not_connected'  => array( 'red', __( 'Not connected', 'tiny-compress-images' ) ),
	'pending'        => array( 'amber', __( 'Waiting for activation', 'tiny-compress-images' ) ),
	'empty'          => array( 'amber', __( 'Nothing to compress', 'tiny-compress-images' ) ),
	'ready'          => array( 'green', __( 'Ready to compress', 'tiny-compress-images' ) ),
	'compressing'    => array( 'green', __( 'Compressing', 'tiny-compress-images' ) ),
	'done'           => array( 'green', __( 'All done', 'tiny-compress-images' ) ),
	'credits_short'  => array( 'amber', __( 'Not enough credits', 'tiny-compress-images' ) ),
	'out_of_credits' => array( 'red', __( 'Out of credits', 'tiny-compress-images' ) ),
);

$pandas = array(
	'not_connected'  => 'panda-juggle',
	'pending'        => 'panda-unbox',
	'empty'          => 'panda-astronaut',
	'ready'          => 'panda-skate',
	'compressing'    => 'panda-rocket',
	'done'           => 'panda-duck',
	'credits_short'  => 'panda-skate',
	'out_of_credits' => 'panda-coffee',
);

list( $badge_colour, $badge_text ) = $badges[ $state ];

$has_library_bar  = ! in_array( $state, array( 'pending', 'empty' ), true );
$has_credits_view = in_array( $state, array( 'credits_short', 'out_of_credits' ), true );
$has_credits_line = null !== $widget['remaining_credits'] &&
	! in_array( $state, array( 'not_connected', 'pending' ), true );

$time_left_text = '';
if ( null !== $widget['time_left'] ) {
	if ( $widget['time_left'] < 60 ) {
		$time_left_text = __( 'Less than a minute left.', 'tiny-compress-images' );
	} elseif ( $widget['time_left'] < 90 * 60 ) {
		$minutes        = intval( round( $widget['time_left'] / 60 ) );
		$time_left_text = sprintf(
			/* translators: %s: number of minutes */
			_n( 'About %s minute left.', 'About %s minutes left.', $minutes, 'tiny-compress-images' ),
			number_format_i18n( $minutes )
		);
	} else {
		$hours          = intval( round( $widget['time_left'] / 3600 ) );
		$time_left_text = sprintf(
			/* translators: %s: number of hours */
			_n( 'About %s hour left.', 'About %s hours left.', $hours, 'tiny-compress-images' ),
			number_format_i18n( $hours )
		);
	}
}
?>

<div class="tiny-widget" data-state="<?php echo esc_attr( $state ); ?>">
	<div class="tiny-widget-main">
		<div class="tiny-widget-top">
			<div class="tiny-widget-content">
				<span class="tiny-widget-badge tiny-widget-badge-<?php echo esc_attr( $badge_colour ); ?>"><?php echo esc_html( $badge_text ); ?></span>
				<?php if ( 'pending' === $state ) { ?>
					<h3 class="tiny-widget-heading"><?php esc_html_e( 'Check your inbox', 'tiny-compress-images' ); ?></h3>
					<p>
						<?php
						if ( $widget['email_address'] ) {
							printf(
								/* translators: %s: email address */
								esc_html__( 'We sent an activation link to %s. Open it to start compressing.', 'tiny-compress-images' ),
								esc_html( $widget['email_address'] )
							);
						} else {
							esc_html_e( 'We sent an activation link to your email address. Open it to start compressing.', 'tiny-compress-images' );
						}
						?>
					</p>
				<?php } elseif ( 'empty' === $state ) { ?>
					<h3 class="tiny-widget-heading"><?php esc_html_e( 'No images yet', 'tiny-compress-images' ); ?></h3>
					<p><?php esc_html_e( 'Upload images to your Media Library and Tinify compresses them automatically.', 'tiny-compress-images' ); ?></p>
				<?php } else { ?>
					<div class="tiny-widget-hero">
						<?php echo esc_html( number_format_i18n( $widget['percentage'] ) ); ?>%
						<span><?php esc_html_e( 'of your images are compressed', 'tiny-compress-images' ); ?></span>
					</div>
					<?php if ( 'not_connected' === $state ) { ?>
						<p><?php esc_html_e( 'Connect a free Tinify account to start. Free accounts get 500 credits every month.', 'tiny-compress-images' ); ?></p>
					<?php } elseif ( 'compressing' === $state ) { ?>
						<p>
							<?php echo esc_html( $time_left_text ); ?>
							<?php esc_html_e( 'Compression continues in the background, even if you close this page.', 'tiny-compress-images' ); ?>
						</p>
					<?php } elseif ( 'done' === $state ) { ?>
						<p><?php esc_html_e( 'New uploads are compressed automatically.', 'tiny-compress-images' ); ?></p>
					<?php } ?>
				<?php } ?>
			</div>
			<img
				class="tiny-widget-panda"
				src="<?php echo esc_url( plugins_url( '/images/widget/' . $pandas[ $state ] . '.png', __DIR__ ) ); ?>"
				width="128" height="128" alt="" />
		</div>

		<div class="tiny-widget-below">
			<?php if ( $has_library_bar ) { ?>
				<div class="tiny-widget-library">
					<div class="tiny-widget-bar">
						<div class="tiny-widget-bar-fill" style="width: <?php echo esc_attr( $widget['percentage'] ); ?>%"></div>
					</div>
					<div class="tiny-widget-legend">
						<span>
							<i class="tiny-widget-swatch"></i>
							<?php
							printf(
								/* translators: %s: number of compressed images */
								wp_kses( _n( '<strong>%s</strong> compressed', '<strong>%s</strong> compressed', $widget['images_optimized'], 'tiny-compress-images' ), $strong ),
								esc_html( number_format_i18n( $widget['images_optimized'] ) )
							);
							?>
						</span>
						<span>
							<?php if ( 'done' === $state ) { ?>
								<?php
								printf(
									/* translators: %s: bytes saved, e.g. 21.7 MB */
									wp_kses( __( '<strong>%s</strong> saved', 'tiny-compress-images' ), $strong ),
									esc_html( size_format( $widget['bytes_saved'], 1 ) )
								);
								?>
							<?php } else { ?>
								<i class="tiny-widget-swatch tiny-widget-swatch-todo"></i>
								<?php
								if ( 'compressing' === $state ) {
									/* translators: %s: number of images still to compress */
									$to_compress = _n( '<strong>%s</strong> to go', '<strong>%s</strong> to go', $widget['images_remaining'], 'tiny-compress-images' );
								} else {
									/* translators: %s: number of images still to compress */
									$to_compress = _n( '<strong>%s</strong> to compress', '<strong>%s</strong> to compress', $widget['images_remaining'], 'tiny-compress-images' );
								}
								printf(
									wp_kses( $to_compress, $strong ),
									esc_html( number_format_i18n( $widget['images_remaining'] ) )
								);
								?>
							<?php } ?>
						</span>
					</div>
				</div>
			<?php } ?>

			<?php if ( 'not_connected' === $state ) { ?>
				<div class="tiny-widget-actions">
					<a class="button button-primary button-large" href="<?php echo esc_url( $settings_url ); ?>"><?php esc_html_e( 'Connect Tinify', 'tiny-compress-images' ); ?></a>
				</div>
			<?php } elseif ( 'pending' === $state ) { ?>
				<div class="tiny-widget-actions">
					<a href="<?php echo esc_url( $settings_url ); ?>"><?php esc_html_e( 'Use an API key instead', 'tiny-compress-images' ); ?></a>
				</div>
			<?php } elseif ( 'empty' === $state ) { ?>
				<div class="tiny-widget-actions">
					<a class="button button-primary button-large" href="<?php echo esc_url( $upload_url ); ?>"><?php esc_html_e( 'Upload images', 'tiny-compress-images' ); ?></a>
				</div>
			<?php } elseif ( 'ready' === $state ) { ?>
				<div class="tiny-widget-actions">
					<a class="button button-primary button-large" href="<?php echo esc_url( $start_url ); ?>">
						<?php
						printf(
							/* translators: %s: number of images to compress */
							esc_html( _n( 'Compress %s image', 'Compress %s images', $widget['images_remaining'], 'tiny-compress-images' ) ),
							esc_html( number_format_i18n( $widget['images_remaining'] ) )
						);
						?>
					</a>
				</div>
				<p class="tiny-widget-caption">
					<?php
					if ( null !== $widget['remaining_credits'] ) {
						printf(
							/* translators: %1$s: estimated number of credits, %2$s: number of credits left */
							esc_html( _n( 'Uses about %1$s of your %2$s credits. Opens the Bulk Optimizer.', 'Uses about %1$s of your %2$s credits. Opens the Bulk Optimizer.', $widget['estimated_credit_use'], 'tiny-compress-images' ) ),
							esc_html( number_format_i18n( $widget['estimated_credit_use'] ) ),
							esc_html( number_format_i18n( $widget['remaining_credits'] ) )
						);
					} else {
						printf(
							/* translators: %s: estimated number of compressions */
							esc_html( _n( 'Uses about %s compression. Opens the Bulk Optimizer.', 'Uses about %s compressions. Opens the Bulk Optimizer.', $widget['estimated_credit_use'], 'tiny-compress-images' ) ),
							esc_html( number_format_i18n( $widget['estimated_credit_use'] ) )
						);
					}
					?>
				</p>
			<?php } elseif ( 'compressing' === $state ) { ?>
				<div class="tiny-widget-actions">
					<a class="button button-large" href="<?php echo esc_url( $bulk_url ); ?>"><?php esc_html_e( 'View progress', 'tiny-compress-images' ); ?></a>
				</div>
			<?php } elseif ( 'credits_short' === $state ) { ?>
				<div class="tiny-widget-actions">
					<button type="button" class="button button-primary button-large" data-tiny-action="more-credits"><?php esc_html_e( 'Upgrade', 'tiny-compress-images' ); ?></button>
					<?php if ( $widget['images_affordable'] >= 1 ) { ?>
						<a class="button button-large" href="<?php echo esc_url( $start_url ); ?>">
							<?php
							printf(
								/* translators: %s: number of images to compress */
								esc_html( _n( 'Compress %s image', 'Compress %s images', $widget['images_affordable'], 'tiny-compress-images' ) ),
								esc_html( number_format_i18n( $widget['images_affordable'] ) )
							);
							?>
						</a>
					<?php } ?>
				</div>
				<p class="tiny-widget-caption">
					<?php
					if ( $widget['remaining_credits'] <= Tiny_Config::MONTHLY_FREE_COMPRESSIONS ) {
						printf(
							/* translators: %1$s: estimated number of credits, %2$s: number of credits left */
							esc_html( _n( 'This needs about %1$s credit. You have %2$s left this month.', 'This needs about %1$s credits. You have %2$s left this month.', $widget['estimated_credit_use'], 'tiny-compress-images' ) ),
							esc_html( number_format_i18n( $widget['estimated_credit_use'] ) ),
							esc_html( number_format_i18n( $widget['remaining_credits'] ) )
						);
					} else {
						printf(
							/* translators: %1$s: estimated number of credits, %2$s: number of credits left */
							esc_html( _n( 'This needs about %1$s credit. You have %2$s left.', 'This needs about %1$s credits. You have %2$s left.', $widget['estimated_credit_use'], 'tiny-compress-images' ) ),
							esc_html( number_format_i18n( $widget['estimated_credit_use'] ) ),
							esc_html( number_format_i18n( $widget['remaining_credits'] ) )
						);
					}
					?>
				</p>
			<?php } elseif ( 'out_of_credits' === $state ) { ?>
				<div class="tiny-widget-actions">
					<button type="button" class="button button-primary button-large" data-tiny-action="more-credits"><?php esc_html_e( 'Upgrade', 'tiny-compress-images' ); ?></button>
				</div>
				<p class="tiny-widget-caption"><?php esc_html_e( 'You\'ve used all your free credits. Compression continues when they renew next month, or upgrade to keep going.', 'tiny-compress-images' ); ?></p>
			<?php } ?>
		</div>
	</div>

	<?php if ( $has_credits_view ) { ?>
		<div class="tiny-widget-credits" hidden>
			<button type="button" class="tiny-widget-link" data-tiny-action="back">&larr; <?php esc_html_e( 'Back', 'tiny-compress-images' ); ?></button>
			<h3 class="tiny-widget-credits-heading"><?php esc_html_e( 'Get more credits', 'tiny-compress-images' ); ?></h3>
			<div class="tiny-widget-offer">
				<div class="tiny-widget-offer-text">
					<strong><?php esc_html_e( 'Credit bundle', 'tiny-compress-images' ); ?></strong>
					<span><?php esc_html_e( 'Extra credits that never expire.', 'tiny-compress-images' ); ?></span>
				</div>
				<a class="button button-primary button-large" href="<?php echo esc_url( $credits_url ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Buy a credit bundle', 'tiny-compress-images' ); ?> <span aria-hidden="true">&nearr;</span></a>
			</div>
			<div class="tiny-widget-offer">
				<div class="tiny-widget-offer-text">
					<strong><?php esc_html_e( 'Pay per compression', 'tiny-compress-images' ); ?></strong>
					<span><?php esc_html_e( 'No monthly limit. Pay for what you compress.', 'tiny-compress-images' ); ?></span>
				</div>
				<a class="button button-primary button-large" href="<?php echo esc_url( $api_url ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Switch to pay per compression', 'tiny-compress-images' ); ?> <span aria-hidden="true">&nearr;</span></a>
			</div>
		</div>
	<?php } ?>

	<div class="tiny-widget-footer">
		<nav>
			<a href="<?php echo esc_url( $bulk_url ); ?>"><?php esc_html_e( 'Bulk Optimizer', 'tiny-compress-images' ); ?></a>
			<a href="<?php echo esc_url( $settings_url ); ?>"><?php esc_html_e( 'Settings', 'tiny-compress-images' ); ?></a>
		</nav>
		<?php if ( $has_credits_line ) { ?>
			<?php
			if ( 0 === $widget['remaining_credits'] ) {
				$credits_line_class = 'tiny-widget-credits-line tiny-widget-credits-line-error';
			} elseif ( 'credits_short' === $state ) {
				$credits_line_class = 'tiny-widget-credits-line tiny-widget-credits-line-warning';
			} else {
				$credits_line_class = 'tiny-widget-credits-line';
			}
			?>
			<span class="<?php echo esc_attr( $credits_line_class ); ?>">
				<?php
				if ( $widget['remaining_credits'] <= Tiny_Config::MONTHLY_FREE_COMPRESSIONS ) {
					printf(
						/* translators: %1$s: number of credits left, %2$s: monthly free credits */
						esc_html( _n( '%1$s of %2$s free credit left', '%1$s of %2$s free credits left', Tiny_Config::MONTHLY_FREE_COMPRESSIONS, 'tiny-compress-images' ) ),
						esc_html( number_format_i18n( $widget['remaining_credits'] ) ),
						esc_html( number_format_i18n( Tiny_Config::MONTHLY_FREE_COMPRESSIONS ) )
					);
				} else {
					printf(
						/* translators: %s: number of credits left */
						esc_html( _n( '%s credit left', '%s credits left', $widget['remaining_credits'], 'tiny-compress-images' ) ),
						esc_html( number_format_i18n( $widget['remaining_credits'] ) )
					);
				}
				?>
			</span>
		<?php } ?>
	</div>
</div>
