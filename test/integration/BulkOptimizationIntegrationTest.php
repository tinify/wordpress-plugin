<?php

// require_once dirname( __FILE__ ) . '/IntegrationTestCase.php';
//
// use Facebook\WebDriver\WebDriverBy;
// use Facebook\WebDriver\WebDriverExpectedCondition;
//
// class BulkOptimizationIntegrationTest extends IntegrationTestCase {
//
// 	public function set_up() {
// 		parent::set_up();
// 		$this->set_api_key( 'PNG123' );
// 		$this->enable_compression_sizes( array( '0', 'thumbnail', 'medium', 'large') );
// 		$this->setup_fixtures();
// 		self::$driver->get( wordpress( '/wp-admin/upload.php?page=tiny-bulk-optimization' ) );
// 	}
//
// 	public function tear_down() {
// 		parent::tear_down();
// 		clear_settings();
// 		clear_uploads();
// 		reset_webservice();
// 	}
//
// 	public function setup_fixtures() {
// 		$this->create_non_compressed_image( 1001, 'non-compressed.jpg' );
// 		$this->create_partially_compressed_image( 1002, 'partially-compressed.jpg' );
// 		$this->create_fully_compressed_image( 1003, 'fully-compressed.jpg' );
// 	}
//
// 	// In one test because it is terribly slow
// 	public function test_all_the_values() {
// 		//testShouldShowUploadedImages() {
// 		$this->assertEquals( '3', $this->find( '#uploaded-images' )->getText() );
//
// 		// testShouldShowUncompressedImageSizes() {
// 		$this->assertEquals( '6', $this->find( '#optimizable-image-sizes' )->getText() );
//
// 		// TODO mock number of compressions done and better test cost estimation for large numbers.
// 		// testShouldShowEstimatedCost() {
// 		$this->assertEquals( '$ 0.00', $this->find( '#estimated-cost' )->getText() );
//
// 		// testShouldShowSavingsPercentage() {
// 		$this->assertEquals( '35.18%', $this->find( '#savings-percentage' )->getText() );
//
// 		// testShouldShowImageSizesCompressed() {
// 		$this->assertEquals( '4', $this->find( '#optimized-image-sizes' )->getText() );
//
// 		// testShouldShowTotalUnoptimizedSize() {
// 		$this->assertEquals( '305.29 kB', $this->find( '#unoptimized-library-size' )->getText() );
//
// 		// testShouldShowTotalOptimizedSize() {
// 		$this->assertEquals( '197.88 kB', $this->find( '#optimized-library-size' )->getText() );
// 	}
//
// 	// SKIP TODO
// 	public function test_should_show_progress_bar() {
// 	}
// }
