<?php
/**
 * @var \App\View\AppView $this
 * @var \Feeds\Model\Entity\FeedCategory $feedCategory
 */
use Cake\Core\Configure;

$isHtmx = $this->request->is('htmx');

$this->setLayout('sidebar');
if ($isHtmx) {
    $this->set('closable', true);
    $this->setLayout('modal');
}

?>
<div class="modal-title">
    <h2>Edit <?= h($feedCategory->title) ?> Category</h2>
    <button class="modal-close" modal-close="true">&#x2715;</button>
</div>
<?php
echo $this->Form->create($feedCategory);
echo $this->Form->control('color', [
    'type' => 'colorpicker',
    'colors' => Configure::read('Colors'),
]);
echo $this->Form->control('title');
?>
<div class="button-bar">
    <?= $this->Form->submit('Save', ['class' => 'button button-primary']) ?>
    <a href="<?= h($referer) ?>" class="button button-muted">
        Cancel
    </a>
</div>
