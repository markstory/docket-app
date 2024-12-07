<?php
/**
 * @var \App\Model\Entity\FeedSubscription $feedSubscription
 * @var \App\Model\Entity\FeedItem $feedItem
 * @var \Cake\View\View $this
 */
$class = 'feed-item';
if ($feedItem->feed_item_user && $feedItem->feed_item_user->read_at) {
    $class .= ' feed-item-read';
}
?>
<div class="<?= h($class); ?>">
    <h2><?= h($feedItem->title) ?></h2>
    <p>
        <?= $this->Html->link($feedSubscription->alias, ['_name' => 'feedsubscriptions:view', 'id' => $feedSubscription->id]) ?> |
        <?= $this->Time->timeAgoInWords($feedItem->published_at) ?>
        <?php if ($feedItem->author) : ?>
            by <?= h($feedItem->author) ?>
        <?php endif; ?>
    </p>
    <p><?= $feedItem->summary ?></p>
    <?php if (!$feedItem->content) : ?>
        <p><?= $this->Html->link(
            'Visit',
            ['_name' => 'feedsubscriptions:readvisit', 'id' => $feedSubscription->id, 'itemId' => $feedItem->id],
            ['noreferrer' => true, 'target' => '_blank']
        ) ?></p>
    <?php else : ?>
        <p><?= $this->Html->link(
            'Read more',
            ['_name' => 'feedsubscriptions:viewitem', 'itemId' => $feedItem->id, 'id' => $feedSubscription->id],
        ) ?></p>
    <?php endif; ?>
</div>
