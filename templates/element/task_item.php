<?php
declare(strict_types=1);
/**
 * @var App\Model\Entity\Task $task
 * @var bool $showProject
 * @var bool showDueOn
 */
$taskUrl = $this->Url->build(['_name' => 'tasks:view', $task->id]);

// TODO implement restore view for trashbin
?>
<div class="task-row">
    <input type="checkbox" value="<?= $task->id ?>" />
    <a href="<?= h($taskUrl) ?>">
        <span class="title">
            <?= h($task->title) ?>
        </span>
        <div class="attributes">
            <?php if ($showProject ?? false): ?>
                <span class="project-badge">
                    <?= $this->element('icons/dot16', ['color' => $task->project->color_hex]) ?>
                    <?= h($task->project->name) ?>
                </span>
            <?php endif ?>
            <?php
            if ($showDueOn ?? false):
                // TODO implement an element for dueOn.
                echo h($task->dueOn);
            endif;
            ?>
            <?php if ($task->subtask_count > 1): ?>
                <span class="counter">
                    <?= $this->element('icons/workflow16') ?>
                    <?= $task->complete_subtask_count ?>
                    /
                    <?= $task->subtask_count ?>
                </span>
            <?php endif ?>
        </div>
    </a>
    <?= $this->element('task_menu', ['task' => $task]) ?>
</div>
