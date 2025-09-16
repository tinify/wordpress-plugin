=== TinyPNG - JPEG, PNG & WebP image compression ===
Contributors: TinyPNG
Donate link: https://tinypng.com/
Tags: compress images, compression, image size, page speed, performance
Requires at least: 4.0
Tested up to: 6.8
Stable tag: 3.6.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Speed up your website. Optimize your JPEG, PNG, and WebP images automatically with TinyPNG.

== Description ==

Make your website faster by optimizing your JPEG, PNG, and WebP images. This plugin automatically optimizes all your images by integrating with the popular image compression services TinyJPG and TinyPNG.

= Features =

* Automatically optimize new images on upload.
* Advanced background optimization to speed up your workflow.
* Optimize individual images already in your media library.
* Easy bulk optimization of your existing media library.
* Automatically resize huge image uploads by setting a maximum width and/or height for more reasonable browser display.
* Display JPEG images more quickly with progressive JPEG encoding.
* Chose to preserve copyright metadata, creation date and GPS location in your original images.
* Supports compression of animated PNG.
* Select which thumbnail sizes of an image may be optimized.
* Multisite support with a single API key.
* [WPML](https://wpml.org/documentation/plugins-compatibility/optimize-wordpress-images-multilingual-sites/) compatible.
* WooCommerce compatible.
* WP Retina 2x compatible.
* See your usage on the settings page and during bulk optimization.
* Color profiles are automatically translated to standard RGB color.
* Convert CMYK to RGB to save more space and add compatibility.
* Dashboard widget with your total savings.
* Optimize and resize uploads with the WordPress mobile app.

= How does it work? =

After you upload an image to your WordPress site, each resized image is uploaded to the TinyJPG or TinyPNG service. Your image is analyzed to apply the best possible optimization. Based on the content of your image an optimal strategy is chosen. The result is sent back to your WordPress site and will replace the original image with one smaller in size. On average JPEG images are compressed by 40-60% and PNG images by 50-80% without visible loss in quality. Your website will load faster for your visitors, and you’ll save storage space and bandwidth!

= Getting started =

Install this plugin and follow the instructions to set up your account. With a regular WordPress installation you can optimize **roughly 100 images each month** for free. The exact total depends on the number of thumbnail sizes that are in use in your WordPress installation. You can change which of the generated thumbnail sizes should be optimized on the *Settings > TinyPNG* page. Once installed you can also switch to a [paid account](https://tinypng.com/dashboard/api) which removes the limits and allows you to optimize as many images as you like.

= Optimizing all your images =

You can *bulk optimize* your existing JPEG, PNG, and WebP images all at once by going to *Media > Bulk TinyPNG*. Clicking on the big button will start optimizing all unoptimized images in your media library.

= Multisite support =

The plugin is fully multisite compatible and you can set the API key for all sites by defining the key in your *wp-config.php* file. View the installation instructions for more information.

= Contact us =

Got questions or feedback? Let us know! Contact us at support@tinypng.com or find us on X: [@tinypng](https://twitter.com/tinypng).

= Contributors =

Want to contribute? Check out the [Tinify Wordpress plugin on GitHub](https://github.com/tinify/wordpress-plugin).

== Installation ==

= From your WordPress dashboard =

1. Visit *Plugins > Add New*.
2. Search for 'tinypng' and press the 'Install Now' button for the plugin named 'TinyPNG - JPEG, PNG & WebP image compression' by 'TinyPNG'.
3. Activate the plugin from your *Plugins* page.
4. Go to the *Settings > TinyPNG* page and register a new account.
5. Or enter the API key you got from https://tinypng.com/developers.
6. Go to *Media > Bulk TinyPNG* and optimize all your images!

= From WordPress.org =

1. Download the plugin named 'TinyPNG - JPEG, PNG & WebP image compression' by 'TinyPNG'.
2. Upload the `tiny-compress-images` directory to your `/wp-content/plugins/` directory, using your favorite method (ftp, sftp, scp, etc...)
3. Activate the plugin from your Plugins page.
4. Go to the *Settings > TinyPNG* page and register a new account.
5. Or enter the API key you got from https://tinypng.com/developers.
6. Go to *Media > Bulk TinyPNG* and optimize all your images!

= Optional configuration =

The API key can also be configured in your *wp-config.php* file. To do this you can add the following PHP code that sets the API key.

`define( 'TINY_API_KEY', 'YOUR_API_KEY_HERE' );`

Once set up you will see a message on the *Settings > TinyPNG* page. This will work for normal and multisite WordPress installations.

== Screenshots ==

1. Register a new account or enter your existing API key. Then choose the image sizes to optimize and any other options like resizing and preserving metadata in your original image uploads.
2. In the Media Library list view you can see the savings on your images.
3. From the Media Library you can compress individual images and use the Bulk Actions drop-down to quickly optimize multiple images at once.
4. Last but not least you can also use Bulk TinyPNG to optimize your entire WordPress site.

== Frequently Asked Questions ==

= Q: How many images can I optimize for free? =
A: In a default WordPress installation you can optimize around 100 images for free each month. WordPress creates different thumbnails of your images which all have to be compressed. Some plugins even add more sizes, so take a look at the *Settings > TinyPNG - JPEG, PNG & WebP image compression* page before you start optimization.

= Q: How can I remove the 500 limit? =
A: Just add your *Payment details* on your [account dashboard](https://tinypng.com/dashboard/api) to remove the limit. For a small fee the additional compressions above 500 will then be charged to your account at the end of each month.

= Q: What happens to the optimized images when I uninstall the plugin? =
A: When you remove the plugin all your optimized images will remain optimized.

= Q: I don't recall uploading 500 photos this month but my limit is already reached. How is this number calculated? =
A: When you upload an image to your website, WordPress will create different sized versions of it (see *Settings > TinyPNG*). The plugin will compress each of these sizes, so when you have 100 images and 5 different sizes you will do 500 compressions.

= Q: Is there a file size limit? =
A: Yes, but they are **very** generous! The maximum file size is 500MB and the maximum canvas size is 256MP (16000px by 16000px in any ratio).

= Q: Can I use the same API key on all my websites? =
A: Yes, there are no limitations. You can add the same key everywhere.

= Q: Can I hide my API key from the admin? =
A: Yes, you can hide your API key from the admin so other admin users will not be able to see it. To do this you should define the API key in your *wp-config.php* file. See the installation instructions for more information.

= Q: What happens when I reach my monthly limit? =
A: Everything will keep on working, but newly uploaded images will not be optimized. Of course we encourage everyone to [switch](https://tinypng.com/dashboard/api) to a paid account to cover the hosting and development costs of the service.

= Q: Can I optimize all existing images in my media library? =
A: Yes! After installing the plugin, go to *Media > Bulk TinyPNG*, and click on the start button to optimize all unoptimized images in your media library.

= Q: How do I switch to a paid account? =
A: You can upgrade to a paid account by adding your *Payment details* on your [account dashboard](https://tinypng.com/dashboard/api). Additional compressions above 500 will then be charged at the end of each month as a one-time fee.

== Changelog ==
= 3.6.2 =
* Resolved an issue where the amount of parameters were incorrect when rendering a notice.

= 3.6.1 =
* Resolved issue where a picture element would be nested within a picture element

= 3.6.0 =
* Added a link to leave a review in the bulk and settings page
* Renamed menu title for bulk to "Bulk TinyPNG"
* Renamed menu title for settings to "TinyPNG"
* Resolved warning when library was empty
* Resolved warning when resize options was empty
* New feature to enable converting images to WebP or AVIF

= 3.5.2 =
* Removed devdependencies and test files from plug-in
* Fixed a warning when library contained no images

= 3.5.1 =
* Will only check local file removal if AS3CF is active

= 3.5.0 =
* Support files uploaded through the WordPress REST API
* Removed notification on WooCommerce incompatibility
* Updated Tinify SDK from 1.5.2 to 1.6.2
* Improved support for WP Offload

= 3.4.6 =
* Error resolved where Tiny_Helpers was not available

= 3.4.5 =
* Error when mb_strimwidth is not available resolved

= 3.4.4 =
* CSRF fix, added nonce check 

= 3.4.3 =
* Removed more deprecated PHP syntax that was triggering a warning message.
* Updated Wordpress compatibility

= 3.4.2 =
* Removed deprecated PHP syntax that was triggering a warning message.

= 3.4.1 =
* Fixed a bug that sometimes prevented failing requests to the Tinify servers from being retried.

= 3.4 =
* Correct filesize metadata retrieval for compressed images in WordPress 6.
* Updated WordPress compatibility.

= 3.3 =
* You can now optimise WebP images, in addition to JPEG and PNG.

= 3.2.1 =
* Fixed bug that caused the original version of images that started with a special character to not be compressed.
* Updated Wordpress compatibility.

= 3.2.0 =
* Support for WP Retina 2x Pro.
* More capability checks for extra security.
* Less resource intensive AJAX requests.
* Fixed CSS issues from Analytify plugin.
* Removed legacy Enhanced Media Library compatibility.

= 3.1.0 =
* Remaining free compressions shown in settings page.
* Easier way to upgrade a free account.
* WPML and WPML Media compatibility in collaboration with the authors of WPML. Make sure to upgrade WPML to version 4.1.
* Added a notice to the Bulk Optimization page for free accounts with not enough available free compressions.
* Added a new hook after compression of an image useful for CDN cache flushing.

= 3.0.1 =
* Fixed bug that caused an error when registering a new account.
* No longer use create_function, which is deprecated in PHP 7.2. The plugin no longer supports PHP 5.2.
* Rephrased incompatible plugins notice to avoid confusion.

= 3.0.0 =
* Compress new images in the background to speed up your workflow.
* Turbo-charged Bulk Optimization page with simultaneous image compression.
* Improved memory usage for installations with extreme media libraries.
* Detection of incompatible plugins.
* Fallback to fopen whenever the curl_exec function is disabled.
* Additional notices for WP Offload S3 users.
* Several minor fixes and tweaks.

= 2.2.6 =
* Fixed bug in compression of file types other than JPEG and PNG that have a thumbnail generated automatically.

= 2.2.5 =
* Fixed bug in the Bulk Optimization page that sometimes caused it to stop.
* Fixed a problem that would prevent dashboard widget from loading.
* Tweaked styling of the dashboard widget for the latest version of WordPress.
* Fixed a warning in the media library when certain plugins are used.

= 2.2.4 =
* Fixed bug with drop-down menu in the Media Library.
* Compression limit notice now links directly to your API dashboard.
* Tweaked styling of the dashboard widget.

= 2.2.3 =
* Changed links to make it easier to go directly to your API dashboard.
* Fixed a compression issue for some fopen users.

= 2.2.2 =
* Improved robustness in case an unexpected network error occurs.
* Fixed false positive warning with Sucuri scanner.
* Fixed compression issue for fopen users.

= 2.2.1 =
* Fixed an error that was introduced in v2.2.0 where all custom image sizes did not show up.
* Added support for image sizes with unspecified height or width.

= 2.2.0 =
* New dashboard widget that shows your media library savings.
* Support compressing medium_large image sizes.

= 2.1.0 =
* Compression of retina images generated by WP Retina 2x.
* Solved a bug which caused the API key to be cleared on the settings page.
* Fixed an error that occurred with some PHP 7 installations.
* Fixed an fopen error when preserving metadata.

= 2.0.2 =
* Faster Bulk Optimization page with reduced memory usage (thanks to @esmus).
* Fixed XML-RPC error (thanks @ironmanixs, @gingerdog, @quicoto and @isaumya).

= 2.0.1 =
* Fixed a bug when searching from the admin interface (thanks to @bapcsuk).

= 2.0.0 =
* Completely new Bulk Optimization page.
* Better detection of image sizes with duplicate filenames.
* Simplified account activation and API key creation.
* Fix to the bottom drop-down menu in the Media Library.
* Use the latest PHP client library for connecting to TinyJPG and TinyPNG.
* Added fallback to fopen for older systems running PHP 5.2.

= 1.7.2 =
* Show more information about compressed image sizes in details popup.
* Add compression details to image overview.

= 1.7.1 =
* Preserve GPS locations and creation dates in the original JPEG images.
* Option to preserve copyright information in your original PNG images.
* Improved detection of unsupported file types.

= 1.7.0 =
* Option to preserve copyright information in your original JPEG images.
* Added proxy support for cURL.
* Support for translate.wordpress.org plugin translations.

= 1.6.0 =
* Improved compression status in the Media Library with new details window.
* Show total compression savings on the Media Settings page.
* Moved Compress All Images from the Tools to the Media menu.

= 1.5.0 =
* Resize original images by specifying a maximum width and/or height.
* Support for the mobile WordPress app (thanks to David Goodwin).

= 1.4.0 =
* Indication of the number of images you can compress for free each month.
* Link to the Media Settings page from the plugin listing.
* Clarification that original images will be overwritten when compressed.

= 1.3.2 =
* Detect different thumbnail sizes with the same dimensions.

= 1.3.1 =
* Media library shows files that are in the process of compression.

= 1.3.0 =
* Added option to bulk compress your whole media library in one go.
* Better indication of image sizes that have been compressed.
* Detection of image sizes modified after compression by other plugins.

= 1.2.1 =
* Prevent compressing the original image if it is the only selected image size.

= 1.2.0 =
* Show if you entered a valid API key.
* Display connection status and number of compressions this month.
* Show a notice to administrators when the free compression limit is reached.
* The plugin now works when php's parse_ini_file is disabled on your host.
* Avoid warnings when no image thumbnail sizes have been selected.

= 1.1.0 =
* The API key can now be set with the TINY_API_KEY constant in wp-config.php. This will work for normal and multisite WordPress installations.
* Enable or disable compression of the original uploaded image.
* Improved display of original sizes and compressed sizes showing the total compression size in the Media Library list view.

= 1.0.0 =
* Initial version.
