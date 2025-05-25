<?php
declare(strict_types=1);
/**
 * @var \Tasks\Model\Entity\Task $task
 * @var \Tasks\Model\Entity\Project[] $projects
 * @var \Tasks\Model\Entity\ProjectSection[] $sections
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
