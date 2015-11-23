=== Compress JPEG & PNG images ===
Contributors: TinyPNG
Donate link: https://tinypng.com/
Tags: compress, optimize, shrink, resize, fit, scale, improve, images, tinypng, tinyjpg, jpeg, jpg, png, lossy, jpegmini, crunch, minify, smush, save, bandwidth, website, speed, faster, performance, panda, wordpress app
Requires at least: 3.0.6
Tested up to: 4.4
Stable tag: 1.5.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Speed up your website. Optimize your JPEG and PNG images automatically with TinyPNG.

== Description ==

Make your website faster by compressing your JPEG and PNG images. This plugin automatically optimizes your images by integrating with the popular image compression services TinyJPG and TinyPNG.

= Features =

* Automatically compress new images.
* Optionally resize original image to fit a lower resolution.
* Easy bulk compression of your existing media library.
* Compress individual images already in your media library.
* Multisite support with a single API key.
* Color profiles are translated to the standard RGB color space.
* See your usage directly from the media settings and during bulk compression.
* Select which thumbnail sizes of an image may be compressed.
* Converts from CMYK to RGB to save more space and maximize compatibility.
* Automatic detection of images that can be recompressed.
* Compress and resize uploads from the Wordpress mobile app.
* No file size limit.

= How does it work? =

After you upload an image to your WordPress site, each resized image is uploaded to the TinyJPG or TinyPNG service. Your image is analyzed to apply the best possible compression. Based on the content of your image an optimal strategy is chosen. The result is sent back to your WordPress site and will replace the original image with one smaller in size. On average JPEG images are compressed by 40-60% and PNG images by 50-80% without visible loss in quality. Your website will load faster for your visitors, and you’ll save storage space and bandwidth!

= Getting started =

Install this plugin and obtain your free API key from https://tinypng.com/developers. With a free account you can compress roughly 100 images each month (based on a regular WordPress installation). The exact number depends on the number of thumbnail sizes you use. You can change which of the generated thumbnail sizes should be compressed, because each one of them counts as a compression. And if you’re a heavy user, you can compress more images for a small additional fee per image.

= Multisite support =

The API key can optionally be configured in wp-config.php. This removes the need to set a key on each site individually in your multisite network.

= Contact us =

Got questions or feedback? Let us know! Contact us at support@tinypng.com or find us on [Twitter @tinypng](https://twitter.com/tinypng).

= Contributors =

Want to contribute? Checkout our [GitHub page](https://github.com/tinify/wordpress-plugin).

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
2. See how much space TinyPNG has saved you from the media browser and compress additional sizes per image.
3. Bulk compress existing images after installing the plugin or when additional sizes have to be compressed.
4. Show progress while bulk compressing (selection from) media library.
5. Bulk compress complete media library.

== Frequently Asked Questions ==

= Q: I don't recall uploading 500 photos this month but my limit is already reached. How is this number calculated? =
A: When you upload an image to your website, Wordpress will create different sized versions of it (see Settings > Media). The plugin will compress each of these sizes, so when you have 100 images and 5 different sizes you will do 500 compressions.

= Q: What happens to the compressed images when I uninstall the plugin? =
A: When you remove the TinyPNG plugin all your compressed images will remain compressed.

= Q: Is there a file size limit? =
A: No. There are no limitations on the size of the images you want to compress.

= Q: What happens when I reach my monthly compression limit? =
A: Everything will keep on working, but newly uploaded images will not be compressed. Of course we encourage everyone to sign up for a full subscription.

= Q: Can I compress all existing images in my media library? =
A: Yes! After installing the plugin, go to Tools > Compress JPEG & PNG images, and click on "Compress all images" to compress all uncompressed images in your media library.

== Changelog ==

= 1.5.0 =
* Resize original images automatically when compressing, using the TinyPNG API. Set a maximum width and/or height and the original image will be scaled to fit your maximum resolution.
* Added support for compressing and resizing uploads from the mobile Wordpress app (thanks to contributor Pale Purple!).

= 1.4.0 =
* Added indication of number of images you can compress for free each month.
* Added link to settings page from the plugin listing.
* Added clarification that by checking the original image size your original images will be overwritten.

= 1.3.2 =
* In some cases a user would have different file sizes defined in Settings > Media which have the exact same pixel dimensions. Compressing images could then occasionally result in compressing the same image multiple times without being seen as 'compressed'. We now detect duplicate file sizes and don't compress them again.

= 1.3.1 =
* Media library now shows when files are in the process of being compressed.

= 1.3.0 =
* Improved bulk compressions from media library. You can now also bulk compress your whole media library in one step.
* Intelligent detection if file is already compressed or was altered by another plugin and should be recompressed.

= 1.2.1 =
* Bugfix that prevents recompressing the original when no additional image sizes can be found in the metadata. (introduced in 1.2.0)

= 1.2.0 =
* Display connection status and number of compressions this month on the settings page. This also allows you to check if you entered a valid API key.
* Show a notice to administrators when reaching the monthly compression limit (in case you're on a fixed or free plan).
* The plugin will now work when php's parse_ini_file is disabled on your host.
* Bugfix that avoids a warning when no additional image sizes can be found in the metadata.

= 1.1.0 =
* The API key can now be set with the TINY_API_KEY constant in wp-config.php. This will work for normal and multisite WordPress installations.
* You can now enable or disable compression of the original uploaded image. If you upgrade the plugin from version 1.0 you may need to go to media settings to include it for compression.
* Improved display of original sizes and compressed sizes showing the total size of all compressed images in media library list view.

= 1.0.0 =
* Initial version.
