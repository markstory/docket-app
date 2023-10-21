<?php
declare(strict_types=1);
/**
 * @var \App\Model\Entity\Project $project
 * @var \App\Model\Entity\ProjectSection $section
 */
$taskAddUrl = $this->Url->build(['_name' => 'tasks:add', 'project_id' => $project->id]);
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

    <?php // TODO This needs to set the project & section ?>
    <a class="button-icon-primary" data-testid="add-task" href="<?= $taskAddUrl ?>">
        <?= $this->element('icons/plus16') ?>
    </a>
</h3>
<?= $this->element('section_menu', ['section' => $section, 'project' => $project]) ?>
