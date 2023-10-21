<?php
declare(strict_types=1);
/**
 * @var \App\Model\Entity\Project $project
 * @var \App\Model\Entity\ProjectSection $section
 */
// configure layout
$this->set('closable', false);
$this->set('open', true);

$this->setLayout('modal');

echo $this->element('confirm_dialog', [
    'target' => ['_name' => 'projectsections:delete', 'projectSlug' => $project->slug, 'id' => $section->id],
    'title' => 'Are you sure?',
    'description' => 'This will delete all tasks in this section as well.',
]);
