<?php

require_once dirname( __FILE__ ) . '/TinyTestCase.php';

class Tiny_Image_Size_Test extends Tiny_TestCase {
	protected $original;
	protected $thumbnail;
	protected $small;
	protected $medium;
	protected $large;

	public function set_up() {
		parent::set_up();

		$this->wp->createImagesFromJSON( $this->json( 'image_filesystem_data' ) );
		$this->wp->setTinyMetadata( 1, $this->json( 'image_database_metadata' ) );
		$tiny_image = new Tiny_Image( new Tiny_Settings(), 1, $this->json( '_wp_attachment_metadata' ) );

		$this->original = $tiny_image->get_image_size();
		$this->thumbnail = $tiny_image->get_image_size( 'thumbnail' );
		$this->small = $tiny_image->get_image_size( 'small' );
		$this->medium = $tiny_image->get_image_size( 'medium' );
		$this->large = $tiny_image->get_image_size( 'large' );
	}

	public function test_end_time_should_return_end_from_meta() {
		$this->assertEquals( 1447925138, $this->original->end_time() );
	}

	public function test_end_time_should_return_end_from_timestamp_if_end_is_unavailable() {
		$this->assertEquals( 1437925244, $this->thumbnail->end_time() );
	}

	public function test_end_time_should_return_null_if_unavailable() {
		$this->assertEquals( null, $this->medium->end_time() );
	}

	public function test_add_tiny_meta_start_should_add_start_time() {
		$this->large->add_tiny_meta_start();
		$this->assertEqualWithinDelta( time(), $this->large->meta['start'], 2 );
	}

	public function test_add_tiny_meta_start_should_unset_previous_response() {
		$this->medium->add_tiny_meta_start();
		$this->assertEqualWithinDelta( time(), $this->medium->meta['start'], 2 );
	}

	public function test_add_tiny_meta_should_add_end_time() {
		$this->large->add_tiny_meta_start();
		$this->large->add_tiny_meta( array(
			'input' => array(
				'size' => 1024,
			),
			'output' => array(
				'size' => 1024,
			),
		) );
		$this->assertEqualWithinDelta( time(), $this->large->meta['end'], 2 );
	}

	public function test_add_response_should_response() {
		$this->large->add_tiny_meta_start();
		$this->large->add_tiny_meta( array(
			'input' => array(
				'size' => 1024,
			),
			'output' => array(
				'size' => 1024,
			),
		) );
		$actual = $this->large->meta;
		unset( $actual['end'] );
		$this->assertEquals( array(
			'input' => array(
				'size' => 1024,
			),
			'output' => array(
				'size' => 1024,
			),
		), $actual );
	}

	public function test_add_response_should_not_add_if_no_request_was_made() {
		$this->large->add_tiny_meta( array(
			'input' => array(
				'size' => 1024,
			),
			'output' => array(
				'size' => 1024,
			),
		) );
		$this->assertEquals( array(), $this->large->meta );
	}

	public function test_add_exception_should_add_message_and_error() {
		$this->large->add_tiny_meta_start();
		$this->large->add_tiny_meta_error( new Tiny_Exception( 'Image could not be found', 'Not found' ) );
		unset( $this->large->meta['timestamp'] );
		$this->assertEquals( array(
			'error' => 'Not found',
			'message' => 'Image could not be found',
		),  $this->large->meta );
	}

	public function test_add_exception_should_add_timestamp() {
		$this->large->add_tiny_meta_start();
		$this->large->add_tiny_meta_error( new Tiny_Exception( 'Image could not be found', 'Not found' ) );
		$this->assertEqualWithinDelta( time(), $this->large->meta['timestamp'], 2 );
	}

	public function test_add_exception_should_not_add_if_no_request_was_made() {
		$this->large->add_tiny_meta_error( new Tiny_Exception( 'Image could not be found', 'Not found' ) );
		unset( $this->large->meta['timestamp'] );
		$this->assertEquals( array(), $this->large->meta );
	}

	public function test_image_has_been_compressed_if_meta_has_output() {
		$this->assertTrue( $this->original->has_been_compressed() );
	}

	public function test_image_has_not_been_compressed_if_meta_does_not_have_output() {
		$this->assertFalse( $this->large->has_been_compressed() );
	}

	public function test_image_size_filename() {
		$image_size = new Tiny_Image_Size( 'random_file_name.jpg' );
		$this->assertEquals( 'random_file_name.jpg', $image_size->filename );
	}

	public function test_image_does_not_still_exist_if_file_does_not_exist() {
		$image = new Tiny_Image_Size( 'file_that_does_not_exist.jpg' );
		$this->assertFalse( $image->still_exists() );
	}

