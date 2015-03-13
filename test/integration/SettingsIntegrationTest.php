<?php

require_once(dirname(__FILE__) . "/IntegrationTestCase.php");

class SettingsIntegrationTest extends IntegrationTestCase {

    public function setUp() {
        parent::setUp();
        self::$driver->get(wordpress('/wp-admin/options-media.php'));
    }

    public function testTitlePresence()
    {
        $h3s = self::$driver->findElements(WebDriverBy::tagName('h3'));
        $texts = array_map('innerText', $h3s);
        $this->assertContains('PNG and JPEG compression', $texts);
    }

    public function testApiKeyInputPresence() {
        $elements = self::$driver->findElements(WebDriverBy::name('tinypng_api_key'));
        $this->assertEquals(1, count($elements));
    }

    public function testShouldPersistApiKey() {
        $element = $this->set_api_key('1234');
        $this->assertEquals('1234', $element->getAttribute('value'));
    }

    public function testDefaultSizesBeingCompressed() {
        $elements = self::$driver->findElements(
            WebDriverBy::xpath('//input[@type="checkbox" and starts-with(@name, "tinypng_sizes") and @checked="checked"]'));
        $size_ids = array_map('elementName', $elements);
        $this->assertContains('tinypng_sizes[0]', $size_ids);
        $this->assertContains('tinypng_sizes[thumbnail]', $size_ids);
        $this->assertContains('tinypng_sizes[medium]', $size_ids);
        $this->assertContains('tinypng_sizes[large]', $size_ids);
    }

    public function testShouldPersistSizes() {
        $element = self::$driver->findElement(WebDriverBy::id('tinypng_sizes_medium'));
        $element->click();
        $element = self::$driver->findElement(WebDriverBy::id('tinypng_sizes_0'));
        $element->click();
        self::$driver->findElement(WebDriverBy::tagName('form'))->submit();

        $elements = self::$driver->findElements(
            WebDriverBy::xpath('//input[@type="checkbox" and starts-with(@name, "tinypng_sizes") and @checked="checked"]'));
        $size_ids = array_map('elementName', $elements);
        $this->assertNotContains('tinypng_sizes[0]', $size_ids);
        $this->assertContains('tinypng_sizes[thumbnail]', $size_ids);
        $this->assertNotContains('tinypng_sizes[medium]', $size_ids);
        $this->assertContains('tinypng_sizes[large]', $size_ids);
    }

    public function testShouldPersistNoSizes() {
        $elements = self::$driver->findElements(
            WebDriverBy::xpath('//input[@type="checkbox" and starts-with(@name, "tinypng_sizes") and @checked="checked"]'));
        foreach ($elements as $element) {
            $element->click();
        }
        self::$driver->findElement(WebDriverBy::tagName('form'))->submit();

        $elements = self::$driver->findElements(
            WebDriverBy::xpath('//input[@type="checkbox" and starts-with(@name, "tinypng_sizes") and @checked="checked"]'));
        $this->assertEquals(0, count(array_map('elementName', $elements)));
    }
}
