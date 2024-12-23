<?php
declare(strict_types=1);
/**
 * @var \App\Model\Entity\Task $task
 * @var \Cake\View\View $this
 */
echo $this->element('confirm_dialog', [
    'target' => ['_name' => 'tasks:delete', 'id' => $task->id],
    'title' => 'Are you sure?',
    'description' => 'This will also delete all subtasks this task has.',
]);
