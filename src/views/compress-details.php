<div class="details-container">
    <?php if (count($uncompressed) > 0) { ?>
        <button type="button" class="tiny-compress button button-primary" data-id="<?= $tiny_metadata->get_id() ?>">
            <?= self::translate_escape('Compress') ?>
        </button>
        <div class="spinner hidden"></div>
    <?php } ?>
    <span class="details">
        <?php if ($error) { ?>
            <span class="icon error"></span>
        <?php } else if ($missing > 0 || $modified > 0) { ?>
            <span class="icon alert"></span>
        <?php } else { ?>
            <span class="icon success"></span>
        <?php } ?>

        <span class="message">
            <strong><?= $tiny_metadata->get_success_count() ?></strong>
            <span><?php printf(self::translate_escape('%s compressed'), ($tiny_metadata->get_success_count() == 1) ? "size" : "sizes") ?></span>
        </span>
        <br/>

        <?php if (count($uncompressed) > 0 && $modified == 0) { ?>
            <span class="message">
                <?php printf(self::translate_escape('%d %s not compressed'), count($uncompressed), (count($uncompressed) == 1) ? "size" : "sizes") ?>
            </span>
            <br />
        <?php } ?>

        <?php if ($missing > 0) { ?>
            <span class="message">
                <?php printf(self::translate_escape('%d %s missing'), $missing, ($missing == 1) ? "file" : "files") ?>
            </span>
            <br />
        <?php } ?>

        <?php if ($modified > 0) { ?>
            <span class="message">
                <?php printf(self::translate_escape('%d %s modified after compression'), $modified, ($modified == 1) ? "file" : "files") ?>
            </span>
            <br />
        <?php } ?>

        <?php if ($error) { ?>
            <span class="message error_message">
                <?= self::translate_escape('Latest error') . ': '. self::translate_escape($error) ?>
            </span>
            <br/>
        <?php } ?>

        <?php if ($tiny_metadata->get_success_count() > 0) { ?>
            <a class="thickbox message" href="#TB_inline?width=700&amp;height=500&amp;inlineId=modal_<?= $tiny_metadata->get_id() ?>">Details</a>
        <?php } ?>
    </span>
</div>
<?php if ($tiny_metadata->get_success_count() > 0) { ?>
    <div class="modal" id="modal_<?= $tiny_metadata->get_id() ?>">
        <div class="tiny-compression-details">
            <h3><?php printf(self::translate_escape('Compression details for %s'), $tiny_metadata->get_name()) ?></h3>
            <table>
                <tr>
                    <th><?= self::translate_escape('Size') ?></th>
                    <th><?= self::translate_escape('Original size') ?></th>
                    <th><?= self::translate_escape('Compressed size') ?></th>
                    <th><?= self::translate_escape('Date') ?></th>
                </tr>
                <?php $i = 0; ?>
                <?php foreach ($tiny_metadata->get_compressed_sizes() as $size) { ?>
                    <tr class="<?= ($i % 2 == 0) ? 'even' : 'odd' ?>">
                        <td>
                            <?php
                            if ($size == "0") {
                                echo 'original';
                            } else {
                                echo $size;
                            }
                            if ($tiny_metadata->still_exists($size)) {
                                if ($tiny_metadata->is_compressed($size)) {
                                    if ($tiny_metadata->is_resized($size)) {
                                        $original = $tiny_metadata->get_value($size);
                                        ?><em>&nbsp;<?php printf(self::translate_escape('(resized to %dx%d)'), $original['output']['width'], $original['output']['height']) ?></em><?php
                                    }
                                } else {
                                    ?><em>&nbsp;<?= self::translate_escape('(modified after compression)') ?></em><?php
                                }
                            } else {
                                ?><em>&nbsp;<?= self::translate_escape('(missing)') ?></em><?php
                            }
                            ?>
                        </td>
                        <td><?= size_format($tiny_metadata->get_value($size)["input"]["size"], 1) ?></td>
                        <td><?= size_format($tiny_metadata->get_value($size)["output"]["size"], 1) ?></td>
                        <td><?= human_time_diff($tiny_metadata->get_value($size)["end"]) . ' ' . self::translate_escape('ago') ?></td>
                    </tr>
                    <?php $i++; ?>
                <?php } ?>
                <?php if ($savings['count'] > 0) { ?>
                <tfoot>
                    <tr>
                        <td><?= self::translate_escape('Combined') ?></td>
                        <td><?= size_format($savings['input'], 1) ?></td>
                        <td><?= size_format($savings['output'], 1) ?></td>
                        <td></td>
                    </tr>
                </tfoot>
                <?php } ?>
            </table>

            <p class="tiny-important"><?= self::translate_escape('Total savings') ?>:&nbsp;<?= size_format($tiny_metadata->get_savings()["input"] - $tiny_metadata->get_savings()["output"], 1) ?></p>
        </div>
    </div>
<?php } ?>