<?php
declare(strict_types=1);

$closedby = $this->get('sheet.closedby') ?? null;
if ($closedby) {
    $closedby = sprintf('closedby="%s"', h($closedby));
}
?>
<modal-window open="1">
    <dialog id="modal-window-dialog" class="modal-sheet <?= $this->get('sheet.class') ?>"<?= $closedby ?>>
        <?= $this->fetch('content') ?>
    </dialog>
</modal-window>
