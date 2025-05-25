<?php
declare(strict_types=1);
/**
 * @var \Tasks\Model\Entity\Project $project
 * @var \Tasks\Model\Entity\ProjectSection $section
 * @var \Cake\View\View $this
 */
echo $this->element('confirm_dialog', [
    'target' => ['_name' => 'projectsections:delete', 'projectSlug' => $project->slug, 'id' => $section->id],
    'title' => 'Are you sure?',
    'description' => 'This will delete all tasks in this section as well.',
]);
