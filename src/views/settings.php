<div class="wrap">
	<h1><?php esc_html_e( 'TinyPNG - JPEG, PNG & WebP image compression', 'tiny-compress-images' ) ?></h2>
	<p><?php esc_html_e( 'Speed up your website. Optimize your JPEG, PNG, and WebP images automatically with TinyPNG.', 'tiny-compress-images' ) ?></p>
	<div class="tiny-compress-images">
		<span id="tiny-compress-images"></span>
		<form action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>" id="tinify-settings" method="post">
			<?php settings_fields( 'tinify' ) ?>
			<table class="form-table tinify-settings">
				<tbody>
					<tr>
						<th scope="row"><?php esc_html_e( 'Tinify account', 'tiny-compress-images' ) ?></th>
						<td>
							<?php $this->render_pending_status() ?>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'New image uploads', 'tiny-compress-images' ) ?></th>
						<td>
							<?php $this->render_compression_timing_settings() ?>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Image sizes', 'tiny-compress-images' ) ?></th>
						<td>
							<h4><?php esc_html_e( 'Select image sizes to be compressed', 'tiny-compress-images' ) ?></h4>
							<p class="intro">
								<?php
								esc_html_e(
									'Wordpress generates resized versions of every image. Choose which sizes to compress.',
									'tiny-compress-images'
								)
								?>
							</p>
							<div class="sizes">
								<?php $this->render_sizes() ?>
							</div>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Conversion', 'tiny-compress-images' ) ?></th>
						<td>
							<h4><?php esc_html_e( 'Convert files to different formats', 'tiny-compress-images' ) ?></h4>
							<p class="intro">
								<?php
								esc_html_e(
									'Generate optimized formats like WebP or AVIF. These file types will improve site performance but might take up more disk space. Creating an optimized image will take 1 additional compression for each image size.',
									'tiny-compress-images'
								)
								?>
							</p>
							<?php $this->render_format_conversion(); ?>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Original image', 'tiny-compress-images' ) ?></th>
						<td>
							<?php $this->render_resize() ?>
						</td>
					</tr>
				</tbody>
			</table>
			<p><?php echo Tiny_Plugin::request_review();?></p>
			<p class="submit">
				<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_html_e( 'Save Changes', 'tiny-compress-images' ) ?>">
			</p>
		</form>
	</div>
</div>
