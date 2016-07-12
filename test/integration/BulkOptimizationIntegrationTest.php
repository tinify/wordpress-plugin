<?php

require_once dirname( __FILE__ ) . '/IntegrationTestCase.php';

class BulkOptimizationIntegrationTest extends IntegrationTestCase {
	public function tear_down() {
		parent::tear_down();
		clear_settings();
		clear_uploads();
	}

	public function test_presence_of_summary_values() {
		$this->set_api_key( 'JPG123' );

		$this->enable_compression_sizes( array() );
		$this->upload_media( 'test/fixtures/input-example.jpg' );

		$this->enable_compression_sizes( array( '0' ) );
		$this->upload_media( 'test/fixtures/input-example.jpg' );

		$this->enable_compression_sizes( array( '0', 'thumbnail', 'medium' ) );
		$this->upload_media( 'test/fixtures/input-example.jpg' );

		$this->visit( '/wp-admin/upload.php?page=tiny-bulk-optimization' );

		$this->assertEquals( '3', $this->find( '#uploaded-images' )->getText() );
		$this->assertEquals( '5', $this->find( '#optimizable-image-sizes' )->getText() );
		$this->assertEquals( '$ 0.00', $this->find( '#estimated-cost' )->getText() );
		$this->assertEquals( '24.4%', $this->find( '#savings-percentage' )->getText() );
		$this->assertEquals( '4', $this->find( '#optimized-image-sizes' )->getText() );
		$this->assertEquals( '2.82 MB', $this->find( '#unoptimized-library-size' )->getText() );
		$this->assertEquals( '2.13 MB', $this->find( '#optimized-library-size' )->getText() );
	}
}
