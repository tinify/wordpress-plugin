<?php

/**
 * Dashboard Widget placeholder, replaced by views/dashboard-widget.php once
 * dashboard-widget.js has fetched it.
 */
?>

<div class="tiny-widget-loading" data-nonce="<?php echo esc_attr( wp_create_nonce( 'tiny-dashboard-widget' ) ); ?>">
	<span class="spinner is-active"></span>
	<p hidden><?php esc_html_e( 'The image statistics could not be loaded. Reload the page to try again.', 'tiny-compress-images' ); ?></p>
</div>
