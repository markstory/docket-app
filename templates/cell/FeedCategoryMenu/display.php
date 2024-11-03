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
            <!-- item element needs a toggle for open/close the category -->
            <?= $this->element('feed_category_item', ['feedCategory' => $category]) ?>
            <!-- read the category.open and conditionally render the menu -->
            <ul>
            <?php foreach ($category->feed_subscriptions as $subscription) : ?>
                <li><?= $this->Html->link($subscription->alias, ['_name' => 'feedsubscriptions:view', 'id' => $subscription->id]) ?></li>
            <?php endforeach; ?>
            </ul>
        </div>
    </div>
<?php endforeach; ?>
<?= $this->Form->end() ?>
