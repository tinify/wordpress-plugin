<h2><?= esc_html__( 'JPEG and PNG optimization', 'tiny-compress-images' ) ?></h2>
<p><?= esc_html__( 'Make your website faster by optimizing your JPEG and PNG images.', 'tiny-compress-images' ) ?></p>

<div class="tiny-compress-images">
  <span id="tiny-compress-images"></span>
  <form action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>" id="tinify-settings" method="post">
    <?php settings_fields( "tinify" ) ?>

    <table class="form-table tinify-settings">
      <tbody>
        <tr>
          <th scope="row"><?= esc_html__( 'Tinify account', 'tiny-compress-images' ) ?></th>
          <td>
            <?php $this->render_pending_status() ?>
          </td>
        </tr>
        <tr>
          <th scope="row"><?= esc_html__( 'Optimization method', 'tiny-compress-images' ) ?></th>
          <td>
            <?php $this->render_optimization_method_settings() ?>
          </td>
        </tr>
        <tr>
          <th scope="row"><?= esc_html__( 'Image sizes', 'tiny-compress-images' ) ?></th>
          <td>
            <h4><?= esc_html__( 'Select image sizes to be optimized', 'tiny-compress-images' ) ?></h4>
            <p class="intro">
              <?= esc_html__(
                    'Wordpress generates resized versions of every image. Choose which sizes to optimize.',
                    'tiny-compress-images'
                  )
              ?>
            </p>
            <div class="sizes">
              <?php $this->render_sizes() ?>
            </div>
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
