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
