<?php
declare(strict_types=1);
/**
 * @var \App\Model\Entity\ProjectSection $projectSection
 * @var \App\Model\Entity\Project $project
 */
$this->setLayout('ajax');

echo $this->Form->create($projectSection, [
    'class' => 'section-quickform form-inline-rename',
    'hx-post' => $this->Url->build(['_name' => 'projectsections:edit', 'projectSlug' => $project->slug, 'id' => $projectSection->id]),
    'hx-target' => 'main.main',
]);
?>
<div class="title">
    <?= $this->Form->text('name') ?>
</div>
<div class="button-bar button-bar-inline">
    <?= $this->Form->button('Save', [
        'class' => 'button button-primary',
        'data-testid' => 'save-section',
    ]); ?>
    <?= $this->Form->button('Cancel', [
        // TODO implement cancel by fetching the view fragment or restoring?
        'class' => 'button button-muted',
    ]); ?>
</div>
<?= $this->Form->end() ?>
