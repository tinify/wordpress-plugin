<?php $link = "<a href='" . admin_url( 'upload.php?page=tiny-bulk-optimization' ) . "'>" . esc_html__( 'bulk optimization', 'tiny-compress-images' ) . '</a>'; ?>

<style type="text/css" >
div.media-library-optimized {
	display: none;
}
div#optimization-chart {
	display: none;
}
.ie8 div#optimization-chart {
	display: none !important;
}
#optimization-chart svg circle.main {
	stroke: <?php echo $admin_colors[2] ?>;
}
</style>

<div id="widget-spinner" class=""></div>
<div class="sky-background"></div>
<div class="cloud"></div>
<div class="panda-background"></div>
<div class="grass"></div>
<div class="media-library-optimized" id="no-images-uploaded">
	<p><?php esc_html_e( 'You do not seem to have uploaded any JPEG or PNG images yet.', 'tiny-compress-images' ) ?></p>
</div>
<div class="media-library-optimized" id="widget-not-optimized">
	<p>
		<?php printf( esc_html__( 'Hi %s, you haven’t compressed any images in your media library.', 'tiny-compress-images' ), $this->friendly_user_name() ) ?>
		<?php printf( wp_kses( __( 'If you like you can to optimize your whole library in one go with the %s page.', 'tiny-compress-images' ), array( 'a' => array( 'href' => array() ) ) ), $link )?>
	</p>
</div>
<div class="media-library-optimized" id="widget-full-optimized">
	<p><?php printf( esc_html__( '%s, this is great! Your entire library is optimized!', 'tiny-compress-images' ), $this->friendly_user_name() ) ?></p>
	<p id="ie8-compressed"><?php printf( wp_kses( __( 'You have <strong>saved %s</strong> of your media library size.', 'tiny-compress-images' ), array( 'span' => array(), 'strong' => array() ) ), '<span></span>%' )?></p>
</div>
<div class="media-library-optimized" id="widget-half-optimized">
	<p>
		<?php printf( esc_html__( '%s, you are doing good.', 'tiny-compress-images' ), $this->friendly_user_name() ) ?>
		<?php printf( esc_html__( 'With your current settings you can still optimize %1$s image sizes from your %2$s uploaded JPEG and PNG images.', 'tiny-compress-images' ), '<span id="unoptimised-sizes"></span>', '<span id="uploaded-images"></span>' ) ?>
		<?php printf( wp_kses( __( 'Start the %s to optimize the remainder of your library.', 'tiny-compress-images' ), array( 'a' => array( 'href' => array() ) ) ), $link )?>
	</p>
</div>

<?php require_once dirname( __FILE__ ) . '/bulk-optimization-chart.php'; ?>
