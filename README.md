[<img src="https://travis-ci.org/TinyPNG/tiny-compress-images.svg?branch=master"  alt="Build Status">](https://travis-ci.org/TinyPNG/tiny-compress-images)

# TinyPNG image compression for WordPress
Make your website faster by compressing your JPEG and PNG images.

This plugin automatically optimizes your images by integrating with the popular image compression services TinyJPG and TinyPNG.

Learn more about TinyPNG at https://tinypng.com.

## Contact us
Got questions or feedback? Let us know! Contact us at support@tinypng.com.

## Information for plugin contributors

### Prerequisites
* A working docker installation (https://docs.docker.com/installation/).
* Composer (https://getcomposer.org/download/).
* PHPUnit (https://phar.phpunit.de/phpunit.phar).
* Selenium Server (http://www.seleniumhq.org/download/).
* Mysql client and admin tools.
* Java runtime.

### Running the plugin in Wordpress
1. Run `bin/run-wordpress <version>`. E.g. `bin/run-wordpress 41`.
2. Use `docker ps` to check which port to use to connect to WordPress.

### Running the unit tests
1. Run `phpunit test/unit`.

### Running the integration tests
1. Start Selenium server: `java -jar selenium-server-standalone-2.44.0.jar`.
2. Run `bin/test-wordpress <version>`. E.g. `bin/test-wordpress 41`.

## License
Copyright (C) 2015 Voormedia B.V.

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
