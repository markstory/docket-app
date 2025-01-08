<?php
declare(strict_types=1);
/**
 * @var \App\Model\Entity\FeedCategory $feedCategory
 */
$menuId = 'feed-category-menu-' . uniqid();
$deleteConfirm = ['_name' => 'feedcategories:deleteconfirm', 'id' => $feedCategory->id];
$feedEdit = ['_name' => 'feedcategories:edit', 'id' => $feedCategory->id];
?>
<drop-down>
    <button
        class="button-icon button-default"
        aria-haspopup="true"
        aria-controls="<?= h($menuId) ?>"
        aria-label="Category actions"
        type="button"
    >
        <?= $this->element('icons/kebab16') ?>
    </button>
    <drop-down-menu id="<?= h($menuId) ?>" role="menu">
        <?= $this->Html->link(
            $this->element('icons/pencil16') . ' Edit Category',
            $feedEdit,
            [
                'hx-get' => $this->Url->build($feedEdit),
                'hx-target' => 'body',
                'hx-swap' => 'beforeend',
                'class' => 'icon-edit',
                'escape' => false,
                'role' => 'menuitem',
            ]
        ) ?>
        <?= $this->Html->link(
            $this->element('icons/trash16') . ' Delete Category',
            $deleteConfirm,
            [
                'class' => 'icon-delete',
                'escape' => false,
                'role' => 'menuitem',
                'dropdown-close' => true,
                'hx-get' => $this->Url->build($deleteConfirm),
                'hx-target' => 'body',
                'hx-swap' => 'beforeend',
            ]
        ) ?>
    </drop-down-menu>
</drop-down>
