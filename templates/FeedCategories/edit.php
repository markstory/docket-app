<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\FeedCategory $feedCategory
 */
use Cake\Core\Configure;

$isHtmx = $this->request->is('htmx');

$this->setLayout('sidebar');
if ($isHtmx) {
    $this->set('closable', true);
    $this->setLayout('sheet');
}

?>
<h2>Edit Category</h2>
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
