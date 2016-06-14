<?php

require_once(dirname(__FILE__) . "/TinyTestCase.php");

class Tiny_Metadata_Empty_Test extends TinyTestCase {
    public function setUp() {
        parent::setUp();

        $this->wp->setMetadata(1, array());
        $this->subject = new Tiny_Metadata(1, $this->json("wp_meta_default_sizes"));
    }

    public function testGetImageSizesCompressed() {
        $this->assertEquals(0, $this->subject->get_image_sizes_optimized());
    }

    public function testGetImageSizesUnCompressed() {
        $active_sizes = array(0 => Tiny_Metadata::ORIGINAL, 1 => "thumbnail", 2 => "small", 3 => "medium", 4 => "large");
        $this->assertEquals(4, $this->subject->get_image_sizes_available_for_compression($active_sizes));
    }

    public function testGetSavings() {
        $this->assertEquals(0, $this->subject->get_savings());
    }

    public function testGetInitialTotalSize() {
        $this->assertEquals(0, $this->subject->get_total_size_before_optimization());
    }

    public function testGetCompressedTotalSize() {
        $this->assertEquals(0, $this->subject->get_total_size_after_optimization());
    }
}
