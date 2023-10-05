<?php
declare(strict_types=1);
/**
 * @var \App\Model\Entity\Project> $project
 */
// TODO implement active page highlight

$url = $this->Url->build(['_path' => 'Projects::view', 'slug' => $project->slug]);
?>
<div class="project-item">
    <a href="<?= $url ?>">
        <span class="project-badge">
            <?= $this->element('icons/dot16', ['color' => $project->color_hex]) ?>
            <span><?= h($project->name) ?></span>
        </span>
        <span class="counter"><?= h($project->incomplete_task_count) ?></span>
    </a>
</div>
