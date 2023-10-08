<?php
declare(strict_types=1);
/**
 * @var \App\Model\Entity\Project $project
 * @var string $targetId
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
            <?= $this->Html->link(
                $this->element('icons/pencil16') . ' Edit Project',
                ['_name' => 'projects:edit', 'slug' => $project->slug],
                ['class' => 'edit', 'escape' => false, 'role' => 'menuitem', 'data-reach-menu-item' => '',]
            ) ?>
            <!-- todo include add-section and view completed buttons -->
            <div class="separator"></div>
            <?php if ($project->archived) : ?>
                <a
                    class="archive"
                    href=""
                    role="menuitem"
                    data-reach-menu-item=""
                    hx-post="<?= $this->Url->build(['_name' => 'projects:unarchive', $project->slug]) ?>"
                    hx-target="#<?= $targetId ?>"
                    hx-swap="outerHTML"
                >
                    <?= $this->element('icons/archive16') ?> Unarchive Project
                </a>
            <?php else : ?>
                <a
                    class="archive"
                    href=""
                    role="menuitem"
                    data-reach-menu-item=""
                    hx-post="<?= $this->Url->build(['_name' => 'projects:archive', $project->slug]) ?>"
                    hx-target="#<?= $targetId ?>"
                    hx-swap="outerHTML"
                >
                    <?= $this->element('icons/archive16') ?> Archive Project
                </a>
            <?php endif ?>
            <?php /* 
            TODO: implement confirm on delete 
            delete could be a GET to fetch the confirm window
            and then a POST/PUT to confirm the deletion.
            That might work better with htmx
            */ ?>
            <a
                class="delete"
                href=""
                role="menuitem"
                data-reach-menu-item=""
                hx-post="<?= $this->Url->build(['_name' => 'projects:delete', $project->slug]) ?>"
                hx-target="#<?= $targetId ?>"
                hx-swap="outerHTML"
            >
                <?= $this->element('icons/trash16') ?> Delete Project
            </a>
        </div>
    </div>
</div>
