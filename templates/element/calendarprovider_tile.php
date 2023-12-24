<?php
declare(strict_types=1);
/**
 * @var \App\Model\Entity\CalendarProvider $provider
 */
$icon = null;
if ($provider->kind === 'google') :
    $icon = <<<'HTML'
      <img
        src="/img/google-calendar-logo.svg"
        alt="Google Calendar logo"
        width="30"
        height="30"
      />
    HTML;
endif;
?>
<?= $icon ?>
<?= h($provider->display_name) ?>
<?php if ($provider->broken_auth) : ?>
    <span class="list-item-block icon-error" title="Unable to load calendar data. Reconnect this provider.">
        <?= $this->element('icons/alert16') ?>
    </span>
<?php endif; ?>

