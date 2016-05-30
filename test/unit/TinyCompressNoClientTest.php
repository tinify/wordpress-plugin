<?php

require_once(dirname(__FILE__) . "/TinyTestCase.php");

class Tiny_Compress_No_Client_Test extends TinyTestCase {
    protected $php_mock;

    public function setUp() {
        parent::setUp();
        $this->php_mock = \Mockery::mock('alias:Tiny_PHP');
        $this->php_mock->shouldReceive('client_library_supported')->andReturn(false);
    }

    public function testShouldReturnFopenCompressorIfClientNotSupported() {
        $this->php_mock->shouldReceive('fopen_available')->andReturn(true);
        $compressor = Tiny_Compress::create('api1234');
        $this->assertInstanceOf('Tiny_Compress_Fopen', $compressor);
    }

    /**
     * @expectedException Tiny_Exception
     */
    public function testShouldThrowErrorWhenCurlAndFopenUnavailable() {
        $this->php_mock->shouldReceive('fopen_available')->andReturn(false);
        $compressor = Tiny_Compress::create('api1234');
        $this->assertInstanceOf('Tiny_Compress', $compressor);
    }
}
