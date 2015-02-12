<?php

require_once(dirname(__FILE__) . "/IntegrationTestCase.php");

class BulkCompressIntegrationTest extends IntegrationTestCase {

    public function setUp() {
        parent::setUp();
    }

    public function tearDown() {
        $this->enable_compression_sizes(array('thumbnail', 'medium', 'large', 'post-thumbnail'));
        clear_uploads(self::$driver);
    }

    public function testBulkCompressActionShouldBePresent()
    {
        $this->upload_image(dirname(__FILE__) . '/../fixtures/example-tinypng.png');
        self::$driver->get(wordpress('/wp-admin/upload.php?mode=list'));
        $this->assertEquals('tinypng_bulk_compress', self::$driver->findElement(
            WebDriverBy::cssSelector('select[name="action"] option[value="tinypng_bulk_compress"]')
        )->getAttribute('value'));
    }

    public function testBulkCompressShouldCompressUncompressedSizes() {
        $this->enable_compression_sizes(array('thumbnail'));

        $this->set_api_key('PNG123');
        $this->upload_image(dirname(__FILE__) . '/../fixtures/example-large.png');

        $this->enable_compression_sizes(array('thumbnail', 'medium'));
        media_bulk_action(self::$driver, 'tinypng_bulk_compress');

        $this->assertContains('Compressed 2 out of 2 sizes', self::$driver->findElement(WebDriverBy::cssSelector('td.tiny-compress-images'))->getText());
    }
}
