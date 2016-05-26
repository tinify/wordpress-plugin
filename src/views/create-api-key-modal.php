<?php
$field = 'tinypng_api_key';
$key = $this->get_api_key();
global $current_user;
$name = $current_user->user_firstname . ' ' . $current_user->user_lastname;
$mail = $current_user->user_email;
$alias = get_bloginfo( 'url' );
?>
<div id='tinypng_api_key_container' style="display: none">
  <div class='tinypng_api_key_step1'>
    <span class='tinypng_api_key_text'>
      <?php echo esc_html_e('Create new API key', 'tiny-compress-images') ?>
    </span>
    <input class='tinypng_api_key_input' type='text' id='tinypng_api_key_name' name='tinypng_api_key_name' value="<?php echo htmlspecialchars($name) ?>" />
    <input class='tinypng_api_key_input' type='text' id='tinypng_api_key_mail' name='tinypng_api_key_mail' value="<?php echo htmlspecialchars($mail) ?>" />
    <input type='hidden' id='tinypng_api_key_alias' name='tinypng_api_key_alias' value="<?php echo htmlspecialchars($alias) ?>" />
    <button type='button' class='tinypng-create-api-key button'>
      <?php echo esc_html__('Get me a key!', 'tiny-compress-images') ?>
    </button>
    </div>
  <div class='tinypng_api_key_step2'>
    <div class='tinypng_api_key_container'>
    <span class='tinypng_api_key_text'>
      <?php printf(esc_html__('Enter API key', 'tiny-compress-images')) ?>
    </span>
    <input class='tinypng_api_key_input' type='text' id='tinypng_api_key_modal' name='tinypng_api_key' size='40' value="<?php echo htmlspecialchars($key) ?>" />
    <div class="invalid-key">
      <span class="key-error">
        <?php echo esc_html__('The provided API key is invalid', 'tiny-compress-images') ?>
      </span>
    <button type='button' class='tinypng-save-api-key button'>
      <?php echo esc_html__('Save', 'tiny-compress-images') ?>
    </button>
    </div>
    </div>
  </div>
</div>
