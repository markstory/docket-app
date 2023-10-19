<?php
declare(strict_types=1);
/**
 * @var \App\Model\Entity\Project $project
 * @var \App\Model\Entity\ProjectSection $section
 */
$menuId = 'section-menu-' . uniqid();
$deleteConfirm = ['_name' => 'projectsections:deleteconfirm', 'projectSlug' => $project->slug, 'id' => $section->id];
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
            $this->element('icons/pencil16') . ' Edit Section',
            ['_name' => 'projectsections:edit', 'projectSlug' => $project->slug, 'id' => $section->id],
            ['class' => 'edit', 'escape' => false, 'role' => 'menuitem']
        ) ?>
        <?= $this->Html->link(
            $this->element('icons/trash16') . ' Delete Section',
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
