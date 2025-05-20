<?php
declare(strict_types=1);

$this->setLayout('ajax');

echo $this->cell('Tasks.ProjectsMenu', ['identity' => $identity]);
