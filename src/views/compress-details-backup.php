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
if ($backup_enabled) {
	$backup   = $tiny_image->get_backup();
	$modal_id = 'modal_' . absint($tiny_image->get_id()) . '_backup';
?>
	<?php if ($backup) { ?>
		<div class="tiny-card">
			<p><?php esc_html_e('The original upload has been backed up.', 'tiny-compress-images'); ?><br />
				<a href="<?php echo esc_url($backup); ?>" target="_blank">
					<?php esc_html_e('View uncompressed file', 'tiny-compress-images'); ?>
				</a>
			</p>
			<a class="button" href="#" data-dialog-id="<?php echo esc_attr($modal_id); ?>" data-id="<?php echo absint($tiny_image->get_id()); ?>">
				<svg class="tiny-icon-backup" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 20 20" aria-hidden="true" focusable="false"><path d="M13.65 2.88c3.93 2.01 5.48 6.84 3.47 10.77s-6.83 5.48-10.77 3.47a7.94 7.94 0 0 1-3.86-4.4l1.64-1.03a6.13 6.13 0 0 0 3.08 3.76c3.01 1.54 6.69.35 8.23-2.66A6.114 6.114 0 1 0 4.56 7.21l1.88.97-4.95 3.08-.39-5.82 1.78.91C4.9 2.4 9.75.89 13.65 2.88m-4.36 7.83A1 1 0 0 1 9 10c0-.07.03-.12.04-.19h-.01L10 5l.97 4.81L14 13l-4.5-2.12.02-.02c-.08-.04-.16-.09-.23-.15"/></svg>
				<?php esc_html_e('Restore Backup', 'tiny-compress-images'); ?>
			</a>
			<dialog id="<?php echo esc_attr($modal_id); ?>" class="tiny-dialog">
				<strong class="tiny-dialog-title"><?php esc_html_e('Restore Backup', 'tiny-compress-images'); ?></strong>
				<p><?php esc_html_e('This will restore the originally uncompressed image.', 'tiny-compress-images'); ?></p>
				<p class="tiny-dialog-error" hidden></p>

				<div class="tiny-dialog-actions">
					<span class="spinner"></span>
					<button value="cancel" commandfor="<?php echo esc_attr($modal_id); ?>" command="close" class="button">
						<?php esc_html_e('Cancel', 'tiny-compress-images'); ?>
					</button>
					<button type="button" value="submit" class="button button-primary" autofocus>
						<?php esc_html_e('Restore', 'tiny-compress-images'); ?>
					</button>
				</div>
			</dialog>
		<?php } else { ?>
			<span>
				<?php esc_html_e('No backup available', 'tiny-compress-images'); ?>
			</span>
		<?php } ?>
		</div>
	<?php } ?>