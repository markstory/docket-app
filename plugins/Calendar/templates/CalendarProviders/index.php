<?php
declare(strict_types=1);
/**
 * @var \Calendar\Model\Entity\CalendarProvider $activeProvider
 * @var \Calendar\Model\Entity\CalendarProvider[] $providers
 * @var string $referer
 */
$this->setLayout('sidebar');
$this->assign('title', 'Synced Calendars');
?>
<div class="heading-actions">
    <h2 class="heading-actions-item">Synced Calendars</h2>
    <a class="button-primary" href="/auth/google/authorize">
        <?= $this->element('icons/plus16') ?>
        Add Google Account
    </a>
</div>
<p>
    Events from linked calendars will be displayed in "today" and "upcoming" views.
</p>
<h3>Connected Calendar Accounts</h3>
<ul class="list-items">
    <?php foreach ($providers as $provider) : ?>
        <?= $this->element(
            'Calendar.calendarprovider_item',
            [
                'provider' => $provider,
            ]
        );
        ?>
    <?php endforeach ?>
</ul>
