<?php
/**
 * Settings section for showing credit cost per image 
 * 
 * @var int $tiny_sizes_count number of sizes that are compressed
 * @var bool $tiny_resize wether or not the original size is resized
 * @var bool $tiny_wr2x plugin wr2x compressed causes images to be compressed twice
 * @var bool $tiny_convert wether or not images are being converted
 */
?>

<div class="tiny-card">
    <div class="tiny-card-section">
        <div class="tiny-card-head">
            <h2><?php esc_html_e('How credits are used', 'tiny-compress-images'); ?></h2>
        </div>
        <dl class="tiny-credit-rows">
            <div>
                <dt><?php esc_html_e('1 credit', 'tiny-compress-images'); ?></dt>
                <dd><?php esc_html_e('for each image size that is compressed', 'tiny-compress-images'); ?></dd>
            </div>
            <div>
                <dt><?php esc_html_e('+1 credit', 'tiny-compress-images'); ?></dt>
                <dd><?php esc_html_e('per size for a WebP or AVIF version', 'tiny-compress-images'); ?></dd>
            </div>
            <div>
                <dt><?php esc_html_e('+1 credit', 'tiny-compress-images'); ?></dt>
                <dd><?php esc_html_e('when a large original is resized', 'tiny-compress-images'); ?></dd>
            </div>
        </dl>
    </div>
    <div class="tiny-card-footer" id="tiny-credits-per-image">
        <?php $this->render_credits_per_image(self::get_credits_per_image($tiny_sizes_count, $tiny_resize, $tiny_wr2x, $tiny_convert)); ?>
    </div>
</div>