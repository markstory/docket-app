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
// Store the referer for this menu so that we can use it as a redirect
// at the end of this operation.
echo $this->Form->hidden('redirect', ['value' => $referer]);
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
            'hx-target' => 'main.main',
        ]) ?>
    </div>
<?= $this->Form->end() ?>
