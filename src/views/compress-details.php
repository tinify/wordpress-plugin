<span>
    <?php if ($error) { ?>
        <span class="error"><?= self::translate_escape('Latest error') . ': '. self::translate_escape($error) ?></span>
        <br/>
    <?php } ?>
    <?php printf(self::translate_escape('%d %s compressed'), $tiny_metadata->get_success_count(), ($tiny_metadata->get_success_count() == 1) ? "size" : "sizes") ?>
    <?php if ($tiny_metadata->get_success_count() > 0) { ?>
        <a class="popup" href="#" data-id="<?= $tiny_metadata->get_id() ?>">details...</a>
    <?php } ?>
    <br/>
    <?php if (count($uncompressed) > 0) { ?>
        <?php printf(self::translate_escape('%d %s not compressed'), count($uncompressed), (count($uncompressed) == 1) ? "size" : "sizes") ?>
        <button type="button" class="tiny-compress" data-id="<?= $tiny_metadata->get_id() ?>">
            <?= self::translate_escape('Compress') ?>
        </button>
        <div class="spinner hidden"></div>
    <?php } ?>
</span>
<?php if ($tiny_metadata->get_success_count() > 0) { ?>
    <div class="popup" data-id="<?= $tiny_metadata->get_id() ?>">
        <table>
            <tr>
                <th>Size</th>
                <th>Input size</th>
                <th>Output size</th>
                <th>Date</th>
            </tr>
            <?php foreach ($tiny_metadata->get_compressed_sizes() as $size) { ?>
                <tr>
                    <td>
                        <?php
                        if ($size == "0") {
                            echo "original";
                        } else {
                            echo $size;
                        }
                        if ($tiny_metadata->still_exists($size)) {
                            if ($tiny_metadata->is_compressed($size)) {
                                if ($tiny_metadata->is_resized($size)) {
                                    $original = $tiny_metadata->get_value($size);
                                    printf(self::translate_escape(' (resized to %dx%d)'), $original['output']['width'], $original['output']['height']);
                                }
                            } else {
                                echo " (modified after compression)";
                            }
                        } else {
                            echo " (missing)";
                        }
                        ?>
                    </td>
                    <td><?= size_format($tiny_metadata->get_value($size)["input"]["size"], 1) ?></td>
                    <td><?= size_format($tiny_metadata->get_value($size)["output"]["size"], 1) ?></td>
                    <td><?= human_time_diff($tiny_metadata->get_value($size)["end"]) ?> ago</td>
                    <!-- <td><?= date_i18n(Tiny_Plugin::DATETIME_FORMAT, $tiny_metadata->get_value($size)["end"]) ?></td> -->
                </tr>
            <?php } ?>
            <?php if ($savings['count'] > 0) { ?>
            <tfoot>
                <tr>
                    <td>combined</td>
                    <td><?= size_format($savings['input'], 1) ?></td>
                    <td><?= size_format($savings['output'], 1) ?></td>
                    <td></td>
                    <td></td>
                </tr>
            </tfoot>
            <?php } ?>
        </table>
    </div>
<?php } ?>