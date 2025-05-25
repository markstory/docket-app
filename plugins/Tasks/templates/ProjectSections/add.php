<?php

declare(strict_types=1);
/**
 * @var \Tasks\Model\Entity\ProjectSection $section
 * @var \Tasks\Model\Entity\Project $project
 * @var string $referer
 */
$this->setLayout('modal');
$this->assign('title', 'Create a section');
?>
<div class="modal-title">
    <h1>Create a section</h1>
    <button class="modal-close" modal-close="true">&#x2715;</button>
</div>
<p class="form-modal">
    Sections help organize tasks in a project into logical chunks.
    When a section is deleted, all the tasks within that section are also deleted.
    Sections in a project can be sorted as you see fit.
</p>
<?php
echo $this->Form->create($section, [
    'class' => 'form-modal',
]);
echo $this->Form->hidden('referer', ['value' => $referer]);
echo $this->Form->control('name');
?>
<div class="button-bar">
    <?= $this->Form->button('Save', [
        'class' => 'button button-primary',
        'data-testid' => 'save-section',
    ]); ?>
</div>
<?= $this->Form->end() ?>
