<?php
declare(strict_types=1);
/**
 * @var \App\Model\Entity\Project> $project
 * @var bool|null $showMenu
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
    </a>
    <?php
    if (isset($showMenu) && $showMenu) :
        echo $this->element('project_menu', ['project' => $project]);
    else : ?>
        <span class="counter"><?= h($project->incomplete_task_count) ?></span>
    <?php endif; ?>
</div>
