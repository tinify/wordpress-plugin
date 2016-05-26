<?php
$field = 'tinypng_api_key';
$key = $this->get_api_key();
global $current_user;
$name = $current_user->user_firstname . ' ' . $current_user->user_lastname;
$mail = $current_user->user_email;
$alias = get_bloginfo( 'url' );
?>
<div id='tinypng_api_key_container'>
  <div class='tinypng_api_key_step1'>
    <span class='tinypng_api_key_text'>
      <?php echo esc_html_e('Create new API key', 'tiny-compress-images') ?>
    </span>
    <input class='tinypng_api_key_input' type='text' id='tinypng_api_key_name' name='tinypng_api_key_name' value="<?php echo htmlspecialchars($name) ?>" />
    <input class='tinypng_api_key_input' type='text' id='tinypng_api_key_mail' name='tinypng_api_key_mail' value="<?php echo htmlspecialchars($mail) ?>" />
    <input type='hidden' id='tinypng_api_key_alias' name='tinypng_api_key_alias' value="<?php echo htmlspecialchars($alias) ?>" />
    <button type='button' class='tiny-new-api-key-create'>
      <?php echo esc_html__('Get me a key!', 'tiny-compress-images') ?>
    </button>
    </div>
  <div class='tinypng_api_key_step2'>
    <div class='tinypng_api_key_container'>
    <span class='tinypng_api_key_text'>
      <?php printf(esc_html__('Enter API key', 'tiny-compress-images')) ?>
    </span>
    <input class='tinypng_api_key_input' type='text' id='tinypng_api_key' name='tinypng_api_key' size='40' />
    <?php echo submit_button('Save', '', 'tinypng_api_key_save') ?>
    </div>
  </div>
</div>
