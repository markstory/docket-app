<?php
declare(strict_types=1);
/**
 * @var \App\Model\Entity\Task $task
 */
$menuId = 'task-menu-' . uniqid();
?>
<drop-down>
    <button
        class="button-icon button-default"
        aria-haspopup="true"
        aria-controls="<?= h($menuId) ?>"
        type="button"
    >
        <?= $this->element('icons/kebab16') ?>
    </button>
    <drop-down-menu id="<?= h($menuId) ?>" role="menu">
        <?= $this->Html->link(
            $this->element('icons/pencil16') . ' Move',
            ['_name' => 'tasks:move', 'id' => $task->id],
            ['class' => 'edit', 'escape' => false, 'data-testid' => 'move', 'role' => 'menuitem']
        ) ?>
        <?= $this->Html->link(
            $this->element('icons/calendar16') . ' Reschedule',
            // TODO need to build a view for this submenu.
            ['_name' => 'tasks:view', 'id' => $task->id],
            ['class' => 'calendar', 'escape' => false, 'data-testid' => 'reschedule', 'role' => 'menuitem']
        ) ?>
        <?= $this->Html->link(
            $this->element('icons/trash16') . ' Delete Task',
            // TODO need to build a view for the deletion confirm
            ['_name' => 'tasks:view', 'id' => $task->id],
            ['class' => 'delete', 'escape' => false, 'data-testid' => 'delete', 'role' => 'menuitem']
        ) ?>
    </drop-down-menu>
</drop-down>
