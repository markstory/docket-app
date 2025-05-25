<?php
/**
 * @var \App\View\AppView $this
 * @var \Feeds\Model\Entity\FeedCategory $feedCategory
 */
use Cake\Core\Configure;

$isHtmx = $this->request->is('htmx');

$this->setLayout('Feeds.feedreader');
if ($isHtmx) {
    $this->setLayout('sheet');
}

$this->assign('title', 'Create Category');
?>
<div class="modal-title">
    <h2>Create Category</h2>
    <button class="modal-close" modal-close="true">&#x2715;</button>
</div>
<?php
echo $this->Form->create($feedCategory, ['class' => 'form-narrow']);
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
