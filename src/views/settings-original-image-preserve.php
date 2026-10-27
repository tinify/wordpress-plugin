<label class="tiny-option tiny-check">
	<input type="checkbox"
		id="<?php echo esc_attr( $data['id'] ); ?>"
		name="<?php echo esc_attr( $data['field'] ); ?>"
		<?php checked( $data['checked'] ); ?>
		value="on"
	/>
	<span class="tiny-option-label"><?php echo esc_html( $data['label'] ); ?></span>
</label>
