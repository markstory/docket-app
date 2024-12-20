<?php
/**
 * @var \App\Model\Entity\FeedSubscription $feedSubscription
 * @var \App\Model\Entity\FeedItem $feedItem
 * @var \Cake\View\View $this
 */
$class = 'feed-item';
$isRead = $feedItem->feed_item_user && $feedItem->feed_item_user->read_at;
if ($isRead) {
    $class .= ' feed-item-read';
}
?>
<div class="<?= h($class); ?>">
    <h2>
        <?= $this->Html->link($feedItem->title, ['_name' => 'feedsubscriptions:viewitem', 'itemId' => $feedItem->id, 'id' => $feedSubscription->id]) ?>
    </h2>
    <p class="feed-item-byline">
        <?= $this->Html->link($feedSubscription->alias, ['_name' => 'feedsubscriptions:view', 'id' => $feedSubscription->id]) ?> |
        <?= $this->Time->timeAgoInWords($feedItem->published_at) ?>
        <?php if ($feedItem->author) : ?>
            by <?= h($feedItem->author) ?>
        <?php endif; ?>
    </p>

    <?php if (!$isRead && $feedItem->summary) : ?>
        <?php // This should be a component that can be toggled. ?>
        <div class="feed-item-summary"><?= $feedItem->summary ?></div>
    <?php endif; ?>

    <?php if (!$feedItem->content) : ?>
    <p>
        <?= $this->Html->link(
            'Visit',
            ['_name' => 'feedsubscriptions:readvisit', 'id' => $feedSubscription->id, 'itemId' => $feedItem->id],
            ['noreferrer' => true, 'target' => '_blank']
        ) ?>
    </p>
    <?php else : ?>
    <p>
        <?= $this->Html->link(
            'Read more',
            ['_name' => 'feedsubscriptions:viewitem', 'itemId' => $feedItem->id, 'id' => $feedSubscription->id],
        ) ?>
    </p>
    <?php endif; ?>
</div>
