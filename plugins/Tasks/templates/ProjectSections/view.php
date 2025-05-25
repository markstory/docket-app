<?php
declare(strict_types=1);
/**
 * @var \Tasks\Model\Entity\Project $project
 * @var \Tasks\Model\Entity\ProjectSection $projectSection
 */
$this->setLayout('ajax');

echo $this->element('Tasks.projectsection_item', [
    'project' => $project,
    'section' => $section,
]);
