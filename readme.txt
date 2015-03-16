=== Compress JPEG & PNG images ===
Contributors: TinyPNG
Donate link: https://tinypng.com/
Tags: compress, optimize, shrink, improve, images, tinypng, tinyjpg, jpeg, jpg, png, lossy, jpegmini, crunch, minify, smush, save, bandwidth, website, speed, faster, performance, panda
Requires at least: 3.0.6
Tested up to: 4.1
Stable tag: 1.1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Speed up your website. Optimize your JPEG and PNG images automatically with TinyPNG.

== Description ==

Make your website faster by compressing your JPEG and PNG images.

This plugin automatically optimizes your images by integrating with the popular image compression services TinyJPG and TinyPNG.

= How does it work? =

After you upload an image to your WordPress site, each resized image is uploaded to the TinyPNG or TinyJPG service. Your image is analyzed to apply the best possible compression. Based on the content of your image an optimal strategy is chosen. The result is sent back to your WordPress site. On average JPEG images are compressed by 40-60% and PNG images by 50-80% without visible loss in quality. Your website will load faster for your visitors, and you’ll save storage space and bandwidth!

= Getting started =

Install this plugin and obtain your free API key from https://tinypng.com/developers. The first 500 compressions per month are completely free, so roughly 100 images can be uploaded to WordPress for free, no strings attached! You can also change which of the generated thumbnail sizes should be compressed, because each one of them counts as a compression. And if you’re a heavy user, you can compress additional images for a small additional fee per image.

= Multisite support =

The API key can optionally be configured in wp-config.php. This removes the need to set a key on each site individually in your multisite network.

= Contact us =

Got questions or feedback? Let us know! Contact us at support@tinypng.com or find us on [Twitter @tinypng](https://twitter.com/tinypng).

= Contributors =

Want to contribute? Checkout our [GitHub page](https://github.com/TinyPNG/wordpress-plugin).

== Installation ==

= From your WordPress dashboard =

1. Visit 'Plugins > Add New'.
2. Search for 'tinypng' and press the Install Now button for the plugin named 'Compress JPEG & PNG images' by 'TinyPNG'.
3. Activate the plugin from your Plugins page.
4. Register for an API key on https://tinypng.com/developers.
5. Configure the API key in the Settings -> Media page.
6. Upload an image and see it be compressed!

= From WordPress.org =

1. Download the plugin name 'Compress JPEG & PNG images' by 'TinyPNG'.
2. Upload the 'tiny-compress-images' directory to your '/wp-content/plugins/' directory, using your favorite method (ftp, sftp, scp, etc...)
3. Activate the plugin from your Plugins page.
4. Register for an API key on https://tinypng.com/developers.
5. Configure the API key in the Settings -> Media page.
6. Upload an image and see it be compressed!

= Optional configuration =

The API key can also be configured in wp-config.php. You can add a TINY_API_KEY constant with your API key. Once set up you will see a message on the media settings page. This will work for normal and multisite WordPress installations.

== Screenshots ==

1. Enter your TinyPNG or TinyJPG API key and configure the image sizes you would like to have compressed.
2. See how much space TinyPNG has saved you from the media browser!
3. Bulk compress existing images after installing the plugin or when additional sizes have to be compressed.
4. Compress individual images in case additional sizes have to be compressed.

== Changelog ==

= 1.1.0 =
* The API key can now be set with the TINY_API_KEY constant in wp-config.php. This will work for normal and multisite WordPress installations.
* You can now enable or disable compression of the original uploaded image. If you upgrade the plugin from version 1.0 you may need to go to media settings to include it for compression.
* Improved display of original sizes and compressed sizes showing the total size of all compressed images in media library list view.

= 1.0.0 =
* Initial version.