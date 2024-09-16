<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\FeedCategory $feedCategory
 * @var \Cake\Collection\CollectionInterface|string[] $users
 */
?>
<div class="row">
    <div class="column">
        <div class="feedCategories form content">
            <?= $this->Form->create($feedCategory) ?>
            <fieldset>
                <legend><?= __('Add Feed Category') ?></legend>
                <?php
                    echo $this->Form->control('title');
                    echo $this->Form->control('color');
                ?>
            </fieldset>
            <?= $this->Form->button(__('Save')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
