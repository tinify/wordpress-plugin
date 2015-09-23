<?php

require_once(dirname(__FILE__) . "/TinyTestCase.php");

class Tiny_Metadata_Test extends TinyTestCase {
    public function setUp() {
        parent::setUp();

        $this->wp->addOption("tinypng_api_key", "test123");
        $this->wp->addOption("tinypng_sizes[0]", "on");
        $this->wp->addOption("tinypng_sizes[custom-size]", "on");
        $this->wp->addOption("tinypng_sizes[custom-size-2]", "on");
        $this->wp->addImageSize('custom-size', array('width' => 150, 'height' => 150));
        $this->wp->addImageSize('custom-size-2', array('width' => 150, 'height' => 150));
        $this->wp->createImages();

        $wp_metadata = array();
        $wp_metadata['file'] = "2015/09/panda.jpg";
        $wp_metadata['width'] = 1080;
        $wp_metadata['height'] = 720;
        $wp_metadata['sizes'] = array();

        $wp_metadata['sizes']['custom-size'] = array();
        $wp_metadata['sizes']['custom-size']['file'] = "panda-150x150.jpg";
        $wp_metadata['sizes']['custom-size']['width'] = 150;
        $wp_metadata['sizes']['custom-size']['height'] = 150;

        $wp_metadata['sizes']['custom-size-2'] = array();
        $wp_metadata['sizes']['custom-size-2']['file'] = "panda-150x150.jpg";
        $wp_metadata['sizes']['custom-size-2']['width'] = 150;
        $wp_metadata['sizes']['custom-size-2']['height'] = 150;

        $this->subject = new Tiny_Metadata(150, $wp_metadata);
    }

    public function testGetUncompressedSizesShouldReturnOnlyUniqueSizes() {
        $tinify_sizes = array(Tiny_Metadata::ORIGINAL, "custom-size", "custom-size-2");
        $uncompressed_sizes = array(Tiny_Metadata::ORIGINAL, "custom-size");
        $this->assertEquals($uncompressed_sizes, $this->subject->get_uncompressed_sizes($tinify_sizes));
    }
}
