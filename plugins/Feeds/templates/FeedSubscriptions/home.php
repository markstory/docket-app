<?php
declare(strict_types=1);

/**
 * @var \Cake\View\View $this
 * @var array<\App\Model\Entity\FeedItem> $feedItems
 */
$this->setLayout('feedreader');
$this->assign('title', 'Feeds');

$addUrl = $this->Url->build(['_name' => 'feedsubscriptions:discover']);

$groupedItems = [];
foreach ($feedItems as $item) :
    $sub = $item->feed_subscription;
    if (!isset($groupedItems[$sub->feed_category_id])) :
        $groupedItems[$sub->feed_category_id] = [
            'category' => $sub->feed_category,
            'items' => [],
        ];
    endif;
    $groupedItems[$sub->feed_category_id]['items'][] = $item;
endforeach;

?>
<h3 class="heading-icon">
    <?= __("What's new") ?>
    <?= $this->Html->link(
        $this->element('icons/plus16'),
        $addUrl,
        [
            'escape' => false,
            'class' => 'button-icon-primary',
            'data-testid' => 'add-task',
            'hx-get' => $addUrl,
            'hx-target' => 'main.main',
            'hx-swap' => 'beforeend',
        ]
    ) ?>
</h3>
<div class="feed-items">
<?php if (empty($groupedItems)) : ?>
    <?= $this->element('Feeds.feeds_empty') ?>
<?php endif; ?>

<?php foreach ($groupedItems as $group) : ?>
<h3 class="heading-feed-group">
    <span class="heading-feed-category">
        <?= $this->element('icons/directory16', ['color' => $group['category']->color_hex]) ?>
        <?= h($group['category']->title) ?>
    </span>
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

<div class="paginator">
    <ul class="pagination">
        <?= $this->Paginator->prev('< ' . __('previous')) ?>
        <?= $this->Paginator->numbers() ?>
        <?= $this->Paginator->next(__('next') . ' >') ?>
    </ul>
</div>
