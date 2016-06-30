<?php
$available_sizes = array_keys( $this->settings->get_sizes() );
$active_sizes = $this->settings->get_sizes();;
$active_tinify_sizes = $this->settings->get_active_tinify_sizes();
$error = $tiny_image->get_latest_error();
$total = $tiny_image->get_count( array( 'modified', 'missing', 'has_been_compressed', 'compressed' ) );
$active = $tiny_image->get_count( array( 'uncompressed', 'never_compressed' ), $active_tinify_sizes );
$image_statistics = $tiny_image->get_statistics();
$available_unoptimized_sizes = $image_statistics['available_unoptimized_sizes'];
$size_before = $image_statistics['initial_total_size'];
$size_after = $image_statistics['optimized_total_size'];

$size_active = array_fill_keys( $active_tinify_sizes, true );
$size_exists = array_fill_keys( $available_sizes, true );
ksort( $size_exists );

?>
<div class="details-container">
	<div id="tiny-compress-details" class="details" >
		<?php if ( $tiny_image->can_be_compressed() ) { ?>
			<?php if ( $error ) { ?>
				<span class="icon dashicons dashicons-warning error"></span>
			<?php } else if ( $total['missing'] > 0 || $total['modified'] > 0 ) { ?>
				<span class="icon dashicons dashicons-yes alert"></span>
			<?php } else if ( $total['compressed'] > 0 && $available_unoptimized_sizes > 0 ) { ?>
				<span class="icon dashicons dashicons-yes alert"></span>
			<?php } else if ( $total['compressed'] > 0 ) { ?>
				<span class="icon dashicons dashicons-yes success"></span>
			<?php } ?>
			<span class="icon spinner hidden"></span>

			<?php if ( $total['has_been_compressed'] > 0 || (0 == $total['has_been_compressed'] && 0 == $available_unoptimized_sizes) ) { ?>
				<span class="message">
					<strong><?php echo $total['has_been_compressed'] ?></strong>
					<span>
						<?php echo htmlspecialchars( _n( 'size compressed', 'sizes compressed', $total['has_been_compressed'], 'tiny-compress-images' ) ) ?>
					</span>
				</span>
				<br/>
			<?php } ?>

			<?php if ( $available_unoptimized_sizes > 0 ) { ?>
				<span class="message" stlye="color: red" >
					<?php echo htmlspecialchars( sprintf( _n( '%d size to be compressed', '%d sizes to be compressed', $available_unoptimized_sizes, 'tiny-compress-images' ), $available_unoptimized_sizes ) ) ?>
				</span>
				<br />
			<?php } ?>

			<?php if ( $size_before - $size_after ) { ?>
				<span class="message">
					<?php
					printf( esc_html__( 'Total savings %.0f%%', 'tiny-compress-images' ), (1 - $size_after / floatval( $size_before )) * 100 )
					?>
				</span>
				<br />
			<?php } ?>

			<?php if ( $error ) { ?>
				<span class="message error_message">
					<?php echo esc_html__( 'Latest error', 'tiny-compress-images' ) . ': '. esc_html__( $error, 'tiny-compress-images' ) ?>
				</span>
				<br/>
			<?php } ?>

			<a class="thickbox message" href="#TB_inline?width=700&amp;height=500&amp;inlineId=modal_<?php echo $tiny_image->get_id() ?>">Details</a>
		<?php } ?>
	</div>

	<?php if ( $tiny_image->can_be_compressed() && $active['uncompressed'] > 0 ) { ?>
		<button type="button" class="tiny-compress button button-small button-primary" data-id="<?php echo $tiny_image->get_id() ?>">
			<?php echo esc_html__( 'Compress', 'tiny-compress-images' ) ?>
		</button>
	<?php } ?>
</div>

