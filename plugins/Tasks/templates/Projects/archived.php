<?php
declare(strict_types=1);
/**
 * @var \Tasks\Model\Entity\Project[] $projects
 * @var string $referer
 */
$this->setLayout('sidebar');
$this->assign('title', 'Archived Projects');
?>
<h2>Archived Projects</h2>
<?php
foreach ($projects as $project):
    echo $this->element('Tasks.project_item', ['project' => $project, 'showMenu' => true]);
endforeach;

if (empty($projects)) : ?>
    <div>
        <h2>Nothing to see</h2>
        <p>You don't have any archived projects.</p>
    </div>
<?php endif; ?>
