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
	const CONVERSION_AVIF = 'image/avif';
	const CONVERSION_WEBP = 'image/webp';

	/**
	 * The options for the conversion format.
	 *
	 * @var array{string} The options for the conversion format.
	 */
	const CONVERSION_FORMAT_OPTIONS = array(
		self::CONVERSION_AVIF,
		self::CONVERSION_WEBP,
	);
}
