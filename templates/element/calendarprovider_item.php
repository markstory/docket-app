<?php
declare(strict_types=1);
/**
 * @var \App\Model\Entity\CalendarProvider $provider
 * @var \App\Model\Entity\CalendarSource[] $unlinked
 * @var bool $active
 * @var string $referer
 */
$expandUrl = ['_name' => 'calendarproviders:index', '?' => ['provider' => $provider->id]];
?>
<li class="list-item-panel" data-active="<?= $active ? 'true' : 'false'?>">
    <div class="list-item-panel-header">
    <?php if ($active) : ?>
        <span class="list-item-block">
            <?= $this->element('calendarprovider_tile', ['provider' => $provider]) ?>
        </span>
    <?php else : ?>
        <?= $this->Html->link(
            $this->element('calendarprovider_tile', ['provider' => $provider]),
            $expandUrl,
            [
                'class' => 'list-item-block',
                'hx-get' => $this->Url->build($expandUrl),
                'hx-target' => 'main.main',
                'escape' => false,
            ]
        ) ?>
    <?php endif ?>
    <div class="list-item-block">
        TODO menu bar
        <!--
      <OverflowActionBar
        label={t('Calendar Actions')}
        foldWidth={700}
        items={[
          {
            buttonClass: 'button-danger',
            menuItemClass: 'delete',
            onSelect: handleDelete,
            label: t('Unlink'),
            icon: <InlineIcon icon="trash" />,
          },
        ]}
      />
        -->
    </div>
  </div>
    <!-- 
TODO implement this too
  {unlinked && (
    <div className="list-item-panel-item">
      <CalendarSources calendarProvider={provider} unlinked={unlinked} />
    </div>
  )}
    -->
</li>
