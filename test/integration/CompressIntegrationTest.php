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
}
