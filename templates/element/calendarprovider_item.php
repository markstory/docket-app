<?php
declare(strict_types=1);
/**
 * @var \App\Model\Entity\CalendarProvider $provider
 * @var bool $active
 * @var string $referer
 * @var \Cake\View\View $this
 */
$syncUrl = ['_name' => 'calendarproviders:sync', 'id' => $provider->id];
$deleteUrl = ['_name' => 'calendarproviders:delete', 'id' => $provider->id];
$menuId = 'provider-menu-' . $provider->id;

?>
<li class="list-item-panel">
    <div class="list-item-panel-header">
        <span class="list-item-block">
            <?= $this->element('calendarprovider_tile', ['provider' => $provider]) ?>
        </span>

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
                    <?= $this->Form->postLink(
                        $this->element('icons/sync16') . ' Refresh calendars',
                        $syncUrl,
                        [
                            'class' => 'icon-complete',
                            'escape' => false,
                            'data-testid' => 'sync',
                            'role' => 'menuitem',
                            'hx-post' => $this->Url->build($syncUrl),
                        ]
                    ) ?>
                    <?= $this->Form->postLink(
                        $this->element('icons/trash16') . ' Unlink',
                        $deleteUrl,
                        [
                            'class' => 'icon-delete',
                            'escape' => false,
                            'data-testid' => 'delete',
                            'role' => 'menuitem',
                            'hx-post' => $this->Url->build($deleteUrl),
                        ]
                    ) ?>
                </drop-down-menu>
            </drop-down>
        </div>
    </div>
    <div class="list-item-panel-item">
        <ul class="list-items full-width">
        <?php if ($provider->broken_auth) : ?>
            <li class="list-item-error icon-error">
                <span>
                   <?= $this->element('icons/alert16') ?>
                   Unable to load calendar data. Reconnect this provider.
                </span>
            </li>
        <?php endif; ?>
        <?php if (empty($provider->calendar_sources)) : ?>
            <li class="list-item-empty">
                <?= $this->element('icons/alert16') ?>
                You have no calendars in this provider.
                <?= $this->Form->postButton(
                    'Refresh calendars',
                    $syncUrl,
                    [
                        'class' => 'button button-primary',
                        'data-testid' => 'sync',
                        'hx-post' => $this->Url->build($syncUrl),
                    ]
                ) ?>
            </li>
        <?php else : ?>
            <?php foreach ($provider->calendar_sources as $source) : ?>
                <?= $this->element('calendarprovider_source', [
                    'source' => $source,
                    'mode' => 'edit',
                    'providerId' => $provider->id
                ]) ?>
            <?php endforeach ?>
        <?php endif ?>
        </ul>
    </div>
</li>
