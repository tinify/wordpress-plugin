<?php

require_once dirname(__FILE__) . '/TinyTestCase.php';

class Tiny_Picture_Test extends Tiny_TestCase
{

    /** @var Tiny_Picture */
    protected $tiny_picture;

    public function set_up()
    {
        parent::set_up();

        $this->tiny_picture = new Tiny_Picture($this->vfs->url(), array('https://www.tinifytest.com'));
    }

    /**
     * Ensure absolute urls handled correctly. Absolute URLs
     * are only handled if they are from the current domain.
     * Other domains can be remote domains so searching for a
     * local optimized file not be possible.
     */
    public function test_absolute_url_to_image_has_optimized_format()
    {
        $this->wp->createImage(37857, '2025/01', 'test.webp');

        $output = $this->tiny_picture->replace_sources('<img src="https://www.tinifytest.com/wp-content/uploads/2025/01/test.png">');
        $expected_output = '<picture><source srcset="https://www.tinifytest.com/wp-content/uploads/2025/01/test.webp" type="image/webp" /><img src="https://www.tinifytest.com/wp-content/uploads/2025/01/test.png"></picture>';
        $this->assertEquals($expected_output, $output);
    }

    /**
     * Images from a domain that is not the current site
     * will be skipped.
     */
    public function test_absolute_url_to_remote_image()
    {
        $output = $this->tiny_picture->replace_sources('<img src="https://www.remotetinify.com/wp-content/uploads/2025/01/test.png">');
        $expected_output = '<img src="https://www.remotetinify.com/wp-content/uploads/2025/01/test.png">';
        $this->assertEquals($expected_output, $output);
    }

    /**
     * Ensure we can handle relative URLs as a source.
     */
    public function test_srcset_contains_preferred_format_with_relative_url()
    {
        $this->wp->createImage(37857, '2025/01', 'test.webp');

        $output = $this->tiny_picture->replace_sources('<img src="/wp-content/uploads/2025/01/test.png">');
        $expected_output = '<picture><source srcset="/wp-content/uploads/2025/01/test.webp" type="image/webp" /><img src="/wp-content/uploads/2025/01/test.png"></picture>';
        $this->assertEquals($expected_output, $output);
    }

    /**
     * Images that have no alternative format stored locally
     * will not have an optimized format.
     */
    public function test_img_with_no_alternate_format_should_not_change()
    {
        $output = $this->tiny_picture->replace_sources('<img src="/wp-content/uploads/2025/01/missing.png">');
        $expected_output = '<img src="/wp-content/uploads/2025/01/missing.png">';
        $this->assertEquals($expected_output, $output);
    }

    /**
     * Ensure we can handle images with a query parameter.
     * Basicly we strip the query parameter off and search
     * for the remaineder locally.
     */
    public function test_img_with_query_string_keeps_query()
    {
        $this->wp->createImage(37857, '2025/01', 'test.avif');

        $output = $this->tiny_picture->replace_sources('<img src="/wp-content/uploads/2025/01/test.png?ver=123">');
        $expected_output = '<picture><source srcset="/wp-content/uploads/2025/01/test.avif" type="image/avif" /><img src="/wp-content/uploads/2025/01/test.png?ver=123"></picture>';
        $this->assertEquals($expected_output, $output);
    }

    public function test_multiple_img_tags()
    {
        $this->wp->createImage(1000, '2025/01', 'first.webp');
        $this->wp->createImage(1000, '2025/01', 'second.webp');

        $input = '<img src="/wp-content/uploads/2025/01/first.png" /><p>Hello</p><img src="/wp-content/uploads/2025/01/second.png" />';
        $expected = '<picture><source srcset="/wp-content/uploads/2025/01/first.webp" type="image/webp" /><img src="/wp-content/uploads/2025/01/first.png" /></picture><p>Hello</p><picture><source srcset="/wp-content/uploads/2025/01/second.webp" type="image/webp" /><img src="/wp-content/uploads/2025/01/second.png" /></picture>';
        $output = $this->tiny_picture->replace_sources($input);
        $this->assertEquals($expected, $output);
    }

