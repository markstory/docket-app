<?php
/**
 * @var \App\Model\Entity\FeedSubscription $feedSubscription
 * @var \App\Model\Entity\FeedItem $feedItem
 */
?>
<div class="feed-item">
    <h2><?= h($feedItem->title) ?></h2>
    <p>
        <?= $this->Html->link($feedSubscription->alias, ['_name' => 'feedsubscriptions:view', 'id' => $feedSubscription->id]) ?> |
        <?= $this->Time->timeAgoInWords($feedItem->published_at) ?>
    </p>
    <p><?= h($feedItem->summary) ?></p>
    <?php if (!$feedItem->content) : ?>
        <p><?= $this->Html->link(
            'Read more',
            $feedItem->url,
            ['noreferrer' => true, 'target' => '_blank']
        ) ?></p>
    <?php else : ?>
        <p><?= $this->Html->link(
            'Read more',
            ['_name' => 'feedsubscriptions:viewitem', 'itemId' => $feedItem->id, 'id' => $feedSubscription->id],
        ) ?></p>
    <?php endif; ?>
</div>
