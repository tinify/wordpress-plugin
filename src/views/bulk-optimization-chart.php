<?php

function render_percentage_chart($optimized_library_size, $unoptimized_library_size) {

	$width = 200; // same as width of ' .savings-chart > .textual'
	$radius = 80;

	if ( $unoptimized_library_size != 0 ) {
		$percentage = round( 100 - ($optimized_library_size / $unoptimized_library_size * 100), 1 );
	} else {
		$percentage = 0;
	}

	$height = $width;
	$offset = $width / 2;
	$stroke_width = $radius / 2;
	$inner_radius = $radius - ($stroke_width / 2.5);
	$outer_radius = $radius + ($stroke_width * 0.66);
	$full_circle = 2 * pi() * $radius;
	$dash_array_1 = $percentage * $full_circle / 100;

	?>
		<style>
			div.savings-chart svg circle.main {
				fill: #ebebeb;
				stroke: #7acb44;
				stroke-width: <?php echo $stroke_width; ?>;
				stroke-dasharray: <?php echo $dash_array_1 . ' ' . $full_circle; ?>;
				animation: shwoosh 1s ease;
				transition: all 1s ease;
			}
			@keyframes shwoosh {
				from {
					stroke-dasharray: 0 <?php echo $full_circle; ?>
				}
				to {
					stroke-dasharray: <?php echo $dash_array_1 . ' ' . $full_circle; ?>
				}
			}
			.savings-chart svg circle.inner {
				fill: #fff;
			}
			.savings-chart svg circle.outer {
				fill: #fff;
				fill: none;
				stroke-width: <?php echo $stroke_width; ?>;
				stroke: #fff;
			}
		</style>

	<div class="savings-chart" data-full-circle-size="<?php echo $full_circle; ?>" data-percentage-factor="<?php echo $radius; ?>" >
		<svg width="<?php echo $width; ?>" height="<?php echo $height; ?>">
		  <circle class="main" transform="rotate(-90, <?php echo $offset; ?>, <?php echo $offset; ?>)" r="<?php echo $radius; ?>" cx="<?php echo $offset; ?>" cy="<?php echo $offset; ?>"/>
		  <circle class="inner" r="<?php echo $inner_radius; ?>" cx="<?php echo $offset; ?>" cy="<?php echo $offset; ?>" />
		  <circle class="outer" r="<?php echo $outer_radius; ?>" cx="<?php echo $offset; ?>" cy="<?php echo $offset; ?>" />
		</svg>
		<div class="textual">
			<div class="percentage" id="savings-percentage"><?php echo $percentage; ?>%</div>
			<div>savings</div>
		</div>
	</div>

<?php
}
?>
