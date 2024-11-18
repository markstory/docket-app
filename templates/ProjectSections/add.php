<?php

declare(strict_types=1);
/**
 * @var \App\Model\Entity\ProjectSection $section
 * @var \App\Model\Entity\Project $project
 * @var string $referer
 */
$this->set('open', true);
$this->setLayout('modal');
?>
<div class="modal-title">
    <h1>Create a section</h1>
    <button class="modal-close" modal-close="true">&#x2715;</button>
</div>
<p>
    Sections help organize tasks in a project into logical chunks.
    When a section is deleted, all the tasks within that section are also deleted.
    Sections in a project can be sorted as you see fit.
</p>
<?php
echo $this->Form->hidden('referer', ['value' => $referer]);
echo $this->Form->create($section, [
    'class' => 'form-narrow',
    // Replace the main view content as we are redirected back to refering url
    'hx-post' => $this->Url->build([
        '_name' => 'projectsections:add',
        'projectSlug' => $project->slug,
        'id' => $section->id,
    ]),
    'hx-target' => 'main.main',
]);
echo $this->Form->control('name');
?>
<div class="button-bar">
    <?= $this->Form->button('Save', [
        'class' => 'button button-primary',
        'data-testid' => 'save-section',
    ]); ?>
</div>
<?= $this->Form->end() ?>
