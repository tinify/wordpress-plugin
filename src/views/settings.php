<h2><?= esc_html__( 'JPEG and PNG optimization', 'tiny-compress-images' ) ?></h2>

<div class="tiny-compress-images">
  <span id="tiny-compress-images"></span>
  <form action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>" id="tinify-settings" method="post">
    <?php settings_fields( "tinify" ) ?>

    <table class="form-table">
      <tbody>
        <tr>
          <th scope="row"><?= esc_html__( 'TinyPNG account', 'tiny-compress-images' ) ?></th>
          <td>
            <?php $this->render_pending_status() ?>
          </td>
        </tr>
        <tr>
          <th scope="row">General settings</th>
          <td>
            <?php $this->render_general_settings() ?>
          </td>
        </tr>
        <tr>
          <th scope="row"><?= esc_html__( 'File compression', 'tiny-compress-images' ) ?></th>
          <td>
            <?php $this->render_sizes() ?>
          </td>
        </tr>
        <tr>
          <th scope="row">Original image</th>
          <td>
            <?php $this->render_resize() ?>
          </td>
        </tr>
      </tbody>
    </table>
    <p class="submit">
      <input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes">
    </p>
  </form>
</div>
