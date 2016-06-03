<?php

require(dirname(__FILE__) . '/../helpers/integration_helper.php');
require(dirname(__FILE__) . '/../helpers/setup.php');

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\UselessFileDetector;

abstract class IntegrationTestCase extends PHPUnit_Framework_TestCase {
    protected static $driver;

    public static function setUpBeforeClass() {
        self::$driver = RemoteWebDriver::createBySessionId($GLOBALS['global_session_id'], $GLOBALS['global_webdriver_host']);
    }

    protected function has_postbox_container() {
        return wordpress_version() >= 35;
    }

    protected function postbox_dimension_selector() {
        $version = wordpress_version();
        if ($version < 37)
            return 'div.misc-pub-section:nth-child(5)';
        elseif ($version == 37)
            return 'div.misc-pub-section:nth-child(6)';
        else
            return 'div.misc-pub-dimensions';
    }

    protected function upload_media($path) {
        self::$driver->get(wordpress('/wp-admin/media-new.php?browser-uploader&flash=0'));
        $links = self::$driver->findElements(WebDriverBy::xpath('//a[text()="browser uploader"]'));
        if (count($links) > 0) {
            $link = $links[0];
            if ($link->isDisplayed()) {
                $link->click();
            }
        }
        self::$driver->wait(2)->until(WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::name('async-upload')));
        $file_input = self::$driver->findElement(WebDriverBy::name('async-upload'));
        $file_input->setFileDetector(new UselessFileDetector());
        $file_input->sendKeys($path);
        self::$driver->findElement(WebDriverBy::xpath('//input[@value="Upload"]'))->click();
        self::$driver->wait(2)->until(WebDriverExpectedCondition::presenceOfElementLocated(
            WebDriverBy::xpath('//h1[contains(text(),"Media Library")]|//h2[contains(text(),"Media Library")]')));
    }

    protected function set_api_key($api_key, $wait = true) {
        $url = wordpress('/wp-admin/options-media.php');
        if (self::$driver->getCurrentUrl() != $url) {
            self::$driver->get($url);
        }

        $status = self::$driver->findElements(WebDriverBy::id('tiny-compress-status'));
        if (empty($status)) {
            $status[0]->findElement(WebDriverBy::linkText('Change API key'))->click();

            $modal = self::$driver->findElement(WebDriverBy::id('tiny-update-account-container'));
            $modal->findElement(WebDriverBy::name('tinypng_api_key_modal'))->clear()->sendKeys($api_key);
            $modal->findElement(WebDriverBy::cssSelector('button.tiny-account-update-key'))->click();
        } else {
            $status[0]->findElement(WebDriverBy::name('tinypng_api_key'))->clear()->sendKeys($api_key);
            $status[0]->findElement(WebDriverBy::cssSelector('button.tiny-account-update-key'))->click();
        }

        if ($wait) {
            self::$driver->wait(2)->until(WebDriverExpectedCondition::presenceOfElementLocated(
                WebDriverBy::cssSelector('#tiny-compress-status p.tiny-account-status')));
        }
    }

    protected function enable_compression_sizes($sizes) {
        $url = wordpress('/wp-admin/options-media.php');
        if (self::$driver->getCurrentUrl() != $url) {
            self::$driver->get($url);
        }
        $elements = self::$driver->findElements(WebDriverBy::xpath('//input[starts-with(@id, "tinypng_sizes_")]'));
        foreach($elements as $element) {
            $size = str_replace('tinypng_sizes_', '', $element->getAttribute('id'));
            if (in_array($size, $sizes)) {
                if (!$element->getAttribute('checked')) {
                    $element->click();
                }
            } else {
                if ($element->getAttribute('checked')) {
                    $element->click();
                }
            }
        }
        self::$driver->findElement(WebDriverBy::tagName('form'))->submit();
    }

    protected function enable_resize($width, $height) {
        $url = wordpress('/wp-admin/options-media.php');
        if (self::$driver->getCurrentUrl() != $url) {
            self::$driver->get($url);
        }
        $element = self::$driver->findElement(WebDriverBy::id('tinypng_resize_original_enabled'));
        if (!$element->getAttribute('checked')) {
            $element->click();
        }
        self::$driver->findElement(WebDriverBy::id('tinypng_resize_original_width'))->clear()->sendKeys($width);
        self::$driver->findElement(WebDriverBy::id('tinypng_resize_original_height'))->clear()->sendKeys($height);
        self::$driver->findElement(WebDriverBy::tagName('form'))->submit();
    }

    protected function disable_resize() {
        $url = wordpress('/wp-admin/options-media.php');
        if (self::$driver->getCurrentUrl() != $url) {
            self::$driver->get($url);
        }
        $element = self::$driver->findElement(WebDriverBy::id('tinypng_resize_original_enabled'));
        if ($element->getAttribute('checked')) {
            $element->click();
        }
        self::$driver->findElement(WebDriverBy::tagName('form'))->submit();
    }

    protected function enable_preserve($keys) {
        $url = wordpress('/wp-admin/options-media.php');
        if (self::$driver->getCurrentUrl() != $url) {
            self::$driver->get($url);
        }
        $elements = self::$driver->findElements(WebDriverBy::xpath('//input[starts-with(@id, "tinypng_preserve_data")]'));
        foreach($elements as $element) {
            $key = str_replace('tinypng_preserve_data_', '', $element->getAttribute('id'));
            if (in_array($key, $keys)) {
                if (!$element->getAttribute('checked')) {
                    $element->click();
                }
            } else {
                if ($element->getAttribute('checked')) {
                    $element->click();
                }
            }
        }
        self::$driver->findElement(WebDriverBy::tagName('form'))->submit();
    }

    protected function view_edit_image($image_title = 'input-example') {
        $url = wordpress('/wp-admin/upload.php');
        if (self::$driver->getCurrentUrl() != $url) {
            self::$driver->get($url);
        }
        if (wordpress_version() >= 43) {
            $selector = "//span[text()='" . $image_title . "']";
        } else {
            $selector = "//a[contains(text(),'" . $image_title . "')]";
        }
        self::$driver->findElement(WebDriverBy::xpath($selector))->click();
    }

    protected function getValue($selector) {
        return self::$driver->findElement(WebDriverBy::cssSelector($selector))->getText();
    }

    protected function create_image_fixture($id, $name, $wp_meta, $tiny_meta = false) {
        $db = new mysqli(getenv('HOST_IP'), 'root', getenv('MYSQL_ROOT_PASSWORD'), getenv('WORDPRESS_DATABASE'));
        $db->prepare("
            INSERT INTO wp_posts(ID, post_author, post_date, post_date_gmt, post_content, post_excerpt, post_title,
                                 post_status, comment_status, ping_status, post_name,
                                 post_modified, post_modified_gmt, post_parent, guid,
                                 menu_order, post_type, post_mime_type, comment_count)
            VALUES(" . $id . ", 1, '2016-04-22 07:11:04', '2016-04-22 07:11:04', '', '', '" . $name . "',
                   'inherit', 'open', 'closed', '" . $name . "',
                   '2016-04-22 07:11:04', '2016-04-22 07:11:04', 0, 'http://wordpress.dev/wp-content/uploads/2016/04/" . $name . ".png',
                   0, 'attachment', 'image/png', '0');
        ")->execute();

        $db->prepare("
            INSERT INTO wp_postmeta(post_id, meta_key, meta_value)
            VALUES(" . $id . ", '_wp_attachment_metadata', '" . $wp_meta . "');
        ")->execute();

        if ($tiny_meta) {
            $db->prepare("
                INSERT INTO wp_postmeta(post_id, meta_key, meta_value)
                VALUES(" . $id . ", 'tiny_compress_images', '" . $tiny_meta . "');
            ")->execute();
        }
    }

    public function create_non_compressed_image($id, $name) {
        $wp_meta = 'a:5:{s:5:"width";i:1490;s:6:"height";i:1500;s:4:"file";s:33:"2016/04/detective-panda-large.png";s:5:"sizes";a:4:{s:9:"thumbnail";a:4:{s:4:"file";s:33:"detective-panda-large-150x150.png";s:5:"width";i:150;s:6:"height";i:150;s:9:"mime-type";s:9:"image/png";}s:6:"medium";a:4:{s:4:"file";s:33:"detective-panda-large-298x300.png";s:5:"width";i:298;s:6:"height";i:300;s:9:"mime-type";s:9:"image/png";}s:5:"large";a:4:{s:4:"file";s:35:"detective-panda-large-1017x1024.png";s:5:"width";i:1017;s:6:"height";i:1024;s:9:"mime-type";s:9:"image/png";}s:14:"post-thumbnail";a:4:{s:4:"file";s:35:"detective-panda-large-1200x1208.png";s:5:"width";i:1200;s:6:"height";i:1208;s:9:"mime-type";s:9:"image/png";}}s:10:"image_meta";a:12:{s:8:"aperture";s:1:"0";s:6:"credit";s:0:"";s:6:"camera";s:0:"";s:7:"caption";s:0:"";s:17:"created_timestamp";s:1:"0";s:9:"copyright";s:0:"";s:12:"focal_length";s:1:"0";s:3:"iso";s:1:"0";s:13:"shutter_speed";s:1:"0";s:5:"title";s:0:"";s:11:"orientation";s:1:"0";s:8:"keywords";a:0:{}}}';
        $this->create_image_fixture($id, $name, $wp_meta);
    }

    public function create_partially_compressed_image($id, $name) {
        $wp_meta = 'a:5:{s:5:"width";i:626;s:6:"height";i:603;s:4:"file";s:19:"2016/04/small-2.jpg";s:5:"sizes";a:2:{s:9:"thumbnail";a:4:{s:4:"file";s:19:"small-2-150x150.jpg";s:5:"width";i:150;s:6:"height";i:150;s:9:"mime-type";s:10:"image/jpeg";}s:6:"medium";a:4:{s:4:"file";s:19:"small-2-300x289.jpg";s:5:"width";i:300;s:6:"height";i:289;s:9:"mime-type";s:10:"image/jpeg";}}s:10:"image_meta";a:12:{s:8:"aperture";s:3:"5.6";s:6:"credit";s:0:"";s:6:"camera";s:6:"NEX-3N";s:7:"caption";s:0:"";s:17:"created_timestamp";s:10:"1428328340";s:9:"copyright";s:0:"";s:12:"focal_length";s:2:"43";s:3:"iso";s:4:"3200";s:13:"shutter_speed";s:6:"0.0125";s:5:"title";s:0:"";s:11:"orientation";s:1:"1";s:8:"keywords";a:0:{}}}';
        $tiny_meta = 'a:1:{i:0;a:3:{s:5:"input";a:2:{s:4:"size";i:124538;s:4:"type";s:10:"image/jpeg";}s:6:"output";a:6:{s:4:"size";i:74432;s:5:"width";i:626;s:6:"height";i:603;s:5:"ratio";d:0.59770000000000001;s:4:"type";s:10:"image/jpeg";s:3:"url";s:50:"https://api.tinify.com/output/oq8j3ht4j6uf5okc.jpg";}s:3:"end";i:1461333048;}}';
        $this->create_image_fixture($id, $name, $wp_meta, $tiny_meta);
    }

    public function create_fully_compressed_image($id, $name) {
        $wp_meta = 'a:5:{s:5:"width";i:626;s:6:"height";i:603;s:4:"file";s:19:"2016/04/small-1.jpg";s:5:"sizes";a:2:{s:9:"thumbnail";a:4:{s:4:"file";s:19:"small-1-150x150.jpg";s:5:"width";i:150;s:6:"height";i:150;s:9:"mime-type";s:10:"image/jpeg";}s:6:"medium";a:4:{s:4:"file";s:19:"small-1-300x289.jpg";s:5:"width";i:300;s:6:"height";i:289;s:9:"mime-type";s:10:"image/jpeg";}}s:10:"image_meta";a:12:{s:8:"aperture";s:3:"5.6";s:6:"credit";s:0:"";s:6:"camera";s:6:"NEX-3N";s:7:"caption";s:0:"";s:17:"created_timestamp";s:10:"1428328340";s:9:"copyright";s:0:"";s:12:"focal_length";s:2:"43";s:3:"iso";s:4:"3200";s:13:"shutter_speed";s:6:"0.0125";s:5:"title";s:0:"";s:11:"orientation";s:1:"1";s:8:"keywords";a:0:{}}}';
        $tiny_meta = 'a:3:{i:0;a:3:{s:5:"input";a:2:{s:4:"size";i:124538;s:4:"type";s:10:"image/jpeg";}s:6:"output";a:6:{s:4:"size";i:74432;s:5:"width";i:626;s:6:"height";i:603;s:5:"ratio";d:0.59770000000000001;s:4:"type";s:10:"image/jpeg";s:3:"url";s:50:"https://api.tinify.com/output/3t1cf2baqc7l9o24.jpg";}s:3:"end";i:1461332938;}s:9:"thumbnail";a:3:{s:5:"input";a:2:{s:4:"size";i:15623;s:4:"type";s:10:"image/jpeg";}s:6:"output";a:6:{s:4:"size";i:13033;s:5:"width";i:150;s:6:"height";i:150;s:5:"ratio";d:0.83420000000000005;s:4:"type";s:10:"image/jpeg";s:3:"url";s:50:"https://api.tinify.com/output/n3i3pp1slh5s6g9s.jpg";}s:3:"end";i:1461332940;}s:6:"medium";a:3:{s:5:"input";a:2:{s:4:"size";i:47920;s:4:"type";s:10:"image/jpeg";}s:6:"output";a:6:{s:4:"size";i:40728;s:5:"width";i:300;s:6:"height";i:289;s:5:"ratio";d:0.84989999999999999;s:4:"type";s:10:"image/jpeg";s:3:"url";s:50:"https://api.tinify.com/output/q9nhl1go1vakeorj.jpg";}s:3:"end";i:1461332950;}}';
        $this->create_image_fixture($id, $name, $wp_meta, $tiny_meta);
    }
}
