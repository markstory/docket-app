<?php
declare(strict_types=1);
/**
 * @var \App\Model\Entity\Task[] $tasks
 * @var \App\Model\Entity\Project[] $projects
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
    <?= $this->element('task_item_restore', ['task' => $task]) ?>
<?php endforeach; ?>
