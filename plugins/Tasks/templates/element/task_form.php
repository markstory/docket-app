<?php
declare(strict_types=1);
/**
 * @var \Tasks\Model\Entity\Task $task
 * @var \Tasks\Model\Entity\Project[] $projects
 * @var \Tasks\Model\Entity\ProjectSection[] $sections
 * @var string $referer
 * @var array $url
 */
$sectionPickerUrl = ['_name' => 'projectsections:options'];

$newSubtaskIndex = count($task->subtasks) + 1;
?>
<div class="task-view">
<?= $this->Form->create($task, [
    'url' => $url,
    'class' => 'form-stacked form-icon-headers',
]) ?>
<?= $this->Form->hidden('redirect', ['value' => $referer]) ?>

<div class="task-view-summary">
    <div class="task-header">
        <?= $this->element('Tasks.task_checkbox', ['checked' => $task->completed]) ?>
        <?= $this->Form->text('title', [
            'class' => 'task-title-input',
            'aria-label' => 'Task Title',
            'autofocus' => true,
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
            <?= $this->element('../ProjectSections/options', ['sections' => $sections, 'value' => $task->section_id]) ?>
        </div>
        <div class="form-control form-dueon-control">
            <label for="due-on" class="form-section-heading icon-tomorrow">
                <?= $this->element('icons/calendar16') ?> Due On
            </label>
            <div class="form-input form-dueon-control">
                <?= $this->Form->input('due_on', [
                    'type' => 'dueon',
                    'value' => $task,
                    'aria-label' => 'Change the due date',
                ]) ?>
            </div>
        </div>
    </div>

<div class="task-notes">
    <?= $this->element('Tasks.task_body') ?>
</div>

<div class="form-control task-subtasks">
    <h3 class="form-section-heading icon-complete">
        <?= $this->element('icons/workflow16') ?>
        Sub-tasks
    </h3>

    <ul class="task-subtask-list dnd-dropper-left-offset" hx-ext="subtask-sorter" id="subtask-list">
        <?= $this->Form->hidden('subtasks', ['value' => '']) ?>
    <?php foreach ($task->subtasks as $i => $subtask) : ?>
        <li class="task-subtask dnd-item" data-id="<?= h($subtask->id) ?>">
            <button class="dnd-handle" role="button" aria-roledescription="sortable">
                <?= $this->element('icons/grabber24') ?>
            </button>
            <div class="subtask-item">
                <?= $this->Form->hidden("subtasks.{$i}.id", ['value' => $subtask->id]) ?>
                <?= $this->Form->hidden("subtasks.{$i}.task_id", ['value' => $subtask->task_id]) ?>
                <?= $this->Form->hidden("subtasks.{$i}.ranking", ['value' => $subtask->ranking]) ?>
                <?= $this->element('Tasks.task_checkbox', [
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
                    'remove-row-target' => '.task-subtask',
                ]) ?>
            </div>
        </li>
    <?php endforeach ?>
    </ul>

    <div class="subtask-addform">
        <?= $this->Form->text('_subtaskadd', [
            'id' => 'subtask-add-text',
            'value' => '',
            'placeholder' => 'Create a subtask',
        ]) ?>
        <?= $this->Form->button('Add', [
            'type' => 'button',
            'class' => 'button button-secondary',
            'id' => 'subtask-add',
            'data-testid' => 'subtask-add',
        ]) ?>
        <script type="text/template" id="subtask-template">
            <button class="dnd-handle" role="button" aria-roledescription="sortable">
                <?= $this->element('icons/grabber24') ?>
            </button>
            <div class="subtask-item">
                <?= $this->element('Tasks.task_checkbox', [
                    'name' => 'subtasks.{index}.completed',
                    'checked' => false,
                ]) ?>
                <?= $this->Form->text('subtasks.{index}.title', ['value' => '{value}']) ?>
                <?= $this->Form->hidden("subtasks.{index}.ranking", ['value' => '{index}']) ?>
                <?= $this->Form->button($this->element('icons/trash16'), [
                    'type' => 'button',
                    'value' => '',
                    'class' => 'icon-overdue button-icon',
                    'escapeTitle' => false,
                    'hx-ext' => 'remove-row',
                ]) ?>
            </div>
        </script>
        <?= $this->Html->scriptStart(['type' => 'module']) ?>
        (function () {
            const button = document.getElementById('subtask-add');
            button.addEventListener('click', function (event) {
                event.preventDefault();
                const list = document.getElementById('subtask-list');
                const input = document.getElementById('subtask-add-text');
                const index = list.querySelectorAll('li').length;

                let template = document.getElementById('subtask-template').textContent;
                template = template.replaceAll('{index}', index).replaceAll('{value}', input.value);
                const item = document.createElement('li');
                item.classList = 'task-subtask dnd-item';
                item.innerHTML = template;

                list.appendChild(item);
                input.value = '';
                input.focus();
            });
        }());
        <?= $this->Html->scriptEnd() ?>
    </div>
</div>

<div class="button-bar">
    <?= $this->Form->button('Save', ['class' => 'button-primary', 'data-testid' => 'save-task']) ?>
</div>
<?= $this->Form->end() ?>
</div>
