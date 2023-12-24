<?php
declare(strict_types=1);
/**
 * @var \App\Model\Entity\Project $project
 * @var \App\Model\Entity\ProjectSection $projectSection
 */
$this->setLayout('ajax');

echo $this->element('projectsection_item', [
    'project' => $project,
    'section' => $section,
]);
