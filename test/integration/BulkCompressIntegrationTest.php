<?php

require_once(dirname(__FILE__) . "/IntegrationTestCase.php");

class BulkCompressIntegrationTest extends IntegrationTestCase {

    public function setUp() {
        parent::setUp();
    }

    public function tearDown() {
        clear_settings();
        clear_uploads();
        reset_webservice();
    }

    public function testBulkCompressActionShouldBePresentInMedia() {
        $this->upload_image(dirname(__FILE__) . '/../fixtures/input-example.png');
        self::$driver->get(wordpress('/wp-admin/upload.php?mode=list'));
        $this->assertEquals('Compress Images', self::$driver->findElement(
            WebDriverBy::cssSelector('select[name="action"] option[value="tiny_bulk_compress"]')
        )->getText());
    }

    private function prepare($normal=1, $large=0) {
        $this->set_api_key('PNG123');
        $this->enable_compression_sizes(array());

        for ($i = 0; $i < $normal; $i++) {
            $this->upload_image(dirname(__FILE__) . '/../fixtures/input-example.png');
        }
        for ($i = 0; $i < $large; $i++) {
            $this->upload_image(dirname(__FILE__) . '/../fixtures/input-large.png');
        }

        $this->enable_compression_sizes(array('thumbnail', 'medium', 'large'));
    }

    public function testBulkCompressShouldInMediaShouldRedirect() {
        $this->prepare();

        self::$driver->get(wordpress('/wp-admin/upload.php?mode=list'));
        $checkboxes = self::$driver->findElements(WebDriverBy::cssSelector('th input[type="checkbox"]'));
        $checkboxes[0]->click();

        self::$driver->findElement(WebDriverBy::cssSelector('select[name="action"] option[value="tiny_bulk_compress"]'))->click();
        self::$driver->findElement(WebDriverBy::cssSelector('div.actions input[value="Apply"]'))->click();

        self::$driver->wait(2)->until(WebDriverExpectedCondition::textToBePresentInElement(
            WebDriverBy::cssSelector('.updated'), 'All images are processed'));

        $this->assertContains("tools.php?page=tiny-bulk-compress&ids=", self::$driver->getCurrentUrl());
    }

    // TODO: More tests
}
