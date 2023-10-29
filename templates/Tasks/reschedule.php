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

$isWeekend = $today->isSaturday() || $today->isSunday();
$isFriday = $today->isFriday();
$futureDate = $task->due_on !== null && $task->due_on != $today;

$monday = $today->modify('next monday');

$menuItem = function (string $title, array $data) use ($referer, $taskEditUrl) {
    $data['redirect'] = $referer;
    echo $this->Form->create(null, [
        'url' => $taskEditUrl,
        'hx-post' => $this->Url->build($taskEditUrl),
        'hx-target' => 'main.main',
    ]);
    foreach ($data as $field => $value) {
        echo $this->Form->hidden($field, ['value' => $value]);
    }
    echo $this->Form->button($title, ['escape' => false]);
};

?>
<?php if (!$isToday) : ?>
<div role="menuitem">
    <?php $menuItem('Today', ['due_on' => $today->format('Y-m-d'), 'evening' => '0']) ?>
<?php endif; ?>
<?php if (!$isThisEvening) : ?>
<div role="menuitem">
    <?php $menuItem('This Evening', ['due_on' => $today->format('Y-m-d'), 'evening' => '1']) ?>
<?php endif; ?>
