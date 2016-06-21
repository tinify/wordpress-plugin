    <button class="button button-large tiny-bulk-optimization-actions">
        <?php
        if ($auto_start_bulk) { ?>
            <span class="start-optimizing">
        <?php } else { ?>
            <span class="start-optimizing active">
        <?php } ?>
            <?php echo esc_html_e("Start Bulk Optimization", "tiny-compress-images") ?>
        </span>

        <?php
        if ($auto_start_bulk) { ?>
            <span class="optimizing active">
        <?php } else { ?>
            <span class="optimizing">
        <?php } ?>
            <?php echo esc_html_e("Optimizing", "tiny-compress-images") ?>...
        </span>
        <span class="cancel"><?php echo esc_html_e("Cancel", "tiny-compress-images") ?></span>
        <span class="cancelling"><?php echo esc_html_e("Cancelling", "tiny-compress-images") ?>...</span>
    </button>
    <div class="spinner"></div>
