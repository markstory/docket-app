<?php
declare(strict_types=1);
/**
 * @var Array<\App\Model\Entity\FeedCategory> $feedCategories
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
    <?php $itemId = 'category-item-' . uniqid(); ?>
    <div class="dnd-item" id="<?= h($itemId) ?>">
        <?= $this->Form->hidden('id[]', ['value' => $category->id]) ?>
        <button class="dnd-handle" role="button" aria-roledescription="sortable">
            <?= $this->element('icons/grabber24') ?>
        </button>
        <div>
            <?php
            $viewUrl = $this->Url->build(['_name' => 'feedcategories:view', 'id' => $category->id]);
            $toggleUrl = $this->Url->build(['_name' => 'feedcategories:toggleexpanded', 'id' => $category->id]);
            ?>
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

            <?php if ($category->expanded) : ?>
                <ul>
                <?php foreach ($category->feed_subscriptions as $subscription) : ?>
                    <li><?= $this->Html->link($subscription->alias, ['_name' => 'feedsubscriptions:view', 'id' => $subscription->id]) ?></li>
                <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>
<?= $this->Form->end() ?>
