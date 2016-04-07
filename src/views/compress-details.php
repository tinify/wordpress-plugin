<?php
$error = $tiny_metadata->get_latest_error();
$total = $tiny_metadata->get_count(array('modified', 'missing', 'has_been_compressed', 'compressed'));
$active = $tiny_metadata->get_count(array('uncompressed', 'never_compressed'), $active_tinify_sizes);
$savings = $tiny_metadata->get_savings();

?><div class="details-container">
    <div class="details" id="tinify-compress-details">
        <?php if ($tiny_metadata->can_be_compressed()) { ?>
            <?php if ($error) { ?>
                <span class="icon dashicons dashicons-warning error"></span>
            <?php } else if ($total['missing'] > 0 || $total['modified'] > 0) { ?>
                <span class="icon dashicons dashicons-yes alert"></span>
            <?php } else if ($total['compressed'] > 0 && $active['uncompressed'] > 0) { ?>
                <span class="icon dashicons dashicons-yes alert"></span>
            <?php } else if ($total['compressed'] > 0) { ?>
                <span class="icon dashicons dashicons-yes success"></span>
            <?php } ?>
            <span class="icon spinner hidden"></span>

            <?php if ($total['has_been_compressed'] > 0 || ($total['has_been_compressed'] == 0 && $active['uncompressed'] == 0)) { ?>
                <span class="message">
                    <strong><?php echo $total['has_been_compressed'] ?></strong>
                    <span>
                        <?php echo htmlspecialchars(_n('size compressed', 'sizes compressed', $total['has_been_compressed'], 'tiny-compress-images')) ?>
                    </span>
                </span>
                <br/>
            <?php } ?>

            <?php if ($active['never_compressed'] > 0) { ?>
                <span class="message">
                    <?php echo htmlspecialchars(sprintf(_n('%d size not compressed', '%d sizes not compressed', $active['never_compressed'], 'tiny-compress-images'), $active['never_compressed'])) ?>
                </span>
                <br />
            <?php } ?>

            <?php if ($total['missing'] > 0) { ?>
                <span class="message">
                    <?php echo htmlspecialchars(sprintf(_n('%d file removed', '%d files removed', $total['missing'], 'tiny-compress-images'), $total['missing'])) ?>
                </span>
                <br />
            <?php } ?>

            <?php if ($total['modified'] > 0) { ?>
                <span class="message">
                    <?php echo htmlspecialchars(sprintf(_n('%d file modified after compression', '%d files modified after compression', $total['modified'], 'tiny-compress-images'), $total['modified'])) ?>
                </span>
                <br />
            <?php } ?>

            <?php if ($savings["input"] - $savings["output"]) { ?>
                <span class="message">
                    <?php printf(esc_html__('Total savings %s', 'tiny-compress-images'), str_replace(" ", "&nbsp;", size_format($savings["input"] - $savings["output"], 1))) ?>
                </span>
                <br />
            <?php } ?>

            <?php if ($error) { ?>
                <span class="message error_message">
                    <?php echo esc_html__('Latest error', 'tiny-compress-images') . ': '. esc_html__($error, 'tiny-compress-images') ?>
                </span>
                <br/>
            <?php } ?>

            <?php if ($total['has_been_compressed'] > 0) { ?>
                <a class="thickbox message" href="#TB_inline?width=700&amp;height=500&amp;inlineId=modal_<?php echo $tiny_metadata->get_id() ?>">Details</a>
            <?php } ?>
        <?php } ?>
    </div>

    <?php if ($tiny_metadata->can_be_compressed() && $active['uncompressed'] > 0) { ?>
        <button type="button" class="tiny-compress button button-small button-primary" data-id="<?php echo $tiny_metadata->get_id() ?>">
            <?php echo esc_html__('Compress', 'tiny-compress-images') ?>
        </button>
    <?php } ?>
</div>
<?php if ($total['has_been_compressed'] > 0) { ?>
    <div class="modal" id="modal_<?php echo $tiny_metadata->get_id() ?>">
        <div class="tiny-compression-details">
            <h3>
                <?php printf(esc_html__('Compression details for %s', 'tiny-compress-images'), $tiny_metadata->get_name()) ?>
            </h3>
            <table>
                <tr>
                    <th><?php esc_html_e('Size', 'tiny-compress-images') ?></th>
                    <th><?php esc_html_e('Original', 'tiny-compress-images') ?></th>
                    <th><?php esc_html_e('Compressed', 'tiny-compress-images') ?></th>
                    <th><?php esc_html_e('Date', 'tiny-compress-images') ?></th>
                </tr>
                <?php $i = 0 ?>
                <?php foreach ($tiny_metadata->filter_images('has_been_compressed') as $size => $image) {
                        $meta = $image->meta ? $image->meta : array() ?>
                    <tr class="<?php echo ($i % 2 == 0) ? 'even' : 'odd' ?>">
                        <td>
                            <?php
                            echo ($size === Tiny_Metadata::ORIGINAL ? esc_html__('original', 'tiny-compress-images') : $size ) . ' ';
                            if ($image->missing()) {
                                echo '<em>' . esc_html__('(file removed)', 'tiny-compress-images') . '</em>';
                            } else if ($image->modified()) {
                                echo '<em>' . esc_html__('(modified after compression)', 'tiny-compress-images') . '</em>';
                            } else if ($image->resized()) {
                                printf('<em>' . esc_html__('(resized to %dx%d)', 'tiny-compress-images') . '</em>', $meta['output']['width'], $meta['output']['height']);
                            } else if (!$image->compressed()) {
                                echo '<em>' . esc_html__('(unknown state)', 'tiny-compress-images') . '</em>';
                            }
                            ?>
                        </td>
                        <td><?php echo size_format($meta["input"]["size"], 1) ?></td>
                        <td><?php echo size_format($meta["output"]["size"], 1) ?></td>
                        <td><?php echo human_time_diff($image->end_time($size)) . ' ' . esc_html__('ago', 'tiny-compress-images') ?></td>
                    </tr>
                    <?php $i++ ?>
                <?php } ?>
                <?php if ($savings['count'] > 0) { ?>
                <tfoot>
                    <tr>
                        <td><?php esc_html_e('Combined', 'tiny-compress-images') ?></td>
                        <td><?php echo size_format($savings['input'], 1) ?></td>
                        <td><?php echo size_format($savings['output'], 1) ?></td>
                        <td></td>
                    </tr>
                </tfoot>
                <?php } ?>
            </table>
            <p><strong><?php printf(esc_html__('Total savings %s', 'tiny-compress-images'), size_format($savings["input"] - $savings["output"], 1)) ?></strong></p>
        </div>
    </div>
<?php } ?>
