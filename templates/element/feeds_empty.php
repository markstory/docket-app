<?php
declare(strict_types=1);

$createParams = $this->get('globalAddContext') ?? [];

$categoryAddUrl = ['_name' => 'feedcategories:add'];
$feedAddUrl = ['_name' => 'feedsubscriptions:discover'];
?>
<div class="empty-state-container">
    <span class="hero-icon">
        <?= $this->element('icons/rss16', ['size' => 48]) ?>
    </span>
    <h2>No feeds</h2>
    <p>You have no feed subscriptions. Add a category and a subscription to get started.</p>
    <p class="button-bar">
        <?= $this->Html->link(
            $this->element('icons/plus16') . 'Create a Category',
            $categoryAddUrl,
            [
                'escape' => false,
                'class' => 'button-primary',
                'hx-get' => $this->Url->build($categoryAddUrl),
                'hx-target' => 'main.main',
                'hx-swap' => 'beforeend',
            ]
        ) ?>
        <?= $this->Html->link(
            $this->element('icons/plus16') . 'Add a Feed',
            $feedAddUrl,
            [
                'escape' => false,
                'class' => 'button-primary',
                'hx-get' => $this->Url->build($feedAddUrl),
                'hx-target' => 'main.main',
                'hx-swap' => 'beforeend',
            ]
        ) ?>
    </p>
</div>
