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
    <div class="heading-actions-item">
        <h1><?= h($feedItem->title) ?></h1>
    </div>
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
</div>
<div class="feed-item-meta">
    <span class="feed-name"><?= $this->Html->link(
        $feedItem->feed_subscription->alias,
        $feedUrl,
    ) ?></span>
    <span class="feed-published-at"><?= $this->Time->nice($feedItem->published_at) ?></span>
</div>
<div class="feed-item-body">
    <?= $feedItem->content ?>
</div>
<div class="feed-item-footer">
    <?= $this->Html->link(
        'View website',
        $feedItem->url,
        ['target' => '_blank']
    ) ?>
</div>
