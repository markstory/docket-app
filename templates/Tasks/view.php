<?php
declare(strict_types=1);
/**
 * @var \App\Model\Entity\Task $task
 * @var \App\Model\Entity\Project[] $projects
 * @var \App\Model\Entity\ProjectSection[] $sections
 * @var string $referer
 */
$this->setLayout('sidebar');
$this->assign('title', 'Tasks - ' . h($task->title));

$editUrl = ['_name' => 'tasks:edit', 'id' => $task->id];
$sectionPickerUrl = ['_name' => 'tasks:viewmode', 'mode' => 'projectsection', 'id' => $task->id];

$newSubtaskIndex = count($task->subtasks) + 1;
?>
<div class="task-view">
<?= $this->Form->create($task, [
    'url' => $editUrl,
    'class' => 'form-stacked form-icon-headers',
]) ?>
<?= $this->Form->hidden('redirect', ['value' => $referer]) ?>

<div class="task-view-summary">
    <div class="task-header">
        <?= $this->element('task_checkbox') ?>
        <?= $this->Form->text('title', [
            'class' => 'task-title-input',
        ]) ?>
    </div>
    <!-- TODO figure out how to show sections input -->
    <?= $this->Form->control('project_id', [
        'label' => [
            'class' => 'form-section-heading icon-today',
            'text' => $this->element('icons/directory16') . 'Project',
            'escape' => false,
        ],
        'type' => 'projectpicker',
        'projects' => $projects,
        'hx-get' => $this->Url->build($sectionPickerUrl),
        'hx-target' => '#task-section-container',
        'hx-trigger' => 'selected',
        // TODO add loading indicator
    ]) ?>
    <div id="task-section-container">
    <?php
    if ($task->section_id || count($sections)) :
        echo $this->element('../Tasks/projectsection', ['sections' => $sections]);
    endif;
    ?>
    </div>
    <!--
    Could make a custom component for this 
    Have a dropdown (in a portal) that listens for form submission
    event, swallows it and updates the form in the parent form.
    -->
    <?= $this->Form->control('due_on', [
        'label' => [
            'class' => 'form-section-heading icon-tomorrow',
            'text' => $this->element('icons/calendar16') . 'Due On',
            'escape' => false,
        ],
    ]) ?>
</div>

<div class="task-notes">
<?= $this->Form->control('body', [
    'label' => [
        'class' => 'form-section-heading icon-not-due',
        'text' => $this->element('icons/note16') . 'Notes',
        'escape' => false,
    ],
    'rows' => 5,
]) ?>
</div>

<div class="form-control">
    <h3 class="form-section-heading icon-week">
        <?= $this->element('icons/workflow16') ?>
        Sub-tasks
    </h3>
    <ul class="task-subtask-list">
    <?php foreach ($task->subtasks ?? [] as $i => $subtask) : ?>
        <li class="task-subtask">
        <?= $this->Form->hidden("subtasks.{$i}.id", ['value' => $subtask->id]) ?>
        <?= $this->Form->hidden("subtasks.{$i}.task_id", ['value' => $subtask->task_id]) ?>
        <?= $this->Form->hidden("subtasks.{$i}.ranking", ['value' => $subtask->ranking]) ?>
        <?= $this->element('task_checkbox', ['name' => "subtasks.{$i}.completed", 'checked' => $subtask->completed]) ?>
        <?= $this->Form->text("subtasks.{$i}.title", ['value' => $subtask->title]) ?>
    <!--
    Could do an hx-post to subtask remove endpoint.
    Could also remove the element locally and have endpoint overwrite association data.
    Removing the row locally could be done with the htmx remove-me extension
    -->
    <?= $this->Form->button($this->element('icons/trash16'), [
    'value' => $subtask->id,
    'class' => 'icon-overdue button-icon',
    'escapeTitle' => false,
]) ?>
        </li>
    <?php endforeach ?>
    </ul>

    <!-- While this works it creates a new task each time the form is submitted -->
    <div class="subtask-addform">
        <?= $this->Form->hidden("subtasks.{$newSubtaskIndex}.task_id", ['value' => $task->id]) ?>
        <?= $this->Form->text("subtasks.{$newSubtaskIndex}.title", ['value' => '', 'placeholder' => 'Create a subtask']) ?>
        <?= $this->Form->button('Add', ['class' => 'button button-secondary']) ?>
    </div>
</div>

<?= $this->Form->button('Save', ['class' => 'button-primary']) ?>
<?= $this->Form->end() ?>
</div>
