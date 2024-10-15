<?php
declare(strict_types=1);
/**
 * @var \App\Model\Entity\FeedSubscription $feedSubscription
 */
$menuId = 'feedsubscription-menu-' . uniqid();

$deleteConfirmUrl = [
    '_name' => 'feedsubscriptions:deleteconfirm',
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
                'class' => 'icon-edit',
                'data-testid' => 'sync-feed',
            ]
        ) ?>
        <?= $this->Html->link(
            $this->element('icons/pencil16') . ' Edit',
            ['action' => 'edit', 'id' => $feedSubscription->id],
            [
                'class' => 'icon-edit',
                'escape' => false,
                'role' => 'menuitem',
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
