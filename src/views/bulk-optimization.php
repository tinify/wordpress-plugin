<style>

/* Admin color scheme colors */

div.tiny-bulk-optimization div.available div.tooltip span.dashicons {
	color: <?php echo $admin_colors[3] ?>;
}
div.tiny-bulk-optimization div.savings div.tiny-optimization-chart div.value {
	color: <?php echo $admin_colors[2] ?>;
}
div.tiny-bulk-optimization div.savings div.tiny-optimization-chart svg circle.main {
	stroke: <?php echo $admin_colors[2] ?>;
}
div.tiny-bulk-optimization div.savings table td.emphasize {
	color: <?php echo $admin_colors[2] ?>;
}
div.tiny-bulk-optimization div.dashboard div.optimize div.progressbar div.progress {
	background-color: <?php echo $admin_colors[0] ?>;
	background-image: linear-gradient(
		-63deg,
		<?php echo $admin_colors[0] ?> 0%,
		<?php echo $admin_colors[0] ?> 25%,
		<?php echo $admin_colors[1] ?> 25%,
		<?php echo $admin_colors[1] ?> 50%,
		<?php echo $admin_colors[0] ?> 50%,
		<?php echo $admin_colors[0] ?> 75%,
		<?php echo $admin_colors[1] ?> 75%,
		<?php echo $admin_colors[1] ?> 100%
	);
}

</style>

