<?php
declare(strict_types=1);
/**
 * @var \App\Model\Entity\Task $task
 * @var ?bool $showProject
 * @var ?bool showDueOn
 * @var ?bool $restore
 */
$taskUrl = $this->Url->build(['_name' => 'tasks:view', $task->id]);

$showProject ??= true;
$showDueOn ??= false;
$restore ??= false;

$taskCheckboxUrl = $this->Url->build([
    '_name' => $task->completed ? 'tasks:incomplete' : 'tasks:complete',
    'id' => $task->id,
]);
?>
<div class="dnd-item" data-id="<?= $task->id ?>">
    <button class="dnd-handle" role="button" aria-roledescription="sortable">
        <?= $this->element('icons/grabber24') ?>
    </button>
    <div class="task-row">
        <?= $this->element('Tasks.task_checkbox', [
            'name' => 'completed',
            'checked' => $task->completed,
            'attrs' => [
                'value' => 1,
                'hx-post' => $taskCheckboxUrl,
                'hx-target' => 'main.main',
             ],
        ]) ?>
        <a href="<?= h($taskUrl) ?>" hx-boost="1">
            <span class="title">
                <?= h($task->title) ?>
            </span>
            <div class="attributes">
                <?php if ($showProject ?? false) : ?>
                    <span class="project-badge">
                        <?= $this->element('icons/dot16', ['color' => $task->project->color_hex]) ?>
                        <?= h($task->project->name) ?>
                    </span>
                <?php endif ?>
                <?php
                if ($showDueOn ?? false) :
                    echo $this->element('Tasks.task_due_on', ['task' => $task]);
                endif;
                ?>
                <?php if ($task->subtask_count > 0) : ?>
                    <span class="counter">
                        <?= $this->element('icons/workflow16') ?>
                        <?= h($task->complete_subtask_count) ?>
                        /
                        <?= h($task->subtask_count) ?>
                    </span>
                <?php endif ?>
            </div>
        </a>
        <?= $this->element('Tasks.task_menu', ['task' => $task]) ?>
    </div>
</div>
