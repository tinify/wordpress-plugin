<?php

require_once(dirname(__FILE__) . "/IntegrationTestCase.php");

class BulkCompressIntegrationTest extends IntegrationTestCase {

    public function setUp() {
        parent::setUp();
    }

    public function tearDown() {
        clear_settings();
        clear_uploads();
    }

    public function testBulkCompressActionShouldBePresent()
    {
        $this->upload_image(dirname(__FILE__) . '/../fixtures/input-example.png');
        self::$driver->get(wordpress('/wp-admin/upload.php?mode=list'));
        $this->assertEquals('Compress all uncompressed sizes', self::$driver->findElement(
            WebDriverBy::cssSelector('select[name="action"] option[value="tiny_bulk_compress"]')
        )->getText());
    }

    public function testBulkCompressShouldCompressUncompressedSizes() {
        $this->enable_compression_sizes(array('thumbnail'));

        $this->set_api_key('PNG123');
        $this->upload_image(dirname(__FILE__) . '/../fixtures/input-large.png');

        $this->enable_compression_sizes(array('thumbnail', 'medium'));

        self::$driver->get(wordpress('/wp-admin/upload.php?mode=list'));
        $checkboxes = self::$driver->findElements(WebDriverBy::cssSelector('th input[type="checkbox"]'));
        $checkboxes[0]->click();
        self::$driver->findElement(WebDriverBy::cssSelector('select[name="action"] option[value="' . 'tiny_bulk_compress' . '"]'))->click();
        self::$driver->findElement(WebDriverBy::cssSelector('div.actions input[value="Apply"]'))->click();

        $this->assertContains('Compressed 2 out of 2 sizes', self::$driver->findElement(WebDriverBy::cssSelector('td.tiny-compress-images'))->getText());
    }
}
