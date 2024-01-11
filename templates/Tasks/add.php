<?php
declare(strict_types=1);
/**
 * @var \App\Model\Entity\Task $task
 * @var \App\Model\Entity\Project[] $projects
 * @var \App\Model\Entity\ProjectSection[] $sections
 * @var string $referer
 */
$isHtmx = $this->request->is('htmx');

$this->setLayout('sidebar');
if ($isHtmx) {
    $this->set('closable', true);
    $this->setLayout('sheet');
}

$this->assign('title', 'New Task');
?>
<div class="task-add-contents">
    <div class="modal-title">
        <h2>Create a Task</h2>
        <button class="modal-close" modal-close="true">&#x2715;</button>
    </div>
    <?= $this->element('task_form', [
        'task' => $task,
        'projects' => $projects,
        'sections' => $sections,
        'referer' => $referer,
        'url' => ['_name' => 'tasks:add'],
    ]); ?>
</div>
