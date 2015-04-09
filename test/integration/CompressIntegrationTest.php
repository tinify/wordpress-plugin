<?php

require_once(dirname(__FILE__) . "/IntegrationTestCase.php");

class CompressIntegrationTest extends IntegrationTestCase {

    public function setUp() {
        parent::setUp();
    }

    public function tearDown() {
        clear_uploads(self::$driver);
    }

    public function testInvalidCredentialsShouldStillUploadImage()
    {
        $this->set_api_key('1234');
        $this->upload_image(dirname(__FILE__) . '/../fixtures/example-tinypng.png');
        $this->assertContains('example-tinypng', self::$driver->findElement(WebDriverBy::xpath('//img[contains(@src, "example-tinypng")]'))->getAttribute('src'));
    }

    public function testInvalidCredentialsShouldShowError()
    {
        $this->set_api_key('1234');
        $this->upload_image(dirname(__FILE__) . '/../fixtures/example-tinypng.png');
        $this->assertContains('Latest error: Credentials are invalid', self::$driver->findElement(WebDriverBy::cssSelector('span.error'))->getText());
    }

    public function testShrink() {
        $this->set_api_key('PNG123');
        $this->upload_image(dirname(__FILE__) . '/../fixtures/example-tinypng.png');
        $this->assertContains('Compressed size', self::$driver->findElement(WebDriverBy::cssSelector('td.tiny-compress-images'))->getText());
    }

    public function testLimitReached() {
        $this->set_api_key('LIMIT123');
        $this->upload_image(dirname(__FILE__) . '/../fixtures/example-tinypng.png');
        $elements = self::$driver->findElement(WebDriverBy::className('error'))->findElements(WebDriverBy::tagName('p'));
        $error_messages = array_map('innerText', $elements);
        $this->assertContains('Tiny Compress Images: You have reached your limit of 500 compressions this month. Upgrade your TinyPNG API subscription if you like to compress more images. | Dismiss', $error_messages);
    }

    public function testLimitReachedDismisses() {
        $this->set_api_key('LIMIT123');
        $this->upload_image(dirname(__FILE__) . '/../fixtures/example-tinypng.png');
        $dismiss_links = self::$driver->findElements(WebDriverBy::xpath('//a[contains(@href, "tinypng_limit_reached=0")]'));
        $dismiss_links[0]->click();
        $elements = self::$driver->findElement(WebDriverBy::className('error'))->findElements(WebDriverBy::tagName('p'));
        $error_messages = array_map('innerText', $elements);
        $this->assertNotContains('Tiny Compress Images: You have reached your limit of 500 compressions this month. Upgrade your TinyPNG API subscription if you like to compress more images. | Dismiss', $error_messages);
    }
}
