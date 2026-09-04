<?php
declare(strict_types=1);
/**
 * @var string|null $name
 * @var boolean|null $checked
 * @var boolean|null $disabled
 * @var array|null $attrs
 */
$attrs ??= [];

$id = 'checkbox-' . uniqid();
$name = $name ?? 'completed';

$attrs['id'] = $id;
$attrs['checked'] = $checked ?? false;
$attrs['disabled'] = $disabled ?? false;

$boxClass = 'box';
if ($attrs['disabled']) {
    $boxClass .= ' disabled';
}
?>
<label for="<?= h($id) ?>" class="checkbox">
    <?= $this->Form->checkbox($name, $attrs) ?>
    <span class="<?= h($boxClass) ?>"></span>
    <span class="check">
        <?= $this->element('icons/check16') ?>
    </span>
</label>
