<?php
?><div class="details-container">
    <div class="details">
        <span class="icon spinner"></span>
        <span class="message">
            <strong><?php echo count($in_progress) ?></strong>
            <span><?php echo _n('size being compressed', 'sizes being compressed', count($in_progress), 'tiny-compress-images') ?></span>
        </span>
    </div>
</div>
