<?php
declare(strict_types=1);
/**
 * @var \App\Model\Entity\CalendarItem[] $calendarItems
 * @var \App\Model\Entity\User $identity
 */
?>
<div class="calendar-item-list">
<?php foreach ($calendarItems as $item) : ?>
    <?php
    $start = '';
    $allDay = $item->all_day;
    $startTime = $item->getFormattedTime($identity->timezone);
    if ($startTime) :
        $start = '<time date-time="' . '">' . h($startTime) . '</time>';
    endif;
    $class = 'calendar-item';
    if ($allDay) :
        $class .= ' all-day';
    endif;
    $style = '--calendar-color: ' . h($item->color_hex) . ';';
    ?>
    <div class="<?= h($class) ?>" style="<?= h($style) ?>">
        <?= $start ?>
        <a href="<?= $item->html_link ?>" target="_blank" rel="noreferrer">
            <?= h($item->title) ?>
        </a>
    </div>
<?php endforeach; ?>
</div>
