<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\FeedCategory $feedCategory
 */
$this->setLayout('feedreader');

$subscriptionAddUrl = $this->Url->build(['_name' => 'feedsubscriptions:add', '?' => ['feed_category_id' => $feedCategory->id]]);
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
