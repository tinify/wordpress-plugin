<?php
/**
 * Onboarding progress: Account, Confirm email, Settings.
 * Without the confirm email step, the steps are numbered by their position.
 *
 * @var int  $current_step
 * @var bool $confirm_email
 */
$tiny_steps = array(
	1 => __( 'Account', 'tiny-compress-images' ),
	2 => __( 'Confirm email', 'tiny-compress-images' ),
	3 => __( 'Settings', 'tiny-compress-images' ),
);
if ( ! $confirm_email ) {
	unset( $tiny_steps[2] );
}
$tiny_number = 0;
?>
<ol class="tiny-onboarding-steps">
	<?php foreach ( $tiny_steps as $tiny_step => $tiny_label ) { ?>
		<?php ++$tiny_number; ?>
		<?php if ( $tiny_step < $current_step ) { ?>
			<li class="tiny-step-done">
				<span class="tiny-step-dot">
					<svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
						<rect width="24" height="24" rx="12" fill="currentColor" fill-opacity="0.09"/>
						<path d="M7 11.75L10.5 15.25L17 8.75" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
				</span>
				<?php echo esc_html( $tiny_label ); ?>
			</li>
		<?php } elseif ( $tiny_step === $current_step ) { ?>
			<li class="tiny-step-current" aria-current="step">
				<span class="tiny-step-dot"><?php echo esc_html( $tiny_number ); ?></span>
				<?php echo esc_html( $tiny_label ); ?>
			</li>
		<?php } else { ?>
			<li>
				<span class="tiny-step-dot"><?php echo esc_html( $tiny_number ); ?></span>
				<?php echo esc_html( $tiny_label ); ?>
			</li>
		<?php } ?>
	<?php } ?>
</ol>
