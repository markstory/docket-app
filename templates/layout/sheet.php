<?php
declare(strict_types=1);
?>
<modal-window open="1">
    <dialog id="modal-window-dialog" class="modal-sheet <?= $this->get('sheet.class') ?>">
    <?= $this->fetch('content') ?>
    </dialog>
</modal-window>
