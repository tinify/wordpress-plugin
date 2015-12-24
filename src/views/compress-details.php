<div class="details-container">
    <div class="details">
        <?php if ($error) { ?>
            <span class="icon dashicons dashicons-warning error"></span>
        <?php } else if ($missing > 0 || $modified > 0) { ?>
            <span class="icon dashicons dashicons-yes alert"></span>
        <?php } else if ($tiny_metadata->get_success_count() > 0 && count($uncompressed) > 0) { ?>
            <span class="icon dashicons dashicons-yes alert"></span>
        <?php } else if ($tiny_metadata->get_success_count() > 0) { ?>
            <span class="icon dashicons dashicons-yes success"></span>
        <?php } ?>
        <span class="icon spinner hidden"></span>

        <?php if ($tiny_metadata->get_compressed_count() > 0 || ($tiny_metadata->get_compressed_count() == 0 && count($uncompressed) == 0)) { ?>
            <span class="message">
                <strong><?php echo $tiny_metadata->get_compressed_count() ?></strong>
                <span><?php printf(self::ntranslate_escape('size compressed', 'sizes compressed', $tiny_metadata->get_compressed_count())) ?></span>
            </span>
            <br/>
        <?php } ?>

        <?php if ($not_compressed_active > 0) { ?>
            <span class="message">
                <?php printf(self::ntranslate_escape('%d size not compressed', '%d sizes not compressed', $not_compressed_active), $not_compressed_active) ?>
            </span>
            <br />
        <?php } ?>

        <?php if ($missing > 0) { ?>
            <span class="message">
                <?php printf(self::ntranslate_escape('%d file removed', '%d files removed', $missing), $missing) ?>
            </span>
            <br />
        <?php } ?>

        <?php if ($modified > 0) { ?>
            <span class="message">
                <?php printf(self::ntranslate_escape('%d file modified after compression', '%d files modified after compression', $modified), $modified) ?>
            </span>
            <br />
        <?php } ?>

        <?php if ($savings["input"] - $savings["output"]) { ?>
            <span class="message">
                <?php printf(self::translate_escape('Total savings %s' ), str_replace( " ", "&nbsp;", size_format($savings["input"] - $savings["output"], 1))) ?>
            </span>
            <br />
        <?php } ?>

        <?php if ($error) { ?>
            <span class="message error_message">
                <?php echo self::translate_escape('Latest error') . ': '. self::translate_escape($error) ?>
            </span>
            <br/>
        <?php } ?>

        <?php if ($tiny_metadata->get_compressed_count() > 0) { ?>
            <a class="thickbox message" href="#TB_inline?width=700&amp;height=500&amp;inlineId=modal_<?php echo $tiny_metadata->get_id() ?>">Details</a>
        <?php } ?>
    </div>

    <?php if (count($uncompressed) > 0) { ?>
        <button type="button" class="tiny-compress button button-small button-primary" data-id="<?php echo $tiny_metadata->get_id() ?>">
            <?php echo self::translate_escape('Compress') ?>
        </button>
    <?php } ?>
</div>
<?php if ($tiny_metadata->get_compressed_count() > 0) { ?>
    <div class="modal" id="modal_<?php echo $tiny_metadata->get_id() ?>">
        <div class="tiny-compression-details">
            <h3><?php printf(self::translate_escape('Compression details for %s'), $tiny_metadata->get_name()) ?></h3>
            <table>
                <tr>
                    <th><?php echo self::translate_escape('Size') ?></th>
                    <th><?php echo self::translate_escape('Original') ?></th>
                    <th><?php echo self::translate_escape('Compressed') ?></th>
                    <th><?php echo self::translate_escape('Date') ?></th>
                </tr>
                <?php $i = 0; ?>
                <?php foreach ($tiny_metadata->get_compressed_sizes() as $size) { ?>
                    <?php $meta = $tiny_metadata->get_value($size); ?>
                    <tr class="<?php echo ($i % 2 == 0) ? 'even' : 'odd' ?>">
                        <td>
                            <?php
                            echo ($size == "0" ? self::translate_escape('original') : $size ) . ' ';
                            if ($tiny_metadata->still_exists($size)) {
                                if ($tiny_metadata->is_compressed($size)) {
                                    if ($tiny_metadata->is_resized($size)) {
                                        printf('<em>' . self::translate_escape('(resized to %dx%d)') . '</em>', $meta['output']['width'], $meta['output']['height']);
                                    }
                                } else {
                                    echo '<em>' . self::translate_escape('(modified after compression)') . '</em>';
                                }
                            } else {
                                echo '<em>' . self::translate_escape('(file removed)') . '</em>';
                            }
                            ?>
                        </td>
                        <td><?php echo size_format($meta["input"]["size"], 1) ?></td>
                        <td><?php echo size_format($meta["output"]["size"], 1) ?></td>
                        <td><?php echo human_time_diff($tiny_metadata->get_end_time($size)) . ' ' . self::translate_escape('ago') ?></td>
                    </tr>
                    <?php $i++; ?>
                <?php } ?>
                <?php if ($savings['count'] > 0) { ?>
                <tfoot>
                    <tr>
                        <td><?php echo self::translate_escape('Combined') ?></td>
                        <td><?php echo size_format($savings['input'], 1) ?></td>
                        <td><?php echo size_format($savings['output'], 1) ?></td>
                        <td></td>
                    </tr>
                </tfoot>
                <?php } ?>
            </table>
            <p><strong><?php printf( self::translate_escape( 'Total savings %s' ), size_format($savings["input"] - $savings["output"], 1)) ?></strong></p>
        </div>
    </div>
<?php } ?>