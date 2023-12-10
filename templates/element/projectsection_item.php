<?php
declare(strict_types=1);
/**
 * @var \App\Model\Entity\Project $project
 * @var \App\Model\Entity\ProjectSection $section
 */
$taskAddUrl = $this->Url->build([
    '_name' => 'tasks:add',
    '?' => ['project_id' => $project->id, 'section_id' => $section->id],
]);
?>
<h3 class="heading">
    <button class="dnd-handle" role="button" aria-roledescription="sortable">
        <?= $this->element('icons/grabber24') ?>
    </button>
    <?php
    $sectionEditUrl = $this->Url->build([
        '_name' => 'projectsections:edit',
        'projectSlug' => $project->slug,
        'id' => $section->id,
    ]);
    ?>
    <span
        class="editable"
        hx-get="<?= h($sectionEditUrl) ?>"
        hx-target="#section-controls-<?= h($section->id) ?>"
    >
        <?= h($section->name) ?>
    </span>

    <?= $this->Html->link(
        $this->element('icons/plus16'),
        $taskAddUrl,
        [
            'escape' => false,
            'class' => 'button-icon-primary',
            'data-testid' => 'section-add-task',
            'hx-get' => $taskAddUrl,
            'hx-target' => 'main.main',
            'hx-swap' => 'beforeend',
        ]
    ) ?>
</h3>
<?= $this->element('section_menu', ['section' => $section, 'project' => $project]) ?>
