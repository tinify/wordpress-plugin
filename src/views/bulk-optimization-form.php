<div id="bulk-optimization-actions" class="optimization-buttons">
	<?php
	if ( $auto_start_bulk ) {
		$button_start_visibility = '';
		$button_optimizing_visibility = ' visible';
	} else {
		$button_start_visibility = ' visible';
		$button_optimizing_visibility = '';
	}
	submit_button( 'Start Bulk Optimization', 'primary button-large huge' . $button_start_visibility, 'id-start', false );
	submit_button( 'Optimizing' . '...', 'primary button-large huge' . $button_optimizing_visibility, 'id-optimizing', false );
	submit_button( 'Cancel', 'primary button-large huge red', 'id-cancel', false );
	submit_button( 'Cancelling' . '...', 'primary button-large huge red', 'id-cancelling', false );
	?>
	<div id="optimization-spinner" class="spinner"></div>
</div>
