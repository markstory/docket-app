<?php
declare(strict_types=1);

$id = 'checkbox-' . uniqid();
$name = $name ?? 'completed';
$checked = $checked ?? false;
?>
<label for="<?= h($id) ?>" class="checkbox">
    <?= $this->Form->checkbox($name, ['id' => $id, 'checked' => $checked]) ?>
    <span class="box"></span>
    <span class="check">
        <?= $this->element('icons/check16') ?>
    </span>
</label>
