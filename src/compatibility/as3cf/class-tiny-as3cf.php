<?php

/**
 * Class Tiny_AS3CF
 * Manages integration with WP Media Offload (aka Amazon S3 and CloudFront).
 *
 * Known issues with integration:
 * - When as3cf removes local files, Tinify can't do compression on files that are
 *   already offloaded. Possible solutions are to download the remote file, compress
 *   We can use `tiny_image_after_compression` to possible remove the file after
 *   compression is done. A lot of functonality for Tinify is based on having the
 *   has to be changed in order to support remote files.
 */
class Tiny_AS3CF {


	/**
	 * Tiny_Plugin $settings
	 *
	 * @var Tiny_Settings
	 */
	private $tiny_settings;

	/**
	 * Checks wether the lite version is active
	 */
	public static function lite_is_active() {
		$lite_name = 'amazon-s3-and-cloudfront/wordpress-s3.php';

		return is_plugin_active( $lite_name );
	}

	/**
	 * Checks wether the pro version is active
	 */
	public static function pro_is_active() {
		$pro_name = 'amazon-s3-and-cloudfront-pro/amazon-s3-and-cloudfront-pro.php';

		return is_plugin_active( $pro_name );
	}

	public function __construct( $settings ) {
		$this->tiny_settings = $settings;
		$this->add_hooks();
	}

	/**
	 * Will verify if either the Lite or Pro version of AS3CF is active.
	 */
	public static function is_active() {
		return Tiny_AS3CF::pro_is_active() || Tiny_AS3CF::lite_is_active();
	}

	public static function remove_local_files_setting_enabled() {
		$settings = get_option( 'tantan_wordpress_s3' );
		return array_key_exists( 'remove-local-file', $settings ) && $settings['remove-local-file'];
	}

	/**
	 * Registers hooks required for the AS3CF integration.
	 */
	public function add_hooks() {
		add_action( 'as3cf_pre_upload_object', array( $this, 'as3cf_before_offload' ), 10, 2 );
	}

	/**
	 * handler for 'as3cf_pre_upload_object' action
	 *
	 * @see Tiny_Image->compress()
	 *
	 * Will handle file before file is possibly offloaded
	 *
	 * @param Item  $as3cf_item
	 * @param array $args
	 */
	public function as3cf_before_offload( $as3cf_item, $args ) {
		if ( ! $this->tiny_settings->auto_compress_enabled() ) {
			return;
		}

		$tiny_image = new Tiny_Image( $this->tiny_settings, $as3cf_item->source_id() );
		$result = $tiny_image->compress();
	}
}
