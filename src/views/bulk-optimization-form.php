<div id="bulk-optimization-actions" class="optimization-buttons" style="<?php echo $show_notice ? 'display:none;' : '' ?>">
	<?php
	$button_start_visibility = ' visible';
	$button_optimizing_visibility = '';
	submit_button( esc_attr__( 'Start Bulk Optimization', 'tiny-compress-images' ), 'button-primary button-hero' . $button_start_visibility, 'id-start', false );
	submit_button( esc_attr__( 'Optimizing', 'tiny-compress-images' ) . '...', 'button-primary button-hero' . $button_optimizing_visibility, 'id-optimizing', false );
	submit_button( esc_attr__( 'Cancel', 'tiny-compress-images' ), 'button-primary button-hero red', 'id-cancel', false );
	submit_button( esc_attr__( 'Cancelling', 'tiny-compress-images' ) . '...', 'button-primary button-hero red', 'id-cancelling', false );
	?>
	<div id="optimization-spinner" class="spinner"></div>
</div>
