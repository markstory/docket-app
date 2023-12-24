<?php
declare(strict_types=1);
/**
 * @var \App\Model\Entity\CalendarProvider $activeProvider
 * @var \App\Model\Entity\CalendarProvider[] $providers
 * @var string $referer
 */
$this->setLayout('sidebar');
$this->assign('title', 'Synced Calendars');
?>
<h2>Synced Calendars</h2>
<p>
    Events from linked calendars will be displayed in "today" and "upcoming" views.
</p>
<div class="button-bar">
    <a class="button-primary" href="/auth/google/authorize">
        <?= $this->element('icons/plus16') ?>
        Add Google Account
    </a>
</div>
<h3>Connected Calendar Accounts</h3>
<ul class="list-items">
    <?php foreach ($providers as $provider) : ?>
        <?php
        $isActive = $activeProvider && $provider->id === $activeProvider->id;
        $unlinked = $isActive ? $unlinked : [];
        echo $this->element(
            'calendarprovider_item',
            [
                'provider' => $provider,
                'active' => $isActive,
                'unlinked' => $unlinked,
            ]
        );
        ?>
    <?php endforeach ?>
</ul>