<div class="modal" id="modal_<?php echo $tiny_image->get_id() ?>">
	<div class="tiny-compression-details">
		<h3>
			<?php printf( esc_html__( 'Compression details for %s', 'tiny-compress-images' ), $tiny_image->get_name() ) ?>
		</h3>
		<table>
			<tr>
				<th><?php esc_html_e( 'Size', 'tiny-compress-images' ) ?></th>
				<th><?php esc_html_e( 'Original', 'tiny-compress-images' ) ?></th>
				<th><?php esc_html_e( 'Compressed', 'tiny-compress-images' ) ?></th>
				<th><?php esc_html_e( 'Date', 'tiny-compress-images' ) ?></th>
			</tr>
			<?php $i = 0 ?>
			<?php
			$sizes = $tiny_image->get_image_sizes() + $size_exists;
			foreach ( $sizes as $size_name => $size ) {
				if ( ! is_object( $size ) ) { $size = new Tiny_Image_Size(); }
				?>
				<tr class="<?php echo ($i % 2 == 0) ? 'even' : 'odd' ?>">
					<td><?php
					echo ( Tiny_Image::is_original( $size_name ) ? esc_html__( 'original', 'tiny-compress-images' ) : $size_name ) . ' ';
					if ( ! array_key_exists( $size_name, $active_sizes ) ) {
						echo '<em>' . esc_html__( '(not in use)', 'tiny-compress-images' ) . '</em>';
					} else if ( $size->missing() ) {
						echo '<em>' . esc_html__( '(file removed)', 'tiny-compress-images' ) . '</em>';
					} else if ( $size->modified() ) {
						echo '<em>' . esc_html__( '(modified after compression)', 'tiny-compress-images' ) . '</em>';
					} else if ( $size->resized() ) {
						printf( '<em>' . esc_html__( '(resized to %dx%d)', 'tiny-compress-images' ) . '</em>', $size->meta['output']['width'], $size->meta['output']['height'] );
					}
					?>
					</td>
					<td><?php
					if ( $size->has_been_compressed() ) {
						echo size_format( $size->meta['input']['size'], 1 );
					} else if ( $size->exists() ) {
						echo size_format( $size->filesize(), 1 );
					} else {
						echo '-';
					}
					?></td>
					<?php
					if ( $size->has_been_compressed() ) {
						echo '<td>' . size_format( $size->meta['output']['size'], 1 ) . '</td>';
						echo '<td>' . human_time_diff( $size->end_time( $size_name ) ) . ' ' . esc_html__( 'ago', 'tiny-compress-images' ) .'</td>';
					} else if ( ! $size->exists() ) {
						echo '<td colspan=2><em>' . esc_html__( 'Not present or duplicate', 'tiny-compress-images' ) . '</em></td>';
					} else if ( isset( $size_active[ $size_name ] ) ) {
						echo '<td colspan=2><em>' . esc_html__( 'Not compressed', 'tiny-compress-images' ) . '</em></td>';
					} else if ( isset( $size_exists[ $size_name ] ) ) {
						echo '<td colspan=2><em>' . esc_html__( 'Not configured to be compressed', 'tiny-compress-images' ) . '</em></td>';
					} else if ( ! array_key_exists( $size_name, $active_sizes ) ) {
						echo '<td colspan=2><em>' . esc_html__( 'Size is not in use', 'tiny-compress-images' ) . '</em></td>';
					} else {
						echo '<td>-</td>';
					}
					?>
				</tr>
				<?php $i++ ?>
			<?php } ?>
			<?php if ( $image_statistics['image_sizes_optimized'] > 0 ) { ?>
			<tfoot>
				<tr>
					<td><?php esc_html_e( 'Combined', 'tiny-compress-images' ) ?></td>
					<td><?php echo size_format( $size_before, 1 ) ?></td>
					<td><?php echo size_format( $size_after, 1 ) ?></td>
					<td></td>
				</tr>
			</tfoot>
			<?php } ?>
		</table>
		<?php if ( $size_before && $size_after ) { ?>
			<p>
				<strong>
					<?php
					printf( esc_html__( 'Total savings %.0f%% (%s)', 'tiny-compress-images' ),
						( 1 - $size_after / floatval( $size_before ) ) * 100,
						size_format( $size_before - $size_after, 1 )
					)
					?>
				</strong>
			</p>
		<?php } ?>
	</div>
</div>
