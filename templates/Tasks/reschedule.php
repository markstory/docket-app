<?php
declare(strict_types=1);

use Cake\I18n\FrozenDate;

/**
 * @var \App\Model\Entity\Task $task
 * @var string $referer
 */
$this->setLayout('ajax');

$this->response = $this->response->withHeader('Hx-Trigger-After-Swap', 'reposition');

$taskEditUrl = ['_name' => 'tasks:edit', 'id' => $task->id];

$taskDue = $task->due_on;
$today = FrozenDate::today();
$tomorrow = $today->modify('+1 days');

$isEvening = $task->evening;
$isToday = $task->due_on == $today && !$isEvening;
$isThisEvening = $task->due_on == $today && $isEvening;
$isTomorrow = $task->due_on == $tomorrow;

// On the weekend we often move to monday
$isWeekend = $today->isSaturday() || $today->isSunday();
// Same with on friday
$isFriday = $today->isFriday();
$futureDate = $task->due_on !== null && $task->due_on != $today;

$monday = $today->modify('next monday');

$menuItem = function (string $title, string $icon, string $id, array $data) use ($referer, $taskEditUrl) {
    $data['redirect'] = $referer;
    echo $this->Form->create(null, [
        'url' => $taskEditUrl,
        'hx-post' => $this->Url->build($taskEditUrl),
        'hx-target' => 'main.main',
    ]);
    foreach ($data as $field => $value) {
        echo $this->Form->hidden($field, ['value' => $value]);
    }
    $title = $this->element("icons/{$icon}16") . ' ' . $title;
    echo $this->Form->button($title, [
        'escapeTitle' => false,
        'data-testid' => $id,
        'class' => 'menu-item-button',
    ]);
    echo $this->Form->end();
};

?>
<?php if (!$isToday) : ?>
<div role="menuitem" class="today">
    <?php $menuItem('Today', 'clippy', 'today', ['due_on' => $today->format('Y-m-d'), 'evening' => '0']) ?>
<?php endif; ?>
</div>
<?php if (!$isThisEvening) : ?>
<div role="menuitem" class="evening">
    <?php $menuItem('This Evening', 'moon', 'evening', ['due_on' => $today->format('Y-m-d'), 'evening' => '1']) ?>
<?php endif; ?>
</div>
<?php if (!$isTomorrow) : ?>
<div role="menuitem" class="tomorrow">
    <?php $menuItem('Tomorrow', 'sun', 'tomorrow', ['due_on' => $tomorrow->format('Y-m-d')]) ?>
<?php endif; ?>
</div>
<?php if ($isWeekend || $isFriday) : ?>
<div role="menuitem" class="tomorrow">
    <?php $menuItem('Monday', 'calendar', 'monday', ['due_on' => $monday->format('Y-m-d')]) ?>
<?php endif; ?>
</div>
<div role="menuitem" class="not-due">
    <?php $menuItem('Later', 'clock', 'later', ['due_on' => '']) ?>
</div>

<?php
$current = $today;
$begin = $current;

if (!$begin->isSunday()) {
    $begin = $begin->modify('previous sunday');
}
$next = $begin;

// Guess at how much time folks need. Could be a setting later?
$end = FrozenDate::parse($current->format('Y-m-t'))->modify('+30 days');

/**
 * The list of cells to render
 */
$grouped = [];
$curVal = $begin;
while ($curVal <= $end) {
    $selected = $curVal == $current;
    $available = $curVal >= $current;
    $month = $curVal->format('F Y');
    // Get iso day/week number.
    $weekNum = (int)$curVal->format('W');
    $dayNum = (int)$curVal->format('N');
    // Iso ordering is 1=monday 7=sunday. But we want Sun -> Sat
    if ($dayNum === 7) {
        $dayNum = 0;
        $weekNum += 1;
    }

    $cell = ['available' => $available, 'selected' => $selected, 'date' => $curVal];
    if (!isset($grouped[$month])) {
        $grouped[$month] = [];
    }
    if (!isset($grouped[$month][$weekNum])) {
        $grouped[$month][$weekNum] = [null, null, null, null, null, null, null];
    }
    $grouped[$month][$weekNum][$dayNum] = $cell;

    $curVal = $curVal->addDays(1);
}
?>
<div class="day-picker-menuitem">
<?php
echo $this->Form->create($task, [
    'url' => $taskEditUrl,
    'hx-post' => $this->Url->build($taskEditUrl),
    'hx-target' => 'main.main',
    'class' => 'day-picker'
]);
echo $this->Form->hidden('redirect', ['value' => $referer]);
?>
<?php foreach ($grouped as $month => $weeks) : ?>
    <table class="day-picker-month" cellspacing="0" cellpadding="0">
        <caption class="day-picker-caption"><?= h($month) ?></caption>
        <thead class="day-picker-weekdays">
            <tr>
                <th><abbr class="day-picker-weekday" role="columnheader" title="Sunday">Su</abbr></th>
                <th><abbr class="day-picker-weekday" role="columnheader" title="Monday">Mo</abbr></th>
                <th><abbr class="day-picker-weekday" role="columnheader" title="Tuesday">Tu</abbr></th>
                <th><abbr class="day-picker-weekday" role="columnheader" title="Wednesday">We</abbr></th>
                <th><abbr class="day-picker-weekday" role="columnheader" title="Thursday">Th</abbr></th>
                <th><abbr class="day-picker-weekday" role="columnheader" title="Friday">Fr</abbr></th>
                <th><abbr class="day-picker-weekday" role="columnheader" title="Saturday">Sa</abbr></th>
            </tr>
        </thead>
        <tbody class="day-picker-body">
        <?php foreach ($weeks as $weekNum => $week) : ?>
            <tr class="day-picker-week">
                <?php foreach ($week as $cell) :
                    if ($cell === null) :
                        echo '<td class="day-picker-day disabled" role="gridcell"> </td>';
                        continue;
                    endif;
                    $attributes = [
                        'aria-label' => $cell['date']->format('d M D Y'),
                    ];
                    $class = ['day-picker-day'];
                    $disabled = false;
                    if (!$cell['available']) :
                        $disabled = true;
                        $class[] = 'disabled';
                        $attributes['aria-disabled'] = true;
                    endif;
                    if ($cell['selected']) :
                        $class[] = 'selected';
                        $attributes['aria-selected'] = true;
                    endif;
                    $attributes['class'] = $class;
                    ?>
                    <td <?= $this->Html->templater()->formatAttributes($attributes) ?>>
                        <?= $this->Form->button(
                            $cell['date']->format('j'),
                            ['name' => 'due_on', 'value' => $cell['date']->format('Y-m-d'), 'disabled' => $disabled]
                        ) ?>
                    </td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endforeach; ?>
<?= $this->Form->end() ?>
</div>
