<?php
declare(strict_types=1);
/**
 * @var \Tasks\Model\Entity\Task[] $tasks
 * @var \Tasks\Model\Entity\Project[] $projects
 */
$this->assign('title', 'Trash Bin');

$this->setLayout('sidebar');
?>
<h2 class="heading-icon trash">
    <?= $this->element('icons/trash16') ?>
    Trash Bin
</h2>
<p>Trash tasks will be deleted permanently after 14 days</p>
<?php foreach ($tasks as $task) : ?>
    <?= $this->element('Tasks.task_item_restore', ['task' => $task]) ?>
<?php endforeach; ?>
