<?php
declare(strict_types=1);
/**
 * @var Array<\App\Model\Entity\Project> $projects
 */
?>
<div class="dnd-dropper-left-offset"
    hx-ext="project-sorter"
    hx-trigger="end"
    hx-post="<?= $this->Url->build(['_name' => 'projects:reorder']) ?>"
>
    <?php foreach ($projects as $project) : ?>
        <div class="dnd-item">
            <button class="dnd-handle" role="button">
                <?= $this->element('icons/grabber24') ?>
            </button>
            <?= $this->element('project_item', ['project' => $project]) ?>
            <?= $this->element('project_menu', ['project' => $project]) ?>
        </div>
    <?php endforeach; ?>
</div>
