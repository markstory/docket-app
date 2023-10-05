<?php
declare(strict_types=1);
/**
 * @var Array<\App\Model\Entity\Project> $projects
 */
?>
<div class="dnd-dropper-left-offset">
<?php foreach ($projects as $project) : ?>
    <div class="dnd-item">
        <button class="dnd-handle" role="button">
            <?= $this->element('icons/grabber24') ?>
        </button>
        <?= $this->element('project_item', ['project' => $project]) ?>
    </div>
<?php endforeach; ?>
</div>
