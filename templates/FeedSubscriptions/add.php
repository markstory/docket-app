<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\FeedSubscription $feedSubscription
 */
use Cake\Core\Configure;

$isHtmx = $this->request->is('htmx');

$this->setLayout('sidebar');
if ($isHtmx) {
    $this->setLayout('sheet');
}

$this->assign('title', 'Add Feed');
?>
<div class="modal-title">
    <h2>Add feed</h2>
    <button class="modal-close" modal-close="true">&#x2715;</button>
</div>
<?php
echo $this->Form->create($feedSubscription);
echo $this->Form->control('url', ['label' => 'Feed URL']);
echo $this->Form->control('alias', ['label' => 'Name']);
echo $this->Form->control('feed_category_id', [
    'options' => $feedCategories,
]);
?>
<div class="button-bar">
    <?= $this->Form->submit('Save', ['class' => 'button button-primary']) ?>
    <a href="<?= h($referer) ?>" class="button button-muted">
        Cancel
    </a>
</div>
