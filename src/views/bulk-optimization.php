<?php
require_once(dirname(__FILE__) . '/bulk-optimization/savings-chart.php');
?>

<div class="wrap tiny-bulk-optimization tiny-compress-images" id="tiny-bulk-optimization">
<?php echo '<h1>' . __('Bulk Optimization', 'tiny-compress-images') . '</h2>' ?>

    <div class="overview whitebox">
        <div class="statistics">
            <h3><?php echo __('Available images for optimization', 'tiny-compress-images') ?></h3>

            <p>
                <?php
                if ($optimized_image_sizes + $unoptimized_image_sizes == 0) {
                    echo __('This page is designed to bulk compress all your images. You don\'t seem to have uploaded any.');
                } else {
                    $percentage = round($optimized_image_sizes / ($optimized_image_sizes + $unoptimized_image_sizes) * 100, 2);
                    if ($percentage == 100) {
                        echo __('Great! Your entire library is optimimized!');
                        // TODO: If we have 0 active sizes, show a different message.
                    } else if ($optimized_image_sizes > 0) {
                        echo __('You are doing great!');
                        echo ' ';
                        printf(esc_html__('%d%% of your image library is optimized.', 'tiny-compress-images'), $percentage);
                        echo ' ';
                        printf(esc_html__('Start the bulk optimization to optimize the remainder of your library.', 'tiny-compress-images'));
                    } else {
                        echo __('Here you can start optimizing your entire library. Press the green button to start improving your website speed instantly!');
                    }
                }
                ?>
            </p>

            <div class="totals">
                <div class="item">
                    <h3>
                        <?php echo __('Uploaded', 'tiny-compress-images') ?>
                        <br>
                        <?php echo __('images', 'tiny-compress-images') ?>
                    </h3>
                    <span id="uploaded-images">
                        <?php echo $uploaded_images ?>
                    </span>
                </div>
                <div class="item">
                    <h3>
                        <?php echo __('Uncompressed', 'tiny-compress-images') ?>
                        <br>
                        <?php echo __('image sizes', 'tiny-compress-images') ?>
                    </h3>
                    <span id="optimizable-image-sizes">
                        <?php echo $unoptimized_image_sizes ?>
                    </span>
                    <div class="tooltip">
                        <span class="dashicons dashicons-info"></span>
                        <div class="tip">
                            <?php if ($uploaded_images > 0 && sizeof($active_tinify_sizes) > 0 && $unoptimized_image_sizes > 0) { ?>
                                <p>
                                    <?php
                                    printf(esc_html__('With your current settings you can still optimize %d images sizes from your %d uploaded JPEG and PNG images.',
                                                      'tiny-compress-images'), $unoptimized_image_sizes, $uploaded_images);
                                    ?>
                                </p>
                            <?php } ?>
                            <p>
                                <?php
                                if (sizeof($active_tinify_sizes) == 0) {
                                    echo __('Based on your current settings, nothing will be optimized. There are no active sizes selected for optimization.');
                                } else {
                                    echo __('These sizes are currently activated for compression:');
                                    echo '<ul>';
                                    for ($i = 0; $i < sizeof($active_tinify_sizes); ++$i) {
                                        $name = $active_tinify_sizes[$i];
                                        if ($name == '0') {
                                            echo '<li>- ' . __('original') . '</li>';
                                        } else {
                                            echo '<li>- ' . $name . '</li>';
                                        }
                                    }
                                    echo '</ul>';
                                }
                                ?>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="item">
                    <h3>
                        <?php echo __('Estimated', 'tiny-compress-images') ?>
                        <br>
                        <?php echo __('cost', 'tiny-compress-images') ?>
                    </h3>
                    <span id='estimated-cost'>$ <?php echo number_format(round($estimated_cost, 2), 2) ?></span>
                    USD
                </div>
            </div>
            <div class="notes">
                <p><?php echo __('Remember') ?></p>
                <p>
                    <?php echo __('In order to let us do the work for you, you need to keep this page open. But no worries - when stopped, we\'ll continue where you left off!'); ?>
                </p>
            </div>
        </div>

        <div class="savings">
            <h3><?php echo __('Total Savings') ?></h3>
            <p>
                <?php echo __('Statistics based on all available JPEG and PNG images in your media library.'); ?>
            </p>
            <?php
                render_percentage_chart(round($savings_percentage, 1));
            ?>
            <table class="savings-numbers">
                <tr>
                    <td id="optimized-image-sizes" class="green">
                        <?php echo ($optimized_image_sizes ? $optimized_image_sizes : '0'); ?>
                    </td>
                    <td>
                        <?php echo _n('image size optimized', 'image sizes optimized', $optimized_image_sizes, 'tiny-compress-images') ?>
                    </td>
                </tr>
                <tr>
                    <td id="unoptimized-library-size">
                        <?php echo ($unoptimized_library_size ? size_format($unoptimized_library_size, 2) : '-'); ?>
                    </td>
                    <td>
                        <?php echo __('initial size', 'tiny-compress-images') ?>
                    </td>
                </tr>
                <tr>
                    <td id="optimized-library-size" class="green">
                        <?php echo ($optimized_library_size ? size_format($optimized_library_size, 2) : '-') ?>
                    </td>
                    <td>
                        <?php echo __('current size', 'tiny-compress-images') ?>
                    </td>
                </tr>
            </table>
        </div>

        <div class="optimize">
            <?php if (sizeof($ids_to_compress) > 0) { ?>
                <div class="progressbar" id="compression-progress" data-amount-to-optimize="<?php echo $optimized_image_sizes + $unoptimized_image_sizes ?>" data-amount-optimized="0">
                    <div class="progressbar-progress"></div>
                    <span id="optimized-so-far">
                        <?php echo $optimized_image_sizes ?>
                    </span> /
                    <?php echo $optimized_image_sizes + $unoptimized_image_sizes ?>
                    <span id="percentage"></span>
                </div>
            <?php } ?>
            <?php
            if ($unoptimized_image_sizes > 0) {
                require_once(dirname(__FILE__) . '/bulk-optimization/form.php');
            }
            ?>
        </div>
    </div>

    <?php
    if (sizeof($ids_to_compress) > 0) {
        echo "<script type='text/javascript'>jQuery(function() { startBulkOptimization(" . json_encode($ids_to_compress) . ")})</script>";
    ?>
    <table class="wp-list-table widefat fixed striped media whitebox" id="media-items">
        <tr>
            <th class="thumbnail"></th>
            <th><?php echo __('File', 'tiny-compress-images') ?></th>
            <th><?php echo __('Sizes optimized', 'tiny-compress-images') ?></th>
            <th><?php echo __('Original total size', 'tiny-compress-images') ?></th>
            <th><?php echo __('Optimized total size', 'tiny-compress-images') ?></th>
            <th><?php echo __('Savings', 'tiny-compress-images') ?></th>
            <th><?php echo __('Status', 'tiny-compress-images') ?></th>
        </tr>
    </table>
    <?php } ?>
</div>
