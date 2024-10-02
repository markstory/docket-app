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
            <tr>
                <td><?= $feedSubscription->hasValue('feed_category') ? $this->Html->link($feedSubscription->feed_category->title, ['controller' => 'FeedCategories', 'action' => 'view', $feedSubscription->feed_category->id]) : '' ?></td>
                <td><?= h($feedSubscription->alias) ?></td>
                <td class="actions">
                    <?= $this->Html->link(__('View'), ['action' => 'view', $feedSubscription->id]) ?>
                    <?= $this->Html->link(__('Edit'), ['action' => 'edit', $feedSubscription->id]) ?>
                    <?= $this->Form->postLink(__('Delete'), ['action' => 'delete', $feedSubscription->id], ['confirm' => __('Are you sure you want to delete # {0}?', $feedSubscription->id)]) ?>
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
