<?php
declare(strict_types=1);
/**
 * @var \App\Model\Entity\CalendarSource $source
 * @var int $providerId
 * @var string $mode create or edit.
 */
$createUrl = ['_name' => 'calendarsources:add', 'providerId' => $providerId];
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
        </span>
    </span>
    <div class="list-item-block">
    <?php if ($mode === 'edit') : ?>
        <?= $this->Form->postButton(
            $this->element('icons/sync16') . ' Refresh',
            $refreshUrl,
            [
                'class' => 'button button-secondary',
                'hx-post' => $this->Url->build($refreshUrl),
                'hx-target' => 'main.main',
                'escapeTitle' => false,
            ]
        ) ?>
        <?= $this->Html->link(
            // TODO add delete confirm?
            $this->element('icons/trash16') . ' Unlink',
            $deleteConfirmUrl,
            [
                'escapeTitle' => false,
                'class' => 'button-danger',
                'hx-get' => $this->Url->build($deleteConfirmUrl),
                'hx-target' => 'body',
                'hx-swap' => 'beforeend',
                'data-testid' => 'delete',
                'dropdown-close' => true,
            ]
        ) ?>
    <?php elseif ($mode === 'create') : ?>
        <?= $this->Form->postButton(
            $this->element('icons/plus16') . ' Link',
            $createUrl,
            [
                'class' => 'button-primary',
                'hx-post' => $this->Url->build($createUrl),
                'hx-target' => 'main.main',
                'escapeTitle' => false,
                'data' => [
                    'calendar_provider_id' => $providerId,
                    'provider_id' => $source->provider_id,
                    'name' => $source->name,
                    'color' => $source->color,
                ],
            ]
        ) ?>
    <?php endif ?>
    </div>
</li>
