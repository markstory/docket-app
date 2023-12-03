<?php
declare(strict_types=1);
/**
 * @var \App\Model\Entity\Project $project
 * @var bool $showDetailed
 */
$menuId = 'project-menu-' . uniqid();
$deleteConfirm = ['_name' => 'projects:deleteConfirm', 'slug' => $project->slug];
?>
<drop-down>
    <button
        class="button-icon button-default"
        aria-haspopup="true"
        aria-controls="<?= h($menuId) ?>"
        type="button"
    >
        <?= $this->element('icons/kebab16') ?>
    </button>
    <drop-down-menu id="<?= h($menuId) ?>" role="menu">
        <?= $this->Html->link(
            $this->element('icons/pencil16') . ' Edit Project',
            ['_name' => 'projects:edit', 'slug' => $project->slug],
            ['class' => 'icon-edit', 'escape' => false, 'role' => 'menuitem']
        ) ?>
        <?php if (!empty($showDetailed)) : ?>
            <?= $this->Html->link(
                $this->element('icons/plus16') . ' Add Section',
                ['_name' => 'projectsections:add', 'projectSlug' => $project->slug],
                [
                    'class' => 'icon-complete',
                    'escape' => false,
                    'role' => 'menuitem',
                    'data-testid' => 'add-section',
                    'hx-get' => $this->Url->build(['_name' => 'projectsections:add', 'projectSlug' => $project->slug]),
                    'hx-target' => 'body',
                    'hx-swap' => 'beforeend',
                ]
            ) ?>
            <?= $this->Html->link(
                $this->element('icons/check16') . ' View Completed Tasks',
                ['_name' => 'projects:view', 'slug' => $project->slug, '?' => ['completed' => 1]],
                ['class' => 'icon-complete', 'escape' => false, 'role' => 'menuitem']
            ) ?>
        <?php endif ?>
        <div class="separator"></div>
        <?php if ($project->archived) : ?>
            <?= $this->Form->postLink(
                $this->element('icons/archive16') . ' Unarchive Project',
                ['_name' => 'projects:unarchive', 'slug' => $project->slug],
                ['class' => 'icon-archive', 'escape' => false, 'role' => 'menuitem']
            ) ?>

        <?php else : ?>
            <?= $this->Form->postLink(
                $this->element('icons/archive16') . ' Archive Project',
                ['_name' => 'projects:archive', 'slug' => $project->slug],
                ['class' => 'icon-archive', 'escape' => false, 'role' => 'menuitem']
            ) ?>
        <?php endif ?>
        <?= $this->Html->link(
            $this->element('icons/trash16') . ' Delete Project',
            $deleteConfirm,
            [
                'class' => 'icon-delete',
                'escape' => false,
                'role' => 'menuitem',
                'dropdown-close' => true,
                'hx-get' => $this->Url->build($deleteConfirm),
                'hx-target' => 'body',
                'hx-swap' => 'beforeend',
            ]
        ) ?>
    </drop-down-menu>
</drop-down>
