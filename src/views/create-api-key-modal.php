<?php
$field = 'tinypng_api_key';
$key = $this->get_api_key();
?>
<div id='tinypng_api_key_wrapper' style="display: none">
  <div id='tinypng_api_key_container' class='wp-core-ui' style="border: none">
    <div class='tinypng_api_key_step2'>
      <h4 class='tinypng_api_key_text'>
        <?php printf(esc_html__('Enter API key', 'tiny-compress-images')) ?>
      </h4>
      <input class='tinypng_api_key_input' type='text' id='tinypng_api_key_modal' name='tinypng_api_key' size='40' value="<?php echo htmlspecialchars($key) ?>" />
      <button type='button' class='tinypng-save-api-key button button-primary'>
        <?php echo esc_html__('Save', 'tiny-compress-images') ?>
      </button>
      <div class="api-error" style="display: none">
      <?php include(dirname(__FILE__) . '/render-status.php') ?>
      </div>
    </div>
  </div>
</div>
