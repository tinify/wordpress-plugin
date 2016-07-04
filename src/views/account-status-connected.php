<?php

if ( $status->ok ) {
	echo '<p class="tiny-account-status">';
	if ( isset( $status->message ) ) {
		echo '<span class="icon warning dashicons-before dashicons-email-alt"></span>';
		echo esc_html__( $status->message, 'tiny-compress-images' );
	} else {
		echo '<span class="icon success dashicons-before dashicons-yes"></span>';
		echo esc_html__( 'Your account is connected.', 'tiny-compress-images' );
	}
} else {
	echo '<p class="tiny-account-status tiny-account-status-error">';
	echo '<span class="icon error dashicons-before dashicons-no"></span>';
	echo esc_html__( 'Connection unsuccessful.', 'tiny-compress-images' );
}

echo ' ';

if ( defined( 'TINY_API_KEY' ) ) {
	echo sprintf( esc_html__( 'The API key has been configured in %s', 'tiny-compress-images' ), 'wp-config.php' );
} else {
	add_thickbox();
	echo '<a href="#TB_inline?width=390&amp;height=150&amp;inlineId=tiny-update-account" title="Change API key" class="thickbox">';
	echo esc_html__( 'Change API key', 'tiny-compress-images' );
	echo '</a>';
}

echo '</p>';

if ( $status->ok ) {
	$compressions = self::get_compression_count();
	echo '<p>';
	/* It is not possible to check if a subscription is free or flexible. */
	if ( $compressions == Tiny_Config::MONTHLY_FREE_COMPRESSIONS ) {
		$link = '<a href="https://tinypng.com/developers" target="_blank">' . esc_html__( 'TinyPNG API account', 'tiny-compress-images' ) . '</a>';
		printf( esc_html__( 'You have reached your limit of %s compressions this month.', 'tiny-compress-images' ), $compressions );
		echo '<br>';
		printf( esc_html__( 'If you need to compress more images you can change your %s.', 'tiny-compress-images' ), $link );
	} else {
		printf( esc_html__( 'You have made %s compressions this month.', 'tiny-compress-images' ), $compressions );
	}
	echo '</p>';
} else {
	echo '<p>';

	if ( isset( $status->message ) ) {
		echo esc_html__( 'Error', 'tiny-compress-images' ) . ': ' . esc_html__( $status->message, 'tiny-compress-images' );
	} else {
		esc_html__( 'API status could not be checked, enable cURL for more information', 'tiny-compress-images' );
	}

	echo '</p>';
}

?>