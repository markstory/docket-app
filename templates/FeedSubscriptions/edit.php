<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\FeedSubscription $feedSubscription
 * @var string[]|\Cake\Collection\CollectionInterface $feeds
 * @var string[]|\Cake\Collection\CollectionInterface $users
 * @var string[]|\Cake\Collection\CollectionInterface $feedCategories
 * @var string[]|\Cake\Collection\CollectionInterface $feedItems
 */
$isHtmx = $this->request->is('htmx');

$this->setLayout('sidebar');
if ($isHtmx) {
    $this->setLayout('sheet');
}

$this->assign('title', 'Edit Feed');
?>
<div class="modal-title">
    <h2>Edit feed</h2>
    <button class="modal-close" modal-close="true">&#x2715;</button>
</div>
<?php
echo $this->Form->create($feedSubscription);
echo $this->Form->control('url', ['label' => 'Feed URL', 'value' => $feedSubscription->feed->url]);
echo $this->Form->control('alias', ['label' => 'Name']);
echo $this->Form->control('feed_category_id', [
    'options' => $feedCategories,
]);
?>
<div class="button-bar">
    <?= $this->Form->submit('Save', ['class' => 'button button-primary']) ?>
</div>
<?= $this->Form->end() ?>
