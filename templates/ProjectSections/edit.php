<?php
declare(strict_types=1);
/**
 * @var \App\Model\Entity\ProjectSection $projectSection
 * @var \App\Model\Entity\Project $project
 */
$this->setLayout('ajax');

$sectionViewUrl = $this->Url->build([
    '_name' => 'projectsections:view',
    'projectSlug' => $project->slug,
    'id' => $section->id
]);

echo $this->Form->create($section, [
    'class' => 'section-quickform form-inline-rename',
    'hx-post' => $this->Url->build(['_name' => 'projectsections:edit', 'projectSlug' => $project->slug, 'id' => $section->id]),
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
        'class' => 'button button-muted',
        'hx-get' => $sectionViewUrl,
        'hx-target' => "#section-controls-{$section->id}",
    ]); ?>
</div>
<?= $this->Form->end() ?>
