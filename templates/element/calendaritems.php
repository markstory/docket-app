<?php
declare(strict_types=1);
/**
 * @var \App\Model\Entity\CalendarItem[] $calendarItems
 */
?>
<div class="calendar-item-list">
<?php foreach ($calendarItems as $item) : ?>
    <?php
    $start = '';
    $allDay = $item->all_day;
    if ($item->start_time !== null) :
        $start = '<time date-time="' . '">' . $item->start_time->format('H:i') . '</time>';
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
