<?php
/**
 * Create WebP or AVIF versions, with the format and the delivery to use.
 *
 * @var bool   $convert      whether WebP or AVIF versions are created
 * @var string $convert_to   smallest, webp or avif
 * @var bool   $server_rules whether Apache can rewrite, which server rules need
 * @var string $delivery     htaccess or picture
 * @var string $convert_name name of the convert format option
 */
?>
<div class="tiny-card-section" id="tiny-modern-formats">
	<div class="tiny-card-head">
		<h2><?php esc_html_e( 'Modern formats', 'tiny-compress-images' ); ?></h2>
	</div>
	<label class="tiny-option">
		<input type="checkbox" role="switch" name="<?php echo esc_attr( $convert_name ); ?>[convert]" value="on" id="tiny-convert"
			<?php checked( $convert ); ?>>
		<span class="tiny-option-text">
			<span class="tiny-option-label"><?php esc_html_e( 'Create WebP or AVIF versions', 'tiny-compress-images' ); ?></span>
			<span class="tiny-option-descr"><?php esc_html_e( 'Smaller files for browsers that support them. Uses 1 extra credit per image size.', 'tiny-compress-images' ); ?></span>
		</span>
	</label>

	<div class="tiny-format-options" id="tiny-format-options" <?php echo $convert ? '' : 'hidden'; ?>>
		<p class="tiny-option-group"><?php esc_html_e( 'Format', 'tiny-compress-images' ); ?></p>
		<label class="tiny-option">
			<input type="radio" name="<?php echo esc_attr( $convert_name ); ?>[convert_to]" value="smallest" <?php checked( $convert_to, 'smallest' ); ?>>
			<span class="tiny-option-text">
				<span class="tiny-option-label"><?php esc_html_e( 'Smallest file per image (recommended)', 'tiny-compress-images' ); ?></span>
				<span><?php esc_html_e( 'Tinify picks WebP or AVIF, whichever is smaller.', 'tiny-compress-images' ); ?></span>
			</span>
		</label>
		<label class="tiny-option">
			<input type="radio" name="<?php echo esc_attr( $convert_name ); ?>[convert_to]" value="webp" <?php checked( $convert_to, 'webp' ); ?>>
			<span class="tiny-option-text">
				<span class="tiny-option-label"><?php esc_html_e( 'WebP', 'tiny-compress-images' ); ?></span>
				<span><?php esc_html_e( 'Works in every modern browser.', 'tiny-compress-images' ); ?></span>
			</span>
		</label>
		<label class="tiny-option">
			<input type="radio" name="<?php echo esc_attr( $convert_name ); ?>[convert_to]" value="avif" <?php checked( $convert_to, 'avif' ); ?>>
			<span class="tiny-option-text">
				<span class="tiny-option-label"><?php esc_html_e( 'AVIF', 'tiny-compress-images' ); ?></span>
				<span><?php esc_html_e( 'The smallest files, but not every browser shows them.', 'tiny-compress-images' ); ?></span>
			</span>
		</label>

		<?php if ( $server_rules ) { ?>
			<p class="tiny-option-group"><?php esc_html_e( 'Delivery', 'tiny-compress-images' ); ?></p>
			<label class="tiny-option">
				<input type="radio" name="<?php echo esc_attr( $convert_name ); ?>[delivery_method]" value="htaccess" <?php checked( $delivery, 'htaccess' ); ?>>
				<span class="tiny-option-text">
					<span class="tiny-option-label"><?php esc_html_e( 'Server rules (recommended)', 'tiny-compress-images' ); ?></span>
					<span><?php esc_html_e( 'Adds rules to .htaccess so browsers get the new format automatically.', 'tiny-compress-images' ); ?></span>
				</span>
			</label>
			<label class="tiny-option">
				<input type="radio" name="<?php echo esc_attr( $convert_name ); ?>[delivery_method]" value="picture" <?php checked( $delivery, 'picture' ); ?>>
				<span class="tiny-option-text">
					<span class="tiny-option-label"><?php esc_html_e( 'Picture element', 'tiny-compress-images' ); ?></span>
					<span><?php esc_html_e( 'Uses <picture> tags in your pages. Works on any server.', 'tiny-compress-images' ); ?></span>
				</span>
			</label>
		<?php } ?>

		<p class="tiny-format-note">
			<?php esc_html_e( 'Existing images get the new formats from the Bulk Optimizer.', 'tiny-compress-images' ); ?>
		</p>
	</div>
</div>
