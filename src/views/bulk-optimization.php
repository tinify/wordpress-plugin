<?php
/**
 * Bulk optimization view.
 *
 * @var array       $stats             See Tiny_Bulk_Optimization::get_optimization_statistics().
 * @var array       $widget            See Tiny_Dashboard::get_widget_data().
 * @var string      $state             The widget state, or no_sizes.
 * @var int         $compression_count Compressions made this month so far.
 * @var bool        $start_on_load
 * @var Tiny_Plugin $this
 */

$items      = $stats['available-for-optimization'];
$can_manage = current_user_can( 'manage_options' );

/* A run switches the page to the compressing hero, then to the done hero. */
$heroes = array( $state );
if ( in_array( $state, array( 'ready', 'credits_short' ), true ) ) {
	$heroes[] = 'compressing';
}
if ( in_array( $state, array( 'ready', 'credits_short', 'compressing' ), true ) ) {
	$heroes[] = 'done';
}

$has_library_bar = ! in_array( $state, array( 'pending', 'empty', 'no_sizes' ), true );
$has_list        = count( $items ) > 0 && 'pending' !== $state;
?>
<div class="wrap tiny-settings tiny-bulk-optimization" id="tiny-bulk-optimization">
	<h1><?php esc_html_e( 'Bulk Optimizer', 'tiny-compress-images' ); ?></h1>
	<p class="tiny-settings-intro">
		<?php
		esc_html_e( 'Compress the images that are already in your Media Library.', 'tiny-compress-images' );
		if ( $widget['auto_compress'] ) {
			echo ' ';
			esc_html_e( 'New uploads are compressed automatically.', 'tiny-compress-images' );
		}
		?>
	</p>
	<hr class="wp-header-end">

	<div class="tiny-settings-columns">
		<div class="tiny-settings-main">
			<div class="tiny-card" data-tiny-views>
				<?php
				if ( in_array( $state, array( 'credits_short', 'out_of_credits' ), true ) ) {
					$upgrade_from = 'bulk-optimization';
					require __DIR__ . '/more-credits.php';
				}
				foreach ( $heroes as $hero ) {
					require __DIR__ . '/bulk-optimization-hero.php';
				}
				?>

				<?php if ( $has_library_bar ) { ?>
					<div class="tiny-card-section tiny-bulk-library">
						<div class="tiny-bulk-library-head">
							<h3><?php esc_html_e( 'Media Library', 'tiny-compress-images' ); ?></h3>
							<span>
								<?php
								printf(
									/* translators: %s: percentage of compressed images */
									esc_html__( '%s%% compressed', 'tiny-compress-images' ),
									'<span data-tiny-bulk="library-percentage">' . esc_html( number_format_i18n( $widget['percentage'] ) ) . '</span>'
								);
								?>
							</span>
						</div>
						<div class="tiny-bulk-bar">
							<span data-tiny-bulk="library-bar" style="width: <?php echo esc_attr( $widget['percentage'] ); ?>%"></span>
						</div>
						<div class="tiny-bulk-legend">
							<span class="tiny-bulk-legend-done">
								<?php
								printf(
									/* translators: %1$s: number of compressed images, %2$s: number of images */
									esc_html__( '%1$s of %2$s compressed', 'tiny-compress-images' ),
									'<span data-tiny-bulk="library-compressed">' . esc_html( number_format_i18n( $widget['images_optimized'] ) ) . '</span>',
									esc_html( number_format_i18n( $widget['images_total'] ) )
								);
								?>
							</span>
							<span class="tiny-bulk-legend-todo" data-tiny-bulk="library-todo">
								<?php
								if ( 'compressing' === $state ) {
									/* translators: %s: number of images still to compress */
									$to_compress = __( '%s to go', 'tiny-compress-images' );
								} else {
									/* translators: %s: number of images still to compress */
									$to_compress = __( '%s to compress', 'tiny-compress-images' );
								}
								printf(
									esc_html( $to_compress ),
									esc_html( number_format_i18n( $widget['images_remaining'] ) )
								);
								?>
							</span>
						</div>
					</div>
				<?php } ?>

				<?php if ( $has_list ) { ?>
					<div class="tiny-card-section tiny-bulk-list">
						<h3 data-tiny-bulk="list-title"
							data-compressing="<?php esc_attr_e( 'Activity', 'tiny-compress-images' ); ?>"
							data-done="<?php esc_attr_e( 'Recently compressed', 'tiny-compress-images' ); ?>">
							<?php
							if ( 'compressing' === $state ) {
								esc_html_e( 'Activity', 'tiny-compress-images' );
							} else {
								esc_html_e( 'Waiting to be compressed', 'tiny-compress-images' );
							}
							?>
						</h3>
						<ul class="tiny-bulk-items" id="tiny-bulk-items">
							<?php require __DIR__ . '/bulk-optimization-items.php'; ?>
						</ul>
					</div>
				<?php } ?>

				<?php if ( $has_list || 'done' === $state ) { ?>
					<div class="tiny-bulk-footer">
						<?php if ( $has_list ) { ?>
							<span data-tiny-bulk="list-waiting">
								<?php
								printf(
									/* translators: %s: number of images waiting to be compressed */
									esc_html__( '%s waiting', 'tiny-compress-images' ),
									'<span data-tiny-bulk="waiting">' . esc_html( number_format_i18n( count( $items ) ) ) . '</span>'
								);
								?>
							</span>
						<?php } ?>
						<a href="<?php echo esc_url( admin_url( 'upload.php?mode=list' ) ); ?>"><?php esc_html_e( 'View all in Media Library', 'tiny-compress-images' ); ?></a>
					</div>
				<?php } ?>
			</div>
		</div>

		<div class="tiny-settings-sidebar">
			<?php $this->settings->render_account_status( false ); ?>

			<div class="tiny-card">
				<div class="tiny-card-section">
					<div class="tiny-card-head">
						<h2><?php esc_html_e( 'Statistics', 'tiny-compress-images' ); ?></h2>
					</div>
					<dl class="tiny-account-details">
						<div>
							<dt><?php esc_html_e( 'Images in library', 'tiny-compress-images' ); ?></dt>
							<dd><?php echo esc_html( number_format_i18n( $widget['images_total'] ) ); ?></dd>
						</div>
						<div>
							<dt><?php esc_html_e( 'Optimized', 'tiny-compress-images' ); ?></dt>
							<dd data-tiny-bulk="stat-compressed"><?php echo esc_html( number_format_i18n( $widget['images_optimized'] ) ); ?></dd>
						</div>
						<div>
							<dt><?php esc_html_e( 'Saved so far', 'tiny-compress-images' ); ?></dt>
							<dd data-tiny-bulk="stat-saved"><?php echo esc_html( $widget['bytes_saved'] > 0 ? size_format( $widget['bytes_saved'], 1 ) : '0 B' ); ?></dd>
						</div>
						<div>
							<dt><?php esc_html_e( 'Average saving', 'tiny-compress-images' ); ?></dt>
							<dd data-tiny-bulk="stat-average"><?php echo $stats['display-percentage'] > 0 ? esc_html( number_format_i18n( $stats['display-percentage'] ) . '%' ) : '&ndash;'; ?></dd>
						</div>
					</dl>
				</div>
			</div>

			<?php $this->settings->render_credits( $can_manage ); ?>
		</div>
	</div>
</div>
<script type="text/javascript">
	<?php
	echo 'window.tinyBulk = ' . wp_json_encode(
		array(
			'running'          => 'compressing' === $state,
			'startOnLoad'      => $start_on_load,
			'compressionCount' => $compression_count,
			'workers'          => Tiny_Background_Optimize::WORKERS,
			'imagesOptimized'  => $widget['images_optimized'],
			'imagesTotal'      => $widget['images_total'],
			'imagesRemaining'  => $widget['images_remaining'],
			'bytesSaved'       => $widget['bytes_saved'],
			'initialSize'      => intval( $stats['unoptimized-library-size'] ),
		)
	) . ';';
	?>
</script>
