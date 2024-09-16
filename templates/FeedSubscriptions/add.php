<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\FeedSubscription $feedSubscription
 * @var \Cake\Collection\CollectionInterface|string[] $feeds
 * @var \Cake\Collection\CollectionInterface|string[] $users
 * @var \Cake\Collection\CollectionInterface|string[] $feedCategories
 * @var \Cake\Collection\CollectionInterface|string[] $feedItems
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('List Feed Subscriptions'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="feedSubscriptions form content">
            <?= $this->Form->create($feedSubscription) ?>
            <fieldset>
                <legend><?= __('Add Feed Subscription') ?></legend>
                <?php
                    echo $this->Form->control('feed_id', ['options' => $feeds]);
                    echo $this->Form->control('user_id', ['options' => $users]);
                    echo $this->Form->control('feed_category_id', ['options' => $feedCategories]);
                    echo $this->Form->control('alias');
                    echo $this->Form->control('ranking');
                    echo $this->Form->control('feed_items._ids', ['options' => $feedItems]);
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
