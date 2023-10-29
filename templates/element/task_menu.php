<?php
declare(strict_types=1);
/**
 * @var \App\Model\Entity\Task $task
 */
$menuId = 'task-menu-' . uniqid();
$deleteConfirmUrl = ['_name' => 'tasks:deleteconfirm', 'id' => $task->id];
$taskEditProjectUrl = ['_name' => 'tasks:viewmode', 'id' => $task->id, 'mode' => 'editproject'];
$taskRescheduleUrl = ['_name' => 'tasks:viewmode', 'id' => $task->id, 'mode' => 'reschedule'];
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
            $taskEditProjectUrl,
            [
                'class' => 'edit',
                'escape' => false,
                'data-testid' => 'move',
                'role' => 'menuitem',
                // Switch the menu to the edit project state.
                'hx-get' => $this->Url->build($taskEditProjectUrl),
                'hx-target' => 'closest drop-down-menu',
            ]
        ) ?>
        <?= $this->Html->link(
            $this->element('icons/calendar16') . ' Reschedule',
            $taskRescheduleUrl,
            [
                'class' => 'calendar',
                'escape' => false,
                'data-testid' => 'reschedule',
                'role' => 'menuitem',
                // Switch menu to the date picker state
                'hx-get' => $this->Url->build($taskRescheduleUrl),
                'hx-target' => 'closest drop-down-menu',
            ]
        ) ?>
        <?= $this->Html->link(
            $this->element('icons/trash16') . ' Delete Task',
            $deleteConfirmUrl,
            [
                'class' => 'delete',
                'escape' => false,
                'role' => 'menuitem',
                'data-testid' => 'delete',
                'dropdown-close' => true,
                'hx-get' => $this->Url->build($deleteConfirmUrl),
                'hx-target' => 'body',
                'hx-swap' => 'beforeend',
            ]
        ) ?>
    </drop-down-menu>
</drop-down>
