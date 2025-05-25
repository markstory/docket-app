<?php
declare(strict_types=1);
/**
 * @var \Tasks\Model\Entity\Task $task
 * @var string $referer
 */
$this->setLayout('ajax');

$this->response = $this->response->withHeader('Hx-Trigger-After-Swap', 'reposition');

echo $this->element('Tasks.task_dueon_menu', ['task' => $task, 'referer' => $referer]);
