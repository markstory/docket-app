<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\FeedSubscription $feedSubscription
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('Edit Feed Subscription'), ['action' => 'edit', $feedSubscription->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Delete Feed Subscription'), ['action' => 'delete', $feedSubscription->id], ['confirm' => __('Are you sure you want to delete # {0}?', $feedSubscription->id), 'class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List Feed Subscriptions'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Feed Subscription'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="feedSubscriptions view content">
            <h3><?= h($feedSubscription->alias) ?></h3>
            <table>
                <tr>
                    <th><?= __('Feed') ?></th>
                    <td><?= $feedSubscription->hasValue('feed') ? $this->Html->link($feedSubscription->feed->url, ['controller' => 'Feeds', 'action' => 'view', $feedSubscription->feed->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('User') ?></th>
                    <td><?= $feedSubscription->hasValue('user') ? $this->Html->link($feedSubscription->user->id, ['controller' => 'Users', 'action' => 'view', $feedSubscription->user->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Feed Category') ?></th>
                    <td><?= $feedSubscription->hasValue('feed_category') ? $this->Html->link($feedSubscription->feed_category->title, ['controller' => 'FeedCategories', 'action' => 'view', $feedSubscription->feed_category->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Alias') ?></th>
                    <td><?= h($feedSubscription->alias) ?></td>
                </tr>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= $this->Number->format($feedSubscription->id) ?></td>
                </tr>
                <tr>
                    <th><?= __('Ranking') ?></th>
                    <td><?= $this->Number->format($feedSubscription->ranking) ?></td>
                </tr>
                <tr>
                    <th><?= __('Created') ?></th>
                    <td><?= h($feedSubscription->created) ?></td>
                </tr>
                <tr>
                    <th><?= __('Modified') ?></th>
                    <td><?= h($feedSubscription->modified) ?></td>
                </tr>
            </table>
            <div class="related">
                <h4><?= __('Related Feed Items') ?></h4>
                <?php if (!empty($feedSubscription->feed_items)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Id') ?></th>
                            <th><?= __('Feed Id') ?></th>
                            <th><?= __('Title') ?></th>
                            <th><?= __('Summary') ?></th>
                            <th><?= __('Published At') ?></th>
                            <th><?= __('Thumbnail Image Url') ?></th>
                            <th><?= __('Created') ?></th>
                            <th><?= __('Modified') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($feedSubscription->feed_items as $feedItem) : ?>
                        <tr>
                            <td><?= h($feedItem->id) ?></td>
                            <td><?= h($feedItem->feed_id) ?></td>
                            <td><?= h($feedItem->title) ?></td>
                            <td><?= h($feedItem->summary) ?></td>
                            <td><?= h($feedItem->published_at) ?></td>
                            <td><?= h($feedItem->thumbnail_image_url) ?></td>
                            <td><?= h($feedItem->created) ?></td>
                            <td><?= h($feedItem->modified) ?></td>
                            <td class="actions">
                                <?= $this->Html->link(__('View'), ['controller' => 'FeedItems', 'action' => 'view', $feedItem->id]) ?>
                                <?= $this->Html->link(__('Edit'), ['controller' => 'FeedItems', 'action' => 'edit', $feedItem->id]) ?>
                                <?= $this->Form->postLink(__('Delete'), ['controller' => 'FeedItems', 'action' => 'delete', $feedItem->id], ['confirm' => __('Are you sure you want to delete # {0}?', $feedItem->id)]) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <div class="related">
                <h4><?= __('Related Saved Feed Items') ?></h4>
                <?php if (!empty($feedSubscription->saved_feed_items)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Id') ?></th>
                            <th><?= __('Feed Subscription Id') ?></th>
                            <th><?= __('Title') ?></th>
                            <th><?= __('Body') ?></th>
                            <th><?= __('Created') ?></th>
                            <th><?= __('Modified') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($feedSubscription->saved_feed_items as $savedFeedItem) : ?>
                        <tr>
                            <td><?= h($savedFeedItem->id) ?></td>
                            <td><?= h($savedFeedItem->feed_subscription_id) ?></td>
                            <td><?= h($savedFeedItem->title) ?></td>
                            <td><?= h($savedFeedItem->body) ?></td>
                            <td><?= h($savedFeedItem->created) ?></td>
                            <td><?= h($savedFeedItem->modified) ?></td>
                            <td class="actions">
                                <?= $this->Html->link(__('View'), ['controller' => 'SavedFeedItems', 'action' => 'view', $savedFeedItem->id]) ?>
                                <?= $this->Html->link(__('Edit'), ['controller' => 'SavedFeedItems', 'action' => 'edit', $savedFeedItem->id]) ?>
                                <?= $this->Form->postLink(__('Delete'), ['controller' => 'SavedFeedItems', 'action' => 'delete', $savedFeedItem->id], ['confirm' => __('Are you sure you want to delete # {0}?', $savedFeedItem->id)]) ?>
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
