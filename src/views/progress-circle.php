<?php
/**
 * Progress Circle
 *
 * @var int    $percentage Completion, 0-100.
 * @var string $label      Optional caption below the percentage.
 */
?>
<style>
div.tiny-progress-circle {
	position: relative;
	width: 100px;
	height: 100px;
	flex-shrink: 0;
}

div.tiny-progress-circle svg {
	display: block;
	transform: rotate(-90deg);
}

div.tiny-progress-circle circle {
	fill: none;
	stroke-width: 8;
}

div.tiny-progress-circle circle.track {
	stroke: #e1e1e1;
}

div.tiny-progress-circle circle.progress {
	stroke: #06d28b;
	transition: stroke-dasharray 0.6s ease;
}

div.tiny-progress-circle div.value {
	position: absolute;
	top: 0;
	left: 0;
	display: flex;
	width: 100%;
	height: 100%;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	line-height: 17px;
}

div.tiny-progress-circle div.percentage {
	font-size: 24px;
	font-weight: bold;
	color: #1e1e1e;
}

div.tiny-progress-circle div.label {
	font-size: 11px;
	color: var(--tiny-color-text-muted);
}
</style>

<div class="tiny-progress-circle">
	<svg width="100" height="100" viewBox="0 0 100 100" aria-hidden="true" focusable="false">
		<circle class="track" cx="50" cy="50" r="46" pathLength="100" />
		<?php if ( $percentage > 0 ) { ?>
			<circle class="progress" cx="50" cy="50" r="46" pathLength="100"
				stroke-dasharray="<?php echo esc_attr( $percentage ); ?> 100" />
		<?php } ?>
	</svg>
	<div class="value">
		<div class="percentage"><?php echo esc_html( $percentage ); ?>%</div>
		<?php if ( ! empty( $label ) ) { ?>
			<div class="label"><?php echo esc_html( $label ); ?></div>
		<?php } ?>
	</div>
</div>
