<div id='tiny-update-account' style="display: none">
    <div id='tiny-update-account-container' class='wp-core-ui' style="border: none">
        <div class='tiny-update-account-step2'>
            <p>
            <?php $link = '<a href="https://tinypng.com/developers" target="_blank">' . esc_html__('TinyPNG Developer section', 'tiny-compress-images') . '</a>'; ?>
            <?php printf(esc_html__('Enter your API key. If you have lost your key then head over to the %s to retrieve it.', 'tiny-compress-images'), $link) ?>
            </p>
            <input class='tiny-update-account-input' type='text' id='tinypng_api_key' name='tinypng_api_key' size='35' spellcheck="false" value="<?php echo htmlspecialchars($this->get_api_key()) ?>" />
            <button type='button' class='tiny-account-save-key button button-primary'>
                <?php echo esc_html__('Update', 'tiny-compress-images') ?>
            </button>
            <!-- <p class='tiny-update-account-message invalid-key' style="display: none">
                <span class="dashicons-before dashicons-info"></span>
                <?php printf(esc_html__('The key that you have entered does not exist. Please try a different key.', 'tiny-compress-images')) ?>
            </p>
            <p class='tiny-update-account-message save-error' style="display: none">
                <span class="dashicons-before dashicons-warning"></span>
                <?php printf(esc_html__('Something went wrong. Please try again later.', 'tiny-compress-images')) ?>
            </p> -->
        </div>
    </div>
</div>
