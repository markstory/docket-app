<?php
declare(strict_types=1);
?>
<modal-window closeable="<?= h($closeable ?? 'true') ?>" open="<?=h($open) ?>">
    <?= $this->fetch('content') ?>
</modal-window>
