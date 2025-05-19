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

echo $this->element('Tasks.task_form', [
    'task' => $task,
    'projects' => $projects,
    'sections' => $sections,
    'referer' => $referer,
    'url' => ['_name' => 'tasks:edit', 'id' => $task->id],
]);
