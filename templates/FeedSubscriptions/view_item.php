<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\FeedItem $feedItem
 */
$this->setLayout('feedreader');

$feedUrl = ['_name' => 'feedsubscriptions:view', 'id' => $feedItem->feed_subscription->id];
$menuId = 'feed-item-' . uniqid();
?>
<div class="heading-actions">
    <h1 class="feed-item-title"><?= h($feedItem->title) ?></h1>
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
        $feedItem->url,
        ['target' => '_blank']
    ) ?>
</div>
