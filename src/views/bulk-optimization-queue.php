<?php
/**
 * Queue based bulk optimization view (proof of concept).
 *
 * @var array       $stats
 * @var array       $progress
 * @var float       $estimated_costs
 * @var int         $remaining_credits
 * @var Tiny_Plugin $this
 */

$settings_url = admin_url( 'options-general.php?page=tinify' );
$legacy_url   = admin_url( 'upload.php?page=tiny-bulk-optimization' );
?>

<div class="wrap tiny-bulk-optimization tiny-compress-images" id="tiny-bulk-optimization-queue">
	<h2><?php esc_html_e( 'Bulk Optimization', 'tiny-compress-images' ); ?></h2>

	<table class="widefat whitebox" id="tiny-queue-summary">
		<tbody>
			<tr>
				<th><?php esc_html_e( 'Images available for optimization', 'tiny-compress-images' ); ?></th>
				<td id="tiny-queue-available">
					<?php echo esc_html( count( $stats['available-for-optimization'] ) ); ?>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Image sizes to optimize', 'tiny-compress-images' ); ?></th>
				<td><?php echo esc_html( $stats['available-unoptimized-sizes'] ); ?></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Estimated credits', 'tiny-compress-images' ); ?></th>
				<td>
					<?php echo esc_html( $stats['estimated_credit_use'] ); ?>
					<?php
					printf(
						/* translators: %d: number of remaining credits */
						esc_html__( '(%d remaining this month)', 'tiny-compress-images' ),
						absint( $remaining_credits )
					);
					?>
				</td>
			</tr>
		</tbody>
	</table>

	<p>
		<button type="button" class="button button-primary" id="tiny-queue-start">
			<?php esc_html_e( 'Start bulk optimization', 'tiny-compress-images' ); ?>
		</button>
		<button type="button" class="button" id="tiny-queue-cancel">
			<?php esc_html_e( 'Cancel', 'tiny-compress-images' ); ?>
		</button>
		<span id="tiny-queue-state"></span>
	</p>

	<div class="progressbar" id="tiny-queue-progress-bar">
		<div id="progress-size" class="progress" style="width: 0;"></div>
		<div class="numbers">
			<span id="tiny-queue-processed">0</span>
			/
			<span id="tiny-queue-total">0</span>
			<span id="tiny-queue-percentage"></span>
		</div>
	</div>

	<p id="tiny-queue-counters">
		<span><?php esc_html_e( 'Optimized', 'tiny-compress-images' ); ?>:
			<strong id="tiny-queue-optimized">0</strong></span>
		&nbsp;
		<span><?php esc_html_e( 'Failed', 'tiny-compress-images' ); ?>:
			<strong id="tiny-queue-failed">0</strong></span>
		&nbsp;
		<span><?php esc_html_e( 'Skipped', 'tiny-compress-images' ); ?>:
			<strong id="tiny-queue-skipped">0</strong></span>
	</p>

	<p class="notes">
		<?php
		printf(
			wp_kses(
				/* translators: %s: link to settings page saying here */
				__( 'Configure compression settings %s.', 'tiny-compress-images' ),
				array(
					'a' => array(
						'href' => array(),
					),
				)
			),
			'<a href="' . esc_url( $settings_url ) . '">' .
				esc_html__( 'here', 'tiny-compress-images' ) . '</a>'
		)
		?>
	</p>

	<h3><?php esc_html_e( 'Recently processed', 'tiny-compress-images' ); ?></h3>
	<table class="wp-list-table widefat fixed striped media whitebox" id="tiny-queue-items">
		<thead>
			<tr>
				<th class="thumbnail"></th>
				<th class="column-primary"><?php esc_html_e( 'File', 'tiny-compress-images' ); ?></th>
				<th class="column-author">
					<?php esc_html_e( 'Initial Size', 'tiny-compress-images' ); ?>
				</th>
				<th class="column-author">
					<?php esc_html_e( 'Current Size', 'tiny-compress-images' ); ?>
				</th>
				<th class="column-author savings">
					<?php esc_html_e( 'Savings', 'tiny-compress-images' ); ?>
				</th>
				<th class="column-author status">
					<?php esc_html_e( 'Status', 'tiny-compress-images' ); ?>
				</th>
			</tr>
		</thead>
		<tbody></tbody>
	</table>

	<script type="text/javascript">
	<?php echo 'jQuery(function() { tinyBulkQueue(' . json_encode( $progress ) . ') })'; ?>
	</script>
</div>
