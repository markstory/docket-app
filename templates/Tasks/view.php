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
            'aria-label' => 'Task Title',
        ]) ?>
    </div>
    <div class="task-attributes">
        <?= $this->Form->control('project_id', [
            'label' => [
                'class' => 'form-section-heading icon-today',
                'text' => $this->element('icons/directory16') . 'Project',
                'escape' => false,
            ],
            'type' => 'projectpicker',
            'projects' => $projects,
            'inputAttrs' => [
                'hx-get' => $this->Url->build($sectionPickerUrl),
                'hx-target' => '#task-section-container',
            ],
            // TODO add loading indicator
        ]) ?>
        <div id="task-section-container">
            <?= $this->element('../Tasks/projectsection', ['sections' => $sections]) ?>
        </div>
        <?= $this->Form->control('due_on', [
            'label' => [
                'class' => 'form-section-heading icon-tomorrow',
                'text' => $this->element('icons/calendar16') . 'Due On',
                'escape' => false,
            ],
            'type' => 'dueon',
            'value' => $task,
        ]) ?>
    </div>
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

<div class="form-control task-subtasks">
    <h3 class="form-section-heading icon-week">
        <?= $this->element('icons/workflow16') ?>
        Sub-tasks
    </h3>
    <?php if (count($task->subtasks)) : ?>
        <ul class="task-subtask-list dnd-dropper-left-offset" hx-ext="subtask-sorter">
        <?php foreach ($task->subtasks as $i => $subtask) : ?>
            <li class="task-subtask dnd-item" data-id="<?= h($subtask->id) ?>">
                <button class="dnd-handle" role="button" aria-roledescription="sortable">
                    <?= $this->element('icons/grabber24') ?>
                </button>
                <div class="subtask-item">
                    <?= $this->Form->hidden("subtasks.{$i}.id", ['value' => $subtask->id]) ?>
                    <?= $this->Form->hidden("subtasks.{$i}.task_id", ['value' => $subtask->task_id]) ?>
                    <?= $this->Form->hidden("subtasks.{$i}.ranking", ['value' => $subtask->ranking]) ?>
                    <?= $this->element('task_checkbox', [
                        'name' => "subtasks.{$i}.completed",
                        'checked' => $subtask->completed,
                    ]) ?>
                    <?= $this->Form->text("subtasks.{$i}.title", ['value' => $subtask->title]) ?>
                    <?= $this->Form->button($this->element('icons/trash16'), [
                        'type' => 'button',
                        'value' => $subtask->id,
                        'class' => 'icon-overdue button-icon',
                        'escapeTitle' => false,
                        'hx-ext' => 'remove-row',
                    ]) ?>
                </div>
            </li>
        <?php endforeach ?>
        </ul>
    <?php endif; ?>

    <div class="subtask-addform">
        <?= $this->Form->hidden("subtasks.{$newSubtaskIndex}.task_id", ['value' => $task->id]) ?>
        <?= $this->Form->text("subtasks.{$newSubtaskIndex}.title", [
            'value' => '',
            'placeholder' => 'Create a subtask',
        ]) ?>
        <?= $this->Form->button('Add', ['class' => 'button button-secondary', 'name' => 'subtask_add', 'value' => 1]) ?>
    </div>
</div>

<div class="button-bar">
    <?= $this->Form->button('Save', ['class' => 'button-primary']) ?>
</div>
<?= $this->Form->end() ?>
</div>
