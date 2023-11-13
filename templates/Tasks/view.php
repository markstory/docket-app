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
<?= $this->Form->button('Save', ['class' => 'button-primary']) ?>
<?= $this->Form->end() ?>
</div>
