<?php
declare(strict_types=1);
?>
<modal-window closeable="<?= h($closeable ?? 'true') ?>" open="<?=h($open) ?>">
    <div class="modal-overlay"></div>
    <?= $this->fetch('content') ?>
</modal-window>
