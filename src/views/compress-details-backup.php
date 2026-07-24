<?php
/**
 * Details on the backup of the image
 *
 * @var Tiny_Plugin $this               The plugin instance.
 * @var Tiny_Image  $tiny_image         The image being compressed.
 * @var int[]       $images_to_compress The IDs that are being compressed
 */

$available_sizes              = array_keys( $this->settings->get_sizes() );
$conversion_enabled           = $this->settings->get_conversion_enabled();
$active_sizes                 = $this->settings->get_sizes();
$backup_enabled				  = $this->settings->get_backup_enabled();
$active_tinify_sizes          = $this->settings->get_active_tinify_sizes();
$error                        = $tiny_image->get_latest_error();
$total                        = $tiny_image->get_count( array( 'modified', 'missing', 'has_been_compressed', 'compressed', 'has_been_converted' ) );
$active                       = $tiny_image->get_count( array( 'uncompressed', 'never_compressed', 'unconverted' ), $active_tinify_sizes );
$image_statistics             = $tiny_image->get_statistics( $active_sizes, $active_tinify_sizes );
$available_uncompressed_sizes = $image_statistics['available_uncompressed_sizes'];
$size_before                  = $image_statistics['initial_total_size'];
$size_after                   = $image_statistics['compressed_total_size'];

$size_active = array_fill_keys( $active_tinify_sizes, true );
$size_exists = array_fill_keys( $available_sizes, true );
ksort( $size_exists );

?>
<?php if ($backup_enabled) {
	$backup = $tiny_image->get_backup();
	$modal_id = "modal_" . absint( $tiny_image->get_id() ) . "_backup";
?>
	<p>
		<?php if ($backup) { ?>
			<a href="<?php echo esc_attr( $backup ); ?>" target="_blank">
				<?php esc_html_e('View Uncompressed'); ?>
			</a>
			<a href="#" data-dialog-id="<?php echo esc_attr($modal_id); ?>" data-id="<?php echo absint( $tiny_image->get_id() ); ?>">
				<?php esc_html_e('Restore Backup', 'tiny-compress-images'); ?>
			</a>
			<dialog id="<?php echo esc_attr($modal_id); ?>" class="tiny-dialog">
				<p><?php esc_html_e( 'Are you sure you want to restore the original uncompressed image?', 'tiny-compress-images' ); ?></p>
				
				<form method="dialog" class="tiny-dialog-actions">
					<button value="cancel" class="button">
						<?php esc_html_e( 'Cancel', 'tiny-compress-images' ); ?>
					</button>
					<button value="confirm" class="button button-primary">
						<?php esc_html_e( 'Yes, Restore', 'tiny-compress-images' ); ?>
					</button>
				</form>
			</dialog>
		<?php } else { ?>
			<span>
				<?php esc_html_e('No backup available', 'tiny-compress-images'); ?>
			</span>
		<?php } ?>
	</p>
<?php } ?>