	public function test_image_still_exists_if_file_exists() {
		$this->assertTrue( $this->original->still_exists() );
	}

	public function test_image_compressed_should_return_true_if_file_exists_and_size_is_same() {
		$this->assertTrue( $this->original->compressed() );
	}

	public function test_image_compressed_should_return_false_if_size_is_inequal_to_meta() {
		$this->wp->createImage( 37857, '2015/09', 'tinypng_gravatar-150x150.png' );
		$this->assertFalse( $this->thumbnail->compressed() );
	}

	public function test_image_modified_should_return_true_if_size_is_inequal_to_meta() {
		$this->wp->createImage( 37857, '2015/09', 'tinypng_gravatar-150x150.png' );
		$this->assertTrue( $this->thumbnail->modified() );
	}

	public function test_image_modified_should_return_false_if_compressed_correctly() {
		$this->assertFalse( $this->original->modified() );
	}

	public function test_uncompressed_should_return_true_if_image_exist_and_is_uncompressed() {
		$this->wp->createImage( 37857, '2015/09', 'tinypng_gravatar-150x150.png' );
		$this->assertTrue( $this->thumbnail->uncompressed() );
	}

	public function test_uncompressed_should_return_false_if_image_exist_and_is_compressed() {
		$this->assertFalse( $this->original->uncompressed() );
	}

	public function test_in_progress_should_return_false_if_meta_start_is_long_ago() {
		$image_size = new Tiny_Image_Size( 'test.jpg' );
		$one_hour_ago = date( 'U' ) - (60 * 60);
		$image_size->meta['start'] = $one_hour_ago;
		$this->assertFalse( $image_size->in_progress() );
	}

	public function test_in_progress_should_return_truef_meta_start_is_recent() {
		$image_size = new Tiny_Image_Size( 'test.jpg' );
		$two_minutes_ago = date( 'U' ) - (60 * 2);
		$image_size->meta['start'] = $two_minutes_ago;
		$this->assertTrue( $image_size->in_progress() );
	}

	public function test_in_progress_should_return_false_if_meta_contains_start_and_output() {
		$this->assertFalse( $this->original->in_progress() );
	}

	public function test_in_progress_should_return_false_if_meta_contains_timestamp_and_output() {
		$this->assertFalse( $this->thumbnail->in_progress() );
	}

	public function test_resized_should_return_true_if_meta_have_output_and_resized() {
		$this->assertTrue( $this->original->resized() );
	}

	public function test_resized_should_return_false_if_meta_have_output_and_not_resized() {
		$this->assertFalse( $this->thumbnail->resized() );
	}

	public function test_will_read_mimetype_from_file() {
		// because files in the virtual file system are not really files but empty strings, it is a text/plain.
		$this->assertEquals( $this->original->mimetype(), 'text/plain');
	}

	/**
	 * Customers can select multiple images in the media library and select
	 * images that have already been compressed. We do not want to modify these images.
	 * When image is already compressed, it will not be modified.
	 */
	public function test_when_compressed_will_skip_marking()
	{
		$image_size = $this->getMockBuilder(Tiny_Image_Size::class)
			->onlyMethods(['add_tiny_meta', 'has_been_compressed'])
			->setConstructorArgs(['2015/09/tinypng_gravatar.png'])
			->getMock();

		// mock has_been_compressed to return true
		$image_size->method('has_been_compressed')
			->willReturn(true);

		// assert if that is true
		$this->assertTrue($image_size->has_been_compressed());

		// execute subject of test
		$image_size->mark_as_compressed();

		// validate if add_tiny_meta has not been called
		$image_size->expects($this->never())->method('add_tiny_meta');
	}

	/**
	 * When image is not compressed, it will not marked as compressed
	 */
	public function test_when_not_compressed_will_mark_as_compressed()
	{
		$image_size = new Tiny_Image_Size('2015/09/tinypng_gravatar.png');

		// assert if that is true
		$this->assertFalse($image_size->has_been_compressed());

		// execute subject of test
		$image_size->mark_as_compressed();

		// should now be compressed
		$this->assertTrue($image_size->has_been_compressed());
	}

	/**
	 * Users can still mark an image as converted when compression has already been done.
	 */
	public function test_when_compressed_but_unconverted_will_add_convert_meta()
	{
		$image_size = new Tiny_Image_Size('2015/09/tinypng_gravatar.png');
		$image_size->mark_as_compressed();
		$this->assertTrue($image_size->has_been_compressed());
		
		$this->assertFalse($image_size->has_been_converted());

		$image_size->mark_as_compressed(true);
		$this->assertTrue($image_size->has_been_converted());
	}
}
