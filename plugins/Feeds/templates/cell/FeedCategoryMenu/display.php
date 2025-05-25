<?php
declare(strict_types=1);
/**
 * @var Array<\Feeds\Model\Entity\FeedCategory> $feedCategories
 */
?>
<?= $this->Form->create(
    null,
    [
        'class' => 'dnd-dropper-left-offset',
        'hx-ext' => 'project-sorter',
        'hx-trigger' => 'end',
        'hx-post' => $this->Url->build(['_name' => 'feedcategories:reorder']),
        'hx-swap' => 'outerHTML',
    ]
) ?>
<?php foreach ($feedCategories as $category) : ?>
    <?php
    $itemId = 'category-item-' . uniqid();
    $viewUrl = $this->Url->build(['_name' => 'feedcategories:view', 'id' => $category->id]);
    $toggleUrl = $this->Url->build(['_name' => 'feedcategories:toggleexpanded', 'id' => $category->id]);
    ?>
    <div class="dnd-item" id="<?= h($itemId) ?>">
        <?= $this->Form->hidden('id[]', ['value' => $category->id]) ?>
        <button class="dnd-handle" role="button" aria-roledescription="sortable">
            <?= $this->element('icons/grabber24') ?>
        </button>
        <div class="feed-category-item">
            <button
                role="button"
                class="button-icon"
                title="Expand category"
                hx-post="<?= $toggleUrl ?>"
                hx-target=".dnd-dropper-left-offset"
                hx-swap="outerHTML"
            >
                <?php if ($category->expanded) : ?>
                    <?= $this->element('icons/chevron16') ?>
                <?php else : ?>
                    <?= $this->element('icons/chevron-right16') ?>
                <?php endif; ?>
            </button>
            <a href="<?= $viewUrl ?>" hx-boost="1">
                <span class="feed-category-badge">
                    <?= $this->element('icons/directory16', ['color' => $category->color_hex]) ?>
                    <span><?= h($category->title) ?></span>
                </span>
            </a>
            <span class="counter"><?= h($category->unread_item_count ?? 99) ?></span>
        </div>
    </div>
    <?php if ($category->expanded) : ?>
        <ul class="feed-category-feeds">
        <?php foreach ($category->feed_subscriptions as $subscription) : ?>
        <li>
            <span>
                <?php if ($subscription->feed?->favicon_url) : ?>
                    <?= $this->Html->image($subscription->feed->favicon_url, ['width' => 16, 'height' => 16]) ?>
                <?php else: ?>
                    <?= $this->element('icons/rss16', ['color' => $category->color_hex]) ?>
                <?php endif; ?>
                <?= $this->Html->link($subscription->alias, ['_name' => 'feedsubscriptions:view', 'id' => $subscription->id]) ?>
            </span>
            <span class="counter"><?= h($subscription->unread_item_count ?? 91) ?></span>
        </li>
        <?php endforeach; ?>
        </ul>
    <?php endif; ?>
<?php endforeach; ?>
<?= $this->Form->end() ?>
