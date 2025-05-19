<?php
declare(strict_types=1);
/**
 * @var \App\Model\Entity\Project $project
 * @var \App\Model\Entity\Task[] $tasks
 */
$this->setLayout('sidebar');
$this->assign('title', 'Project - ' . $project->name);

$this->set('showGlobalAdd', true);
$this->set('globalAddContext', ['project_id' => $project->id]);

$taskAddUrl = $this->Url->build(['_name' => 'tasks:add', '?' => ['project_id' => $project->id]]);

$groupedTasks = [];
foreach ($tasks as $task) {
    $groupedTasks[$task->section_id ?? ''][] = $task;
}
?>
<reload-after timestamp="<?= strtotime('+30 minutes') ?>"></reload-after>

<keyboard-list
    class="project-view"
    itemselector=".task-row"
    toggleselector="input[type='checkbox']"
    openselector="a"
>
    <div class="heading-actions" data-archived="<?= $project->archived ?>">
        <div class="heading-actions-item">
            <h1 class="heading-icon">
                <?php
                if ($project->archived) :
                    echo $this->element('icons/archive16');
                endif; ?>
                <?= h($project->name) ?>
            </h1>
            <?php if (!$project->archived) : ?>
                <?= $this->Html->link(
                    $this->element('icons/plus16'),
                    $taskAddUrl,
                    [
                        'escape' => false,
                        'class' => 'button-icon-primary',
                        'data-testid' => 'add-task',
                        'hx-get' => $taskAddUrl,
                        'hx-target' => 'main.main',
                        'hx-swap' => 'beforeend',
                    ]
                ) ?>
            <?php endif; ?>
        </div>
        <?= $this->element('Tasks.project_menu', ['project' => $project, 'showDetailed' => true]) ?>
    </div>

    <?php if (empty($groupedTasks)) : ?>
        <?= $this->element('Tasks.tasks_empty') ?>
    <?php endif ?>

    <?php // Tasks with no section ?>
    <div
        class="task-group dnd-dropper-left-offset"
        hx-ext="task-sorter"
        task-sorter-attr="child_order"
        task-sorter-section=""
    >
    <?php
    foreach ($groupedTasks[''] ?? [] as $task) :
        echo $this->element('Tasks.task_item', ['task' => $task, 'showDueOn' => true]);
    endforeach;
    ?>
    </div>

    <div hx-ext="section-sorter" section-sorter-slug="<?= h($project->slug) ?>">
        <?php // Tasks in sections ?>
        <?php foreach ($project->sections as $section) : ?>
        <div class="section-container" data-testid="section" data-id="<?= h($section->id) ?>">
            <div class="controls" id="section-controls-<?= h($section->id) ?>">
                <?= $this->element('Tasks.projectsection_item', [
                    'project' => $project,
                    'section' => $section,
                ]) ?>
            </div>
            <div
                class="task-group dnd-dropper-left-offset"
                hx-ext="task-sorter"
                task-sorter-attr="child_order"
                task-sorter-section="<?= h($section->id) ?>"
            >
            <?php
            foreach ($groupedTasks[$section->id] ?? [] as $task) :
                echo $this->element('Tasks.task_item', ['task' => $task, 'showDueOn' => true]);
            endforeach;
            ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</keyboard-list>
