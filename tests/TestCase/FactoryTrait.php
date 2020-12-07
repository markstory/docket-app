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

    protected function makeItem($title, $projectId, $order, $props = [])
    {
        $todos = TableRegistry::get('TodoItems');
        $todoItem = $todos->newEntity(array_merge([
            'project_id' => $projectId,
            'title' => $title,
            'day_order' => $order,
            'child_order' => $order,
        ], $props));

        return $todos->saveOrFail($todoItem);
    }

    protected function makeSubtask($title, $todoItemId, $props = [])
    {
        $subtasks = TableRegistry::get('TodoSubtasks');
        $subtask = $subtasks->newEntity(array_merge([
            'todo_item_id' => $todoItemId,
            'title' => $title,
        ], $props));

        return $subtasks->saveOrFail($subtask);
    }
}
