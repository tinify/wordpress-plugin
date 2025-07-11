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

        $output = $this->tiny_picture->replace_img_sources('<img src="https://www.tinifytest.com/wp-content/uploads/2025/01/test.png">');
        $expected_output = '<picture><source srcset="https://www.tinifytest.com/wp-content/uploads/2025/01/test.webp" type="image/webp" /><img src="https://www.tinifytest.com/wp-content/uploads/2025/01/test.png"></picture>';
        $this->assertEquals($expected_output, $output);
    }

    /**
     * Images from a domain that is not the current site
     * will be skipped.
     */
    public function test_absolute_url_to_remote_image()
    {
        $output = $this->tiny_picture->replace_img_sources('<img src="https://www.remotetinify.com/wp-content/uploads/2025/01/test.png">');
        $expected_output = '<img src="https://www.remotetinify.com/wp-content/uploads/2025/01/test.png">';
        $this->assertEquals($expected_output, $output);
    }

    /**
     * Ensure we can handle relative URLs as a source.
     */
    public function test_srcset_contains_preferred_format_with_relative_url()
    {
        $this->wp->createImage(37857, '2025/01', 'test.webp');

        $output = $this->tiny_picture->replace_img_sources('<img src="/wp-content/uploads/2025/01/test.png">');
        $expected_output = '<picture><source srcset="/wp-content/uploads/2025/01/test.webp" type="image/webp" /><img src="/wp-content/uploads/2025/01/test.png"></picture>';
        $this->assertEquals($expected_output, $output);
    }

    /**
     * Images that have no alternative format stored locally
     * will not have an optimized format.
     */
    public function test_img_with_no_alternate_format_should_not_change()
    {
        $output = $this->tiny_picture->replace_img_sources('<img src="/wp-content/uploads/2025/01/missing.png">');
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

        $output = $this->tiny_picture->replace_img_sources('<img src="/wp-content/uploads/2025/01/test.png?ver=123">');
        $expected_output = '<picture><source srcset="/wp-content/uploads/2025/01/test.avif" type="image/avif" /><img src="/wp-content/uploads/2025/01/test.png?ver=123"></picture>';
        $this->assertEquals($expected_output, $output);
    }

    public function test_multiple_img_tags()
    {
        $this->wp->createImage(1000, '2025/01', 'first.webp');
        $this->wp->createImage(1000, '2025/01', 'second.webp');

        $input = '<img src="/wp-content/uploads/2025/01/first.png" /><p>Hello</p><img src="/wp-content/uploads/2025/01/second.png" />';
        $expected = '<picture><source srcset="/wp-content/uploads/2025/01/first.webp" type="image/webp" /><img src="/wp-content/uploads/2025/01/first.png" /></picture><p>Hello</p><picture><source srcset="/wp-content/uploads/2025/01/second.webp" type="image/webp" /><img src="/wp-content/uploads/2025/01/second.png" /></picture>';
        $output = $this->tiny_picture->replace_img_sources($input);
        $this->assertEquals($expected, $output);
    }

    public function test_img_with_additional_attributes()
    {
        $this->wp->createImage(1000, '2025/01', 'test.webp');

        $input = '<img src="/wp-content/uploads/2025/01/test.png" class="lazy" alt="Test" loading="lazy">';
        $expected = '<picture><source srcset="/wp-content/uploads/2025/01/test.webp" type="image/webp" /><img src="/wp-content/uploads/2025/01/test.png" class="lazy" alt="Test" loading="lazy"></picture>';
        $output = $this->tiny_picture->replace_img_sources($input);

        $this->assertEquals($expected, $output);
    }

    public function test_uppercase_img_tag_is_handled()
    {
        $this->wp->createImage(37857, '2025/01', 'test.webp');

        $input = '<IMG SRC="/wp-content/uploads/2025/01/test.png">';
        $expected = '<picture><source srcset="/wp-content/uploads/2025/01/test.webp" type="image/webp" /><IMG SRC="/wp-content/uploads/2025/01/test.png"></picture>';
        $output = $this->tiny_picture->replace_img_sources($input);
        $this->assertEquals($expected, $output);
    }

    public function test_img_inside_anchor_is_wrapped()
    {
        $this->wp->createImage(1000, '2025/01', 'test.webp');

        $input = '<a href="/something"><img src="/wp-content/uploads/2025/01/test.png"></a>';
        $expected = '<a href="/something"><picture><source srcset="/wp-content/uploads/2025/01/test.webp" type="image/webp" /><img src="/wp-content/uploads/2025/01/test.png"></picture></a>';
        $output = $this->tiny_picture->replace_img_sources($input);

        $this->assertEquals($expected, $output);
    }
}
