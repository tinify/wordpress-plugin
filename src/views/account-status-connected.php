<?php

// if (defined('TINY_API_KEY')) {
// echo '<p>' . sprintf(__('The API key has been configured in %s', 'tiny-compress-images'), 'wp-config.php') . '.</p>';
// } else {
//     $status = $this->compressor->get_status();
//     echo '<div class=' . $field . '_exists>';
//     if (!$status->ok && $status->message == "Credentials are invalid") {
//         echo '<span class="dashicons-before dashicons-no"></span>';
//         echo '<p class="tiny-account-status-error"> You need to activate your account.</p>';
//         add_thickbox();
//         echo '<a href="#TB_inline?width=450&height=210&inlineId=tinypng-api-key-wrapper" class="thickbox">' . esc_html__('Change API key', 'tiny-compress-images') . '</a>';
//         echo '</div>';
//     } else {
//         echo '<span class="dashicons-before dashicons-yes"></span>';
//         echo '<p class="tiny-account-status"> Your account is connected. </p>';
//         add_thickbox();
//         echo '<a href="#TB_inline?width=450&height=210&inlineId=tinypng-api-key-wrapper" class="thickbox">' . esc_html__('Change API key', 'tiny-compress-images') . '</a>';
//         echo '</div>';
//     }
// }

if ($status->ok) { ?>
    <p class="tiny-account-status">
        <span class="icon success dashicons-before dashicons-yes"></span><?php echo esc_html__('Your account is connected.', 'tiny-compress-images'); ?>
        <?php add_thickbox(); ?><a href="#TB_inline?width=390&amp;height=150&amp;inlineId=tiny-update-account" title="Change API key" class="thickbox"><?php echo esc_html__('Change API key', 'tiny-compress-images'); ?></a>
    </p>
<?php
} else {
?>
    <p class="tiny-account-status-error">
        <span class="icon error dashicons-before dashicons-no"></span><?php echo esc_html__('Connection unsuccessful', 'tiny-compress-images'); ?>
        <?php add_thickbox(); ?><a href="#TB_inline?width=390&amp;height=150&amp;inlineId=tiny-update-account" title="Change API key" class="thickbox"><?php echo esc_html__('Change API key', 'tiny-compress-images'); ?></a>
    </p>
    <p>
<?php
    if (isset($status->message)) {
        echo esc_html__('Error', 'tiny-compress-images') . ': ' . esc_html__($status->message, 'tiny-compress-images');
    } else {
        esc_html__('API status could not be checked, enable cURL for more information', 'tiny-compress-images');
    }
?>
    </p>
<?php
}

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
       printf(esc_html__('You have made %s compressions this month.', 'tiny-compress-images'), $compressions);
    }
    echo '</p>';
}

?>
