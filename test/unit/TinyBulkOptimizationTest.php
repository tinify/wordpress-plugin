<?php

require_once dirname(__FILE__) . '/TinyTestCase.php';

class Tiny_Bulk_Optimization_Test extends Tiny_TestCase
{
	public function set_up()
	{
		parent::set_up();
	}

	public function test_get_optimization_statistics()
	{
		$this->wp->addOption('tinypng_convert_format', array(
			'convert' => 'on'
		));

		// Image 1: 
		// 4 sizes, all uncompressed and uncoverted
		// lacking output and convert meta data
		// should cost 3 * 2 = 6 credits
		$wp_metadata_4350 = serialize(array(
			'width' => 1256,
			'height' => 1256,
			'file' => '2023/05/image-4350.png',
			'sizes' => array(
				'small' => array(
					'file' => 'image-4350-200x200.png',
					'width' => 200,
					'height' => 200,
					'mime-type' => 'image/png'
				),
				'medium' => array(
					'file' => 'image-4350-300x300.png',
					'width' => 300,
					'height' => 300,
					'mime-type' => 'image/png'
				),
				'thumbnail' => array(
					'file' => 'image-4350-150x150.png',
					'width' => 150,
					'height' => 150,
					'mime-type' => 'image/png'
				)
			)
		));
		$virtual_image_4350 = array(
			'path' => '2023/05',
			'images' => array(
				array(
					"file" => "image-4350.png",
					"size" => 137856,
				),
				array(
					"file" => "image-4350-150x150.png",
					"size" => 37856,
				),
				array(
					"file" => "image-4350-200x200.png",
					"size" => 66480,
				),
				array(
					"file" => "image-4350-300x300.png",
					"size" => 57856,
				),
			)
		);
		$this->wp->createImagesFromJSON($virtual_image_4350);

		// Image 4351: has compressed but not converted
		// has 3 sizes (0, thumb, medium)
		// 3 * conversion only = 3 credits
		$wp_metadata_4351 = serialize(array(
			'width' => 800,
			'height' => 600,
			'file' => '2023/05/image-4351.jpg',
			'sizes' => array(
				'thumbnail' => array(
					'file' => 'image-4351-150x150.jpg',
					'width' => 150,
					'height' => 150,
					'mime-type' => 'image/jpeg',
				),
				'medium' => array(
					'file' => 'image-4351-300x225.jpg',
					'width' => 300,
					'height' => 225,
					'mime-type' => 'image/jpeg',
				)
			)
		));
		$tiny_metadata_4351 = serialize(array(
			'0' => array(
				'input' => array('size' => 180856),
				'output' => array('size' => 137856),
				'end' => time(),
			),
			'thumbnail' => array(
				'input' => array('size' => 80856),
				'output' => array('size' => 37856),
				'end' => time(),
			),
			'medium' => array(
				'input' => array('size' => 90856),
				'output' => array('size' => 47856),
				'end' => time(),
			)
		));
		$virtual_image_4351 = array(
			'path' => '2023/05',
			'images' => array(
				array(
					'size' => 137856,
					'file' => 'image-4351.jpg',
				),
				array(
					'size' => 37856,
					'file' => 'image-4351-150x150.jpg',
				),
				array(
					'size' => 47856,
					'file' => 'image-4351-300x225.jpg',
				),
			)
		);
		$this->wp->createImagesFromJSON($virtual_image_4351);

		// Image 4352: has been compressed and converted
		$wp_metadata_4352 = serialize(array(
			'width' => 600,
			'height' => 400,
			'file' => '2023/05/image-4352.jpg',
			'sizes' => array(
				'thumbnail' => array(
					'file' => 'image-4352-150x150.jpg',
					'width' => 150,
					'height' => 150,
					'mime-type' => 'image/jpeg',
				),
				'medium' => array(
					'file' => 'image-4352-300x200.jpg',
					'width' => 300,
					'height' => 200,
					'mime-type' => 'image/jpeg',
				)
			)
		));
		$tiny_metadata_4352 = serialize(array(
			'0' => array(
				'input' => array('size' => 50000),
				'output' => array('size' => 40000),
				'end' => time(),
				'convert' => array()
			),
			'thumbnail' => array(
				'input' => array('size' => 15000),
				'output' => array('size' => 12000),
				'end' => time(),
				'convert' => array()
			),
			'medium' => array(
				'input' => array('size' => 25000),
				'output' => array('size' => 20000),
				'end' => time(),
				'convert' => array()
			)
		));
		$virtual_image_4352 = array(
			'path' => '2023/05',
			'images' => array(
				array(
					'size' => 40000,
					'file' => 'image-4352.jpg',
				),
				array(
					'size' => 12000,
					'file' => 'image-4352-150x150.jpg',
				),
				array(
					'size' => 20000,
					'file' => 'image-4352-300x200.jpg',
				),
			)
		);
		$this->wp->createImagesFromJSON($virtual_image_4352);

		$wpdb_results = array(
			array(
				'ID' => 1,
				'post_title' => 'I am uncompressed and unconverted',
				'meta_value' => $wp_metadata_4350,
				'tiny_meta_value' => '',
			),
			array(
				'ID' => 4350,
				'post_title' => 'I am compressed but not converted',
				'meta_value' => $wp_metadata_4351,
				'tiny_meta_value' => $tiny_metadata_4351,
			),
			array(
				'ID' => 4352,
				'post_title' => 'I am compressed and converted',
				'meta_value' => '',
				'meta_value' => $wp_metadata_4352,
				'tiny_meta_value' => $tiny_metadata_4352,
			),
		);

		$this->assertEquals(
			array(
				'uploaded-images' => 3,
				'optimized-library-size' => 529136,
				'unoptimized-library-size' => 676136,
				'available-for-optimization' => array(
					array(
						'ID' => 1,
						'post_title' => 'I am uncompressed and unconverted',
						'file' => 'image-4350.png',
						'sizes' => 3,
						'bytes' => 233568,
					),
					array(
						'ID' => 4350,
						'post_title' => 'I am compressed but not converted',
						'file' => 'image-4351.jpg',
						'sizes' => 3,
						'bytes' => 223568,
					),
				),
				'display-percentage' => 21.7,
				'estimated_credit_use' => 9
			),
			Tiny_Bulk_Optimization::get_optimization_statistics(new Tiny_Settings(), $wpdb_results)
		);
	}

