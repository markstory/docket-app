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

$newSubtaskIndex = count($task->subtasks) + 1;
?>
<div class="task-view">
<?= $this->Form->create($task, [
    'url' => $editUrl,
]) ?>
<?= $this->Form->hidden('redirect', ['value' => $referer]) ?>

<div class="task-view-summary">
    <div class="title">
        <?= $this->element('task_checkbox') ?>
        <?= $this->Form->text('title') ?>
    </div>
</div>

<!-- TODO figure out how to show sections input -->
<?= $this->Form->control('project_id', ['type' => 'projectpicker', 'projects' => $projects]) ?>
<!--
Could make a custom component for this 
Have a dropdown (in a portal) that listens for form submission
event, swallows it and updates the form in the parent form.
-->
<?= $this->Form->control('due_on') ?>
<div class="task-notes">
    <h4 class="heading-button">
        <?= $this->element('icons/note16') ?>
        Notes
    </h4>
    <?= $this->Form->textarea('body', ['rows' => 5]) ?>
</div>


<div class="task-subtasks">
    <h3>
        <?= $this->element('icons/workflow16') ?>
        Sub-tasks
    </h3>
    <ul>
    <?php foreach ($task->subtasks ?? [] as $i => $subtask) : ?>
        <li>
        <?= $this->Form->hidden("subtasks.{$i}.id", ['value' => $subtask->id]) ?>
        <?= $this->Form->hidden("subtasks.{$i}.task_id", ['value' => $subtask->task_id]) ?>
        <?= $this->Form->hidden("subtasks.{$i}.ranking", ['value' => $subtask->ranking]) ?>
        <?= $this->Form->checkbox("subtasks.{$i}.completed", ['checked' => $subtask->completed]) ?>
        <?= $this->Form->text("subtasks.{$i}.title", ['value' => $subtask->title]) ?>
    <!--
    Could do an hx-post to subtask remove endpoint.
    Could also remove the element locally and have endpoint overwrite association data.
    Removing the row locally could be done with the htmx remove-me extension
    -->
        <?= $this->Form->button("Remove", ['value' => $subtask->id, 'class' => 'button-danger']) ?>
        </li>
    <?php endforeach ?>
    </ul>

    <!-- While this works it creates a new task each time the form is submitted -->
    <div class="add-subtask">
        <?= $this->Form->hidden("subtasks.{$newSubtaskIndex}.task_id", ['value' => $task->id]) ?>
        <?= $this->Form->text("subtasks.{$newSubtaskIndex}.title", ['value' => '', 'placeholder' => 'Create a subtask']) ?>
        <?= $this->Form->button('Add', ['class' => 'button button-primary']) ?>
    </div>
</div>

<?= $this->Form->button('Save', ['class' => 'button-primary']) ?>
<?= $this->Form->end() ?>
</div>
