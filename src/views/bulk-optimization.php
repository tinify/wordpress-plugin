<?php
require_once dirname( __FILE__ ) . '/bulk-optimization-chart.php';
?>

<div class="wrap tiny-bulk-optimization tiny-compress-images" id="tiny-bulk-optimization">
<?php echo '<h1>' . __( 'Bulk Optimization', 'tiny-compress-images' ) . '</h2>' ?>

	<div class="overview whitebox">
		<div class="statistics">
			<h3><?php echo __( 'Available images for optimization', 'tiny-compress-images' ) ?></h3>

			<p>
				<?php
				if ( 0 == $stats['optimized-image-sizes'] + $stats['available-unoptimised-sizes'] ) {
					echo __( 'This page is designed to bulk compress all your images. There don\'t seem to be any available.' );
				} else {
					$percentage = round( $stats['optimized-image-sizes'] / ($stats['optimized-image-sizes'] + $stats['available-unoptimised-sizes']) * 100, 2 );
					if ( $percentage == 100 ) {
						echo __( 'Great! Your entire library is optimimized!' );
						// TODO: If we have 0 active sizes, show a different message.
					} else if ( $stats['optimized-image-sizes'] > 0 ) {
						echo __( 'You are doing great!' );
						echo ' ';
						printf( esc_html__( '%d%% of your image library is optimized.', 'tiny-compress-images' ), $percentage );
						echo ' ';
						printf( esc_html__( 'Start the bulk optimization to optimize the remainder of your library.', 'tiny-compress-images' ) );
					} else {
						echo __( 'Here you can start optimizing your entire library. Press the green button to start improving your website speed instantly!' );
					}
				}
				?>
			</p>

			<div class="totals">
				<div class="item">
					<h3>
						<?php echo __( 'Uploaded', 'tiny-compress-images' ) ?>
						<br>
						<?php echo __( 'images', 'tiny-compress-images' ) ?>
					</h3>
					<span id="uploaded-images">
						<?php echo $stats['uploaded-images']; ?>
					</span>
				</div>
				<div class="item">
					<h3>
						<?php echo __( 'Uncompressed', 'tiny-compress-images' ) ?>
						<br>
						<?php echo __( 'image sizes', 'tiny-compress-images' ) ?>
					</h3>
					<span id="optimizable-image-sizes">
						<?php echo $stats['available-unoptimised-sizes'] ?>
					</span>
					<div class="tooltip">
						<span class="dashicons dashicons-info"></span>
						<div class="tip">
							<?php if ( $stats['uploaded-images'] > 0 && sizeof( $active_tinify_sizes ) > 0 && $stats['available-unoptimised-sizes'] > 0 ) { ?>
								<p>
									<?php
									printf(esc_html__('With your current settings you can still optimize %d images sizes from your %d uploaded JPEG and PNG images.',
									'tiny-compress-images'), $stats['available-unoptimised-sizes'], $stats['uploaded-images']);
									?>
								</p>
							<?php } ?>
							<p>
								<?php
								if ( 0 == sizeof( $active_tinify_sizes ) ) {
									echo __( 'Based on your current settings, nothing will be optimized. There are no active sizes selected for optimization.' );
								} else {
									echo __( 'These sizes are currently activated for compression:' );
									echo '<ul>';
									for ( $i = 0; $i < sizeof( $active_tinify_sizes ); ++$i ) {
										$name = $active_tinify_sizes[ $i ];
										if ( '0' == $name ) {
											echo '<li>- ' . __( 'original' ) . '</li>';
										} else {
											echo '<li>- ' . $name . '</li>';
										}
									}
									echo '</ul>';
								}
								?>
							</p>
						</div>
					</div>
				</div>
				<div class="item">
					<h3>
						<?php echo __( 'Estimated', 'tiny-compress-images' ) ?>
						<br>
						<?php echo __( 'cost', 'tiny-compress-images' ) ?>
					</h3>
					<span id="estimated-cost">$ <?php echo number_format( $estimated_costs, 2 ) ?></span>
					USD
				</div>
			</div>
			<div class="notes">
				<p><?php echo __( 'Remember' ) ?></p>
				<p>
					<?php echo __( 'For the plugin to do the work, you need to keep this page open. But no worries: when stopped, you can continue where you left off!' ); ?>
				</p>
			</div>
		</div>

		<div class="savings">
			<h3><?php echo __( 'Total Savings' ) ?></h3>
			<p>
				<?php echo __( 'Statistics based on all available JPEG and PNG images in your media library.' ); ?>
			</p>
			<?php
				render_percentage_chart( $stats['optimized-library-size'], $stats['unoptimized-library-size'] );
			?>
			<table class="savings-numbers">
				<tr>
					<td id="optimized-image-sizes" class="green">
						<?php echo $stats['optimized-image-sizes']; ?>
					</td>
					<td>
						<?php echo _n( 'image size optimized', 'image sizes optimized', $stats['optimized-image-sizes'], 'tiny-compress-images' ) ?>
					</td>
				</tr>
				<tr>
					<td id="unoptimized-library-size" data-bytes="<?php echo $stats['unoptimized-library-size']; ?>" >
						<?php echo ($stats['unoptimized-library-size'] ? size_format( $stats['unoptimized-library-size'], 2 ) : '-'); ?>
					</td>
					<td>
						<?php echo __( 'initial size', 'tiny-compress-images' ) ?>
					</td>
				</tr>
				<tr>
					<td id="optimized-library-size" data-bytes="<?php echo $stats['optimized-library-size'] ?>" class="green">
						<?php echo ($stats['optimized-library-size'] ? size_format( $stats['optimized-library-size'], 2 ) : '-') ?>
					</td>
					<td>
						<?php echo __( 'current size', 'tiny-compress-images' ) ?>
					</td>
				</tr>
			</table>
		</div>

		<div class="optimize">
			<?php if ( true ) { ?>
				<div class="progressbar" id="compression-progress" data-amount-to-optimize="<?php echo $stats['optimized-image-sizes'] + $stats['available-unoptimised-sizes'] ?>" data-amount-optimized="0">
					<div class="progressbar-progress"></div>
					<span id="optimized-so-far">
						<?php echo $stats['optimized-image-sizes'] ?>
					</span> /
					<?php echo $stats['optimized-image-sizes'] + $stats['available-unoptimised-sizes'] ?>
					<span id="percentage"></span>
				</div>
			<?php } ?>
			<?php
			if ( $stats['available-unoptimised-sizes'] > 0 ) {
				require_once dirname( __FILE__ ) . '/bulk-optimization-form.php';
			}
			?>
		</div>
	</div>

	<script type="text/javascript">
	<?php
	if ( $auto_start_bulk ) {
		echo 'jQuery(function() { bulkOptimizationAutorun(' . json_encode( $this->get_ids_to_compress() ) . ')})';
	} else {
		echo 'jQuery(function() { bulkOptimization(' . json_encode( $stats['available-for-optimization'] ) . ')})';
	}
	?>
	</script>

	<table class="wp-list-table widefat fixed striped media whitebox" id="media-items" >
		<thead>
			<tr>
				<th class="thumbnail"></th>
				<th><?php echo __( 'File', 'tiny-compress-images' ) ?></th>
				<th><?php echo __( 'Sizes optimized', 'tiny-compress-images' ) ?></th>
				<th><?php echo __( 'Original total size', 'tiny-compress-images' ) ?></th>
				<th><?php echo __( 'Optimized total size', 'tiny-compress-images' ) ?></th>
				<th><?php echo __( 'Savings', 'tiny-compress-images' ) ?></th>
				<th><?php echo __( 'Status', 'tiny-compress-images' ) ?></th>
			</tr>
		</thead>
		<tbody>
		</tbody>
	</table>
</div>
