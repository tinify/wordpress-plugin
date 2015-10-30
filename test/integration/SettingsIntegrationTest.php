<?php

require_once(dirname(__FILE__) . "/IntegrationTestCase.php");

class SettingsIntegrationTest extends IntegrationTestCase {

    public function setUp() {
        parent::setUp();
        self::$driver->get(wordpress('/wp-admin/options-media.php'));
    }

    public function tearDown() {
        clear_settings();
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

    public function testShouldShowNoticeIfNoApiKeyIsSet() {
        $element = self::$driver->findElement(WebDriverBy::cssSelector('.error a'));
        $this->assertStringEndsWith('options-media.php#tiny-compress-images', $element->getAttribute('href'));
    }

    public function testShouldShowNoNoticeIfApiKeyIsSet() {
        $this->set_api_key('1234');
        $elements = self::$driver->findElements(WebDriverBy::cssSelector('.error a'));
        $this->assertEquals(0, count($elements));
    }

    public function testNoApiKeyNoticeShouldLinkToSettings() {
        self::$driver->findElement(WebDriverBy::cssSelector('.error a'))->click();
        $this->assertStringEndsWith('options-media.php#tiny-compress-images', self::$driver->getCurrentURL());
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

    public function testShouldShowTotalImagesInfo() {
        $elements = self::$driver->findElement(WebDriverBy::id('tiny-image-sizes-notice'))->findElements(WebDriverBy::tagName('p'));
        $statuses = array_map('innerText', $elements);
        $this->assertContains('With these settings you can compress 100 images for free each month.', $statuses);
    }

    public function testShouldUpdateTotalImagesInfo() {
        $element = self::$driver->findElement(
            WebDriverBy::xpath('//input[@type="checkbox" and @name="tinypng_sizes[0]" and @checked="checked"]'));
        $element->click();
        self::$driver->wait(2)->until(WebDriverExpectedCondition::textToBePresentInElement(
            WebDriverBy::cssSelector('#tiny-image-sizes-notice'), 'With these settings you can compress 125 images for free each month.'));
        // Not really necessary anymore to assert this.
        $elements = self::$driver->findElement(WebDriverBy::id('tiny-image-sizes-notice'))->findElements(WebDriverBy::tagName('p'));
        $statuses = array_map('innerText', $elements);
        $this->assertContains('With these settings you can compress 125 images for free each month.', $statuses);
    }

    public function testShouldShowCorrectNoImageSizesInfo() {
        $elements = self::$driver->findElements(
            WebDriverBy::xpath('//input[@type="checkbox" and starts-with(@name, "tinypng_sizes") and @checked="checked"]'));
        foreach ($elements as $element) {
            $element->click();
        }
        self::$driver->wait(2)->until(WebDriverExpectedCondition::textToBePresentInElement(
            WebDriverBy::cssSelector('#tiny-image-sizes-notice'), 'With these settings no images will be compressed.'));
        // Not really necessary anymore to assert this.
        $elements = self::$driver->findElement(WebDriverBy::id('tiny-image-sizes-notice'))->findElements(WebDriverBy::tagName('p'));
        $statuses = array_map('innerText', $elements);
        $this->assertContains('With these settings no images will be compressed.', $statuses);
    }

    public function testStatusPresenceOK() {
        reset_webservice();
        $this->set_api_key('PNG123');
        self::$driver->wait(2)->until(WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::cssSelector('#tiny-compress-status p')));
        $elements = self::$driver->findElement(WebDriverBy::id('tiny-compress-status'))->findElements(WebDriverBy::tagName('p'));
        $statuses = array_map('innerText', $elements);
        $this->assertContains('API connection successful', $statuses);
        $this->assertContains('You have made 0 compressions this month.', $statuses);
    }

    public function testStatusPresenseFail() {
        $this->set_api_key('INVALID123');
        self::$driver->wait(2)->until(WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::cssSelector('#tiny-compress-status p')));
        $elements = self::$driver->findElement(WebDriverBy::id('tiny-compress-status'))->findElements(WebDriverBy::tagName('p'));
        $statuses = array_map('innerText', $elements);
        $this->assertContains('API connection unsuccessful', $statuses);
    }
}
