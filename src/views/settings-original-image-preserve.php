<p class="tiny-preserve">
	<input type="checkbox"
		id="<?php echo esc_attr( $data['id'] ); ?>"
		name="<?php echo esc_attr( $data['field'] ); ?>"
		<?php checked( $data['checked'] ); ?> 
		value="on"
	/>
	<label for="<?php echo esc_attr( $data['id'] ); ?>">
		<?php echo esc_html( $data['label'] ); ?>
	</label>
</p>