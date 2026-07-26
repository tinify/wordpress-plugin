<?php

/**
 * Details on the backup of the image
 *
 * @var Tiny_Plugin $this       The plugin instance.
 * @var Tiny_Image  $tiny_image The image being compressed.
 */

$backup_enabled = $this->settings->get_backup_enabled();

?>
<?php
if ( $backup_enabled ) {
	$backup   = $tiny_image->get_backup();
	$modal_id = 'modal_' . absint( $tiny_image->get_id() ) . '_backup';
	?>
	<?php if ( $backup ) { ?>
		<p><?php echo esc_html_e( 'The original upload has been backed up.', 'tiny-compress-images' ); ?></p>
			
		<p>
			<a href="<?php echo esc_attr( $backup ); ?>" target="_blank">
				<?php esc_html_e( 'View uncompressed file' ); ?>
			</a>
			&nbsp;
			<a class="button button-small" href="#" data-dialog-id="<?php echo esc_attr( $modal_id ); ?>" data-id="<?php echo absint( $tiny_image->get_id() ); ?>">
				<?php esc_html_e( 'Restore Backup', 'tiny-compress-images' ); ?>
			</a>
			<dialog id="<?php echo esc_attr( $modal_id ); ?>" class="tiny-dialog">
				<strong class="tiny-dialog-title"><?php esc_html_e( 'Are you sure you want to restore the original uncompressed image?', 'tiny-compress-images' ); ?></strong>
				<p><?php esc_html_e( 'This action will replace all the compressed images with uncompressed images', 'tiny-compress-images' ); ?></p>
				<p class="tiny-dialog-error" hidden></p>

				<div class="tiny-dialog-actions">
					<span class="spinner"></span>
					<button value="cancel" commandfor="<?php echo esc_attr( $modal_id ); ?>" command="close" class="button">
						<?php esc_html_e( 'Cancel', 'tiny-compress-images' ); ?>
					</button>
					<button value="submit" commandfor="<?php echo esc_attr( $modal_id ); ?>" command="request-close" class="button button-primary" autofocus>
						<?php esc_html_e( 'Yes, Restore', 'tiny-compress-images' ); ?>
					</button>
				</div>
			</dialog>
		<?php } else { ?>
			<span>
				<?php esc_html_e( 'No backup available', 'tiny-compress-images' ); ?>
			</span>
		<?php } ?>
		</p>
	<?php } ?>