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
        $this->upload_image(dirname(__FILE__) . '/../fixtures/input-example.png');
        $this->assertContains('input-example',
            self::$driver->findElement(WebDriverBy::xpath('//img[contains(@src, "input-example")]'))->getAttribute('src'));
    }

    public function testInvalidCredentialsShouldShowError()
    {
        $this->set_api_key('1234');
        $this->upload_image(dirname(__FILE__) . '/../fixtures/input-example.png');
        $this->assertContains('Latest error: Credentials are invalid',
            self::$driver->findElement(WebDriverBy::cssSelector('span.error'))->getText());
    }

    public function testShrink() {
        $this->set_api_key('PNG123');
        $this->upload_image(dirname(__FILE__) . '/../fixtures/input-example.png');
        $this->assertContains('Compressed size',
            self::$driver->findElement(WebDriverBy::cssSelector('td.tiny-compress-images'))->getText());
    }

    public function testLimitReached() {
        $this->set_api_key('LIMIT123');
        $this->upload_image(dirname(__FILE__) . '/../fixtures/input-example.png');
        $this->assertContains('You have reached your limit',
            current(self::$driver->findElements(WebDriverBy::cssSelector('div.updated p')))->getText());
    }

    public function testLimitReachedDismisses() {
        $this->set_api_key('LIMIT123');
        $this->upload_image(dirname(__FILE__) . '/../fixtures/input-example.png');
        self::$driver->findElement(WebDriverBy::cssSelector('a.tiny-dismiss'))->click();
        self::$driver->wait(2)->until(WebDriverExpectedCondition::invisibilityOfElementWithText(WebDriverBy::cssSelector('a.tiny-dismiss'), 'Dismiss'));
    }
}
