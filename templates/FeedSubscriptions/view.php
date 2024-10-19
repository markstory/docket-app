<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\FeedSubscription $feedSubscription
 * @var array<\App\Model\Entity\FeedItem> $feedItems
 */
$this->setLayout('feedreader');

?>
<div class="heading-actions">
    <div class="heading-actions-item">
        <h1 class="heading-icon">
            <?= $this->element('icons/directory16', ['color' => $feedSubscription->feed_category->color_hex]) ?>
            <?= h($feedSubscription->alias) ?>
        </h1>
    </div>
    <?= $this->element('feed_subscription_menu', ['feedSubscription' => $feedSubscription]) ?>
</div>

<div class="feed-items">
<?php foreach ($feedItems as $item) : ?>
    <?= $this->element('feed_item', ['feedItem' => $item, 'feedSubscription' => $feedSubscription]) ?>
<?php endforeach; ?>
</div>
