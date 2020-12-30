<?php
declare(strict_types=1);

namespace App\Test\TestCase;

use App\Model\Entity\User;
use Cake\ORM\TableRegistry;

trait FactoryTrait
{
    protected function login($userId = 1)
    {
        $this->session([
            'Auth' => new User([
                'id' => $userId,
                'name' => 'Mark Story'
            ])
        ]);
    }

    protected function makeUser($email, $props = [])
    {
        $users = TableRegistry::get('Users');
        $user = $users->newEntity(array_merge([
            'name' => 'Unknown',
            'email' => $email,
            'email_verified' => true,
            'password' => 'super sekret',
        ], $props));

        return $users->saveOrFail($user);
    }

    protected function makeProject($title, $userId, $ranking = 0, $props = [])
    {
        $projects = TableRegistry::get('Projects');
        $project = $projects->newEntity(array_merge([
            'user_id' => $userId,
            'name' => $title,
            'color' => '663366',
            'ranking' => $ranking,
        ], $props));

        return $projects->saveOrFail($project);
    }

    protected function makeTask($title, $projectId, $order, $props = [])
    {
        $tasks = TableRegistry::get('Tasks');
        $task = $tasks->newEntity(array_merge([
            'project_id' => $projectId,
            'title' => $title,
            'day_order' => $order,
            'child_order' => $order,
        ], $props));

        return $tasks->saveOrFail($task);
    }

    protected function makeSubtask($title, $taskId, $ranking, $props = [])
    {
        $subtasks = TableRegistry::get('Subtasks');
        $subtask = $subtasks->newEntity(array_merge([
            'task_id' => $taskId,
            'title' => $title,
            'ranking' => $ranking,
        ], $props));

        return $subtasks->saveOrFail($subtask);
    }
}
