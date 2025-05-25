<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\Feeds\Model\Entity\FeedSubscription> $feedSubscriptions
 */
$this->setLayout('Feeds.feedreader');
$this->assign('title', 'All Feeds');

$addUrl = $this->Url->build(['_name' => 'feedsubscriptions:discover']);
?>
<h3 class="heading-icon">
    <?= __('Feeds') ?>
    <?= $this->Html->link(
        $this->element('icons/plus16'),
        $addUrl,
        [
            'escape' => false,
            'class' => 'button-icon-primary',
            'data-testid' => 'add-task',
            'hx-get' => $addUrl,
            'hx-target' => 'main.main',
            'hx-swap' => 'beforeend',
        ]
    ) ?>
</h3>
<div class="table-responsive feed-subscriptions">
    <table>
        <thead>
            <tr>
                <th><?= $this->Paginator->sort('feed_category_id', 'Category') ?></th>
                <th><?= $this->Paginator->sort('alias') ?></th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($feedSubscriptions as $feedSubscription): ?>
                <?php
                $menuId = "feed-subscription-{$feedSubscription->id}";
                $viewUrl = $this->Url->build(['_name' => 'feedsubscriptions:view', 'id' => $feedSubscription->id]);
                $deleteConfirmUrl = $this->Url->build([
                    '_name' => 'feedsubscriptions:deleteconfirm',
                    'id' => $feedSubscription->id
                ]);
                ?>
            <tr>
                <td>
                    <?php if ($feedSubscription->feed_category) : ?>
                    <span class="feed-category-badge">
                        <?= $this->element('icons/directory16', ['color' => $feedSubscription->feed_category->color_hex]) ?>
                        <?= $this->Html->link($feedSubscription->feed_category->title, ['_name' => 'feedcategories:view', 'id' => $feedSubscription->feed_category->id]) ?>
                    </span>
                    <?php endif; ?>
                </td>
                <td><?= $this->Html->link($feedSubscription->alias, $viewUrl) ?></td>
                <td class="actions">
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
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<div class="paginator">
    <ul class="pagination">
        <?= $this->Paginator->first('<< ' . __('first')) ?>
        <?= $this->Paginator->prev('< ' . __('previous')) ?>
        <?= $this->Paginator->numbers() ?>
        <?= $this->Paginator->next(__('next') . ' >') ?>
        <?= $this->Paginator->last(__('last') . ' >>') ?>
    </ul>
    <p><?= $this->Paginator->counter(__('Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total')) ?></p>
</div>
