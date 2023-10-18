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
    <div class="task-group">
        <div class="dnd-dropper-left-offset">
        <?php
        foreach ($tasks as $task):
            echo $this->element('task_item', ['task' => $task, 'showDueOn' => true]);
        endforeach;
        ?>
        </div>
    </div>
</div>
