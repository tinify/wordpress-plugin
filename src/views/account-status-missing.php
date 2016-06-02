<?php

$key = $this->get_api_key();
global $current_user;
$name = $current_user->user_firstname . ' ' . $current_user->user_lastname;
$email = $current_user->user_email;
$identifier = get_bloginfo( 'url' );
$link = '<a href="https://tinypng.com/developers" target="_blank">' . esc_html__('TinyPNG developer section', 'tiny-compress-images') . '</a>';
$free_images_per_month = (count(self::get_active_tinify_sizes()) > 0) ? (floor( Tiny_Config::MONTHLY_FREE_COMPRESSIONS / count(self::get_active_tinify_sizes()))) : 500;

?>
<div class='tiny-account-container' class='wp-core-ui'>
    <div class='tiny-update-account-step1'>
        <h4><?php echo esc_html_e('Register new account', 'tiny-compress-images') ?></h4>

        <p><?php printf(__('Provide your name and email address to start optimizing images.', 'tiny-compress-images'), $free_images_per_month) ?></p>

        <input class='tinypng-api-key-input' type='text' id='tinypng_api_key_name' name='tinypng_api_key_name' value="<?php echo htmlspecialchars($name) ?>" />
        <input class='tinypng-api-key-input' type='text' id='tinypng_api_key_email' name='tinypng_api_key_email' value="<?php echo htmlspecialchars($email) ?>" />
        <input type='hidden' id='tinypng_api_key_identifier' name='tinypng_api_key_identifier' value="<?php echo htmlspecialchars($identifier) ?>" />
        <p class="tiny-create-account-message error" style="display: none"></p>
        <button type='submit' class='tiny-account-create-key button button-primary'>
            <?php echo esc_html__('Register account', 'tiny-compress-images') ?>
        </button>

        <!-- <p class='tinypng-api-key-message success' style="display: none">
            <span class="dashicons-before dashicons-email-alt"></span>
            <?php printf(esc_html__('Thank you for registering. Before you can start you have to verify your address in the mail that we just sent you.', 'tiny-compress-images')) ?>
        </p>
        <p class='tinypng-api-key-message already-registered' style="display: none">
            <span class="dashicons-before dashicons-info"></span>
            <?php printf(esc_html__('You have already registed with this email. Please go to %s to retrieve your key.', 'tiny-compress-images'), $link) ?>
        </p>
        <p class='tinypng-api-key-message invalid-form' style="display: none">
            <span class="dashicons-before dashicons-info"></span>
            <?php printf(esc_html__('You have to fill in your name and email address.', 'tiny-compress-images')) ?>
        </p>
        <p class='tinypng-api-key-message error' style="display: none">
            <span class="dashicons-before dashicons-warning"></span>
            <?php printf(esc_html__('Something went wrong:', 'tiny-compress-images')) ?>
            <p class='tinypng-error-message'></p>
        </p> -->
    </div>
    <div class='tiny-update-account-step2'>
        <h4><?php printf(esc_html__('Already have an account?', 'tiny-compress-images')) ?></h4>

        <p><?php printf(esc_html__('Enter your API key. Go to the %s to retrieve it.', 'tiny-compress-images'), $link) ?></p>

        <input class='tinypng-api-key-input' type='text' id='<?php echo self::get_prefixed_name('api_key') ?>' name='<?php echo self::get_prefixed_name('api_key') ?>' />
        <p class="tiny-update-account-message error" style="display: none"></p>
        <button class='tiny-account-save-key button button-primary'>
            <?php echo esc_html__('Save', 'tiny-compress-images') ?>
        </button>

        <!-- <p class='tinypng-api-key-message invalid-key' style="display: none">
            <span class="dashicons-before dashicons-info"></span>
            <?php printf(esc_html__('The key that you have entered does not exist. Please try a different key.', 'tiny-compress-images')) ?>
        </p>
        <p class='tinypng-api-key-message save-error' style="display: none">
            <span class="dashicons-before dashicons-warning"></span>
            <?php printf(esc_html__('Something went wrong. Please try again later.', 'tiny-compress-images')) ?>
        </p> -->
    </div>
</div>
