<?php
declare(strict_types=1);

namespace App\Test\TestCase;

use Cake\ORM\TableRegistry;

trait FactoryTrait
{
    protected function makeProject($title, $userId, $ranking = 0)
    {
        $projects = TableRegistry::get('Projects');
        $project = $projects->newEntity([
            'user_id' => $userId,
            'name' => $title,
            'color' => '663366',
            'ranking' => $ranking,
        ]);

        return $projects->saveOrFail($project);
    }

    protected function makeItem($title, $projectId, $order)
    {
        $todos = TableRegistry::get('TodoItems');
        $todoItem = $todos->newEntity([
            'project_id' => $projectId,
            'title' => $title,
            'day_order' => $order,
            'child_order' => $order,
        ]);

        return $todos->saveOrFail($todoItem);
    }
}
