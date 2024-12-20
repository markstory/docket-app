<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\FeedCategory $feedCategory
 * @var array<\App\Model\Entity\FeedItem> $feedItems
 */
$this->setLayout('feedreader');
$this->assign('title', $feedCategory->title . ' Feeds');

$subscriptionAddUrl = $this->Url->build(['_name' => 'feedsubscriptions:discover', '?' => ['feed_category_id' => $feedCategory->id]]);

$groupedItems = [];
foreach ($feedItems as $item) :
    $sub = $item->feed_subscription;
    $date = $item->published_at->format('Y-m-d');
    if (!isset($groupedItems[$date])) :
        $groupedItems[$date] = [
            'date' => $item->published_at,
            'items' => [],
        ];
    endif;
    $groupedItems[$date]['items'][] = $item;
endforeach;
?>
<div class="heading-actions">
    <div class="heading-actions-item">
        <h1 class="heading-icon">
            <?= $this->element('icons/directory16', ['color' => $feedCategory->color_hex]) ?>
            <?= h($feedCategory->title) ?>
        </h1>
        <?= $this->Html->link(
            $this->element('icons/plus16'),
            $subscriptionAddUrl,
            [
                'escape' => false,
                'class' => 'button-icon-primary',
                'data-testid' => 'add-task',
                'hx-get' => $subscriptionAddUrl,
                'hx-target' => 'main.main',
                'hx-swap' => 'beforeend',
            ]
        ) ?>
    </div>
    <?= $this->element('feed_category_menu', ['feedCategory' => $feedCategory]) ?>
</div>

<div class="feed-items">
<?php foreach ($groupedItems as $group) : ?>
<h3 class="heading-feed-group">
    <time class="heading-feed-category" datetime="<?= h($group['date']->toDateString()) ?>">
        <?= h($group['date']->timeAgoInWords()) ?>
    </time>
</h3>
<?php
    foreach ($group['items'] as $item) :
        echo $this->element('feed_item', [
            'feedItem' => $item,
            'feedSubscription' => $item->feed_subscription,
        ]);
    endforeach;
endforeach;
?>
</div>
