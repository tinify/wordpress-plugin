<?php

require_once(dirname(__FILE__) . "/TinyTestCase.php");

class Tiny_Metadata_Test extends TinyTestCase {
    public function setUp() {
        parent::setUp();

        $meta = array(Tiny_Metadata::META_KEY => array(
            Tiny_Metadata::ORIGINAL => array(
                "input" => array("size" => 146480),
                "output" => array("size" => 137856, "resized" => true)),
            "thumbnail" => array(
                "input" => array("size" => 46480),
                "output" => array("size" => 37856)),
            "medium" => array(
                "input" => array("size" => 66480),
                "output" => array("size" => 57856)),
            "large" => array(
                "input" => array("size" => 66480)),
        ));
        $this->wp->setMetadata(1, $meta);
        $this->wp->createImagesFromMeta($this->json("wp_meta_default_sizes"), $meta, 137856);
        $this->subject = new Tiny_Metadata(1, $this->json("wp_meta_default_sizes"));
    }

    public function testUpdateWpMetadataShouldNotUpdateWithNoResizedOriginal() {
        $tiny_meta = new Tiny_Metadata(150, $this->json("wp_meta_sizes_with_same_files"));
        $wp_metadata = array(
            'width' => 2000,
            'height' => 1000
        );
        $this->assertEquals(array('width' => 2000, 'height' => 1000), $tiny_meta->update_wp_metadata($wp_metadata));
    }

    public function testUpdateWpMetadataShouldUpdateWithResizedOriginal() {
        $tiny_meta = new Tiny_Metadata(150, $this->json("wp_meta_sizes_with_same_files"));
        $wp_metadata = array(
            'width' => 2000,
            'height' => 1000
        );
        $tiny_meta->get_image()->add_request();
        $tiny_meta->get_image()->add_response(array('output' => array('width' => 200, 'height' => 100)));
        $this->assertEquals(array('width' => 200, 'height' => 100), $tiny_meta->update_wp_metadata($wp_metadata));
    }

    public function testGetImagesShouldReturnAllImages() {
        $this->assertEquals(array(Tiny_Metadata::ORIGINAL, 'medium', 'thumbnail', 'large', 'small'), array_keys(
            $this->subject->get_images()));
    }

    public function testFilterImagesShouldFilterCorrectly() {
        $this->assertEquals(array(Tiny_Metadata::ORIGINAL, 'thumbnail', 'medium'), array_keys(
            $this->subject->filter_images('compressed')));
    }

    public function testFilterImagesShouldFilterCorrectlyWhenSizesAreGiven() {
        $this->assertEquals(array(Tiny_Metadata::ORIGINAL), array_keys(
            $this->subject->filter_images('compressed', array(Tiny_Metadata::ORIGINAL, 'invalid'))
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
            ), $this->subject->get_count(array('compressed', 'resized'), array(Tiny_Metadata::ORIGINAL, 'invalid'))
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

    public function testGetImageSizesAvailableForCompression() {
        $active_sizes = array(0 => Tiny_Metadata::ORIGINAL, 1 => "thumbnail", 2 => "small", 3 => "medium", 4 => "large");
        // 1 size (small) can be compressed but it doesn't exist on the filesystem
        $this->assertEquals(0, $this->subject->get_image_sizes_available_for_compression($active_sizes));
    }

    public function testGetInitialTotalSize() {
        $this->assertEquals(325920, $this->subject->get_total_size_before_optimization());
    }

    public function testGetCompressedTotalSize() {
        $this->assertEquals(300048, $this->subject->get_total_size_after_optimization());
    }
}
