<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\FeedSubscription $feedSubscription
 * @var \Cake\Datasource\Paging\PaginatedResultSet<\App\Model\Entity\FeedItem> $feedItems
 * @property \Cake\View\Helper\PaginatorHelper $Paginator
 */
$this->setLayout('feedreader');

$this->assign('title', 'Feed - ' . $feedSubscription->alias);

$itemIds = $feedItems->items()->extract('id')->toList();
$itemCount = count($itemIds);
?>
<div class="heading-actions">
    <div class="heading-actions-item">
        <h1 class="heading-icon">
            <?php if ($feedSubscription->feed->favicon_url) : ?>
                <?= $this->Html->image($feedSubscription->feed->favicon_url, ['width' => 32]) ?>
            <?php else : ?>
                <?= $this->element('icons/rss16', ['color' => $feedSubscription->feed_category->color_hex]) ?>
            <?php endif; ?>
            <?= h($feedSubscription->alias) ?>
        </h1>
    </div>
    <div class="button-bar-inline">
        <?php if ($itemCount > 0) : ?>
            <?= $this->Form->postButton(
                $this->element('icons/check16'),
                ['_name' => 'feedsubscriptions:itemsmarkread', '_method' => 'post'],
                [
                    'title' => __n(
                        'mark {0} item read',
                        'mark {0} items read',
                        $itemCount,
                        [$itemCount]
                    ),
                    'class' => 'button-icon',
                    'data' => ['id' => $itemIds],
                    'escapeTitle' => false,
                ]
            );
            ?>
        <?php endif; ?>
        <?= $this->element('feed_subscription_menu', ['feedSubscription' => $feedSubscription]) ?>
    </div>
</div>

<div class="feed-items">
<?php foreach ($feedItems as $item) : ?>
    <?= $this->element('feed_item', ['feedItem' => $item, 'feedSubscription' => $feedSubscription]) ?>
<?php endforeach; ?>
</div>
<div class="paginator">
    <ul class="pagination">
        <?= $this->Paginator->prev('< ' . __('previous')) ?>
        <?= $this->Paginator->numbers() ?>
        <?= $this->Paginator->next(__('next') . ' >') ?>
    </ul>
</div>
