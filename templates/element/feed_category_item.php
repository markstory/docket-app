<?php
declare(strict_types=1);
/**
 * @var \App\Model\Entity\FeedCategory> $feedCategory
 * @var bool|null $showMenu
 * @var string $url
 */
$url = $this->Url->build(['_name' => 'feedcategories:view', 'id' => $feedCategory->id]);
?>
<div class="feed-category-item">
    <a href="<?= $url ?>" hx-boost="1">
        <span class="feed-category-badge">
            <?= $this->element('icons/directory16', ['color' => $feedCategory->color_hex]) ?>
            <span><?= h($feedCategory->title) ?></span>
        </span>
    </a>
    <span class="counter"><?= h($feedCategory->unread_item_count ?? 99) ?></span>
</div>
