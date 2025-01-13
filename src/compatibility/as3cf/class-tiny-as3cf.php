<?php

/**
 * Class Tiny_AS3CF
 * Manages integration with WP Media Offload (aka Amazon S3 and CloudFront).
 * 
 * Known issues with integration:
 * - When as3cf removes local files, Tinify can't do compression on files that are already offloaded. Possible solutions are to download the remote file, compress and reupload it.
 *   We can use `tiny_image_after_compression` to possible remove the file after compression is done. A lot of functonality for Tinify is based on having the fail locally. This
 *   has to be changed in order to support remote files.
 */
class Tiny_AS3CF
{
    /**
     * Is current process done compressing.
     *
     * @var boolean
     */
    private $compression_done = false;

    /**
     * Checks wether the lite version is active
     */
    public static function lite_is_active()
    {
        $lite_name = 'amazon-s3-and-cloudfront/wordpress-s3.php';

        return is_plugin_active($lite_name);
    }

    /**
     * Checks wether the pro version is active
     */
    public static function pro_is_active()
    {
        $pro_name = 'amazon-s3-and-cloudfront-pro/amazon-s3-and-cloudfront-pro.php';

        return is_plugin_active($pro_name);
    }

    public function __construct()
    {
        $this->add_hooks();
    }

    /**
     * Will verify if either the Lite or Pro version of AS3CF is active.
     */
    public static function is_active()
    {
        return Tiny_AS3CF::pro_is_active() || Tiny_AS3CF::lite_is_active();
    }

    public static function remove_local_files_setting_enabled()
    {
        $setting = get_option('tantan_wordpress_s3');
        return array_key_exists('remove-local-file', $setting) &&  $setting['remove-local-file'] = '1';
    }

    /**
     * Registers hooks required for the AS3CF integration.
     */
    public function add_hooks()
    {
        add_filter('as3cf_wait_for_generate_attachment_metadata', array($this, 'as3cf_wait_for_compression'), 10, 3);
        add_action('tiny_image_after_compression', array($this, 'as3cf_upload'), 10, 2);
    }

    /**
     * AS3CF has a filter to enforce the plug-in to wait before uploading the image.
     * Tinify triggers update metadata once compression is done
     * 
     * @see Media_Library->wp_update_attachment_metadata()
     * 
     * @param boolean True if we should wait AND generate_attachment_metadata hasn't run yet
     * @return boolean wait on a process before uploading to AS3CF provider
     */
    public function as3cf_wait_for_compression($wait)
    {
        // This will prevent AS3CF to upload, when compression is done, we kick off a hook to reinitialize the as3cf upload
        return !$this->compression_done && $wait;
    }

    /**
     * handler for 'tiny_image_after_compression' action
     * 
     * @see Tiny_Image->compress()
     * 
     * @param int $attachment_id The attachment ID.
     * @param bool $success True if the image was successfully compressed.
     */
    public function as3cf_upload($attachment_id, $success)
    {
        $this->compression_done = true;
    }
}
