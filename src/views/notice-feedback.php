<?php
$review_url   = 'https://wordpress.org/support/plugin/tiny-compress-images/reviews/#new-post';
$review_block = sprintf(
	'<a href="%s" target="_blank">%s</span></a>',
	esc_url( $review_url ),
	esc_html__( 'Leave a review', 'tiny-compress-images' )
);
?>

<div class="notice notice-success is-dismissible tiny-notice" data-name="feedback">
	<p>
		<strong><?php _e( 'Enjoying TinyPNG?', 'tiny-compress-images' ); ?></strong>
		<span><?php _e( 'Take 30 seconds to let us know what you think!', 'tiny-compress-images' ); ?></span>
		<?php echo $review_block; ?>
	<a href="#" class="tiny-dismiss"></a></p>
</div>