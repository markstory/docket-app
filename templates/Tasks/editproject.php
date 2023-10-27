<?php
declare(strict_types=1);
/**
 * @var \App\Model\Entity\Task $task
 * @var Array<\App\Model\Entity\Project> $projects
 * @var string $referer
 */
$this->setLayout('ajax');

$this->response = $this->response->withHeader('Hx-Trigger-After-Swap', 'reposition');

$taskEditUrl = ['_name' => 'tasks:edit', 'id' => $task->id];

echo $this->Form->create($task, ['url' => $taskEditUrl]);
echo $this->Form->hidden('refresh', ['value' => 1]);
?>
    <h4 class="dropdown-item-header">Move to project</h4>
    <div role="menuitem">
        <?= $this->Form->input('project_id', [
            'type' => 'projectpicker',
            'projects' => $projects,
            'val' => $task->project_id,
            // Submit to the task update endpoint and remove the
            // menu from the dom
            'hx-post' => $this->Url->build($taskEditUrl),
            'hx-trigger' => 'selected',
            'hx-target' => 'closest form',
            'hx-swap' => 'delete',
        ]) ?>
    </div>
<?= $this->Form->end() ?>
