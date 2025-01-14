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
        $expected_output = "Text to be truncated...";

        $this->assertEquals($expected_output, Tiny_Helpers::truncate_text($input_text, 20));
    }

    public function test_will_not_truncate_if_text_is_shorter_than_length()
    {
        $input_text = "Text will not be truncated";

        $this->assertEquals($input_text, Tiny_Helpers::truncate_text($input_text, 26));
    }
}
