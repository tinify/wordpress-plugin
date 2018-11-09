<?php

if ( ! defined( 'TINY_DEBUG' ) ) {
	define( 'TINY_DEBUG', null );
}

class Tiny_Config {
	/* URL is only used by fopen driver. */
	const SHRINK_URL = 'https://api.tinify.com/shrink';
	const KEYS_URL = 'https://api.tinify.com/keys';
	const MONTHLY_FREE_COMPRESSIONS = 500;
	const META_KEY = 'tiny_compress_images';
}
