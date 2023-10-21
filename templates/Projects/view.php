<?php
declare(strict_types=1);
/**
 * @var \App\Model\Entity\Project $project
 * @var \App\Model\Entity\Task[] $tasks
 * @var \App\Model\Entity\Task[] $completed
 */
$this->setLayout('sidebar');
$this->assign('title', $project->name . " Project");

$taskAddUrl = $this->Url->build(['_name' => 'tasks:add', 'project_id' => $project->id]);

$groupedTasks = [];
foreach ($tasks as $task) {
    $groupedTasks[$task->section_id ?? ''][] = $task;
}
?>
<div class="project-view">
    <div class="heading-actions" data-archived="<?= $project->archived ?>">
        <div class="heading-actions-item">
            <!-- TODO implement editing -->
            <h1 class="heading-icon editable">
                <?php
                if ($project->archived):
                    echo $this->element('icons/archive16');
                endif; ?>
                <?= h($project->name) ?>
            </h1>
            <?php if (!$project->archived): ?>
                <a class="button-icon-primary" data-testid="add-task" href="<?= $taskAddUrl ?>">
                    <?= $this->element('icons/plus16') ?>
                </a>
            <?php endif; ?>
        </div>
        <?= $this->element('project_menu', ['project' => $project, 'showDetailed' => true]) ?>
    </div>

    <?php // Tasks with no section ?>
    <div
        class="task-group dnd-dropper-left-offset"
        hx-ext="task-sorter"
        task-sorter-attr="child_order"
        task-sorter-section=""
    >
    <?php
    foreach ($groupedTasks[''] as $task):
        echo $this->element('task_item', ['task' => $task, 'showDueOn' => true]);
    endforeach;
    ?>
    </div>

    <? // Tasks in sections ?>
    <?php foreach ($project->sections as $section): ?>
    <div class="section-container" data-testid="section">
        <div class="controls">
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
                    hx-target="closest .controls"
                >
                    <?= h($section->name) ?>
                </span>

                <?php // This needs to set the project & section ?>
                <a class="button-icon-primary" data-testid="add-task" href="<?= $taskAddUrl ?>">
                    <?= $this->element('icons/plus16') ?>
                </a>
            </h3>
            <?= $this->element('section_menu', ['section' => $section, 'project' => $project]) ?>
        </div>
        <div
            class="task-group dnd-dropper-left-offset"
            hx-ext="task-sorter"
            task-sorter-attr="child_order"
            task-sorter-section="<?= h($section->id) ?>"
        >
        <?php
        foreach ($groupedTasks[$section->id] ?? [] as $task):
            echo $this->element('task_item', ['task' => $task, 'showDueOn' => true]);
        endforeach;
        ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
