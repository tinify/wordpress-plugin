<?php

require_once(dirname(__FILE__) . "/IntegrationTestCase.php");

class CompressIntegrationTest extends IntegrationTestCase {

    public function setUp() {
        parent::setUp();
    }

    public function tearDown() {
        clear_settings();
        clear_uploads();
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
            self::$driver->findElement(WebDriverBy::cssSelector('span.details'))->getText());
    }

    public function testShrink() {
        $this->set_api_key('PNG123');
        $this->upload_image(dirname(__FILE__) . '/../fixtures/input-example.png');
        $this->assertContains('sizes compressed',
            self::$driver->findElement(WebDriverBy::cssSelector('td.tiny-compress-images'))->getText());
    }

    public function testCompressButton() {
        $this->enable_compression_sizes(array('medium'));
        $this->set_api_key('PNG123');
        $this->upload_image(dirname(__FILE__) . '/../fixtures/input-example.png');
        $this->enable_compression_sizes(array('medium', 'large'));

        self::$driver->get(wordpress('/wp-admin/upload.php'));
        $this->assertContains('1 size compressed',
            self::$driver->findElement(WebDriverBy::cssSelector('td.tiny-compress-images'))->getText());
        $this->assertContains('1 size not compressed',
            self::$driver->findElement(WebDriverBy::cssSelector('td.tiny-compress-images'))->getText());
        self::$driver->findElement(WebDriverBy::cssSelector('td.tiny-compress-images button'))->click();
        self::$driver->wait(2)->until(WebDriverExpectedCondition::textToBePresentInElement(
            WebDriverBy::cssSelector('td.tiny-compress-images'), '2 sizes compressed'));
    }

    public function testLimitReached() {
        $this->set_api_key('LIMIT123');
        $this->upload_image(dirname(__FILE__) . '/../fixtures/input-example.png');
        $this->assertContains('You have reached your limit',
            self::$driver->findElement(WebDriverBy::cssSelector('div.error p'))->getText());
    }

    public function testLimitReachedDismisses() {
        $this->set_api_key('LIMIT123');
        $this->upload_image(dirname(__FILE__) . '/../fixtures/input-example.png');
        self::$driver->findElement(WebDriverBy::cssSelector('.tiny-notice button, .tiny-notice a.tiny-dismiss'))->click();
        self::$driver->wait(2)->until(WebDriverExpectedCondition::invisibilityOfElementWithText(
             WebDriverBy::cssSelector('.tiny-dismiss'), 'Dismiss'));

        self::$driver->get(wordpress('/wp-admin/options-media.php'));
        $this->assertEquals(0, count(self::$driver->findElements(WebDriverBy::cssSelector('div.error p'))));
    }

    public function testIncorrectJsonButton() {
        $this->enable_compression_sizes(array());
        $this->upload_image(dirname(__FILE__) . '/../fixtures/input-example.png');
        $this->enable_compression_sizes(array('medium', 'large'));

        $this->set_api_key('JSON1234');
        self::$driver->get(wordpress('/wp-admin/upload.php'));

        self::$driver->findElement(WebDriverBy::cssSelector('td.tiny-compress-images button'))->click();
        self::$driver->wait(2)->until(WebDriverExpectedCondition::textToBePresentInElement(
            WebDriverBy::cssSelector('td.tiny-compress-images'), 'JSON: Syntax error [4]'));
    }

    public function testResizeFit() {
        $this->set_api_key('JPG123');
        $this->enable_resize(300, 200);
        $this->upload_image(dirname(__FILE__) . '/../fixtures/input-large.png');
        self::$driver->findElement(WebDriverBy::cssSelector('td.tiny-compress-images a.thickbox'))->click();
        $this->assertContains('resized to 300x200',
            self::$driver->findElement(WebDriverBy::cssSelector('div.tiny-compression-details'))->getText());
        $this->view_edit_image();
        $this->assertContains('Dimensions: 300 × 200',
            self::$driver->findElement(WebDriverBy::cssSelector('div.misc-pub-dimensions'))->getText());
    }

    public function testResizeScale() {
        $this->set_api_key('JPG123');
        $this->enable_resize(0, 200);
        $this->upload_image(dirname(__FILE__) . '/../fixtures/input-large.jpg');
        self::$driver->findElement(WebDriverBy::cssSelector('td.tiny-compress-images a.thickbox'))->click();
        $this->assertContains('resized to 300x200', self::$driver->findElement(
            WebDriverBy::cssSelector('div.tiny-compression-details'))->getText());
        $this->view_edit_image();
        $this->assertContains('Dimensions: 300 × 200',
            self::$driver->findElement(WebDriverBy::cssSelector('div.misc-pub-dimensions'))->getText());
    }

    public function testResizeNotNeeded()
    {
        $this->set_api_key('JPG123');
        $this->enable_resize(30000, 20000);
        $this->upload_image(dirname(__FILE__) . '/../fixtures/input-large.jpg');
        self::$driver->findElement(WebDriverBy::cssSelector('td.tiny-compress-images a.thickbox'))->click();
        $this->assertNotContains('resized',
            self::$driver->findElement(WebDriverBy::cssSelector('div.tiny-compression-details'))->getText());
        $this->view_edit_image();
        $this->assertContains('Dimensions: 1080 × 330',
            self::$driver->findElement(WebDriverBy::cssSelector('div.misc-pub-dimensions'))->getText());
    }

    public function testResizeDisabled()
    {
        $this->set_api_key('JPG123');
        $this->enable_resize(300, 200);
        $this->disable_resize();
        $this->upload_image(dirname(__FILE__) . '/../fixtures/input-large.jpg');
        self::$driver->findElement(WebDriverBy::cssSelector('td.tiny-compress-images a.thickbox'))->click();
        $this->assertNotContains('resized',
            self::$driver->findElement(WebDriverBy::cssSelector('div.tiny-compression-details'))->getText());
        $this->view_edit_image();
        $this->assertContains('Dimensions: 1080 × 330',
            self::$driver->findElement(WebDriverBy::cssSelector('div.misc-pub-dimensions'))->getText());
    }
}
