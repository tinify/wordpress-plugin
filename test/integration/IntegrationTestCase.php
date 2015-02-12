<?php

require(dirname(__FILE__) . '/../helpers/integration_helper.php');
require(dirname(__FILE__) . '/../helpers/setup.php');

abstract class IntegrationTestCase extends PHPUnit_Framework_TestCase {

    protected static $driver;

    public static function setUpBeforeClass() {
        self::$driver = RemoteWebDriver::createBySessionId($GLOBALS['global_session_id'], $GLOBALS['global_phantom_host']);
    }

    protected function upload_image($path) {
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
        $file_input->setFileDetector(new LocalFileDetector());
        $file_input->sendKeys($path);
        self::$driver->findElement(WebDriverBy::xpath('//input[@value="Upload"]'))->click();
        $path_elements = explode('/', $path);
        $file_name = array_pop($path_elements);
        $image_elements = explode('.', $file_name);
        $image_name = $image_elements[0];
        self::$driver->wait(2)->until(WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::xpath('//img[contains(@src, "' . $image_name . '")]')));
    }

    protected function set_api_key($api_key) {
        self::$driver->get(wordpress('/wp-admin/options-media.php'));
        self::$driver->findElement(WebDriverBy::name('tinypng_api_key'))->clear()->sendKeys($api_key);
        self::$driver->findElement(WebDriverBy::tagName('form'))->submit();
        return self::$driver->findElement(WebDriverBy::name('tinypng_api_key'));
    }

    protected function enable_compression_sizes($sizes) {
        self::$driver->get(wordpress('/wp-admin/options-media.php'));
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
}
