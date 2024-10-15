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
    <p><?= $feedItem->summary ?></p>
    <p><?= $this->Html->link(
        'View more',
        $feedItem->url,
        ['noreferrer' => true, 'target' => '_blank']
    ) ?></p>
</div>
