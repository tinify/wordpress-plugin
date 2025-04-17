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
}
