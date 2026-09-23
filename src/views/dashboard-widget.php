<?php

/**
 * Dashboard Widget
 *
 * @var array  $widget See Tiny_Dashboard::get_widget_data().
 * @var string $email_address
 *
 */
$percentage = $widget['percentage'];
$label      = $widget['label'];

$bulk_url      = admin_url( 'upload.php?page=tiny-bulk-optimization' );
$settings_url  = admin_url( 'options-general.php?page=tinify' );
$upgrade_url   = 'https://tinypng.com/dashboard/api?type=upgrade&mail=' .
	str_replace( '%20', '%2B', rawurlencode( $email_address ) ) .
	'&utm_source=wordpress-plugin&utm_medium=referral&utm_campaign=upgrade&utm_content=dashboard-widget';
$strong        = array( 'strong' => array() );
$database_icon = '<svg width="12" height="12" viewBox="0 0 12 12" aria-hidden="true" focusable="false"><path d="M1.333 6c0 .198.308.542 1.02.88.923.437 2.232.699 3.647.699s2.724-.262 3.646-.7c.713-.337 1.02-.681 1.02-.879V4.629C9.567 5.273 7.885 5.684 6 5.684s-3.567-.411-4.667-1.055V6Zm9.334 1.787C9.567 8.43 7.885 8.842 6 8.842s-3.567-.412-4.667-1.055v1.37c0 .199.308.543 1.02.88.923.438 2.232.7 3.647.7s2.724-.262 3.646-.7c.713-.337 1.02-.681 1.02-.88V7.788ZM0 9.158V2.842C0 1.272 2.686 0 6 0s6 1.272 6 2.842v6.316C12 10.728 9.314 12 6 12S0 10.728 0 9.158Zm6-4.737c1.415 0 2.724-.262 3.646-.7.713-.337 1.02-.681 1.02-.879 0-.198-.307-.542-1.02-.88C8.724 1.526 7.415 1.264 6 1.264s-2.724.262-3.646.7c-.713.337-1.02.681-1.02.879 0 .198.307.542 1.02.88.922.436 2.231.698 3.646.698Z" fill="currentColor" /></svg>';
?>

<div class="tiny-widget">
	<div class="tiny-widget-main">
		<div class="tiny-widget-content">
			<?php if ( 'empty' === $widget['status'] ) { ?>
				<div class="tiny-widget-summary">
					<strong class="tiny-widget-title"><?php esc_html_e( 'George is hungry', 'tiny-compress-images' ); ?></strong>
					<span><?php esc_html_e( 'There are no images uploaded yet.', 'tiny-compress-images' ); ?></span>
				</div>
			<?php } else { ?>
				<?php require __DIR__ . '/progress-circle.php'; ?>
				<div class="tiny-widget-stats">
					<div class="tiny-widget-summary">
						<span class="tiny-widget-title">
							<?php
							if ( 'done' === $widget['status'] ) {
								echo wp_kses( __( '<strong>All</strong> images are optimized', 'tiny-compress-images' ), $strong );
							} else {
								printf(
									/* translators: %s: number of images that can still be optimized */
									wp_kses( _n( '<strong>%s</strong> image needs optimization', '<strong>%s</strong> images need optimization', $widget['images_remaining'], 'tiny-compress-images' ), $strong ),
									esc_html( number_format_i18n( $widget['images_remaining'] ) )
								);
							}
							?>
						</span>
						<div class="tiny-widget-meta">
							<span>
								<?php
								echo $database_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static markup.
								printf(
									/* translators: %s: bytes saved, e.g. 21.7 MB */
									wp_kses( __( '<strong>%s</strong> saved', 'tiny-compress-images' ), $strong ),
									esc_html( size_format( $widget['bytes_saved'], 1 ) )
								);
								?>
							</span>
							<?php if ( null !== $widget['remaining_credits'] ) { ?>
								<span>
									<?php
									echo $database_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static markup.
									printf(
										/* translators: %s: number of compression credits left */
										wp_kses( _n( '<strong>%s</strong> credit', '<strong>%s credits</strong> remaining', $widget['remaining_credits'], 'tiny-compress-images' ), $strong ),
										esc_html( number_format_i18n( $widget['remaining_credits'] ) )
									);
									?>
								</span>
							<?php } ?>
						</div>
					</div>
					<?php if ( 'in_progress' === $widget['status'] ) { ?>
						<a class="tiny-widget-bulk-button" href="<?php echo esc_url( $bulk_url ); ?>">
							<?php esc_html_e( 'Bulk Optimizer', 'tiny-compress-images' ); ?>
							<svg width="9" height="16" viewBox="0 0 9 16" aria-hidden="true" focusable="false">
								<path d="M8.694 8.739 1.775 15.694a1.04 1.04 0 0 1-1.47-1.478L6.489 8 .306 1.784A1.04 1.04 0 0 1 1.777.306l6.919 6.955a1.05 1.05 0 0 1-.002 1.478Z" fill="currentColor" />
							</svg>
						</a>
					<?php } ?>
				</div>
			<?php } ?>
		</div>

		<nav class="tiny-widget-nav">
			<a href="<?php echo esc_url( $bulk_url ); ?>"><?php esc_html_e( 'Optimize Library', 'tiny-compress-images' ); ?></a>
			<a href="<?php echo esc_url( $settings_url ); ?>"><?php esc_html_e( 'Settings', 'tiny-compress-images' ); ?></a>
		</nav>
	</div>

	<div class="tiny-widget-aside">
		<?php if ( 'no_api_key' === $widget['notice'] ) { ?>
			<a class="tiny-widget-notice tiny-widget-notice-api-key" href="<?php echo esc_url( $settings_url ); ?>">
				<?php esc_html_e( 'Please register or provide an API key to start compressing images.', 'tiny-compress-images' ); ?>
			</a>
		<?php } elseif ( 'low_credits' === $widget['notice'] ) { ?>
			<a class="tiny-widget-notice" href="<?php echo esc_url( $upgrade_url ); ?>" target="_blank">
			<?php
				printf(
					wp_kses(
						/* translators: %s: number of remaining credits */
						__( 'You are on a <strong>free plan</strong> with <strong>%s compressions left</strong>.', 'tiny-compress-images' ),
						$strong
					),
					intval( $widget['remaining_credits'] )
				);
			?>
			</a>
		<?php } ?>
		<img
			class="tiny-widget-panda"
			src="<?php echo esc_url( plugins_url( '/images/' . $widget['panda'], __DIR__ ) ); ?>"
			alt="" />
	</div>
</div>
