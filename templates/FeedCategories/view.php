<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\FeedCategory $feedCategory
 */
$this->setLayout('feedreader');
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('Edit Feed Category'), ['action' => 'edit', $feedCategory->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Delete Feed Category'), ['action' => 'deleteConfirm', $feedCategory->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List Feed Categories'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Feed Category'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="feedCategories view content">
            <h3><?= h($feedCategory->title) ?></h3>
            <table>
                <tr>
                    <th><?= __('Title') ?></th>
                    <td><?= h($feedCategory->title) ?></td>
                </tr>
                <tr>
                    <th><?= __('Color') ?></th>
                    <td><?= $this->Number->format($feedCategory->color) ?></td>
                </tr>
            </table>
            <div class="related">
                <h4><?= __('Related Feed Subscriptions') ?></h4>
                <?php if (!empty($feedCategory->feed_subscriptions)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Id') ?></th>
                            <th><?= __('Feed Id') ?></th>
                            <th><?= __('User Id') ?></th>
                            <th><?= __('Feed Category Id') ?></th>
                            <th><?= __('Alias') ?></th>
                            <th><?= __('Ranking') ?></th>
                            <th><?= __('Created') ?></th>
                            <th><?= __('Modified') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($feedCategory->feed_subscriptions as $feedSubscription) : ?>
                        <tr>
                            <td><?= h($feedSubscription->id) ?></td>
                            <td><?= h($feedSubscription->feed_id) ?></td>
                            <td><?= h($feedSubscription->user_id) ?></td>
                            <td><?= h($feedSubscription->feed_category_id) ?></td>
                            <td><?= h($feedSubscription->alias) ?></td>
                            <td><?= h($feedSubscription->ranking) ?></td>
                            <td><?= h($feedSubscription->created) ?></td>
                            <td><?= h($feedSubscription->modified) ?></td>
                            <td class="actions">
                                <?= $this->Html->link(__('View'), ['controller' => 'FeedSubscriptions', 'action' => 'view', $feedSubscription->id]) ?>
                                <?= $this->Html->link(__('Edit'), ['controller' => 'FeedSubscriptions', 'action' => 'edit', $feedSubscription->id]) ?>
                                <?= $this->Form->postLink(__('Delete'), ['controller' => 'FeedSubscriptions', 'action' => 'delete', $feedSubscription->id], ['confirm' => __('Are you sure you want to delete # {0}?', $feedSubscription->id)]) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
