<?php

require_once(dirname(__FILE__) . "/TinyTestCase.php");

class Tiny_Image_Test extends TinyTestCase {
    public function setUp() {
        parent::setUp();

        $wp_meta = $this->json("_wp_attachment_metadata");
        $tiny_meta = $this->json("tiny_compress_images");

        $this->wp->setMetadata(1, $tiny_meta);
        $this->wp->createImagesFromMeta($wp_meta, $tiny_meta, 137856);
        $this->subject = new Tiny_Image(1, $wp_meta);
    }

    public function testUpdateWpMetadataShouldNotUpdateWithNoResizedOriginal() {
        $tiny_meta = new Tiny_Image(150, $this->json("_wp_attachment_metadata_duplicates"));
        $wp_metadata = array(
            'width' => 2000,
            'height' => 1000
        );
        $this->assertEquals(array('width' => 2000, 'height' => 1000), $tiny_meta->update_wp_metadata($wp_metadata));
    }

    public function testUpdateWpMetadataShouldUpdateWithResizedOriginal() {
        $tiny_meta = new Tiny_Image(150, $this->json("_wp_attachment_metadata_duplicates"));
        $wp_metadata = array(
            'width' => 2000,
            'height' => 1000
        );
        $tiny_meta->get_image()->add_request();
        $tiny_meta->get_image()->add_response(array('output' => array('width' => 200, 'height' => 100)));
        $this->assertEquals(array('width' => 200, 'height' => 100), $tiny_meta->update_wp_metadata($wp_metadata));
    }

    public function testGetImagesShouldReturnAllImages() {
        $this->assertEquals(array(Tiny_Image::ORIGINAL, 'medium', 'thumbnail', 'failed', 'large', 'small'), array_keys(
            $this->subject->get_images()));
    }

    public function testFilterImagesShouldFilterCorrectly() {
        $this->assertEquals(array(Tiny_Image::ORIGINAL, 'medium', 'thumbnail'), array_keys(
            $this->subject->filter_images('compressed')));
    }

    public function testFilterImagesShouldFilterCorrectlyWhenSizesAreGiven() {
        $this->assertEquals(array(Tiny_Image::ORIGINAL), array_keys(
            $this->subject->filter_images('compressed', array(Tiny_Image::ORIGINAL, 'invalid'))
        ));
    }

    public function testGetCountShouldAddCountCorrectly() {
        $this->assertEquals(array(
            'compressed' => 3,
            'resized' => 1,
            ), $this->subject->get_count(array('compressed', 'resized'))
        );
    }

    public function testGetCountShouldAddCountCorrectlyWhenSizesAreGiven() {
        $this->assertEquals(array(
            'compressed' => 1,
            'resized' => 1,
            ), $this->subject->get_count(array('compressed', 'resized'), array(Tiny_Image::ORIGINAL, 'invalid'))
        );
    }

    public function testGetLatestErrorShouldReturnMessage() {
        $this->subject->get_image()->add_request("large");
        $this->subject->get_image()->add_exception(new Tiny_Exception('Could not download output', 'OutputError'), "large");
        $this->assertEquals("Could not download output", $this->subject->get_latest_error());
    }

    public function testGetImageSizesCompressed() {
        $this->assertEquals(3, $this->subject->get_image_sizes_optimized());
    }

    public function testGetImageSizesAvailableForCompressionWhenFileModified() {
        $this->wp->createImage(37857, "2015/09", "tinypng_gravatar-150x150.png");
        $this->assertEquals(1, $this->subject->get_image_sizes_available_for_compression());
    }

    public function testGetSavings() {
        $this->assertEquals(9.9722479185939, $this->subject->get_savings());
    }

    public function testGetInitialTotalSize() {
        $this->assertEquals(259440, $this->subject->get_total_size_before_optimization());
    }

    public function testGetCompressedTotalSize() {
        $this->assertEquals(233568, $this->subject->get_total_size_after_optimization());
    }
}
