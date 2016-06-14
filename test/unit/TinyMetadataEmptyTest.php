<?php

require_once(dirname(__FILE__) . "/TinyTestCase.php");

class Tiny_Metadata_Empty_Test extends TinyTestCase {
    public function setUp() {
        parent::setUp();

        $wp_meta = $this->json("_wp_attachment_metadata");
        $tiny_meta = $this->json("tiny_compress_images");

        $this->wp->setMetadata(1, "");
        $this->wp->createImagesFromMeta($wp_meta, $tiny_meta, 137856);
        $this->subject = new Tiny_Metadata(1, $wp_meta);

    }

    public function testGetImageSizesCompressed() {
        $this->assertEquals(0, $this->subject->get_image_sizes_optimized());
    }

    public function testGetImageSizesUnCompressed() {
        $this->assertEquals(3, $this->subject->get_image_sizes_available_for_compression());
    }

    public function testGetSavings() {
        $this->assertEquals(0, $this->subject->get_savings());
    }

    public function testGetInitialTotalSize() {
        $this->assertEquals(233568, $this->subject->get_total_size_before_optimization());
    }

    public function testGetCompressedTotalSize() {
        $this->assertEquals(233568, $this->subject->get_total_size_after_optimization());
    }
}