    public function test_img_with_additional_attributes()
    {
        $this->wp->createImage(1000, '2025/01', 'test.webp');

        $input = '<img src="/wp-content/uploads/2025/01/test.png" class="lazy" alt="Test" loading="lazy">';
        $expected = '<picture><source srcset="/wp-content/uploads/2025/01/test.webp" type="image/webp" /><img src="/wp-content/uploads/2025/01/test.png" class="lazy" alt="Test" loading="lazy"></picture>';
        $output = $this->tiny_picture->replace_sources($input);

        $this->assertEquals($expected, $output);
    }

    public function test_uppercase_img_tag_is_handled()
    {
        $this->wp->createImage(37857, '2025/01', 'test.webp');

        $input = '<IMG SRC="/wp-content/uploads/2025/01/test.png">';
        $expected = '<picture><source srcset="/wp-content/uploads/2025/01/test.webp" type="image/webp" /><IMG SRC="/wp-content/uploads/2025/01/test.png"></picture>';
        $output = $this->tiny_picture->replace_sources($input);
        $this->assertEquals($expected, $output);
    }

    public function test_img_inside_anchor_is_wrapped()
    {
        $this->wp->createImage(1000, '2025/01', 'test.webp');

        $input = '<a href="/something"><img src="/wp-content/uploads/2025/01/test.png"></a>';
        $expected = '<a href="/something"><picture><source srcset="/wp-content/uploads/2025/01/test.webp" type="image/webp" /><img src="/wp-content/uploads/2025/01/test.png"></picture></a>';
        $output = $this->tiny_picture->replace_sources($input);

        $this->assertEquals($expected, $output);
    }

    public function test_img_in_picture_element()
    {
        $this->wp->createImage(1000, '2025/01', 'test.webp');
        $this->wp->createImage(1000, '2025/01', 'test_500x500.webp');

        $input = '<picture><source media="(max-width: 767px)" srcset="/wp-content/uploads/2025/01/test_500x500.png" /><img src="/wp-content/uploads/2025/01/test.png"></picture>';
        $expected = '<picture><source media="(max-width: 767px)" srcset="/wp-content/uploads/2025/01/test_500x500.png" /><source srcset="/wp-content/uploads/2025/01/test_500x500.webp" media="(max-width: 767px)" type="image/webp" /><source srcset="/wp-content/uploads/2025/01/test.webp" type="image/webp" /><img src="/wp-content/uploads/2025/01/test.png"></picture>';
        $output = $this->tiny_picture->replace_sources($input);

        $this->assertEquals($expected, $output);
    }

    public function test_img_in_picture_element_ordered_attributes()
    {
        $this->wp->createImage(1000, '2025/01', 'test.webp');
        $this->wp->createImage(1000, '2025/01', 'test_500x500.webp');

        $input = '<picture><source srcset="/wp-content/uploads/2025/01/test_500x500.png" media="(max-width: 767px)" /><img src="/wp-content/uploads/2025/01/test.png"></picture>';
        $expected = '<picture><source srcset="/wp-content/uploads/2025/01/test_500x500.png" media="(max-width: 767px)" /><source srcset="/wp-content/uploads/2025/01/test_500x500.webp" media="(max-width: 767px)" type="image/webp" /><source srcset="/wp-content/uploads/2025/01/test.webp" type="image/webp" /><img src="/wp-content/uploads/2025/01/test.png"></picture>';
        $output = $this->tiny_picture->replace_sources($input);

        $this->assertEquals($expected, $output);
    }

