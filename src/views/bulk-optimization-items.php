<?php
/**
 * Rows shown in the bulk optimization table.
 *
 * @var array $items See 'available-for-optimization' in
 *                   Tiny_Bulk_Optimization::get_optimization_statistics().
 */

foreach ( $items as $index => $item ) {
	$size_count = sprintf(
		/* translators: %s: number of image sizes */
		_n( '%s size', '%s sizes', $item['sizes'], 'tiny-compress-images' ),
		number_format_i18n( $item['sizes'] )
	);
	$file_size        = $item['bytes'] > 0 ? size_format( $item['bytes'], 1 ) : '0 B';
	$attachment_descr = $item['file'] . ', ' . $size_count . ', ' . $file_size;
	?>

	<li class="tiny-bulk-item" data-status="waiting" data-id="<?php echo esc_attr( $item['ID'] ); ?>" data-sizes="<?php echo esc_attr( $item['sizes'] ); ?>">
		<div class="tiny-bulk-item-name">
			<strong><?php echo esc_html( $item['post_title'] ); ?></strong>
			<span><?php echo esc_html( $attachment_descr ); ?></span>
		</div>
		<span class="tiny-bulk-item-status" data-testid="bulk-item-status-<?php echo esc_attr( $index ); ?>"><?php esc_html_e( 'Waiting', 'tiny-compress-images' ); ?></span>
	</li>

	<?php
}
