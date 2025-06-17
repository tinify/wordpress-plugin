<?php

require_once dirname( __FILE__ ) . '/TinyTestCase.php';

class Tiny_Bulk_Optimization_Test extends Tiny_TestCase {
	public function set_up() {
		parent::set_up();
		
		$this->wp->createImagesFromJSON( $this->json( 'image_filesystem_data' ) );
		$this->wp->setTinyMetadata( 1, $this->json( 'image_database_metadata' ) );
	}

	public function test_get_optimization_statistics() {
		$wpdb_wp_metadata = serialize( $this->json( '_wp_attachment_metadata' ) );
		$wpdb_tiny_metadata = serialize( $this->json( 'image_database_metadata' ) );
		$wpdb_results = array(
			array(
				'ID' => 1,
				'post_title' => 'I am the one and only',
				'meta_value' => $wpdb_wp_metadata,
				'tiny_meta_value' => $wpdb_wp_metadata,
			),
			array(
				'ID' => 3628,
				'post_title' => 'Ferrari.jpeg',
				'meta_value' => '',
				'tiny_meta_value' => '',
			),
			array(
				'ID' => 4350,
				'post_title' => 'IMG 3092',
				'meta_value' => '',
				'tiny_meta_value' => '',
			),
		);
		$this->assertEquals(
			array(
				'uploaded-images' => 3,
				'optimized-image-sizes' => 0,
				'available-unoptimized-sizes' => 4,
				'optimized-library-size' => 328670,
				'unoptimized-library-size' => 328670,
				'available-for-optimization' => array(
					array(
						'ID' => 1,
						'post_title' => 'I am the one and only',
					),
				),
				'display-percentage' => 0.0,
			),
			Tiny_Bulk_Optimization::get_optimization_statistics( new Tiny_Settings(), $wpdb_results )
		);
	}
}
