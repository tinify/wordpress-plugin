<?php

$strong = array(
	'strong' => array(),
);

?>

<tr>
	<th scope="row"><?php esc_html_e( 'Original image', 'tiny-compress-images' ); ?></th>
	<td>
		<div class="tiny-resize-unavailable" style="display: none">
			<?php
			esc_html_e(
				'Enable compression of the original image size for more options.',
				'tiny-compress-images'
			);
			?>
		</div>
		<div class="tiny-resize-available">
			<?php
			$resize_original_enabled_id   = self::get_prefixed_name( 'resize_original_enabled' );
			$resize_original_enabled_name = self::get_prefixed_name( 'resize_original[enabled]' );
			$resize_original_enabled      = $this->get_resize_enabled();
			?>
			<input
				type="checkbox"
				id="<?php echo esc_attr( $resize_original_enabled_id ); ?>"
				name="<?php echo esc_attr( $resize_original_enabled_name ); ?>"
				value="on"
				<?php checked( $resize_original_enabled ); ?> />
			<label for="<?php echo esc_attr( $resize_original_enabled_id ); ?>">
				<?php
				esc_html_e(
					'Resize the original image',
					'tiny-compress-images'
				);
				?>
			</label><br>
			<div class="tiny-resize-available tiny-resize-resolution">
				<span>
					<?php
					echo wp_kses(
						__(
							'<strong>Save space</strong> by setting a maximum width and height for all images uploaded.',
							'tiny-compress-images'
						),
						$strong
					);
					?>
					<br>
					<?php
					echo wp_kses(
						__(
							'Resizing takes <strong>1 additional compression</strong> for each image that is larger.',
							'tiny-compress-images'
						),
						$strong
					);
					?>
				</span>

				<div class="tiny-resize-inputs">
					<?php esc_html_e( 'Max Width', 'tiny-compress-images' ); ?>:
					<?php $this->render_resize_input( 'width' ); ?>
					<?php esc_html_e( 'Max Height', 'tiny-compress-images' ); ?>:
					<?php $this->render_resize_input( 'height' ); ?>
				</div>
			</div>

			<?php
			$this->render_preserve_input(
				'image',
				esc_html__(
					'Make a backup of the original image',
					'tiny-compress-images'
				)
			);
			$this->render_preserve_input(
				'creation',
				esc_html__(
					'Preserve creation date and time in the original image',
					'tiny-compress-images'
				)
			);
			$this->render_preserve_input(
				'copyright',
				esc_html__(
					'Preserve copyright information in the original image',
					'tiny-compress-images'
				)
			);
			$this->render_preserve_input(
				'location',
				esc_html__(
					'Preserve GPS location in the original image',
					'tiny-compress-images'
				) . ' ' .
				esc_html__( '(JPEG only)', 'tiny-compress-images' )
			);
			?>
		</div>
	</td>
</tr>