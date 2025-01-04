<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\User $identity
 * @var \App\Model\Entity\FeedItem $feedItem
 */
$this->setLayout('feedreader');

$feedUrl = ['_name' => 'feedsubscriptions:view', 'id' => $feedItem->feed_subscription->id];
$menuId = 'feed-item-' . uniqid();

$titleClass = 'feed-item-title';
$isRead = $feedItem->feed_item_user && $feedItem->feed_item_user->read_at;
if ($isRead) {
    $titleClass .= ' feed-item-read';
}
?>
<div class="heading-actions">
    <h1 class="<?= h($titleClass) ?>">
        <?= h($feedItem->title) ?>
        <?php if ($isRead) : ?>
            <span
                title="You read this on <?= h($feedItem->feed_item_user->read_at->nice($identity->timezone)) ?>" >
                <?= $this->element('icons/check16') ?>
            </span>
        <?php endif; ?>
    </h1>
    <?php /* TODO add when items have actions for labelling
    like 'read later' or 'saved'
    ?>
    <drop-down>
        <button
            class="button-icon button-default"
            aria-haspopup="true"
            aria-controls="<?= h($menuId) ?>"
            aria-label="Post actions"
            type="button"
        >
            <?= $this->element('icons/kebab16') ?>
        </button>
        <drop-down-menu id="<?= h($menuId) ?>" role="menu">
            menu goes here?
        </drop-down-menu>
    </drop-down>
    <?php */ ?>
</div>
<div class="feed-item-byline">
    <?= $this->Html->link(
        $feedItem->feed_subscription->alias,
        $feedUrl,
    ) ?>
    <?= $this->Time->timeAgoInWords($feedItem->published_at) ?>
    <?php if ($feedItem->author) : ?>
        by <?= h($feedItem->author) ?>
    <?php endif; ?>
</div>

<?php if ($feedItem->summary !== "" && !$feedItem->content) : ?>
<div class="feed-item-body">
    <?= $feedItem->summary ?>
</div>
<?php else : ?>
<div class="feed-item-body">
    <?= $feedItem->content ?>
</div>
<?php endif; ?>

<div class="feed-item-footer">
    <?= $this->Html->link(
        'View website',
        ['_name' => 'feedsubscriptions:readvisit', 'id' => $feedItem->feed_subscription->id, 'itemId' => $feedItem->id],
        // $feedItem->url,
        ['target' => '_blank']
    ) ?>
</div>
