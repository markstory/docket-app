<?php
declare(strict_types=1);
/**
 * @var Array<\App\Model\Entity\Project> $projects
 */
?>
<?= $this->Form->create(
    null,
    [
        'class' => 'dnd-dropper-left-offset',
        'hx-ext' => 'project-sorter',
        'hx-trigger' => 'end',
        'hx-post' => $this->Url->build(['_name' => 'projects:reorder']),
        'hx-swap' => 'outerHTML',
    ]
) ?>
<?php foreach ($projects as $project) : ?>
    <?php $itemId = 'project-item-' . uniqid(); ?>
    <div class="dnd-item" id="<?= h($itemId) ?>">
        <?= $this->Form->hidden('id[]', ['value' => $project->id]) ?>
        <button class="dnd-handle" role="button" aria-roledescription="sortable">
            <?= $this->element('icons/grabber24') ?>
        </button>
        <?= $this->element('Tasks.project_item', ['project' => $project]) ?>
    </div>
<?php endforeach; ?>
<?= $this->Form->end() ?>
