<?php
declare(strict_types=1);

/**
 * @var \Calendar\Model\Entity\CalendarSource $source
 * @var int $providerId
 * @var string $mode create or edit.
 */
$menuId = 'calendar-source-menu-' . uniqid();
$refreshUrl = ['_name' => 'calendarsources:sync', 'providerId' => $providerId, 'id' => $source->id];
$deleteConfirmUrl = ['_name' => 'calendarsources:deleteconfirm', 'providerId' => $providerId, 'id' => $source->id];
$editUrl = ['_name' => 'calendarsources:edit', 'providerId' => $providerId, 'id' => $source->id];
?>
<li>
    <span class="list-item-block">
        <?php if ($mode === 'create') : ?>
            <?= $this->element('icons/dot16') ?>
        <?php else : ?>
            <?= $this->Form->create($source, [
                'url' => $editUrl,
                'type' => 'post',
                'hx-post' => $this->Url->build($editUrl),
                'hx-trigger' => 'selected',
                'hx-target' => 'main.main',
            ]) ?>
            <?= $this->Form->control('color', [
                'class' => 'select-box-mini',
                'label' => false,
                'type' => 'colorpicker',
                'colors' => $source->getColors(),
                'value' => $source->color,
                'showName' => false,
                'templates' => [
                    'inputContainer' => '{{content}}',
                ]
            ]) ?>
            <?= $this->Form->end() ?>
        <?php endif ?>
        <span class="calendar-name">
            <?= h($source->name) ?>
            <?php if ($source->synced) : ?>
                <span class="icon-complete" title="Calendar linked">
                    <?= $this->element('icons/link16') ?>
                </span>
            <?php endif; ?>
        </span>
    </span>
    <div class="list-item-block">
        <drop-down>
            <button
                class="button-icon button-default"
                aria-haspopup="true"
                aria-controls="<?= h($menuId) ?>"
                aria-label="Calendar actions"
                type="button"
            >
                <?= $this->element('icons/kebab16') ?>
            </button>
            <drop-down-menu id="<?= h($menuId) ?>" role="menu">
                <?php if ($source->synced) : ?>
                    <?= $this->Form->postLink(
                        $this->element('icons/sync16') . ' Refresh',
                        $refreshUrl,
                        [
                            'class' => 'icon-complete',
                            'hx-post' => $this->Url->build($refreshUrl),
                            'escapeTitle' => false,
                            'role' => 'menuitem',
                        ]
                    ) ?>
                    <?= $this->Form->postLink(
                        $this->element('icons/unlink16') . ' Unlink',
                        $editUrl,
                        [
                            'escapeTitle' => false,
                            'class' => 'icon-edit',
                            'hx-post' => $this->Url->build($editUrl),
                            'data-testid' => 'unlink',
                            'data' => [
                                'synced' => false,
                            ],
                            'role' => 'menuitem',
                        ]
                    ) ?>
                <?php else: ?>
                    <?= $this->Form->postLink(
                        $this->element('icons/link16') . ' Link',
                        $editUrl,
                        [
                            'class' => 'icon-complete',
                            'hx-post' => $this->Url->build($editUrl),
                            'escapeTitle' => false,
                            'data' => [
                                'synced' => true,
                            ],
                            'role' => 'menuitem',
                        ]
                    ) ?>
                <?php endif ?>
                <?= $this->Html->link(
                    $this->element('icons/trash16') . ' Delete',
                    $editUrl,
                    [
                        'escapeTitle' => false,
                        'class' => 'icon-delete',
                        'hx-get' => $this->Url->build($deleteConfirmUrl),
                        'hx-target' => 'body',
                        'hx-swap' => 'beforeend',
                        'data-testid' => 'delete',
                        'role' => 'menuitem',
                    ]
                ) ?>
            </drop-down-menu>
        </drop-down>
    </div>
</li>
