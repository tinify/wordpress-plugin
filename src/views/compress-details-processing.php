<div class="details-container">
    <div class="details">
        <span class="icon spinner"></span>
        <span class="message">
            <strong><?= $compressing ?></strong>
            <span><?php printf(self::translate_escape('%s being compressed'), $compressing == 1 ? 'size' : 'sizes'); ?></span>
        </span>
    </div>
</div>
