<?php

class TinifyMockClient extends Tinify\Client {
	function __construct($key = null, $appIdentifier = null) {
		parent::__construct( $key, $appIdentifier );
		$this->handlers = array();
	}

	public function request($method, $url, $body = null, $header = array()) {
		$key = $this->get_key( $method, $url );
		if ( isset( $this->handlers[ $key ] ) ) {
			$handler = $this->handlers[ $key ];
			$handler( $this );
		} else {
			throw new Exception( 'No handler for ' . $key );
		}
	}

	public function register($method, $url, $handler) {
		$key = $this->get_key( $method, $url );
		$this->handlers[ $key ] = $handler;
	}

	private function get_key($method, $url) {
		return strtoupper( $method ) . ' ' . $url;
	}
}
