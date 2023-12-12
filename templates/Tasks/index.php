<?php
declare(strict_types=1);

use Cake\I18n\FrozenDate;

/**
 * @var \App\Model\Entity\Task[] $tasks
 * @var \App\Model\Entity\CalendarItem[] $calendarItems
 * @var string $start
 * @var string $nextStart
 */
$this->setLayout('sidebar');
$this->assign('title', "Upcoming Tasks");

$this->set('showGlobalAdd', true);

$start = FrozenDate::parse($start);
$nextStart = FrozenDate::parse($nextStart);
$duration = $nextStart->diffInDays($start);

$groupedTasks = [];
$current = $start;
while ($current < $nextStart) {
    $groupedTasks[$current->format('Y-m-d')] = [];
    $current = $current->addDays(1);
}

foreach ($tasks as $task) {
    $key = '';
    if ($task->due_on) {
        $key = $task->due_on->format('Y-m-d');
    }
    if ($task->evening) {
        $key = "evening:{$key}";
    }
    $groupedTasks[$key][] = $task;
}

// TODO group calendarItems
?>
<h1>Upcoming</h1>

<?php foreach ($groupedTasks as $key => $taskGroup) : ?>
    <?php
    $dateStr = $key;
    $isEvening = false;
    if (str_starts_with($dateStr, 'evening:')) {
        $dateStr = substr($dateStr, 8);
        $isEvening = true;
    }
    $taskAddUrl = $this->Url->build([
        '_name' => 'tasks:add',
        '?' => ['due_on' => $dateStr],
    ]);
    $currentDate = FrozenDate::parse($dateStr);
    [$heading, $subheading] = $this->Date->formatDateHeading($currentDate);

    ?>
    <?php // TODO display calendar items ?>

    <?php // Task day section ?>
    <?php if ($isEvening) : ?>
        <h5 class="heading-evening-group">
            <?= $this->element('icons/moon16') ?>
            Evening
        </h5>
        <div
            class="task-group dnd-dropper-left-offset"
            hx-ext="task-sorter"
            task-sorter-attr="day_order"
            task-sorter-dueon="<?= $dateStr ?>"
            task-sorter-evening="1"
        >
            <?php
            foreach ($taskGroup as $task) :
                echo $this->element('task_item', ['task' => $task]);
            endforeach;
            ?>
        </div>
    <?php else : ?>
        <h3 class="heading-task-group">
            <?= h($heading) ?>
            <?php if ($subheading && $subheading != $heading) : ?>
                <span class="minor"><?= h($subheading) ?></span>
            <?php endif ?>
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
        </h3>
        <div
            class="task-group dnd-dropper-left-offset"
            hx-ext="task-sorter"
            task-sorter-attr="day_order"
            task-sorter-dueon="<?= $dateStr ?>"
            task-sorter-evening="0"
        >
            <?php
            foreach ($taskGroup as $task) :
                echo $this->element('task_item', ['task' => $task]);
            endforeach;
            ?>
        </div>
    <?php endif ?>
<?php endforeach; ?>

<?php // TODO build pagination ?>
