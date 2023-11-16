<?php
declare(strict_types=1);

$id = 'checkbox-' . uniqid();
?>
<label for="<?= h($id) ?>" class="checkbox">
    <?= $this->Form->checkbox('completed', ['id' => $id]) ?>
    <span class="box"></span>
    <span class="check">
        <?= $this->element('icons/check16') ?>
    </span>
</label>
