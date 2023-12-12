<?php
declare(strict_types=1);
/**
 * @var \App\Model\Entity\Task[] $tasks
 * @var \App\Model\Entity\CalendarItem[] $calendarItems
 * @var \Cake\I18n\FrozenDate $date
 */
$this->setLayout('sidebar');
$this->assign('title', "Today's Tasks");

$dateStr = $date->format('Y-m-d');

$this->set('showGlobalAdd', true);
$this->set('globalAddContext', ['due_on' => $dateStr]);

$taskAddUrl = $this->Url->build([
    '_name' => 'tasks:add',
    '?' => ['due_on' => $dateStr],
]);
$taskAddEveningUrl = $this->Url->build([
    '_name' => 'tasks:add',
    '?' => ['due_on' => $dateStr, 'evening' => true],
]);

$groupedTasks = [];
foreach ($tasks as $task) {
    $key = '';
    if ($task->due_on) {
        $key = $task->due_on->format('Y-m-d');
    }
    if ($task->due_on && $task->due_on->equals($date)) {
        $key = 'today';
    }
    if ($task->evening) {
        $key = 'evening';
    }
    if ($task->due_on && $task->due_on->lessThan($date)) {
        $key = 'overdue';
    }
    $groupedTasks[$key][] = $task;
}

// Overdue section
if (!empty($groupedTasks['overdue'])) : ?>
    <h2 class="heading-icon overdue">
        <?= $this->element('icons/alert16') ?>
        Overdue
    </h2>
    <div
        class="task-group dnd-dropper-left-offset"
        hx-ext="task-sorter"
        task-sorter-put="false"
        task-sorter-attr="day_order"
    >
    <?php
    foreach ($groupedTasks['overdue'] ?? [] as $task) :
        echo $this->element('task_item', ['task' => $task, 'showDueOn' => true]);
    endforeach;
    ?>
    </div>
<?php endif; ?>

<?php // TODO display calendar items ?>

<?php // Today section ?>
<h2 class="heading-icon today">
    <?= $this->element('icons/calendar16') ?>
    Today
    <?= $this->Html->link(
        $this->element('icons/plus16'),
        $taskAddUrl,
        [
            'escape' => false,
            'class' => 'button-icon-primary',
            'data-testid' => 'add-task',
            'hx-get' => $taskAddUrl,
            'hx-target' => 'main.main',
            'hx-swap' => 'beforeend',
        ]
    ) ?>
</h2>
<div
    class="task-group dnd-dropper-left-offset"
    hx-ext="task-sorter"
    task-sorter-attr="day_order"
    task-sorter-dueon="<?= $dateStr ?>"
    task-sorter-evening="0"
>
<?php
foreach ($groupedTasks['today'] ?? [] as $task) :
    echo $this->element('task_item', ['task' => $task]);
endforeach;
?>
</div>

<?php // Evening section ?>
<h2 class="heading-icon today">
    <?= $this->element('icons/moon16') ?>
    This Evening
    <?= $this->Html->link(
        $this->element('icons/plus16'),
        $taskAddUrl,
        [
            'escape' => false,
            'class' => 'button-icon-primary',
            'data-testid' => 'add-task',
            'hx-get' => $taskAddEveningUrl,
            'hx-target' => 'main.main',
            'hx-swap' => 'beforeend',
        ]
    ) ?>
</h2>
<div
    class="task-group dnd-dropper-left-offset"
    hx-ext="task-sorter"
    task-sorter-attr="day_order"
    task-sorter-evening="1"
    task-sorter-dueon="<?= $dateStr ?>"
>
<?php
foreach ($groupedTasks['evening'] ?? [] as $task) :
    echo $this->element('task_item', ['task' => $task]);
endforeach;
?>
</div>
