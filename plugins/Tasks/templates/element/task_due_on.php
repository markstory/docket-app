<?php
declare(strict_types=1);
/**
 * @var \Tasks\Model\Entity\Task $task
 * @var bool $showNull
 */
if ($task->due_on) :
    $diff = $this->Date->today()->diffInDays($task->due_on, false);
    $className = 'due-on ';
    $thisEvening = $diff == 0 && $task->evening;

    if ($diff < 0) :
        $className .= 'overdue';
    elseif ($diff == 0 && !$task->evening) :
        $className .= 'today';
    elseif ($thisEvening) :
        $className .= 'evening';
    elseif ($diff >= 1 && $diff < 2) :
        $className .= 'tomorrow';
    elseif ($diff >= 2 && $diff < 8) :
        $className .= 'week';
    endif;

    $formatted = $thisEvening ? 'This evening' : $this->Date->formatCompact($task->due_on);
    $icon = $task->evening ? 'moon' : 'calendar';
?>
    <time class="<?= h($className) ?>" datetime="<?= h($formatted) ?>">
        <?= $this->element("icons/{$icon}16") ?>
        <?= h($formatted) ?>
    </time>
<?php elseif ($showNull ?? false) : ?>
    <span class="due-on none">No Due Date</span>
<?php endif; ?>
