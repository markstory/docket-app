<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\FeedSubscription> $feedSubscriptions
 */
$this->setLayout('feedreader');

$addUrl = $this->Url->build(['_name' => 'feedsubscriptions:add']);
?>
<h3 class="heading-icon">
    <?= __('Feeds') ?>
    <?= $this->Html->link(
        $this->element('icons/plus16'),
        ['action' => 'add'],
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
<div class="table-responsive">
    <table>
        <thead>
            <tr>
                <th><?= $this->Paginator->sort('feed_category_id') ?></th>
                <th><?= $this->Paginator->sort('alias') ?></th>
                <th class="actions"><?= __('Actions') ?></th>
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
                <td><?= $feedSubscription->hasValue('feed_category') ? $this->Html->link($feedSubscription->feed_category->title, ['controller' => 'FeedCategories', 'action' => 'view', $feedSubscription->feed_category->id]) : '' ?></td>
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
