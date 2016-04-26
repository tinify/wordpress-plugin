<form method='post' action='?page=tiny-bulk-optimization'>
    <input type='hidden' name='_wpnonce' value=''<?php echo wp_create_nonce('tiny-bulk-optimization') ?>''>
    <input type='hidden' name='start-optimization' value='1'>
    <button class='button button-large' type='submit'>
        <?php
        if(sizeof($ids_to_compress) > 0) { ?>
            <span class='start-optimizing'>
        <?php } else { ?>
            <span class='start-optimizing active'>
        <?php } ?>
            <?php echo esc_html_e('Start Bulk Optimization', 'tiny-compress-images') ?>
        </span>

        <?php
        if(sizeof($ids_to_compress) <= 0) { ?>
            <span class='optimizing'>
        <?php } else { ?>
            <span class='optimizing active'>
        <?php } ?>
            <?php echo esc_html_e('Optimizing', 'tiny-compress-images') ?>...
        </span>
        <span class='cancel'><?php echo esc_html_e('Cancel', 'tiny-compress-images') ?></span>
        <span class='cancelling'><?php echo esc_html_e('Cancelling', 'tiny-compress-images') ?>...</span>
    </button>
    <div class="spinner"></div>
</form>
