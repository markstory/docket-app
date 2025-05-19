<?php
declare(strict_types=1);
/**
 * @var string $name
 * @var boolean $checked
 * @var array $attrs
 */
$attrs ??= [];

$id = 'checkbox-' . uniqid();
$name = $name ?? 'completed';
$checked = $checked ?? false;

$attrs['id'] = $id;
$attrs['checked'] = $checked;
?>
<label for="<?= h($id) ?>" class="checkbox">
    <?= $this->Form->checkbox($name, $attrs) ?>
    <span class="box"></span>
    <span class="check">
        <?= $this->element('icons/check16') ?>
    </span>
</label>
