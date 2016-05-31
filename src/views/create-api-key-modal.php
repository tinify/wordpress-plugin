<?php
$key = $this->get_api_key();
?>
<div id='tinypng_api_key_wrapper' style="display: none">
  <div id='tinypng_api_key_container' class='wp-core-ui' style="border: none">
    <div class='tinypng_api_key_step2'>
      <h4 class='tinypng_api_key_text'>
        <?php printf(esc_html__('Change API key', 'tiny-compress-images')) ?>
      </h4>
      <p class='tinypng-api-key-information'>
        <?php printf(__('Enter your new API key.', 'tiny-compress-images')) ?>
      </p>
      <input class='tinypng_api_key_input' type='text' id='tinypng_api_key_modal' name='tinypng_api_key' size='40' value="<?php echo htmlspecialchars($key) ?>" />
      <button type='button' class='tinypng-save-api-key button button-primary'>
        <?php echo esc_html__('Save', 'tiny-compress-images') ?>
      </button>
      <p class='tinypng-api-key-message invalid-key' style="display: none">
        <span class="dashicons-before dashicons-info"></span>
        <?php printf(esc_html__('The key that you have entered does not exist. Please try a different key.', 'tiny-compress-images')) ?>
      </p>
      <p class='tinypng-api-key-message save-error' style="display: none">
        <span class="dashicons-before dashicons-warning"></span>
        <?php printf(esc_html__('Something went wrong. Please try again later.', 'tiny-compress-images')) ?>
      </p>
    </div>
  </div>
</div>
