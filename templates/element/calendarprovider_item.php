<?php
declare(strict_types=1);
/**
 * @var \App\Model\Entity\CalendarProvider $provider
 * @var \App\Model\Entity\CalendarSource[] $unlinked
 * @var bool $active
 * @var string $referer
 */
$expandUrl = ['_name' => 'calendarproviders:index', '?' => ['provider' => $provider->id]];
$deleteUrl = ['_name' => 'calendarproviders:delete', 'id' => $provider->id];
$menuId = 'provider-menu-' . $provider->id;

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
            <drop-down>
                <button
                    class="button-icon button-default"
                    aria-haspopup="true"
                    aria-controls="<?= h($menuId) ?>"
                    type="button"
                >
                    <?= $this->element('icons/kebab16') ?>
                </button>
                <drop-down-menu id="<?= h($menuId) ?>">
                    <?= $this->Form->postButton(
                        $this->element('icons/trash16') . ' Unlink',
                        $deleteUrl,
                        [
                            'class' => 'button-danger',
                            'escapeTitle' => false,
                            'data-testid' => 'delete',
                            'role' => 'menuitem',
                            'hx-post' => $this->Url->build($deleteUrl),
                            'hx-target' => 'main.main',
                        ]
                    ) ?>
                </drop-down-menu>
            </drop-down>
        </div>
    </div>
    <div class="list-item-panel-item">
        <ul class="list-items full-width">
        <?php if (empty($provider->calendar_sources)) : ?>
            <li class="list-item-empty">
                <?= $this->element('icons/alert16') ?>
                You have no synchronized calendars in this provider. Add one below.
            </li>
        <?php else : ?>
            <?php foreach ($provider->calendar_sources as $source) : ?>
                <?= $this->element('calendarprovider_source', ['source' => $source, 'mode' => 'edit', 'providerId' => $provider->id]) ?>
            <?php endforeach ?>
        <?php endif ?>
        <?php foreach ($unlinked as $source) : ?>
            <?= $this->element('calendarprovider_source', ['source' => $source, 'mode' => 'create', 'providerId' => $provider->id]) ?>
        <?php endforeach ?>
        </ul>
    </div>
</li>
