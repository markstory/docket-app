<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\FeedSubscription> $feedSubscriptions
 */
?>
<div class="feedSubscriptions index content">
    <?= $this->Html->link(__('New Feed Subscription'), ['action' => 'add'], ['class' => 'button float-right']) ?>
    <h3><?= __('Feed Subscriptions') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('id') ?></th>
                    <th><?= $this->Paginator->sort('feed_id') ?></th>
                    <th><?= $this->Paginator->sort('user_id') ?></th>
                    <th><?= $this->Paginator->sort('feed_category_id') ?></th>
                    <th><?= $this->Paginator->sort('alias') ?></th>
                    <th><?= $this->Paginator->sort('ranking') ?></th>
                    <th><?= $this->Paginator->sort('created') ?></th>
                    <th><?= $this->Paginator->sort('modified') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($feedSubscriptions as $feedSubscription): ?>
                <tr>
                    <td><?= $this->Number->format($feedSubscription->id) ?></td>
                    <td><?= $feedSubscription->hasValue('feed') ? $this->Html->link($feedSubscription->feed->url, ['controller' => 'Feeds', 'action' => 'view', $feedSubscription->feed->id]) : '' ?></td>
                    <td><?= $feedSubscription->hasValue('user') ? $this->Html->link($feedSubscription->user->id, ['controller' => 'Users', 'action' => 'view', $feedSubscription->user->id]) : '' ?></td>
                    <td><?= $feedSubscription->hasValue('feed_category') ? $this->Html->link($feedSubscription->feed_category->title, ['controller' => 'FeedCategories', 'action' => 'view', $feedSubscription->feed_category->id]) : '' ?></td>
                    <td><?= h($feedSubscription->alias) ?></td>
                    <td><?= $this->Number->format($feedSubscription->ranking) ?></td>
                    <td><?= h($feedSubscription->created) ?></td>
                    <td><?= h($feedSubscription->modified) ?></td>
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
</div>
