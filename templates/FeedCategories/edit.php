<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\FeedCategory $feedCategory
 * @var string[]|\Cake\Collection\CollectionInterface $users
 */
?>
<div class="row">
    <div class="column column-80">
        <div class="feedCategories form content">
            <?= $this->Form->create($feedCategory) ?>
            <fieldset>
                <legend><?= __('Edit Feed Category') ?></legend>
                <?php
                    echo $this->Form->control('user_id', ['options' => $users]);
                    echo $this->Form->control('title');
                    echo $this->Form->control('color');
                    echo $this->Form->control('ranking');
                ?>
            </fieldset>
            <?= $this->Form->button(__('Save')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>

    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Form->postLink(
                __('Delete'),
                ['action' => 'delete', $feedCategory->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $feedCategory->id), 'class' => 'side-nav-item']
            ) ?>
        </div>
    </aside>
</div>
