<?php
declare(strict_types=1);

use Cake\Core\Configure;

/**
 * @var \App\Model\Entity\Project $project
 * @var string $referer
 */
$this->setLayout('sidebar');
$this->assign('title', 'New Project');
?>
<h2>New Project</h2>
<?php
echo $this->Form->create(
    $project,
    ['class' => 'form-narrow']
);
echo $this->Form->hidden('referer', ['value' => $referer]);
echo $this->Form->control('name', ['autofocus' => true]);
echo $this->Form->control('color', [
    'type' => 'colorpicker',
    'colors' => Configure::read('Colors'),
]);
?>
<div class="button-bar">
    <?= $this->Form->submit('Save', ['class' => 'button button-primary']) ?>
    <a href="<?= h($referer) ?>" class="button button-muted">
        Cancel
    </a>
</div>
<?= $this->Form->end() ?>