    public function test_img_in_picture_element_srcset_sizes()
    {
        $this->wp->createImage(1000, '2025/01', 'test.webp');
        $this->wp->createImage(1000, '2025/01', 'test_500x500.webp');

        $input = '<picture><source srcset="/wp-content/uploads/2025/01/test_500x500.png" media="(max-width: 767px)" /><img src="/wp-content/uploads/2025/01/test.png"></picture>';
        $expected = '<picture><source srcset="/wp-content/uploads/2025/01/test_500x500.png" media="(max-width: 767px)" /><source srcset="/wp-content/uploads/2025/01/test_500x500.webp" media="(max-width: 767px)" type="image/webp" /><source srcset="/wp-content/uploads/2025/01/test.webp" type="image/webp" /><img src="/wp-content/uploads/2025/01/test.png"></picture>';
        $output = $this->tiny_picture->replace_sources($input);

        $this->assertEquals($expected, $output);
    }

    public function test_img_with_srcsets()
    {
        $this->wp->createImage(1000, '2025/01', 'test-640w.webp');
        $this->wp->createImage(1000, '2025/01', 'test-480w.webp');
        $this->wp->createImage(1000, '2025/01', 'test-320w.webp');

        $input = '<img srcset="/wp-content/uploads/2025/01/test-320w.jpg, /wp-content/uploads/2025/01/test-480w.jpg 1.5x, /wp-content/uploads/2025/01/test-640w.jpg 2x" src="/wp-content/uploads/2025/01/test-640w.jpg" />';
        $expected = '<picture><source srcset="/wp-content/uploads/2025/01/test-320w.webp, /wp-content/uploads/2025/01/test-480w.webp 1.5x, /wp-content/uploads/2025/01/test-640w.webp 2x" type="image/webp" /><img srcset="/wp-content/uploads/2025/01/test-320w.jpg, /wp-content/uploads/2025/01/test-480w.jpg 1.5x, /wp-content/uploads/2025/01/test-640w.jpg 2x" src="/wp-content/uploads/2025/01/test-640w.jpg" /></picture>';
        $output = $this->tiny_picture->replace_sources($input);

        $this->assertEquals($expected, $output);
    }

    public function test_get_largest_width_descriptor_returns_largest_value()
    {
        $srcsets = array(
            array('path' => '/wp-content/uploads/2025/01/test_320x320.jpg', 'size' => '320w'),
            array('path' => '/wp-content/uploads/2025/01/test_320x320.jpg', 'size' => '2000w'),
            array('path' => 'c', 'size' => '2x'), // this is effectively ignored because there are width descriptors
        );

        $imgSource = new Tiny_Image_Source('<img srcset="/wp-content/uploads/2025/01/test-420w.jpg 420w, /wp-content/uploads/2025/01/test-650w.jpg 650w, /wp-content/uploads/2025/01/test.jpg 2000w" src="/wp-content/uploads/2025/01/test.jpg" />', '', array());
        $largest = Tiny_Image_Source::get_largest_width_descriptor($srcsets);

        $this->assertEquals(2000, $largest);
    }

    public function test_get_largest_width_descriptor_without_widths_returns_zero()
    {
        $srcsets = array(
            array('path' => '/wp-content/uploads/2025/01/test@1x.jpg', 'size' => '1x'),
            array('path' => '/wp-content/uploads/2025/01/test.jpg', 'size' => '2x'),
        );

        $largest = Tiny_Image_Source::get_largest_width_descriptor($srcsets);

        $this->assertSame(0, $largest);
    }

    public function test_srcset_contains_width_descriptor_returns_true_when_present()
    {
        $parts = array(
            '/wp-content/uploads/2025/01/test-320w.webp 320w',
            '/wp-content/uploads/2025/01/test-640w.webp 640w',
        );

        $this->assertTrue(Tiny_Image_Source::srcset_contains_width_descriptor($parts, 640));
    }

