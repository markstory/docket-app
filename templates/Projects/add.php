<?php
declare(strict_types=1);
/**
 * @var \App\Model\Entity\Project $project
 * @var string $referer
 */
$this->setLayout('sidebar');
?>
<h2>New Project</h2>
<?php
echo $this->Form->create(
    $project,
    ['class' => 'form-narrow']
);
echo $this->Form->control('name');
echo $this->Form->control('color', [
    'type' => 'colorpicker',
    'colors' => $project->getColors(),
]);
?>
<div class="button-bar">
    <?= $this->Form->submit('Save', ['class' => 'button button-primary']) ?>
    <a href="<?= h($referer) ?>" class="button button-muted">
        Cancel
    </a>
</div>
<?= $this->Form->end() ?>
