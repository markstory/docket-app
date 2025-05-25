<?php
declare(strict_types=1);
/**
 * @var \Tasks\Model\Entity\Project $project
 * @var \Cake\View\View $this
 */
echo $this->element('confirm_dialog', [
    'target' => ['_name' => 'projects:delete', 'slug' => $project->slug],
    'title' => 'Are you sure?',
    'description' => 'This will delete all tasks in this project as well.',
]);
