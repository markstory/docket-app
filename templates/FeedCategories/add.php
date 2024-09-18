<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\FeedCategory $feedCategory
 * @var \Cake\Collection\CollectionInterface|string[] $users
 */
use Cake\Core\Configure;

$this->setLayout('sidebar');
$this->assign('title', 'New Category');
?>
<h2>Create Category</h2>
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
