# TinyPNG - JPEG, PNG & WebP image compression for WordPress

Make your website faster by optimizing your JPEG, PNG, and WebP images.

This plugin automatically optimizes your images by integrating with the
popular image compression services TinyJPG and TinyPNG. You can download the
plugin from https://wordpress.org/plugins/tiny-compress-images/.

Learn more about TinyJPG and TinyPNG at https://tinypng.com/.

## Contact us

Got questions or feedback? Let us know! Contact us at support@tinypng.com.

## Information for plugin contributors

### Prerequisites
* A working Docker 1.12+ and Docker Compose installation (https://docs.docker.com/installation/).
* Composer (https://getcomposer.org/download/).
* PhantomJS 2.1 or greater (http://phantomjs.org).
* MySQL client and admin tools.

### Running the plugin in WordPress
1. Run `bin/run-wordpress <version>`. E.g. `bin/run-wordpress 60`.
2. Connect to Wordpress on port `80<version>` (e.g. port `8060`).

### Running the unit tests
1. Run `bin/unit-tests <optional path to file>`.

### Running the integration tests
1. Install Docker 1.12 and docker-compose.
2. Run `bin/integration-tests <version>`. E.g. `bin/integration-tests 60`.

### Check if the code follows WordPress standard
1. Run `bin/check-style` to make sure there are no errors.

### Test XML-RPC code
WordPress can either be used via the web interface or through the official
WordPress apps for mobile devices. WordPress uses XML-RPC internally to
communicate between the app and the WordPress admin. Make sure therefore
that when developing functionality that is linked to functionality available
in the mobile app that it also works over XML-RPC.

### Translating the plugin
Language packs will be generated for the plugin once translations for a
language are 100% filled in and approved.

See https://translate.wordpress.org/projects/wp-plugins/tiny-compress-images.

For development you may create .po and .mo files for a each language. The .mo
files can be created with [gettext](https://www.gnu.org/software/gettext/).
Install gettext and generate the .mo language file do the following:

1. Install gettext for example run `brew install gettext`.
2. Add a link msgfmt `ln -s /usr/local/Cellar/gettext/0.19.7/bin/msgfmt ~/.bin`.
3. Generate the .mo files `bin/format-language-files`.

When finished modifying, you can upload the changes to the SVN trunk. Within
roughly 15 minutes WordPress will be updated and allow to add missing
translations before publishing the new plugin release.

## License

Copyright (C) 2015-2023 Tinify B.V.

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

[View the complete license](LICENSE).
