<?php
$tiny_logging_formfield_id   = self::get_prefixed_name( 'enable_logging' );
$tiny_logging_formfield_name = self::get_prefixed_name( 'logging_enabled' );
$tiny_logging_value          = get_option( $tiny_logging_formfield_name );
?>
<table class="form-table tinify-settings">
	<tbody>
		<tr>
			<th><?php esc_html_e( 'Troubleshooting', 'tiny-compress-images' ); ?></th>
			<td>
				<p class="intro">
					<?php esc_html_e( 'Whenever you run into issues, we can help faster if we can see what is happening. Please enable logging for a short period, reproduce the problem, then send us a message at support@tinify.com with the diagnostics file attached.', 'tiny-compress-images' ); ?>
				</p>
				<p class="tiny-check">
					<input type="checkbox" name="<?php echo esc_attr( $tiny_logging_formfield_name ); ?>" id="<?php echo esc_attr( $tiny_logging_formfield_id ); ?>" <?php checked( $tiny_logging_value, 'on', true ); ?>>
					<label for="<?php echo esc_attr( $tiny_logging_formfield_id ); ?>">
						<?php esc_html_e( 'Enable logging', 'tiny-compress-images' ); ?>
					</label>
				</p>
				<div class="tiny-d-flex tiny-mt-2">
					<button class="button" type="button" id="tiny-download-diagnostics">
					<?php esc_html_e( 'Download Diagnostics', 'tiny-compress-images' ); ?>
					</button>
					<div id="download-diagnostics-spinner" class="hidden spinner inline"></div>
				</div>
			</td>
		</tr>
	</tbody>
</table>
