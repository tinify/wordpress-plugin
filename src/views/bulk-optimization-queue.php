<?php
/**
 * Queue based bulk optimization view (proof of concept).
 *
 * @var array       $progress
 * @var int         $remaining_credits
 * @var Tiny_Plugin $this
 */

$settings_url = admin_url( 'options-general.php?page=tinify' );
$legacy_url   = admin_url( 'upload.php?page=tiny-bulk-optimization' );
?>

<div class="wrap tiny-bulk-optimization tiny-compress-images" id="tiny-bulk-optimization-queue">
	<h2><?php esc_html_e( 'Bulk Optimization V2', 'tiny-compress-images' ); ?></h2>

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

	<table class="widefat whitebox" id="tiny-queue-counts">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Optimized', 'tiny-compress-images' ); ?></th>
				<th><?php esc_html_e( 'Nothing to do', 'tiny-compress-images' ); ?></th>
				<th><?php esc_html_e( 'Failed', 'tiny-compress-images' ); ?></th>
				<th><?php esc_html_e( 'In progress', 'tiny-compress-images' ); ?></th>
				<th><?php esc_html_e( 'Waiting', 'tiny-compress-images' ); ?></th>
				<th><?php esc_html_e( 'Credits left', 'tiny-compress-images' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td id="tiny-queue-optimized">0</td>
				<td id="tiny-queue-skipped">0</td>
				<td id="tiny-queue-failed">0</td>
				<td id="tiny-queue-processing">0</td>
				<td id="tiny-queue-pending">0</td>
				<td><?php echo esc_html( $remaining_credits ); ?></td>
			</tr>
		</tbody>
	</table>

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
