<?php

if ( ! defined( 'TINY_DEBUG' ) ) {
	define( 'TINY_DEBUG', null );
}

class Tiny_Config {
	/* URL is only used by fopen driver. */
	const SHRINK_URL = 'http://webservice/shrink';
	const KEYS_URL = 'http://webservice/keys';
	const MONTHLY_FREE_COMPRESSIONS = 500;
	const META_KEY = 'tiny_compress_images';
}
