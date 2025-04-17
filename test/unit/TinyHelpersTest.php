<?php

require_once dirname(__FILE__) . '/TinyTestCase.php';

class Tiny_Helpers_Test extends Tiny_TestCase
{
    public function set_up()
    {
        parent::set_up();
    }

    public function test_truncates_text_to_length()
    {
        $input_text = "Text to be truncated because it is long";
        $expected_output = "Text to be trunca...";

        $this->assertEquals($expected_output, Tiny_Helpers::truncate_text($input_text, 20));
    }

    public function test_will_not_truncate_if_text_is_shorter_than_length()
    {
        $input_text = "Text will not be truncated";

        $this->assertEquals($input_text, Tiny_Helpers::truncate_text($input_text, 26));
    }

    public function test_will_change_file_extension()
    {
        $input_file = "/home/user/image.png";
        $expected_output = "/home/user/image.avif";

        $this->assertEquals($expected_output, Tiny_Helpers::replace_file_extension("image/avif", $input_file));
    }

    public function test_will_not_change_file_extension_if_file_has_query_parameters()
    {
        $input_file = "/home/user/image.png?v=123";
        $expected_output = "/home/user/image.webp";

        $this->assertEquals($expected_output, Tiny_Helpers::replace_file_extension("image/webp", $input_file));
    }

    public function test_can_handle_directory_separator()
    {
        $input_file = "/home/user.png/image.png";
        $expected_output = "/home/user.png/image.webp";

        $this->assertEquals($expected_output, Tiny_Helpers::replace_file_extension("image/webp", $input_file));
    }

    public function test_returns_original_if_no_extension()
{
    $input  = '/home/user/image';               // no “.ext”
    $output = Tiny_Helpers::replace_file_extension('image/webp', $input);
    $this->assertEquals($input, $output);
}

public function test_returns_original_if_unsupported_mimetype()
{
    $input  = '/home/user/image.png';
    $output = Tiny_Helpers::replace_file_extension('application/pdf', $input);
    $this->assertEquals($input, $output);
}

public function test_strips_url_fragment_alone()
{
    $input    = '/home/user/image.png#section2';
    $expected = '/home/user/image.avif';
    $this->assertEquals($expected, Tiny_Helpers::replace_file_extension('image/avif', $input));
}

public function test_strips_both_query_and_fragment()
{
    $input    = '/home/user/image.png?v=123#top';
    $expected = '/home/user/image.webp';
    $this->assertEquals($expected, Tiny_Helpers::replace_file_extension('image/webp', $input));
}

public function test_multiple_dots_in_filename()
{
    $input    = '/home/user/archive.tar.gz';
    $expected = '/home/user/archive.tar.avif';
    $this->assertEquals($expected, Tiny_Helpers::replace_file_extension('image/avif', $input));
}

public function test_double_backslash_paths()
{
    $input    = 'C:\\images\\photo.png?foo=bar';
    $expected = 'C:\\images\\photo.webp';
    $this->assertEquals($expected, Tiny_Helpers::replace_file_extension('image/webp', $input));
}

public function test_filename_only_in_current_dir()
{
    $input    = 'image.png';
    $expected = 'image.avif';
    $this->assertEquals($expected, Tiny_Helpers::replace_file_extension('image/avif', $input));
}

public function test_root_directory_file()
{
    $input    = '/image.png';
    $expected = '/image.webp';
    $this->assertEquals($expected, Tiny_Helpers::replace_file_extension('image/webp', $input));
}

public function test_uppercase_extension_and_mimetype_case_insensitive()
{
    $input    = '/home/user/PICTURE.PNG';
    $expected = '/home/user/PICTURE.avif';
    $this->assertEquals($expected, Tiny_Helpers::replace_file_extension('image/avif', $input));
}
}
