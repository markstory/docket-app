<?php
/**
 * @var \Feeds\Model\Entity\FeedSubscription $feedSubscription
 * @var \Feeds\Model\Entity\FeedItem $feedItem
 * @var \App\Model\Entity\User $identity
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
        <?php if ($isRead) : ?>
            <span
                title="You read this on <?= h($feedItem->feed_item_user->read_at->nice($identity->timezone)) ?>"
                class="icon-complete"
            >
                <?= $this->element('icons/check16') ?>
            </span>
        <?php endif; ?>
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
