<?php
declare(strict_types=1);
/**
 * @var \App\Model\Entity\Task $task
 * @var \App\Model\Entity\Project[] $projects
 * @var string $referer
 */
$this->setLayout('sidebar');
$this->assign('title', 'Tasks - ' . h($task->title));

$editUrl = ['_name' => 'tasks:edit', 'id' => $task->id];
?>
<div class="task-view">
<?= $this->Form->create($task, [
    'url' => $editUrl,
]) ?>
<?= $this->Form->hidden('redirect', ['value' => $referer]) ?>
<?= $this->Form->checkbox('completed') ?>
<?= $this->Form->control('title') ?>
<?= $this->Form->control('project_id', ['type' => 'projectpicker', 'projects' => $projects]) ?>
<!--
Could make a custom component for this 
Have a dropdown (in a portal) that listens for form submission
event, swallows it and updates the form in the parent form.
-->
<?= $this->Form->control('due_on') ?>
<?= $this->Form->control('body') ?>

<h3>Subtasks</h3>
<ul>
<?php foreach ($task->subtasks ?? [] as $i => $subtask) : ?>
    <li>
    <?= $this->Form->hidden("subtasks.{$i}.id", ['value' => $subtask->id]) ?>
    <?= $this->Form->hidden("subtasks.{$i}.task_id", ['value' => $subtask->task_id]) ?>
    <?= $this->Form->hidden("subtasks.{$i}.ranking", ['value' => $subtask->ranking]) ?>
    <?= $this->Form->checkbox("subtasks.{$i}.completed", ['checked' => $subtask->completed]) ?>
    <?= $this->Form->control("subtasks.{$i}.title", ['value' => $subtask->title]) ?>
<!--
Could do an hx-post to subtask remove endpoint.
Could also remove the element locally and have endpoint overwrite association data.
Removing the row locally could be done with the htmx remove-me extension
-->
    <?= $this->Form->button("Remove", ['value' => $subtask->id]) ?>
    </li>
<?php endforeach ?>
</ul>
<?= $this->Form->button('Save', ['class' => 'button-primary']) ?>
<?= $this->Form->end() ?>
</div>
