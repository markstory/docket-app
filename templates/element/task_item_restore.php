<?php
declare(strict_types=1);
/**
 * @var \App\Model\Entity\Task $task
 * @var bool? $showProject
 */
$taskUrl = $this->Url->build(['_name' => 'tasks:view', $task->id]);
$taskRestoreUrl = $this->Url->build(['_name' => 'tasks:undelete', $task->id]);

$showProject ??= true;
$className = 'task-row';
?>
<div class="<?= h($className) ?>">
    <span class="body">
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
            <?php if ($task->subtask_count > 1) : ?>
                <span class="counter">
                    <?= $this->element('icons/workflow16') ?>
                    <?= h($task->complete_subtask_count) ?>
                    /
                    <?= h($task->subtask_count) ?>
                </span>
            <?php endif ?>
        </div>
    </span>
    <?= $this->Form->postButton('Restore', $taskRestoreUrl, [
        'class' => 'button button-secondary',
        'hx-post' => $taskRestoreUrl,
        'hx-target' => 'main.main',
    ]) ?>
</div>
