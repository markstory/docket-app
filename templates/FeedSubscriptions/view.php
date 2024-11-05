<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\FeedSubscription $feedSubscription
 * @var array<\App\Model\Entity\FeedItem> $feedItems
 */
$this->setLayout('feedreader');

$this->assign('title', $feedSubscription->alias);

$itemIds = $feedItems->extract('id')->toList();
$itemCount = count($itemIds);
?>
<div class="heading-actions">
    <div class="heading-actions-item">
        <h1 class="heading-icon">
            <?= $this->element('icons/rss16', ['color' => $feedSubscription->feed_category->color_hex]) ?>
            <?= h($feedSubscription->alias) ?>
        </h1>
    </div>
    <div class="button-bar-inline">
        <?= $this->Form->postButton(
            $this->element('icons/check16'),
            ['_name' => 'feedsubscriptions:itemsmarkread', 'id' => $feedSubscription->id,  '_method' => 'post'],
            [
                'title' => __n(
                    'mark {} item read',
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
