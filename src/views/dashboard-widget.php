<?php

$link = "<a href='" . admin_url( 'upload.php?page=tiny-bulk-optimization' ) . "'>" .  esc_html__( 'bulk optimization page','tiny-compress-images' ) . '</a>';

$chart = array();

$chart['size'] = 180;
$chart['radius'] = $chart['size'] / 2 * 0.9;
$chart['main-radius'] = $chart['radius'] * 0.88;
$chart['center'] = $chart['size'] / 2;
$chart['stroke'] = $chart['radius'] / 2;
$chart['dash-stroke'] = $chart['radius'] / 4;
$chart['inner-radius'] = $chart['radius'] - $chart['stroke'] / 2;

?>

<style>

#optimization-chart svg circle.main {
	stroke-width: <?php echo $chart['dash-stroke'] ?>;
}

#optimization-chart svg circle.main {
	stroke: #7acb44;
}

#optimization-chart div.chart div.value {
	min-width: <?php echo $chart['size'] ?>px;
}

</style>

<div id='widget-spinner' ></div>
<div class='panda-background'></div>
<div class='media-library-optimized' id='widget-not-optimized'>
	<p><?php printf( esc_html__( 'Hi %s, you haven’t compressed any images in your media library. If you like you can to optimize your whole library in one go with the %s.', 'tiny-compress-images' ), $username, $link ) ?></p>
</div>
<div class='media-library-optimized' id='widget-full-optimized'>
	<p><?php printf( esc_html__( 'Hi %s, great job! Your entire library is optimized!', 'tiny-compress-images' ), $username ) ?></p>
</div>
<div class='media-library-optimized' id='widget-half-optimized'>
	<p>
		<?php printf( esc_html__( 'Hi %s, you are doing good.', 'tiny-compress-images' ), $username ) ?>
		<span class='compressions-remaining'></span>
		<?php printf( esc_html__( 'of your media library is optimized. If you like you can to optimize the remainder of your library with the %s.', 'tiny-compress-images' ), $link ) ?>
	</p>
</div>

<div id="optimization-chart" class="widget-chart hidden" data-percentage-factor="<?php echo $chart['main-radius'] ?>" >
	<svg width="<?php echo $chart['size'] ?>" height="<?php echo $chart['size'] ?>">
		<circle class="main" transform="rotate(-90, <?php echo $chart['center'] ?>, <?php echo $chart['center'] ?>)" r="<?php echo $chart['main-radius'] ?>" cx="<?php echo $chart['center'] ?>" cy="<?php echo $chart['center'] ?>"/>
		<circle class="inner" r="<?php echo $chart['inner-radius'] ?>" cx="<?php echo $chart['center'] ?>" cy="<?php echo $chart['center'] ?>" />
	</svg>
	<div class="value">
		<div class="percentage widget-percentage" id="savings-percentage"><span></span>%</div>
		<div class="label widget-label" ><?php echo esc_html__( 'savings', 'tiny-compress-images' ); ?></div>
	</div>
</div>


