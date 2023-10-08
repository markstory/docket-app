<?php
declare(strict_types=1);
/**
 * @var \App\Model\Entity\Project $project
 */
$menuId = 'project-menu-' . uniqid();
?>
<div
    hx-ext="dropdown"
    dropdown-trigger=".button-icon"
    dropdown-reveal="#<?= h($menuId) ?>"
>
    <button
        class="button-icon button-default" 
        aria-haspopup="true"
        aria-controls="<?= h($menuId) ?>"
        type="button"
    >
        <?= $this->element('icons/kebab16') ?>
    </button>
    <div id="<?= h($menuId) ?>" style="display:none;">
        <div role="menu" data-reach-menu-list="">
            <div class="separator"></div>
            <?= $this->Html->link(
                $this->element('icons/pencil16') . ' Edit Project',
                ['_name' => 'projects:edit', 'slug' => $project->slug],
                ['class' => 'edit', 'escape' => false, 'role' => 'menuitem', 'data-reach-menu-item' => '',]
            ) ?>
            <!-- todo include add-section and view completed buttons -->
            <div class="separator"></div>
            <?php if ($project->archived) : ?>
                <?= $this->Form->postLink(
                    $this->element('icons/archive16') . ' Unarchive Project',
                    ['_name' => 'projects:unarchive', 'slug' => $project->slug],
                    ['class' => 'archive', 'escape' => false, 'role' => 'menuitem', 'data-reach-menu-item' => '']
                ) ?>

            <?php else : ?>
                <?= $this->Form->postLink(
                    $this->element('icons/archive16') . ' Archive Project',
                    ['_name' => 'projects:archive', 'slug' => $project->slug],
                    ['class' => 'archive', 'escape' => false, 'role' => 'menuitem', 'data-reach-menu-item' => '']
                ) ?>
            <?php endif ?>
            <!--
            todo implement confirm on delete 
            delete could be a GET to fetch the confirm window
            and then a POST/PUT to confirm the deletion.
            That might work better with htmx
            -->
            <?= $this->Form->postLink(
                $this->element('icons/trash16') . ' Delete Project',
                ['_name' => 'projects:delete', 'slug' => $project->slug],
                ['class' => 'delete', 'escape' => false, 'role' => 'menuitem', 'data-reach-menu-item' => '',]
            ) ?>
        </div>
    </div>
</div>
