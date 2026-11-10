<?php
/**
 * The top of the bulk optimization page for one state.
 *
 * @var string $hero       The state this hero is for.
 * @var string $state      The state the page loaded in; other heroes start hidden.
 * @var array  $widget     See Tiny_Dashboard::get_widget_data().
 * @var array  $stats      See Tiny_Bulk_Optimization::get_optimization_statistics().
 * @var array  $items      The images that are waiting.
 * @var bool   $can_manage Whether the user can change settings.
 */

$settings_url = admin_url( 'options-general.php?page=tinify' );
$credits_url  = 'https://tinify.com/checkout/credits?utm_source=wordpress-plugin&utm_medium=referral&utm_campaign=upgrade&utm_content=bulk-optimization-credits';
$remaining    = $widget['images_remaining'];

$badge_and_panda = Tiny_Dashboard::get_state_badges_and_pandas()[ $hero ];
$badge_colour    = $badge_and_panda['badge_colour'];
$badge_text      = $badge_and_panda['badge_text'];
$panda           = $badge_and_panda['panda'];
?>
<div class="tiny-card-section tiny-bulk-hero" data-tiny-bulk-hero="<?php echo esc_attr( $hero ); ?>" <?php echo $hero !== $state ? 'hidden' : ''; ?>>
	<img class="tiny-bulk-panda"
		src="<?php echo esc_url( plugins_url( '/images/widget/' . $panda . '.png', __DIR__ ) ); ?>"
		width="128" height="128" alt="" />
	<span class="tiny-bulk-badge tiny-bulk-badge-<?php echo esc_attr( $badge_colour ); ?>"><?php echo esc_html( $badge_text ); ?></span>

	<?php if ( 'not_connected' === $hero ) { ?>
		<h2 class="tiny-bulk-title">
			<?php
			if ( $remaining > 0 ) {
				printf(
					/* translators: %s: number of images */
					esc_html( _n( 'Connect Tinify to compress %s image', 'Connect Tinify to compress %s images', $remaining, 'tiny-compress-images' ) ),
					esc_html( number_format_i18n( $remaining ) )
				);
			} else {
				esc_html_e( 'Connect Tinify', 'tiny-compress-images' );
			}
			?>
		</h2>
		<p>
			<?php
			printf(
				/* translators: %s: number of free credits per month */
				esc_html__( 'Connect a free Tinify account to start. Free accounts get %s credits every month.', 'tiny-compress-images' ),
				esc_html( number_format_i18n( Tiny_Config::MONTHLY_FREE_COMPRESSIONS ) )
			);
			?>
		</p>
		<?php if ( $can_manage ) { ?>
			<p class="tiny-bulk-actions">
				<a class="button button-primary button-hero" href="<?php echo esc_url( admin_url( 'options-general.php?page=' . Tiny_Onboarding::PAGE_SLUG . '&step=1' ) ); ?>"><?php esc_html_e( 'Connect Tinify', 'tiny-compress-images' ); ?></a>
			</p>
		<?php } ?>

	<?php } elseif ( 'pending' === $hero ) { ?>
		<h2 class="tiny-bulk-title"><?php esc_html_e( 'Check your inbox', 'tiny-compress-images' ); ?></h2>
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
		<?php if ( $can_manage ) { ?>
			<p class="tiny-bulk-actions">
				<a href="<?php echo esc_url( $settings_url ); ?>"><?php esc_html_e( 'Use an API key instead', 'tiny-compress-images' ); ?></a>
			</p>
		<?php } ?>

	<?php } elseif ( 'empty' === $hero ) { ?>
		<h2 class="tiny-bulk-title"><?php esc_html_e( 'No images yet', 'tiny-compress-images' ); ?></h2>
		<p>
			<?php
			if ( $widget['auto_compress'] ) {
				esc_html_e( 'Upload images to your Media Library and Tinify compresses them automatically.', 'tiny-compress-images' );
			} else {
				esc_html_e( 'Upload images to your Media Library, then compress them with the Bulk Optimizer.', 'tiny-compress-images' );
			}
			?>
		</p>
		<p class="tiny-bulk-actions">
			<a class="button button-primary button-hero" href="<?php echo esc_url( admin_url( 'media-new.php' ) ); ?>"><?php esc_html_e( 'Upload images', 'tiny-compress-images' ); ?></a>
		</p>

	<?php } elseif ( 'no_sizes' === $hero ) { ?>
		<h2 class="tiny-bulk-title"><?php esc_html_e( 'No image sizes selected', 'tiny-compress-images' ); ?></h2>
		<p><?php esc_html_e( 'Based on your current settings, nothing will be optimized. There are no active sizes selected for optimization.', 'tiny-compress-images' ); ?></p>
		<?php if ( $can_manage ) { ?>
			<p class="tiny-bulk-actions">
				<a class="button button-primary button-hero" href="<?php echo esc_url( $settings_url ); ?>"><?php esc_html_e( 'Change settings', 'tiny-compress-images' ); ?></a>
			</p>
		<?php } ?>

	<?php } elseif ( in_array( $hero, array( 'ready', 'credits_short', 'out_of_credits' ), true ) ) { ?>
		<h2 class="tiny-bulk-title">
			<?php
			printf(
				/* translators: %s: number of images to compress */
				esc_html( _n( '%s image to compress', '%s images to compress', $remaining, 'tiny-compress-images' ) ),
				esc_html( number_format_i18n( $remaining ) )
			);
			?>
		</h2>
		<?php if ( 'ready' === $hero && $stats['display-percentage'] > 0 ) { ?>
			<p>
				<?php
				$waiting_bytes = array_sum( wp_list_pluck( $items, 'bytes' ) );
				printf(
					/* translators: %s: estimated bytes saved, e.g. 125 MB */
					esc_html__( 'Based on your library so far, this saves about %s.', 'tiny-compress-images' ),
					esc_html( size_format( $waiting_bytes * $stats['display-percentage'] / 100, 0 ) )
				);
				?>
			</p>
		<?php } ?>
		<p class="tiny-bulk-actions">
			<?php if ( 'ready' === $hero ) { ?>
				<button type="button" class="button button-primary button-hero" data-tiny-bulk="start">
					<?php
					printf(
						/* translators: %s: number of images to compress */
						esc_html( _n( 'Compress %s image', 'Compress %s images', $remaining, 'tiny-compress-images' ) ),
						esc_html( number_format_i18n( $remaining ) )
					);
					?>
				</button>
			<?php } else { ?>
				<a class="button button-primary button-hero" href="<?php echo esc_url( $credits_url ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Upgrade', 'tiny-compress-images' ); ?></a>
				<?php if ( 'credits_short' === $hero && $widget['images_affordable'] >= 1 ) { ?>
					<button type="button" class="button button-hero" data-tiny-bulk="start">
						<?php
						printf(
							/* translators: %s: number of images to compress */
							esc_html( _n( 'Compress %s image', 'Compress %s images', $widget['images_affordable'], 'tiny-compress-images' ) ),
							esc_html( number_format_i18n( $widget['images_affordable'] ) )
						);
						?>
					</button>
				<?php } ?>
			<?php } ?>
		</p>
		<p class="tiny-bulk-caption">
			<?php
			if ( 'out_of_credits' === $hero ) {
				esc_html_e( 'You\'ve used all your free credits. Compression continues when they renew next month, or upgrade to keep going.', 'tiny-compress-images' );
			} elseif ( 'credits_short' === $hero && $widget['remaining_credits'] <= Tiny_Config::MONTHLY_FREE_COMPRESSIONS ) {
				printf(
					/* translators: %1$s: estimated number of credits, %2$s: number of credits left */
					esc_html( _n( 'This needs about %1$s credit. You have %2$s left this month.', 'This needs about %1$s credits. You have %2$s left this month.', $widget['estimated_credit_use'], 'tiny-compress-images' ) ),
					esc_html( number_format_i18n( $widget['estimated_credit_use'] ) ),
					esc_html( number_format_i18n( $widget['remaining_credits'] ) )
				);
			} elseif ( 'credits_short' === $hero ) {
				printf(
					/* translators: %1$s: estimated number of credits, %2$s: number of credits left */
					esc_html( _n( 'This needs about %1$s credit. You have %2$s left.', 'This needs about %1$s credits. You have %2$s left.', $widget['estimated_credit_use'], 'tiny-compress-images' ) ),
					esc_html( number_format_i18n( $widget['estimated_credit_use'] ) ),
					esc_html( number_format_i18n( $widget['remaining_credits'] ) )
				);
			} elseif ( null !== $widget['remaining_credits'] ) {
				printf(
					/* translators: %1$s: estimated number of credits, %2$s: number of credits left */
					esc_html__( 'Uses about %1$s of your %2$s credits.', 'tiny-compress-images' ),
					esc_html( number_format_i18n( $widget['estimated_credit_use'] ) ),
					esc_html( number_format_i18n( $widget['remaining_credits'] ) )
				);
			} else {
				printf(
					/* translators: %s: estimated number of compressions */
					esc_html( _n( 'Uses about %s compression.', 'Uses about %s compressions.', $widget['estimated_credit_use'], 'tiny-compress-images' ) ),
					esc_html( number_format_i18n( $widget['estimated_credit_use'] ) )
				);
			}
			?>
		</p>

	<?php } elseif ( 'compressing' === $hero ) { ?>
		<h2 class="tiny-bulk-title">
			<?php
			printf(
				/* translators: %1$s: number of images compressed so far, %2$s: number of images to compress */
				esc_html( _n( 'Compressing %1$s of %2$s image', 'Compressing %1$s of %2$s images', count( $items ), 'tiny-compress-images' ) ),
				'<span data-tiny-bulk="run-done">0</span>',
				esc_html( number_format_i18n( count( $items ) ) )
			);
			?>
		</h2>
		<div class="tiny-bulk-progress">
			<div class="tiny-bulk-bar">
				<span data-tiny-bulk="run-bar" style="width: 0%"></span>
			</div>
			<span data-tiny-bulk="run-percentage">0%</span>
		</div>
		<p data-tiny-bulk="run-status"></p>
		<p class="tiny-bulk-actions">
			<button type="button" class="button button-hero" data-tiny-bulk="pause"><?php esc_html_e( 'Pause', 'tiny-compress-images' ); ?></button>
		</p>
		<p class="tiny-bulk-caption" data-tiny-bulk="run-notice"></p>

	<?php } elseif ( 'done' === $hero ) { ?>
		<h2 class="tiny-bulk-title">
			<?php
			printf(
				/* translators: %s: number of images */
				esc_html( _n( 'All %s image is compressed', 'All %s images are compressed', $widget['images_total'], 'tiny-compress-images' ) ),
				esc_html( number_format_i18n( $widget['images_total'] ) )
			);
			?>
		</h2>
		<p>
			<?php
			printf(
				/* translators: %s: bytes saved, e.g. 537 MB */
				esc_html__( 'Your library is %s smaller.', 'tiny-compress-images' ),
				'<span data-tiny-bulk="done-saved">' . esc_html( size_format( $widget['bytes_saved'], 0 ) ) . '</span>'
			);
			if ( $widget['auto_compress'] ) {
				echo ' ';
				esc_html_e( 'New uploads are compressed automatically.', 'tiny-compress-images' );
			}
			?>
		</p>
	<?php } ?>
</div>
