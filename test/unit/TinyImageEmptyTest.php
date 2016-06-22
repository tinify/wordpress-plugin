<?php

require_once dirname( __FILE__ ) . '/TinyTestCase.php';

class Tiny_Image_Empty_Test extends TinyTestCase {
	public function setUp() {
		parent::setUp();

		$this->wp->createImagesFromJSON( $this->json( 'virtual_images' ) );
		$this->wp->setTinyMetadata( 1, '' );
		$this->subject = new Tiny_Image( 1, $this->json( '_wp_attachment_metadata' ) );
	}

	public function testGetSavings() {
		$this->assertEquals( 0, $this->subject->get_savings( $this->subject->get_statistics() ) );
	}

	public function testGetStatistics() {
		$this->assertEquals( array(
			'initial_total_size' => 328670,
			'optimized_total_size' => 328670,
			'image_sizes_optimized' => 0,
			'available_unoptimised_sizes' => 4,
		), $this->subject->get_statistics() );
	}
}
