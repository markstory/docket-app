<?php
declare(strict_types=1);

use Cake\I18n\FrozenDate;

/**
 * @var \App\Model\Entity\Task $task
 * @var \App\Model\Entity\User $identity
 * @var string $referer
 * @var \Closure $itemFormatter
 * @var bool $renderForms
 */
$taskEditUrl = ['_name' => 'tasks:edit', 'id' => $task->id];
$renderForms ??= true;

$taskDue = $task->due_on;
$today = $this->Date->today();
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

$menuItem = $itemFormatter ?? function (
    string $title,
    string $icon,
    string $id,
    array $data
) use (
    $referer,
    $taskEditUrl
) {
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
<div role="menuitem">
<?php if ($renderForms) : ?>
    <?= $this->Form->create($task, [
        'hx-post' => $this->Url->build($taskEditUrl),
        'url' => $taskEditUrl,
        'hx-target' => 'main.main',
    ]) ?>
    <?= $this->Form->hidden('redirect', ['value' => $referer]) ?>
<?php endif; ?>
    <?= $this->Form->input('due_on_string', [
        'class' => 'form-input-like',
        'placeholder' => 'Type a due date',
        'value' => $taskDue ? $taskDue->format('Y-m-d') : '',
    ]) ?>
<?php if ($renderForms) : ?>
    <?= $this->Form->end() ?>
<?php endif; ?>
</div>
<?php if (!$isToday) : ?>
<div role="menuitem" class="today">
    <?php $menuItem('Today', 'clippy', 'today', ['due_on' => $today->format('Y-m-d'), 'evening' => '0']) ?>
</div>
<?php endif; ?>
<?php if (!$isThisEvening) : ?>
<div role="menuitem" class="evening">
    <?php $menuItem('This evening', 'moon', 'evening', ['due_on' => $today->format('Y-m-d'), 'evening' => '1']) ?>
</div>
<?php endif; ?>
<?php if (!$isTomorrow) : ?>
<div role="menuitem" class="tomorrow">
    <?php $menuItem('Tomorrow', 'sun', 'tomorrow', ['due_on' => $tomorrow->format('Y-m-d')]) ?>
</div>
<?php endif; ?>
<?php if ($isWeekend || $isFriday) : ?>
<div role="menuitem" class="tomorrow">
    <?php $menuItem('Monday', 'calendar', 'monday', ['due_on' => $monday->format('Y-m-d')]) ?>
</div>
<?php endif; ?>
<?php if ($futureDate && $isEvening && $taskDue) : ?>
<div role="menuitem" class="tomorrow">
    <?php $menuItem(
        $this->Date->formatCompact($taskDue, false) . ' day',
        'sun',
        'to-day',
        ['due_on' => $taskDue->format('Y-m-d'), 'evening' => '0']
    ) ?>
</div>
<?php endif; ?>
<?php if ($futureDate && !$isEvening && $taskDue) : ?>
<div role="menuitem" class="evening">
    <?php $menuItem(
        $this->Date->formatCompact($taskDue, true) . ' evening',
        'moon',
        'to-evening',
        ['due_on' => $taskDue->format('Y-m-d'), 'evening' => '1']
    ) ?>
</div>
<?php endif; ?>
<div role="menuitem" class="not-due">
    <?php $menuItem('Later', 'clock', 'later', ['due_on' => '']) ?>
</div>

<?php
$begin = $today;

if (!$begin->isSunday()) {
    $begin = $begin->modify('previous sunday');
}
$next = $begin;

// Guess at how much time folks need. Could be a setting later?
$end = FrozenDate::parse($today->format('Y-m-t'))->modify('+30 days');

/**
 * The list of cells to render
 */
$grouped = [];
$curVal = $begin;
while ($curVal <= $end) {
    $selected = $curVal == $taskDue;
    $available = $curVal >= $today;
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
if ($renderForms) :
    echo $this->Form->create($task, [
        'url' => $taskEditUrl,
        'hx-post' => $this->Url->build($taskEditUrl),
        'hx-target' => 'main.main',
        'class' => 'day-picker',
    ]);
    echo $this->Form->hidden('redirect', ['value' => $referer]);
endif;
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
                        'class' => 'day-picker-day',
                        'aria-label' => $cell['date']->format('d M D Y'),
                    ];
                    ?>
                    <td <?= $this->Html->templater()->formatAttributes($attributes) ?>>
                        <?= $this->Form->button(
                            $cell['date']->format('j'),
                            [
                                'name' => 'due_on',
                                'value' => $cell['date']->format('Y-m-d'),
                                'disabled' => !$cell['available'],
                                'aria-selected' => $cell['selected'] ? 'true' : 'false',
                            ]
                        ) ?>
                    </td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endforeach; ?>
<?php if ($renderForms) : ?>
    <?= $this->Form->end() ?>
<?php endif; ?>
</div>
