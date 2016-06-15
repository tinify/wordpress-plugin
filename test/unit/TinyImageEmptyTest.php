<?php

require_once(dirname(__FILE__) . "/TinyTestCase.php");

class Tiny_Image_Empty_Test extends TinyTestCase {
    public function setUp() {
        parent::setUp();

        $this->wp->createImagesFromJSON($this->json("virtual_images"));
        $this->wp->setTinyMetadata(1, "");
        $this->subject = new Tiny_Image(1, $this->json("_wp_attachment_metadata"));
    }

    public function testGetImageSizesCompressed() {
        $this->assertEquals(0, $this->subject->get_image_sizes_optimized());
    }

    public function testGetImageSizesUnCompressed() {
        $this->assertEquals(4, $this->subject->get_image_sizes_available_for_compression());
    }

    public function testGetSavings() {
        $this->assertEquals(0, $this->subject->get_savings());
    }

    public function testGetInitialTotalSize() {
        $this->assertEquals(328670, $this->subject->get_total_size_before_optimization());
    }

    public function testGetCompressedTotalSize() {
        $this->assertEquals(328670, $this->subject->get_total_size_after_optimization());
    }
}
