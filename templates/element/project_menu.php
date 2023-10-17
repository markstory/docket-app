<?php
declare(strict_types=1);
/**
 * @var \App\Model\Entity\Project $project
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
            ['class' => 'edit', 'escape' => false, 'role' => 'menuitem']
        ) ?>
        <!-- todo include add-section and view completed buttons -->
        <div class="separator"></div>
        <?php if ($project->archived) : ?>
            <?= $this->Form->postLink(
                $this->element('icons/archive16') . ' Unarchive Project',
                ['_name' => 'projects:unarchive', 'slug' => $project->slug],
                ['class' => 'archive', 'escape' => false, 'role' => 'menuitem']
            ) ?>

        <?php else : ?>
            <?= $this->Form->postLink(
                $this->element('icons/archive16') . ' Archive Project',
                ['_name' => 'projects:archive', 'slug' => $project->slug],
                ['class' => 'archive', 'escape' => false, 'role' => 'menuitem']
            ) ?>
        <?php endif ?>
        <?= $this->Html->link(
            $this->element('icons/trash16') . ' Delete Project',
            $deleteConfirm,
            [
                'class' => 'delete',
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
