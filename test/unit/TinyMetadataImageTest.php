<?php

require_once(dirname(__FILE__) . "/TinyTestCase.php");

class Tiny_Metadata_Image_Test extends TinyTestCase {
    public function setUp() {
        parent::setUp();

        $wp_meta = $this->json("_wp_attachment_metadata");
        $tiny_meta = $this->json("tiny_compress_images");

        $this->wp->setMetadata(1, $tiny_meta);
        $this->wp->createImagesFromMeta($wp_meta, $tiny_meta, 137856);
        $metadata = new Tiny_Metadata(1, $wp_meta);

        $this->original = $metadata->get_image();
        $this->thumbnail = $metadata->get_image('thumbnail');
        $this->small = $metadata->get_image('small');
        $this->medium = $metadata->get_image('medium');
        $this->large = $metadata->get_image('large');
    }

    public function testEndTimeShouldReturnEndFromMeta() {
        $this->assertEquals(1447925138, $this->original->end_time());
    }

    public function testEndTimeShouldReturnEndFromTimestampIfEndIsUnavailable() {
        $this->assertEquals(1447925244, $this->thumbnail->end_time());
    }

    public function testEndTimeShouldReturnNullIfUnavailable() {
        $this->assertEquals(null, $this->medium->end_time());
    }

    public function testAddRequestShouldAddStartTime() {
        $this->large->add_request();
        $this->assertEqualWithinDelta(time(), $this->large->meta['start'], 2);
    }

    public function testAddRequestShouldUnsetPreviousResponse() {
        $this->medium->add_request();
        $this->assertEqualWithinDelta(time(), $this->medium->meta['start'], 2);
    }

    public function testAddResponseShouldAddEndTime() {
        $this->large->add_request();
        $this->large->add_response(array('input' => array('size' => 1024), 'output' => array('size' => 1024)));
        $this->assertEqualWithinDelta(time(), $this->large->meta['end'], 2);
    }

    public function testAddResponseShouldResponse() {
        $this->large->add_request();
        $this->large->add_response(array('input' => array('size' => 1024), 'output' => array('size' => 1024)));
        $actual = $this->large->meta;
        unset($actual['end']);
        $this->assertEquals(array('input' => array('size' => 1024), 'output' => array('size' => 1024)), $actual);
    }

    public function testAddResponseShouldNotAddIfNoRequestWasMade() {
        $this->large->add_response(array('input' => array('size' => 1024), 'output' => array('size' => 1024)));
        $this->assertEquals(array(), $this->large->meta);
    }

    public function testAddExceptionShouldAddMessageAndError() {
        $this->large->add_request();
        $this->large->add_exception(new Tiny_Exception("Image could not be found", "Not found"));
        unset($this->large->meta['timestamp']);
        $this->assertEquals(array('error' => 'Not found', 'message' => 'Image could not be found'),  $this->large->meta);
    }

    public function testAddExceptionShouldAddTimestamp() {
        $this->large->add_request();
        $this->large->add_exception(new Tiny_Exception("Image could not be found", "Not found"));
        $this->assertEqualWithinDelta(time(), $this->large->meta['timestamp'], 2);
    }

    public function testAddExceptionShouldNotAddIfNoRequestWasMade() {
        $this->large->add_exception(new Tiny_Exception("Image could not be found", "Not found"));
        unset($this->large->meta['timestamp']);
        $this->assertEquals(array(), $this->large->meta);
    }

    public function testImageHasBeenCompressedIfMetaHasOutput() {
        $this->assertTrue($this->original->has_been_compressed());
    }

    public function testImageHasNotBeenCompressedIfMetaDoesNotHaveOutput() {
        $this->assertFalse($this->large->has_been_compressed());
    }

    public function testImageDoesNotStillExistIfFileDoesNotExist() {
        $image = new Tiny_Metadata_Image('does_not_exist');
        $this->assertFalse($image->still_exists());
    }

    public function testImageStillExistsIfFileExists() {
        $this->assertTrue($this->original->still_exists());
    }

    public function testImageCompressedShouldReturnTrueIfFileExistsAndSizeIsSame() {
        $this->assertTrue($this->original->compressed());
    }

    public function testImageCompressedShouldReturnFalseIfSizeIsInequalToMeta() {
        $this->wp->createImage(37857, "2015/09", "tinypng_gravatar-150x150.png");
        $this->assertFalse($this->thumbnail->compressed());
    }

    public function testImageModifiedShouldReturnTrueIfSizeIsInequalToMeta() {
        $this->wp->createImage(37857, "2015/09", "tinypng_gravatar-150x150.png");
        $this->assertTrue($this->thumbnail->modified());
    }

    public function testImageModifiedShouldReturnFalseIfCompressedCorrectly() {
        $this->assertFalse($this->original->modified());
    }

    public function testUncompressedShouldReturnTrueIfImageExistAndIsUncompressed() {
        $this->wp->createImage(37857, "2015/09", "tinypng_gravatar-150x150.png");
        $this->assertTrue($this->thumbnail->uncompressed());
    }

    public function testUncompressedShouldReturnFalseIfImageExistAndIsCompressed() {
        $this->assertFalse($this->original->uncompressed());
    }

    public function testInProgressShouldReturnFalseIfMetaStartIsLongAgo() {
        $image = new Tiny_Metadata_Image("test.jpg", "");
        $one_hour_ago = date('U') - (60 * 60);
        $image->meta['start'] = $one_hour_ago;
        $this->assertFalse($image->in_progress());
    }

    public function testInProgressShouldReturnTruefMetaStartIsRecent() {
        $image = new Tiny_Metadata_Image("test.jpg", "");
        $two_minutes_ago = date('U') - (60 * 2);
        $image->meta['start'] = $two_minutes_ago;
        $this->assertTrue($image->in_progress());
    }

    public function testInProgressShouldReturnFalseIfMetaContainsStartAndOutput() {
        $this->assertFalse($this->original->in_progress());
    }

    public function testInProgressShouldReturnFalseIfMetaContainsTimestampAndOutput() {
        $this->assertFalse($this->thumbnail->in_progress());
    }

    public function testResizedShouldReturnTrueIfMetaHaveOutputAndResized() {
        $this->assertTrue($this->original->resized());
    }

    public function testResizedShouldReturnFalseIfMetaHaveOutputAndNotResized() {
        $this->assertFalse($this->thumbnail->resized());
    }
}