    public function test_srcset_contains_width_descriptor_returns_false_when_missing()
    {
        $parts = array(
            '/wp-content/uploads/2025/01/test-320w.webp 320w',
            '/wp-content/uploads/2025/01/test-640w.webp 640w',
        );

        $this->assertFalse(Tiny_Image_Source::srcset_contains_width_descriptor($parts, 1280));
    }

    public function test_get_largest_width_no_descriptors()
    {
        $srcsets = array(
            array('path' => '/wp-content/uploads/2025/01/test.jpg', 'size' => ''),
        );

        $largest = Tiny_Image_Source::get_largest_width_descriptor($srcsets);

        $this->assertSame(0, $largest);
    }

    public function test_picture_with_srcsets()
    {
        $this->wp->createImage(1000, '2025/01', 'test-640w.webp');
        $this->wp->createImage(1000, '2025/01', 'test-480w.webp');
        $this->wp->createImage(1000, '2025/01', 'test-320w.webp');

        $input = '<picture><img srcset="/wp-content/uploads/2025/01/test-320w.jpg, /wp-content/uploads/2025/01/test-480w.jpg 1.5x, /wp-content/uploads/2025/01/test-640w.jpg 2x" src="/wp-content/uploads/2025/01/test-640w.jpg" /></picture>';
        $expected = '<picture><source srcset="/wp-content/uploads/2025/01/test-320w.webp, /wp-content/uploads/2025/01/test-480w.webp 1.5x, /wp-content/uploads/2025/01/test-640w.webp 2x" type="image/webp" /><img srcset="/wp-content/uploads/2025/01/test-320w.jpg, /wp-content/uploads/2025/01/test-480w.jpg 1.5x, /wp-content/uploads/2025/01/test-640w.jpg 2x" src="/wp-content/uploads/2025/01/test-640w.jpg" /></picture>';
        $output = $this->tiny_picture->replace_sources($input);

        $this->assertEquals($expected, $output);
    }

    public function test_picture_with_attributes()
    {
        $this->wp->createImage(1000, '2025/01', 'test-landscape.webp');

        $input = '<picture><source srcset="/wp-content/uploads/2025/01/test-landscape.jpg" width="200" height="200" media="(width >= 600px)" /><img src="/wp-content/uploads/2025/01/test.jpg" /></picture>';
        $expected = '<picture><source srcset="/wp-content/uploads/2025/01/test-landscape.jpg" width="200" height="200" media="(width >= 600px)" /><source srcset="/wp-content/uploads/2025/01/test-landscape.webp" media="(width >= 600px)" width="200" height="200" type="image/webp" /><img src="/wp-content/uploads/2025/01/test.jpg" /></picture>';
        $output = $this->tiny_picture->replace_sources($input);

        $this->assertEquals($expected, $output);
    }

    public function test_adds_both_avif_and_webp()
    {
        $this->wp->createImage(1000, '2025/01', 'test.webp');
        $this->wp->createImage(1000, '2025/01', 'test.avif');

        $input = '<img src="/wp-content/uploads/2025/01/test.png">';
        $expected = '<picture><source srcset="/wp-content/uploads/2025/01/test.avif" type="image/avif" /><source srcset="/wp-content/uploads/2025/01/test.webp" type="image/webp" /><img src="/wp-content/uploads/2025/01/test.png"></picture>';
        $output = $this->tiny_picture->replace_sources($input);

        $this->assertEquals($expected, $output);
    }

    public function test_img_with_query_and_fragment_keeps_both()
    {
        $this->wp->createImage(37857, '2025/09', 'test.avif');

        $input = '<img src="/wp-content/uploads/2025/09/test.png?v=123#top">';
        $expected = '<picture><source srcset="/wp-content/uploads/2025/09/test.avif" type="image/avif" /><img src="/wp-content/uploads/2025/09/test.png?v=123#top"></picture>';
        $output = $this->tiny_picture->replace_sources($input);

        $this->assertEquals($expected, $output);
    }

