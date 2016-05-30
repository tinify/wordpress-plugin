<?php
$key = $this->get_api_key();
global $current_user;
$name = $current_user->user_firstname . ' ' . $current_user->user_lastname;
$email = $current_user->user_email;
$identifier = get_bloginfo( 'url' );
$link = '<a href="https://tinypng.com/developers" target="_blank">' . esc_html__('TinyPNG Developer section', 'tiny-compress-images') . '</a>';
$free_images_per_month = floor( Tiny_Config::MONTHLY_FREE_COMPRESSIONS / count(self::get_active_tinify_sizes()));
?>
<div id='tinypng_api_key_container' class='wp-core-ui'>
  <div class='tinypng_api_key_step1'>
    <h4 class='tinypng_api_key_text'>
      <?php echo esc_html_e('Register new account', 'tiny-compress-images') ?>
    </h4>
    <p class='tinypng-api-key-information'>
      <?php printf(__('With a free account you can optimize <strong> at least %s images </strong> each month (based on your current settings).', 'tiny-compress-images'), $free_images_per_month) ?>
    </p>
    <input class='tinypng_api_key_input' type='text' id='tinypng_api_key_name' name='tinypng_api_key_name' value="<?php echo htmlspecialchars($name) ?>" />
    <input class='tinypng_api_key_input' type='text' id='tinypng_api_key_email' name='tinypng_api_key_email' value="<?php echo htmlspecialchars($email) ?>" />
    <input type='hidden' id='tinypng_api_key_identifier' name='tinypng_api_key_identifier' value="<?php echo htmlspecialchars($identifier) ?>" />
    <button type='button' class='tinypng-create-api-key button button-primary'>
      <?php echo esc_html__('Register new account!', 'tiny-compress-images') ?>
    </button>
    </div>
  <div class='tinypng_api_key_step2'>
    <h4 class='tinypng_api_key_text'>
      <?php printf(esc_html__('Already have an account?', 'tiny-compress-images')) ?>
    </h4>
    <p class='tinypng-api-key-information'>
      <?php printf(esc_html__('Then you can fill in your API key here. If you have lost your key then head over to %s to retrieve it.', 'tiny-compress-images'), $link) ?>
    </p>
    <input class='tinypng_api_key_input' type='text' id='tinypng_api_key' name='tinypng_api_key' />
    <?php echo submit_button('Save', 'button-primary', 'tinypng_submit_api_key') ?>
  </div>
</div>
