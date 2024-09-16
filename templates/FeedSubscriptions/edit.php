<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\FeedSubscription $feedSubscription
 * @var string[]|\Cake\Collection\CollectionInterface $feeds
 * @var string[]|\Cake\Collection\CollectionInterface $users
 * @var string[]|\Cake\Collection\CollectionInterface $feedCategories
 * @var string[]|\Cake\Collection\CollectionInterface $feedItems
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Form->postLink(
                __('Delete'),
                ['action' => 'delete', $feedSubscription->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $feedSubscription->id), 'class' => 'side-nav-item']
            ) ?>
            <?= $this->Html->link(__('List Feed Subscriptions'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-80">
        <div class="feedSubscriptions form content">
            <?= $this->Form->create($feedSubscription) ?>
            <fieldset>
                <legend><?= __('Edit Feed Subscription') ?></legend>
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
