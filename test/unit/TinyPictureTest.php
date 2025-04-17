<?php

require_once dirname(__FILE__) . '/TinyTestCase.php';

class Tiny_Picture_Test extends Tiny_TestCase
{
    public function set_up()
    {
        parent::set_up();
    }

    /**
     * img tags with a absolute url should be processed correctly
     */
    public function test_replace_image_with_absolute_url_with_picture_tag()
    {
        $this->wp->createImage(37857, '2025/01', 'test.avif');

        $output = Tiny_Picture::replace_img_with_picture_tag('<img src="https://www.tinifytest.com/wp-content/uploads/2025/01/test.png">');
        $expected_output = '<picture><source type="image/avif" srcset="https://www.tinifytest.com/wp-content/uploads/2025/01/test.avif"><img src="https://www.tinifytest.com/wp-content/uploads/2025/01/test.png"></picture>';
        $this->assertEquals($expected_output, $output);
    }

    /**
     * img tags with a relative url should be processed correctly
     */
    public function test_replace_image_with_relative_url_with_picture_tag()
    {
        $this->wp->createImage(37857, '2025/01', 'test.webp');

        $output = Tiny_Picture::replace_img_with_picture_tag('<img src="/wp-content/uploads/2025/01/test.png">');
        $expected_output = '<picture><source type="image/webp" srcset="/wp-content/uploads/2025/01/test.webp"><img src="/wp-content/uploads/2025/01/test.png"></picture>';
        $this->assertEquals($expected_output, $output);
    }

    /**
     * HTML containing a picture element with a img tag nested within,
     * should not be replaced
     */
    public function test_will_not_replace_images_within_picture_tag()
    {
        $current_picture = '<picture><source type="image/webp" srcset="/wp-content/uploads/2025/01/test.webp"><img src="/wp-content/uploads/2025/01/test.png"></picture>';
        $output = Tiny_Picture::replace_img_with_picture_tag($current_picture);
        $this->assertEquals($current_picture, $output);
    }

    public function test_img_with_no_alternate_format_should_not_change()
    {
        $output = Tiny_Picture::replace_img_with_picture_tag('<img src="/wp-content/uploads/2025/01/missing.png">');
        $expected_output = '<img src="/wp-content/uploads/2025/01/missing.png">';
        $this->assertEquals($expected_output, $output);
    }

    public function test_img_with_query_string_keeps_query()
    {
        $this->wp->createImage(37857, '2025/01', 'test.avif');

        $output = Tiny_Picture::replace_img_with_picture_tag('<img src="/wp-content/uploads/2025/01/test.png?ver=123">');
        $expected_output = '<picture><source type="image/avif" srcset="/wp-content/uploads/2025/01/test.avif"><img src="/wp-content/uploads/2025/01/test.png?ver=123"></picture>';
        $this->assertEquals($expected_output, $output);
    }

    public function test_multiple_img_tags()
    {
        $this->wp->createImage(1000, '2025/01', 'first.webp');
        $this->wp->createImage(1000, '2025/01', 'second.webp');

        $input = '<img src="/wp-content/uploads/2025/01/first.png"><p>Hello</p><img src="/wp-content/uploads/2025/01/second.png">';
        $expected = '<picture><source type="image/webp" srcset="/wp-content/uploads/2025/01/first.webp"><img src="/wp-content/uploads/2025/01/first.png"></picture><p>Hello</p><picture><source type="image/webp" srcset="/wp-content/uploads/2025/01/second.webp"><img src="/wp-content/uploads/2025/01/second.png"></picture>';

        $this->assertEquals($expected, Tiny_Picture::replace_img_with_picture_tag($input));
    }

    public function test_img_with_additional_attributes()
    {
        $this->wp->createImage(1000, '2025/01', 'test.webp');

        $input = '<img src="/wp-content/uploads/2025/01/test.png" class="lazy" alt="Test" loading="lazy">';
        $expected = '<picture><source type="image/webp" srcset="/wp-content/uploads/2025/01/test.webp"><img src="/wp-content/uploads/2025/01/test.png" class="lazy" alt="Test" loading="lazy"></picture>';
        $this->assertEquals($expected, Tiny_Picture::replace_img_with_picture_tag($input));
    }

    public function test_uppercase_img_tag_is_handled()
    {
        $this->wp->createImage(37857, '2025/01', 'test.webp');

        $input = '<IMG SRC="/wp-content/uploads/2025/01/test.png">';
        $expected = '<picture><source type="image/webp" srcset="/wp-content/uploads/2025/01/test.webp"><IMG SRC="/wp-content/uploads/2025/01/test.png"></picture>';
        $this->assertEquals($expected, Tiny_Picture::replace_img_with_picture_tag($input));
    }

    public function test_img_inside_anchor_is_wrapped()
    {
        $this->wp->createImage(1000, '2025/01', 'test.webp');

        $input = '<a href="/something"><img src="/wp-content/uploads/2025/01/test.png"></a>';
        $expected = '<a href="/something"><picture><source type="image/webp" srcset="/wp-content/uploads/2025/01/test.webp"><img src="/wp-content/uploads/2025/01/test.png"></picture></a>';

        $this->assertEquals($expected, Tiny_Picture::replace_img_with_picture_tag($input));
    }
}
