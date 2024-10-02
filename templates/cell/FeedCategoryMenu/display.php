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
        <?= $this->element('feed_category_item', ['feedCategory' => $category]) ?>
    </div>
<?php endforeach; ?>
<?= $this->Form->end() ?>
