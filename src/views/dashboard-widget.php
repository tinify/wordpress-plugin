<?php $link = "<a href='" . admin_url( 'upload.php?page=tiny-bulk-optimization' ) . "'>" .  esc_html__( 'bulk optimization','tiny-compress-images' ) . '</a>'; ?>

<div id="widget-spinner" class=""></div>
<div class="sky-background"></div>
<div class="cloud"></div>
<div class="panda-background"></div>
<div class="grass"></div>
<div class="media-library-optimized" id="no-images-uploaded">
	<?php esc_html_e( 'You don\'t seem to have uploaded any JPEG or PNG images yet.', 'tiny-compress-images' ) ?>
</div>
<div class="media-library-optimized" id="widget-not-optimized">
	<p><?php printf( esc_html__( 'Hi %s, you haven’t compressed any images in your media library. If you like you can to optimize your whole library in one go with the %s page.', 'tiny-compress-images' ), $username, $link ) ?></p>
</div>
<div class="media-library-optimized" id="widget-full-optimized">
	<p><?php printf( esc_html__( 'Hi %s, great job! Your entire library is optimized!', 'tiny-compress-images' ), $username ) ?></p>
</div>
<div class="media-library-optimized" id="widget-half-optimized">
	<p>
		<?php printf( esc_html__( 'Hi %s, you are doing good.', 'tiny-compress-images' ), $username ) ?>
		<?php printf( esc_html__( 'With your current settings you can still optimize %s image sizes from your %s uploaded JPEG and PNG images.', 'tiny-compress-images' ), "<span id='unoptimised-sizes'></span>", "<span id='uploaded-images'></span>") ?>
		<?php printf( esc_html__( 'Start the %s to optimize the remainder of your library.', 'tiny-compress-images' ), $link  ) ?>
	</p>
</div>

<?php require_once dirname( __FILE__ ) . '/bulk-optimization-chart.php'; ?>