    /**
     * scenario where there is only a low resolution variant for a certain image.
     * this can happen when credits or API decides that only low resolution image
     * is in a different format (this is resolved in 3.6.4)
     */
    public function test_skip_low_res_if_largest_width_is_not_present()
    {
        $this->wp->createImage(37857, '2025/09', 'test_250x250.webp');

        // largest size should exist otherwise we mark it as a incomplete sourceset
        $input = '<img src="/wp-content/uploads/2025/09/test.png" srcset="/wp-content/uploads/2025/09/test_250x250.png 350w, /wp-content/uploads/2025/09/test.png 2000w">';
        $output = $this->tiny_picture->replace_sources($input);

        // no replacement should be done as there is only a 250x250 but no original
        $this->assertEquals($input, $output);
    }

    /**
     * if the largest width is in a srcset, then we will use the alternative source
     */
    public function test_largest_width_is_present_so_include_sourceset()
    {
        $this->wp->createImage(37857, '2025/09', 'test_250x250.webp');
        $this->wp->createImage(37857, '2025/09', 'test.webp');

        // largest size should be present otherwise we mark it as a incomplete sourceset
        $input = '<img src="/wp-content/uploads/2025/09/test.png" srcset="/wp-content/uploads/2025/09/test_250x250.png 350w, /wp-content/uploads/2025/09/test.png 2000w">';
        $expected = '<picture><source srcset="/wp-content/uploads/2025/09/test_250x250.webp 350w, /wp-content/uploads/2025/09/test.webp 2000w" type="image/webp" /><img src="/wp-content/uploads/2025/09/test.png" srcset="/wp-content/uploads/2025/09/test_250x250.png 350w, /wp-content/uploads/2025/09/test.png 2000w"></picture>';
        $output = $this->tiny_picture->replace_sources($input);

        // no replacement should be done as there is only a 250x250 but no original
        $this->assertSame($expected, $output);
    }

    /**
     * if width and pd descriptors are present, then only width is applicable and
     * pd are effectively ignored
     * 
     * Note that if any resource in a srcset is described with a "w" descriptor, all resources within that srcset must also be described with "w" descriptors, and the image element's src is not considered a candidate.
     * https://developer.mozilla.org/en-US/docs/Web/API/HTMLImageElement/srcset#value
     */
    public function test_mixed_descriptors_in_source()
    {
        $this->wp->createImage(37857, '2025/09', 'test_250x250.webp');
        $this->wp->createImage(37857, '2025/09', 'test.webp');

        // this will show test_250x250.png but that would also happen on the original img
        $input = '<img src="/wp-content/uploads/2025/09/test.png" srcset="/wp-content/uploads/2025/09/test_250x250.png 350w, /wp-content/uploads/2025/09/test.png 2x">';
        $expected = '<picture><source srcset="/wp-content/uploads/2025/09/test_250x250.webp 350w, /wp-content/uploads/2025/09/test.webp 2x" type="image/webp" /><img src="/wp-content/uploads/2025/09/test.png" srcset="/wp-content/uploads/2025/09/test_250x250.png 350w, /wp-content/uploads/2025/09/test.png 2x"></picture>';
        $output = $this->tiny_picture->replace_sources($input);

        $this->assertSame($expected, $output);
    }

    public function test_does_not_register_hooks_when_pagebuilder_request()
    {
        $_GET = array('fl_builder' => '1');
        
        $this->wp->stub('is_admin', function () {
            return false;
        });
        
        $tiny_picture = new Tiny_Picture($this->vfs->url(), array('https://www.tinifytest.com'));

        $template_redirect_registered = false;
        foreach ($this->wp->getCalls('add_action') as $call) {
            if ($call[0] === 'template_redirect') {
                $template_redirect_registered = true;
                break;
            }
        }

        $this->assertFalse(
            $template_redirect_registered,
            'Tiny_Picture should not register template_redirect hook when pagebuilder is active'
        );
        
        $_GET = array();
    }
}
