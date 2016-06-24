<?php

require_once dirname( __FILE__ ) . '/TinyTestCase.php';

class Tiny_Exception_Test extends Tiny_TestCase {
	public function testConstructorCreatesExceptionWithMessage() {
		$err = new Tiny_Exception( 'Message' );
		$this->assertInstanceOf( 'Tiny_Exception', $err );
	}

	public function testConstructorCreatesExceptionWithMessageAndError() {
		$err = new Tiny_Exception( 'Message', 'ErrorType' );
		$this->assertInstanceOf( 'Tiny_Exception', $err );
	}

	public function testConstructorThrowsIfMessageIsNotAString() {
		$this->setExpectedException( 'InvalidArgumentException' );
		new Tiny_Exception( 404, 'ErrorType' );
	}

	public function testConstructorThrowsIfErrorIsNotAString() {
		$this->setExpectedException( 'InvalidArgumentException' );
		new Tiny_Exception( 'Message', new Exception( 'err' ) );
	}
}
