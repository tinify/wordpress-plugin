<?php

$status = $this->compressor->get_status();

echo '<p>';
if ($status->ok) {
    echo '<img src="images/yes.png"> ';
    echo esc_html__('API connection successful', 'tiny-compress-images');
} else {
    echo '<img src="images/no.png"> ';
    if (!$status->ok) {
        echo esc_html__('API connection unsuccessful', 'tiny-compress-images') . '<br>';
        if (isset($status->message)) {
            echo esc_html__('Error', 'tiny-compress-images') . ': ' . esc_html__($status->message, 'tiny-compress-images');
        }
    } else {
        esc_html_e('API status could not be checked, enable cURL for more information', 'tiny-compress-images');
    }
}
echo '</p>';

if ($status->ok) {
    $compressions = self::get_compression_count();
    echo '<p>';
    // It is not possible to check if a subscription is free or flexible.
    if ( $compressions == Tiny_Config::MONTHLY_FREE_COMPRESSIONS ) {
        $link = '<a href="https://tinypng.com/developers" target="_blank">' . esc_html__('TinyPNG API account', 'tiny-compress-images') . '</a>';
        printf(esc_html__('You have reached your limit of %s compressions this month.', 'tiny-compress-images'), $compressions);
        echo '<br>';
        printf(esc_html__('If you need to compress more images you can change your %s.', 'tiny-compress-images'), $link);
    } else {
       printf(esc_html__('You have made %s compressions this month.', 'tiny-compress-images'), self::get_compression_count());
    }
    echo '</p>';
}
?>
