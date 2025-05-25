<?php
declare(strict_types=1);
/**
 * @var \Tasks\Model\Entity\Project> $project
 * @var bool|null $showMenu
 * @var string $url
 */
$url = $this->Url->build(['_name' => 'projects:view', 'slug' => $project->slug]);

$active = strpos($this->request->getPath(), $url) !== false;
?>
<div class="project-item <?= $active ? 'active' : '' ?>">
    <a href="<?= $url ?>" hx-boost="1">
        <span class="project-badge">
            <?= $this->element('icons/dot16', ['color' => $project->color_hex]) ?>
            <span><?= h($project->name) ?></span>
        </span>
    </a>
    <?php
    if (isset($showMenu) && $showMenu) :
        echo $this->element('Tasks.project_menu', ['project' => $project]);
    else : ?>
        <span class="counter"><?= h($project->incomplete_task_count) ?></span>
    <?php endif; ?>
</div>
