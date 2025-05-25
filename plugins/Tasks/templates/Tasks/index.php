<?php
declare(strict_types=1);

use Cake\I18n\FrozenDate;

/**
 * @var \App\Model\Entity\Task[] $tasks
 * @var \App\Model\Entity\CalendarItem[] $calendarItems
 * @var \App\Model\Entity\User $identity
 * @var string $start
 * @var string $nextStart
 */
$this->setLayout('sidebar');
$this->assign('title', 'Upcoming Tasks');

$this->set('showGlobalAdd', true);

$start = FrozenDate::parse($start);
$nextStart = FrozenDate::parse($nextStart);
$duration = $nextStart->diffInDays($start);

$groupedTasks = [];
$current = $start;
while ($current < $nextStart) :
    $groupedTasks[$current->format('Y-m-d')] = [];
    $groupedTasks[$current->format('Y-m-d') . ':evening'] = [];
    $current = $current->addDays(1);
endwhile;

foreach ($tasks as $task) :
    $key = '';
    if ($task->due_on) :
        $key = $task->due_on->format('Y-m-d');
    endif;
    if ($task->evening) :
        $key = "{$key}:evening";
    endif;
    $groupedTasks[$key][] = $task;
endforeach;

$groupedCalendarItems = [];
foreach ($calendarItems as $item) :
    $key = $item->getKey($identity->timezone);
    $groupedCalendarItems[$key][] = $item;
endforeach;

?>
<h1>Upcoming</h1>

<reload-after timestamp="<?= strtotime('+30 minutes') ?>"></reload-after>

<keyboard-list
    itemselector=".task-row"
    toggleselector="input[type='checkbox']"
    openselector="a"
>
<?php foreach ($groupedTasks as $key => $taskGroup) : ?>
    <?php
    $dateStr = $key;
    $isEvening = false;
    if (str_contains($dateStr, ':evening')) {
        $dateStr = substr($dateStr, 0, strpos($dateStr, ':'));
        $isEvening = true;
    }
    $taskAddUrl = $this->Url->build([
        '_name' => 'tasks:add',
        '?' => ['due_on' => $dateStr],
    ]);
    $currentDate = FrozenDate::parse($dateStr);
    [$heading, $subheading] = $this->Date->formatDateHeading($currentDate);

    ?>
    <?php // Task day section ?>
    <?php if ($isEvening) : ?>
        <h5 class="heading-evening-group icon-evening">
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
                echo $this->element('Tasks.task_item', ['task' => $task]);
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
        <?php
        if (!empty($groupedCalendarItems[$key])) :
            echo $this->element('Calendar.calendaritems', [
                'calendarItems' => $groupedCalendarItems[$key],
                'identity' => $identity,
            ]);
        endif;
        ?>
        <div
            class="task-group dnd-dropper-left-offset"
            hx-ext="task-sorter"
            task-sorter-attr="day_order"
            task-sorter-dueon="<?= $dateStr ?>"
            task-sorter-evening="0"
        >
            <?php
            foreach ($taskGroup as $task) :
                echo $this->element('Tasks.task_item', ['task' => $task]);
            endforeach;
            ?>
        </div>
    <?php endif ?>
<?php endforeach; ?>
</keyboard-list>

<?php // TODO build pagination ?>
