<?php

require_once(dirname(__FILE__) . "/TinyTestCase.php");

class Tiny_Image_Test extends TinyTestCase {
    public function setUp() {
        parent::setUp();

        $this->wp->createImagesFromJSON($this->json("virtual_images"));
        $this->wp->setTinyMetadata(1, $this->json("tiny_compress_images"));
        $this->subject = new Tiny_Image(1, $this->json("_wp_attachment_metadata"));
    }

    public function testUpdateWpMetadataShouldNotUpdateWithNoResizedOriginal() {
        $tiny_image = new Tiny_Image(150, $this->json("_wp_attachment_metadata_duplicates"));
        $wp_metadata = array(
            'width' => 2000,
            'height' => 1000
        );
        $this->assertEquals(array('width' => 2000, 'height' => 1000), $tiny_image->update_wp_metadata($wp_metadata));
    }

    public function testUpdateWpMetadataShouldUpdateWithResizedOriginal() {
        $tiny_image = new Tiny_Image(150, $this->json("_wp_attachment_metadata_duplicates"));
        $wp_metadata = array(
            'width' => 2000,
            'height' => 1000
        );
        $tiny_image->get_image_size()->add_request();
        $tiny_image->get_image_size()->add_response(array('output' => array('width' => 200, 'height' => 100)));
        $this->assertEquals(array('width' => 200, 'height' => 100), $tiny_image->update_wp_metadata($wp_metadata));
    }

    public function testGetImagesShouldReturnAllImages() {
        $this->assertEquals(array(Tiny_Image::ORIGINAL, 'medium', 'thumbnail', 'failed', 'large', 'small'), array_keys(
            $this->subject->get_image_sizes()));
    }

    public function testFilterImagesShouldFilterCorrectly() {
        $this->assertEquals(array(Tiny_Image::ORIGINAL, 'medium', 'thumbnail'), array_keys(
            $this->subject->filter_image_sizes('compressed')));
    }

    public function testFilterImagesShouldFilterCorrectlyWhenSizesAreGiven() {
        $this->assertEquals(array(Tiny_Image::ORIGINAL), array_keys(
            $this->subject->filter_image_sizes('compressed', array(Tiny_Image::ORIGINAL, 'invalid'))
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
        $this->subject->get_image_size()->add_request("large");
        $this->subject->get_image_size()->add_exception(new Tiny_Exception('Could not download output', 'OutputError'), "large");
        $this->assertEquals("Could not download output", $this->subject->get_latest_error());
    }

    public function testGetImageSizesCompressed() {
        $this->assertEquals(3, $this->subject->get_image_sizes_optimized());
    }

    public function testGetImageSizesAvailableForCompressionWhenFileModified() {
        $this->wp->createImage(37857, "2015/09", "tinypng_gravatar-150x150.png");
        $this->assertEquals(2, $this->subject->get_image_sizes_available_for_compression());
    }

    public function testGetSavings() {
        $this->assertEquals(7.2973018711464, $this->subject->get_savings());
    }

    public function testGetInitialTotalSize() {
        $this->assertEquals(354542, $this->subject->get_total_size_before_optimization());
    }

    public function testGetCompressedTotalSize() {
        $this->assertEquals(328670, $this->subject->get_total_size_after_optimization());
    }
}