<div class="wrap tiny-bulk-optimization tiny-compress-images" id="tiny-bulk-optimization">
	<div class="icon32" id="icon-upload"><br></div>
	<h2><?php esc_html_e( 'Bulk Optimization', 'tiny-compress-images' ) ?></h2>
	<div class="dashboard">
		<div class="statistics">
			<div class="available">
				<div class="inner">
					<h3><?php esc_html_e( 'Available Images', 'tiny-compress-images' ) ?></h3>
					<p>
						<?php
						if ( 0 == $stats['optimized-image-sizes'] + $stats['available-unoptimised-sizes'] ) {
							$percentage = 0;
						} else {
							$percentage_of_files = round( $stats['optimized-image-sizes'] / ( $stats['optimized-image-sizes'] + $stats['available-unoptimised-sizes'] ) * 100, 2 );
						}
						if ( 0 == $stats['uploaded-images'] + $stats['available-unoptimised-sizes'] ) {
							esc_html_e( 'This page is designed to bulk optimize all your images.', 'tiny-compress-images' );
							echo ' ';
							esc_html_e( 'You do not seem to have uploaded any JPEG or PNG images yet.', 'tiny-compress-images' );
						} elseif ( 0 == sizeof( $active_tinify_sizes ) ) {
							esc_html_e( 'Based on your current settings, nothing will be optimized. There are no active sizes selected for optimization.', 'tiny-compress-images' );
						} elseif ( 0 == $stats['available-unoptimised-sizes'] ) {
							/* translators: %s: friendly user name */
							printf( esc_html__( '%s, this is great! Your entire library is optimized!', 'tiny-compress-images' ), $this->friendly_user_name() );
						} elseif ( $stats['optimized-image-sizes'] > 0 ) {
							if ( $percentage_of_files > 75 ) {
								/* translators: %s: friendly user name */
								printf( esc_html__( '%s, you are doing great!', 'tiny-compress-images' ), $this->friendly_user_name() );
							} else {
								/* translators: %s: friendly user name */
								printf( esc_html__( '%s, you are doing good.', 'tiny-compress-images' ), $this->friendly_user_name() );
							}
							echo ' ';
								/* translators: %1$d%2$s: percentage optimised */
								printf( esc_html__( '%1$d%2$s of your image library is optimized.', 'tiny-compress-images' ), $percentage_of_files, '%' );
							echo ' ';
							/* translators: %s: bulk optimization title */
							printf( esc_html__( 'Start the %s to optimize the remainder of your library.', 'tiny-compress-images' ), esc_html__( 'bulk optimization', 'tiny-compress-images' ) );
						} else {
							esc_html_e( 'Here you can start optimizing your entire library. Press the big button to start improving your website speed instantly!', 'tiny-compress-images' );
						}
						?>
					</p>
					<?php if ( Tiny_Settings::wr2x_active() ) { ?>
						<p>
							<?php esc_html_e( 'Notice that the WP Retina 2x sizes will not be compressed using this page. You will need to bulk generate the retina sizes separately from the WP Retina 2x page.', 'tiny-compress-images' ); ?>
						</p>
					<?php } ?>
					<table class="totals">
						<tr>
							<td class="item">
								<h3>
									<?php echo wp_kses( __( 'Uploaded <br> images', 'tiny-compress-images' ), array(
										'br' => array(),
									) ) ?>
								</h3>
								<span id="uploaded-images">
									<?php echo $stats['uploaded-images']; ?>
								</span>
							</td>
							<td class="item">
								<h3>
									<?php echo wp_kses( __( 'Uncompressed image sizes', 'tiny-compress-images' ), array(
										'br' => array(),
									) ) ?>
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
												/* translators: %1$s: number of sizes that can be optimised, %2$s number of images */
												printf( esc_html__( 'With your current settings you can still optimize %1$s image sizes from your %2$s uploaded JPEG and PNG images.',
												'tiny-compress-images'), $stats['available-unoptimised-sizes'], $stats['uploaded-images'] );
												?>
											</p>
										<?php } ?>
										<p>
											<?php
											if ( 0 == sizeof( $active_tinify_sizes ) ) {
												esc_html_e( 'Based on your current settings, nothing will be optimized. There are no active sizes selected for optimization.', 'tiny-compress-images' );
											} else {
												esc_html_e( 'These sizes are currently activated for compression:', 'tiny-compress-images' );
												echo '<ul>';
												for ( $i = 0; $i < sizeof( $active_tinify_sizes ); ++$i ) {
													$name = $active_tinify_sizes[ $i ];
													if ( '0' == $name ) {
														echo '<li>- ' . esc_html__( 'Original image', 'tiny-compress-images' ) . '</li>';
													} else {
														echo '<li>- ' . esc_html( ucfirst( $name ) ) . '</li>';
													}
												}
												echo '</ul>';
											}
											?>
										</p>
										<p>
										<?php if ( sizeof( $active_tinify_sizes ) > 0 ) { ?>
											<?php
											/* translators: %d: number of sizes to be compressed */
											printf( wp_kses( _n( 'For each uploaded image <strong>%d size</strong> is compressed.', 'For each uploaded image <strong>%d sizes</strong> are compressed.', count( $active_tinify_sizes ), 'tiny-compress-images' ), array(
												'strong' => array(),
											) ), count( $active_tinify_sizes ) ) ?>
										<?php } ?>
										<?php
										/* translators: %s: link to settings page saying here */
										printf( wp_kses( __( 'You can change these settings %s.', 'tiny-compress-images' ), array(
											'a' => array(
											'href' => array(),
											),
										) ), '<a href=' . admin_url( 'options-general.php?page=tinify' ) . '>' . __( 'here', 'tiny-compress-images' ) . '</a>' )?>
										</p>
									</div>
								</div>
							</td>
							<td class="item costs">
								<h3>
									<?php echo wp_kses( __( 'Estimated <br> cost', 'tiny-compress-images' ), array(
										'br' => array(),
									) ) ?>
								</h3>
								<span id="estimated-cost">$ <?php echo number_format( $estimated_costs, 2 ) ?></span>
								USD
								<?php if ( $estimated_costs > 0 ) { ?>
									<div class="tooltip">
										<span class="dashicons dashicons-info"></span>
										<div class="tip">
											<p><?php
											/* translators: %1$d %2$s: number of image sizes, %3$s: link saying upgrade here */
											printf( wp_kses( __( 'If you wish to compress more than <strong>%1$d %2$s</strong> a month and you are still on a free account %3$s.', 'tiny-compress-images' ),
												array(
												'strong' => array(),
												'a' => array(
													'href' => array(),
												),
											) ), Tiny_Config::MONTHLY_FREE_COMPRESSIONS, esc_html__( 'image sizes', 'tiny-compress-images' ), '<a target="_blank" href="https://tinypng.com/dashboard/api?type=upgrade&mail=' . str_replace( '%20', '%2B', rawurlencode( $email_address ) ) . '">' . esc_html__( ' upgrade here', 'tiny-compress-images' ) . '</a>' );
											?></p>
										</div>
									</div>
								<?php } ?>
							</td>
						</tr>
					</table>
					<div class="notes">
						<h4><?php esc_html_e( 'Remember', 'tiny-compress-images' ) ?></h4>
						<p>
							<?php esc_html_e( 'For the plugin to do the work, you need to keep this page open. But no worries: when stopped, you can continue where you left off!', 'tiny-compress-images' ); ?>
						</p>
					</div>
				</div>
			</div>
			<div class="savings">
				<div class="inner">
					<h3><?php esc_html_e( 'Total Savings', 'tiny-compress-images' ) ?></h3>
					<p>
						<?php esc_html_e( 'Statistics based on all available JPEG and PNG images in your media library.', 'tiny-compress-images' ); ?>
					</p>
					<?php
						require_once dirname( __FILE__ ) . '/optimization-chart.php';
					?>
					<div class="legend">
						<table>
							<tr>
								<td id="optimized-image-sizes" class="value emphasize">
									<?php echo $stats['optimized-image-sizes']; ?>
								</td>
								<td class="description">
									<?php echo _n( 'image size optimized', 'image sizes optimized', $stats['optimized-image-sizes'], 'tiny-compress-images' ) ?>
								</td>
							</tr>
							<tr>
								<td id="unoptimized-library-size" class="value" data-bytes="<?php echo $stats['unoptimized-library-size']; ?>" >
									<?php echo ( $stats['unoptimized-library-size'] ? size_format( $stats['unoptimized-library-size'], 2 ) : '-'); ?>
								</td>
								<td class="description">
									<?php esc_html_e( 'initial size', 'tiny-compress-images' ) ?>
								</td>
							</tr>
							<tr>
								<td id="optimized-library-size" class="value emphasize" data-bytes="<?php echo $stats['optimized-library-size'] ?>" class="green">
									<?php echo ($stats['optimized-library-size'] ? size_format( $stats['optimized-library-size'], 2 ) : '-') ?>
								</td>
								<td class="description">
									<?php esc_html_e( 'current size', 'tiny-compress-images' ) ?>
								</td>
							</tr>
						</table>
					</div>
				</div>
			</div>
		</div>
		<?php $show_notice = $is_on_free_plan && $stats['available-unoptimised-sizes'] > $remaining_credits; ?>
		<div class="optimize">
			<div class="progressbar" id="compression-progress-bar" data-number-to-optimize="<?php echo $stats['optimized-image-sizes'] + $stats['available-unoptimised-sizes'] ?>" data-amount-optimized="0" style="<?php echo $show_notice ? 'display:none;' : '' ?>">
				<div id="progress-size" class="progress">
				</div>
				<div class="numbers" >
					<span id="optimized-so-far"><?php echo $stats['optimized-image-sizes'] ?></span>
					/
					<span><?php echo $stats['optimized-image-sizes'] + $stats['available-unoptimised-sizes'] ?></span>
					<span id="percentage"></span>
				</div>
			</div>
			<?php
			if ( $stats['available-unoptimised-sizes'] > 0 ) {
				require_once dirname( __FILE__ ) . '/bulk-optimization-form.php';
			}
			?>
		</div>
		<?php
		if ( $show_notice ) {
			require_once dirname( __FILE__ ) . '/bulk-optimization-upgrade-notice.php';
		}
		?>
  </div>
	<script type="text/javascript">
	<?php echo 'jQuery(function() { bulkOptimization(' . json_encode( $stats['available-for-optimization'] ) . ')})'; ?>
	</script>
	<table class="wp-list-table widefat fixed striped media whitebox" id="optimization-items" >
		<thead>
			<tr>
				<?php // column-author WP 3.8-4.2 mobile view ?>
				<th class="thumbnail"></th>
				<th class="column-primary" ><?php esc_html_e( 'File', 'tiny-compress-images' ) ?></th>
				<th class="column-author"><?php esc_html_e( 'Initial Size', 'tiny-compress-images' ) ?></th>
				<th class="column-author"><?php esc_html_e( 'Current Size', 'tiny-compress-images' ) ?></th>
				<th class="column-author savings" ><?php esc_html_e( 'Savings', 'tiny-compress-images' ) ?></th>
				<th class="column-author status" ><?php esc_html_e( 'Status', 'tiny-compress-images' ) ?></th>
			</tr>
		</thead>
		<tbody>
		</tbody>
	</table>
</div>
