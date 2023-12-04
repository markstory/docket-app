<?php
declare(strict_types=1);
/**
 * @var \App\Model\Entity\Task $task
 * @var \App\Model\Entity\Project[] $projects
 * @var \App\Model\Entity\ProjectSection[] $sections
 * @var string $referer
 */
$this->set('closable', true);
$this->set('open', true);
$this->setLayout('modal');

$this->assign('title', 'New Task');
?>
<dialog class="task-add">
    <h2>Create a Task</h2>
    <button class="modal-close" modal-close="true">&#x2715;</button>
    <?= $this->element('task_form', [
        'task' => $task,
        'projects' => $projects,
        'sections' => $sections,
        'referer' => $referer,
        'url' => ['_name' => 'tasks:add'],
    ]); ?>
</dialog>
