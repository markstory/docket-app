<?php
declare(strict_types=1);
/**
 * @var \Feeds\Model\Entity\FeedSubscription $feedSubscription
 */
$menuId = 'feedsubscription-menu-' . uniqid();

$deleteConfirmUrl = [
    '_name' => 'feedsubscriptions:deleteconfirm',
    'id' => $feedSubscription->id,
];
$editUrl = [
    '_name' => 'feedsubscriptions:edit',
    'id' => $feedSubscription->id,
];
$feedSyncUrl = $this->Url->build([
    '_name' => 'feedsubscriptions:sync',
    'id' => $feedSubscription->id
]);
?>
<drop-down>
    <button
        class="button-icon button-default"
        aria-haspopup="true"
        aria-controls="<?= h($menuId) ?>"
        aria-label="Task actions"
        type="button"
    >
        <?= $this->element('icons/kebab16') ?>
    </button>
    <drop-down-menu id="<?= h($menuId) ?>" role="menu">
        <?= $this->Form->postLink(
            $this->element('icons/sync16') . ' Refresh',
            $feedSyncUrl,
            [
                'escape' => false,
                'role' => 'menuitem',
                'class' => 'icon-complete',
                'data-testid' => 'sync-feed',
            ]
        ) ?>
        <?= $this->Html->link(
            $this->element('icons/pencil16') . ' Edit',
            $editUrl,
            [
                'class' => 'icon-edit',
                'escape' => false,
                'role' => 'menuitem',
                'hx-get' => $this->Url->build($editUrl),
                'hx-target' => 'body',
                'hx-swap' => 'beforeend',
                'dropdown-close' => true,
            ]
        ) ?>
        <?= $this->Html->link(
            $this->element('icons/trash16') . ' Delete',
            $deleteConfirmUrl,
            [
                'class' => 'icon-delete',
                'escape' => false,
                'role' => 'menuitem',
                'data-testid' => 'delete',
                'dropdown-close' => true,
                'hx-get' => $this->Url->build($deleteConfirmUrl),
                'hx-target' => 'body',
                'hx-swap' => 'beforeend',
            ]
        ) ?>
    </drop-down-menu>
</drop-down>
