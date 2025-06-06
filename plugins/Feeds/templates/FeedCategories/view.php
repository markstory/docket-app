<?php
/**
 * @var \App\View\AppView $this
 * @var \Feeds\Model\Entity\FeedCategory $feedCategory
 * @var array<\Feeds\Model\Entity\FeedItem> $feedItems
 */
$this->setLayout('Feeds.feedreader');
$this->assign('title', 'Feed Category - ' . $feedCategory->title);

$subscriptionAddUrl = $this->Url->build([
    '_name' => 'feedsubscriptions:discover',
    '?' => ['feed_category_id' => $feedCategory->id]
]);

$groupedItems = [];
$itemIds = [];
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
    $itemIds[] = $item->id;
endforeach;

$itemCount = count($itemIds);
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
    <div class="button-bar-inline">
        <?php if ($itemCount > 0) : ?>
            <?= $this->Form->postButton(
                $this->element('icons/check16'),
                ['_name' => 'feedsubscriptions:itemsmarkread', '_method' => 'post'],
                [
                    'title' => __n(
                        'mark {0} item read',
                        'mark {0} items read',
                        $itemCount,
                        [$itemCount]
                    ),
                    'class' => 'button-icon',
                    'data' => ['id' => $itemIds],
                    'escapeTitle' => false,
                ]
            );
            ?>
        <?php endif; ?>
        <?= $this->element('Feeds.feed_category_menu', ['feedCategory' => $feedCategory]) ?>
    </div>
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
        echo $this->element('Feeds.feed_item', [
            'feedItem' => $item,
            'feedSubscription' => $item->feed_subscription,
        ]);
    endforeach;
endforeach;
?>
</div>