	public function test_lists_the_uploaded_file_name_when_wordpress_scaled_the_image()
	{
		$this->wp->createImagesFromJSON(array(
			'path' => '2023/05',
			'images' => array(
				array('file' => 'photo-scaled.jpg', 'size' => 40000),
				array('file' => 'photo-150x150.jpg', 'size' => 12000),
			),
		));
		$wpdb_results = array(
			array(
				'ID' => 1,
				'post_title' => 'A scaled photo',
				'meta_value' => serialize(array(
					'file' => '2023/05/photo-scaled.jpg',
					'original_image' => 'photo.jpg',
					'sizes' => array(
						'thumbnail' => array('file' => 'photo-150x150.jpg'),
					),
				)),
				'tiny_meta_value' => '',
			),
		);

		$stats = Tiny_Bulk_Optimization::get_optimization_statistics(new Tiny_Settings(), $wpdb_results);

		$this->assertEquals('photo.jpg', $stats['available-for-optimization'][0]['file']);
	}

	public function test_retrieves_avif_and_jxl_attachments()
	{
		if (!defined('ARRAY_A')) {
			define('ARRAY_A', 'ARRAY_A');
		}
		global $wpdb;
		$wpdb = $this->getMockBuilder(stdClass::class)
			->addMethods(['get_results'])
			->getMock();
		$wpdb->posts = 'wp_posts';
		$wpdb->postmeta = 'wp_postmeta';

		$queries = array();
		$wpdb->method('get_results')
			->willReturnCallback(function ($query) use (&$queries) {
				$queries[] = $query;
				return array();
			});

		Tiny_Bulk_Optimization::get_optimization_statistics(new Tiny_Settings());

		$this->assertStringContainsString("'image/avif'", $queries[0]);
		$this->assertStringContainsString("'image/jxl'", $queries[0]);
	}

	public function test_counts_jxl_without_attachment_metadata()
	{
		$this->wp->addOption('tinypng_sizes', array(Tiny_Image::ORIGINAL => 'on'));
		$this->wp->createImage(5000, '2023/05', 'photo.jxl');
		$this->wp->updateMetadata(4400, '_wp_attached_file', '2023/05/photo.jxl');
		$this->wp->stub('get_post_mime_type', function () {
			return 'image/jxl';
		});
		$this->wp->stub('wp_get_attachment_metadata', function () {
			return false;
		});

		$stats = Tiny_Bulk_Optimization::get_optimization_statistics(new Tiny_Settings(), array(
			array(
				'ID' => 4400,
				'post_title' => 'photo',
				'meta_value' => null,
				'tiny_meta_value' => null,
			),
		));

		$this->assertEquals(1, $stats['estimated_credit_use']);
		$this->assertEquals(1, $stats['available-for-optimization'][0]['sizes']);
	}

	public function test_avif_needs_no_conversion_credits()
	{
		$this->wp->addOption('tinypng_convert_format', array(
			'convert' => 'on',
			'convert_to' => 'smallest',
		));
		$this->wp->addOption('tinypng_sizes', array(
			Tiny_Image::ORIGINAL => 'on',
			'thumbnail' => 'on',
		));
		$this->wp->createImagesFromJSON(array(
			'path' => '2023/05',
			'images' => array(
				array('file' => 'photo.avif', 'size' => 50000),
				array('file' => 'photo-150x150.avif', 'size' => 5000),
			),
		));

		$stats = Tiny_Bulk_Optimization::get_optimization_statistics(new Tiny_Settings(), array(
			array(
				'ID' => 4400,
				'post_title' => 'photo',
				'meta_value' => serialize(array(
					'width' => 1080,
					'height' => 720,
					'file' => '2023/05/photo.avif',
					'sizes' => array(
						'thumbnail' => array('file' => 'photo-150x150.avif'),
					),
				)),
				'tiny_meta_value' => '',
			),
		));

		$this->assertEquals(2, $stats['estimated_credit_use']);
		$this->assertEquals(2, $stats['available-for-optimization'][0]['sizes']);
	}
}
