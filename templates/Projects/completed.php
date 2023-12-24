<?php
declare(strict_types=1);
/**
 * @var \App\Model\Entity\Project $project
 * @var \App\Model\Entity\Task[] $tasks
 */
$this->setLayout('sidebar');
$this->assign('title', $project->name . ' Completed Tasks');

?>
<div class="project-view">
    <div class="heading-actions">
        <div class="heading-actions-item">
            <h1 class="heading-icon">
                <?= $this->Html->link(
                    $this->element('icons/arrowleft16') . h($project->name),
                    ['_name' => 'projects:view', 'slug' => $project->slug],
                    ['class' => 'heading-back', 'escape' => false]
                ) ?>
                / Completed Tasks
            </h1>
        </div>
    </div>

    <div class="task-group dnd-dropper-left-offset">
    <?php
    foreach ($tasks as $task) :
        echo $this->element('task_item', ['task' => $task, 'showDueOn' => true]);
    endforeach;
    ?>
    </div>
</div>
