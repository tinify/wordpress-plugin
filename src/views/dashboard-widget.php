<?php $link = "<a href='" . admin_url( 'upload.php?page=tiny-bulk-optimization' ) . "'>" . esc_html__( 'bulk optimization', 'tiny-compress-images' ) . '</a>'; ?>

<style type="text/css" >
div#tinypng_dashboard_widget div.description {
	display: none;
}
div#tinypng_dashboard_widget div#optimization-chart {
	display: none;
}
div#tinypng_dashboard_widget div#optimization-chart svg circle.main {
	stroke: <?php echo $admin_colors[2] ?>;
}
</style>

<div class="spinner" id="widget-spinner"></div>
<div class="sky"></div>
<div class="cloud"></div>
<div class="panda"></div>
<div class="grass"></div>
<div class="description no-images">
	<p><?php esc_html_e( 'You do not seem to have uploaded any JPEG or PNG images yet.', 'tiny-compress-images' ) ?></p>
</div>
<div class="description not-optimized">
	<p>
		<?php
		/* translators: %s: friendly user name */
		printf( esc_html__( 'Hi %s, you haven’t compressed any images in your media library.', 'tiny-compress-images' ), $this->friendly_user_name() );
		echo ' ';
		/* translators: %s: bulk optimization page */
		printf( wp_kses( __( 'If you like you can to optimize your whole library in one go with the %s page.', 'tiny-compress-images' ), array(
			'a' => array(
			'href' => array(),
			),
		) ), $link );
		?>
	</p>
</div>
<div class="description half-optimized">
	<p>
		<?php
		/* translators: %s: friendly user name */
		printf( esc_html__( '%s, you are doing good.', 'tiny-compress-images' ), $this->friendly_user_name() );
		echo ' ';
		/* translators: %s: number of optimizable sizes and number of uploaded images */
		printf( esc_html__( 'With your current settings you can still optimize %1$s image sizes from your %2$s uploaded JPEG and PNG images.', 'tiny-compress-images' ), '<span id="unoptimised-sizes"></span>', '<span id="uploaded-images"></span>' );
		echo ' ';
		/* translators: %s: bulk optimization link */
		printf( wp_kses( __( 'Start the %s to optimize the remainder of your library.', 'tiny-compress-images' ), array(
			'a' => array(
			'href' => array(),
			),
		) ), $link );
		?>
	</p>
</div>
<div class="description full-optimized">
	<p>
		<?php
		/* translators: %s: friendly user name */
		printf( esc_html__( '%s, this is great! Your entire library is optimized!', 'tiny-compress-images' ), $this->friendly_user_name() );
		?>
	</p>
</div>
<div class="description" id="ie8-compressed">
	<p>
		<?php
		/* translators: %s: savings percentage */
		printf( wp_kses( __( 'You have <strong>saved %s</strong> of your media library size.', 'tiny-compress-images' ), array(
			'span' => array(),
			'strong' => array(),
		) ), '<span></span>%' );
		?>
	</p>
</div>

<?php require_once dirname( __FILE__ ) . '/optimization-chart.php'; ?>
