<?php
/**
 * The ways to get more credits. Upgrade slides to this view, Back slides to
 * the main view again. See admin.js toggleMoreCredits.
 *
 * @var string $upgrade_from Where the upgrade starts, for the checkout's utm_content.
 */

$credits_url = 'https://tinify.com/checkout/credits?utm_source=wordpress-plugin&utm_medium=referral&utm_campaign=upgrade&utm_content=' . $upgrade_from . '-credits';
$api_url     = 'https://tinify.com/checkout/api?utm_source=wordpress-plugin&utm_medium=referral&utm_campaign=upgrade&utm_content=' . $upgrade_from . '-api';
?>
<div class="tiny-more-credits" data-tiny-view="credits" hidden>
	<button type="button" class="tiny-more-credits-back" data-tiny-action="back">&larr; <?php esc_html_e( 'Back', 'tiny-compress-images' ); ?></button>
	<h3 class="tiny-more-credits-heading"><?php esc_html_e( 'Get more credits', 'tiny-compress-images' ); ?></h3>
	<div class="tiny-more-credits-offer">
		<div class="tiny-more-credits-offer-text">
			<strong><?php esc_html_e( 'Credit bundle', 'tiny-compress-images' ); ?></strong>
			<span><?php esc_html_e( 'Extra credits that never expire.', 'tiny-compress-images' ); ?></span>
		</div>
		<a class="button button-primary button-large" href="<?php echo esc_url( $credits_url ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Buy a credit bundle', 'tiny-compress-images' ); ?> <span aria-hidden="true">&nearr;</span></a>
	</div>
	<div class="tiny-more-credits-offer">
		<div class="tiny-more-credits-offer-text">
			<strong><?php esc_html_e( 'Pay per compression', 'tiny-compress-images' ); ?></strong>
			<span>
				<?php
				printf(
					/* translators: %s: number of free credits per month */
					esc_html__( 'Keep %s free credits a month, then pay for what you compress.', 'tiny-compress-images' ),
					esc_html( number_format_i18n( Tiny_Config::MONTHLY_FREE_COMPRESSIONS ) )
				);
				?>
			</span>
		</div>
		<a class="button button-primary button-large" href="<?php echo esc_url( $api_url ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Switch to pay per compression', 'tiny-compress-images' ); ?> <span aria-hidden="true">&nearr;</span></a>
	</div>
</div>
