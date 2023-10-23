<?php
declare(strict_types=1);
/**
 * @var \App\Model\Entity\Task $task
 * @var bool $showProject
 * @var bool showDueOn
 */
$taskUrl = $this->Url->build(['_name' => 'tasks:view', $task->id]);

$taskCheckboxUrl = $this->Url->build([
    '_name' => $task->completed ? 'tasks:incomplete' : 'tasks:complete',
    'id' => $task->id,
]);

// TODO implement restore view for trashbin
?>
<div class="dnd-item" data-id="<?= $task->id ?>">
    <button class="dnd-handle" role="button" aria-roledescription="sortable">
        <?= $this->element('icons/grabber24') ?>
    </button>
    <div class="task-row">
        <?php // Use fancy custom checkbox instead ?>
        <?= $this->Form->checkbox('completed', [
            'checked' => $task->completed,
            'hiddenField' => false,
            'value' => 1,
            'hx-delete' => $taskCheckboxUrl,
            'hx-target' => 'closest .dnd-item',
            'hx-swap' => 'outerHTML swap:500ms',
        ]) ?>
        <a href="<?= h($taskUrl) ?>">
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
                    echo $this->element('task_due_on', ['task' => $task]);
                endif;
                ?>
                <?php if ($task->subtask_count > 1) : ?>
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
</div>
