<?php
declare(strict_types=1);
/**
 * @var \App\Model\Entity\Project $project
 */
// configure layout
$this->set('closable', false);
$this->set('open', true);

$this->setLayout('modal');

echo $this->element('confirm_dialog', [
    'target' => ['_name' => 'projects:delete', 'slug' => $project->slug],
    'title' => 'Are you sure?',
    'description' => 'This will delete all tasks in this project as well.',
]);
